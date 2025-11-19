<?php
declare(strict_types=1);

namespace Framework\ModuleManager;

use Framework\Interfaces\ModuleInterface;
use Framework\Services\Database;
use Framework\Services\Logger;

/**
 * ModuleManager - Gestion des modules
 * 
 * Responsabilités:
 * - Découvrir modules disponibles
 * - Charger modules actifs
 * - Gérer hooks
 * - Installer/désinstaller modules
 */
class ModuleManager
{
    private Database $db;
    private Logger $logger;
    private string $modulesPath;
    private array $loadedModules = [];
    private array $hooks = [];
    
    public function __construct(Database $db, Logger $logger, string $modulesPath)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->modulesPath = rtrim($modulesPath, '/');
    }
    
    /**
     * Découvrir tous les modules disponibles
     */
    public function discoverModules(): array
    {
        $modules = [];
        
        if (!is_dir($this->modulesPath)) {
            return $modules;
        }
        
        $dirs = scandir($this->modulesPath);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $modulePath = $this->modulesPath . '/' . $dir;
            
            if (!is_dir($modulePath)) {
                continue;
            }
            
            // Chercher module.json
            $configFile = $modulePath . '/module.json';
            
            if (!file_exists($configFile)) {
                continue;
            }
            
            $config = json_decode(file_get_contents($configFile), true);
            
            if (!$config) {
                $this->logger->warning("Invalid module.json in: {$dir}");
                continue;
            }
            
            $modules[$dir] = $config;
        }
        
        return $modules;
    }
    
    /**
     * Charger tous les modules actifs
     */
    public function loadModules(): void
    {
        try {
            // Récupérer modules actifs depuis DB
            $activeModules = $this->db->query(
                "SELECT * FROM modules WHERE active = 1 ORDER BY priority ASC"
            );
            
            foreach ($activeModules as $moduleData) {
                try {
                    $this->loadModule($moduleData['name']);
                } catch (\Exception $e) {
                    $this->logger->error("Failed to load module: {$moduleData['name']}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Si table modules n'existe pas, charger tous les modules disponibles
            $this->logger->warning("Table 'modules' not found, loading all available modules");
            
            $availableModules = $this->discoverModules();
            
            foreach ($availableModules as $moduleName => $config) {
                try {
                    $this->loadModule($moduleName);
                } catch (\Exception $ex) {
                    $this->logger->error("Failed to load module: {$moduleName}", [
                        'error' => $ex->getMessage()
                    ]);
                }
            }
        }
        
        $this->logger->info("Loaded " . count($this->loadedModules) . " modules");
    }
    
    /**
     * Charger un module spécifique
     */
    private function loadModule(string $moduleName): void
    {
        $modulePath = $this->modulesPath . '/' . $moduleName;
        
        if (!is_dir($modulePath)) {
            throw new \Exception("Module directory not found: {$moduleName}");
        }
        
        // Charger module.json
        $configFile = $modulePath . '/module.json';
        
        if (!file_exists($configFile)) {
            throw new \Exception("module.json not found in: {$moduleName}");
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        
        // Vérifier classe principale
        $mainClass = $config['class'] ?? null;
        
        if (!$mainClass) {
            throw new \Exception("No main class defined in module.json: {$moduleName}");
        }
        
        // Charger classe (autoloader devrait le gérer)
        if (!class_exists($mainClass)) {
            throw new \Exception("Module class not found: {$mainClass}");
        }
        
        // Instancier module
        $module = new $mainClass($config);
        
        if (!$module instanceof ModuleInterface) {
            throw new \Exception("Module must implement ModuleInterface: {$mainClass}");
        }
        
        // Vérifier compatibilité
        $cmsVersion = $this->getCMSVersion();
        if (!$module->isCompatible($cmsVersion)) {
            throw new \Exception("Module not compatible with CMS version {$cmsVersion}: {$moduleName}");
        }
        
        // Vérifier dépendances
        $this->checkDependencies($module);
        
        // Initialiser module
        $module->init();
        
        // Enregistrer hooks
        $this->registerModuleHooks($module);
        
        // Stocker module chargé
        $this->loadedModules[$moduleName] = $module;
        
        $this->logger->debug("Module loaded: {$moduleName}");
    }
    
    /**
     * Vérifier dépendances d'un module
     */
    private function checkDependencies(ModuleInterface $module): void
    {
        $dependencies = $module->getDependencies();
        
        foreach ($dependencies as $depName => $minVersion) {
            // Vérifier si dépendance chargée
            if (!isset($this->loadedModules[$depName])) {
                throw new \Exception(
                    "Missing dependency: {$depName} for module {$module->getName()}"
                );
            }
            
            // Vérifier version
            $depModule = $this->loadedModules[$depName];
            if (version_compare($depModule->getVersion(), $minVersion, '<')) {
                throw new \Exception(
                    "Dependency version mismatch: {$depName} >= {$minVersion} required"
                );
            }
        }
    }
    
    /**
     * Enregistrer les hooks d'un module
     */
    private function registerModuleHooks(ModuleInterface $module): void
    {
        $hooks = $module->getHooks();
        
        foreach ($hooks as $hookName => $hookData) {
            if (!is_array($hookData)) {
                $hookData = [$hookData, 10]; // [callable, priority]
            }
            
            [$callable, $priority] = $hookData;
            
            if (!isset($this->hooks[$hookName])) {
                $this->hooks[$hookName] = [];
            }
            
            $this->hooks[$hookName][] = [
                'callable' => $callable,
                'priority' => $priority ?? 10,
                'module' => $module->getName()
            ];
        }
        
        // Trier par priorité
        foreach ($this->hooks as $hookName => &$hooks) {
            usort($hooks, fn($a, $b) => $a['priority'] <=> $b['priority']);
        }
    }
    
    /**
     * Exécuter un hook
     */
    public function executeHook(string $hookName, ...$args)
    {
        if (!isset($this->hooks[$hookName])) {
            return null;
        }
        
        $result = null;
        
        foreach ($this->hooks[$hookName] as $hook) {
            try {
                $result = call_user_func($hook['callable'], ...$args);
                
                // Si hook retourne false, arrêter propagation
                if ($result === false) {
                    break;
                }
            } catch (\Exception $e) {
                $this->logger->error("Hook execution failed: {$hookName}", [
                    'module' => $hook['module'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $result;
    }
    
    /**
     * Activer un module
     */
    public function activateModule(string $moduleName): bool
    {
        try {
            // Charger module temporairement
            $modulePath = $this->modulesPath . '/' . $moduleName;
            $config = json_decode(file_get_contents($modulePath . '/module.json'), true);
            $mainClass = $config['class'];
            $module = new $mainClass($config);
            
            // Installer module
            if (!$module->install()) {
                throw new \Exception("Module installation failed");
            }
            
            // Activer en DB
            $this->db->execute(
                "INSERT INTO modules (name, active, priority) VALUES (?, 1, 10)
                 ON DUPLICATE KEY UPDATE active = 1",
                [$moduleName]
            );
            
            $this->logger->info("Module activated: {$moduleName}");
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Module activation failed: {$moduleName}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Désactiver un module
     */
    public function deactivateModule(string $moduleName): bool
    {
        try {
            // Désinstaller module si chargé
            if (isset($this->loadedModules[$moduleName])) {
                $module = $this->loadedModules[$moduleName];
                $module->uninstall();
                unset($this->loadedModules[$moduleName]);
            }
            
            // Désactiver en DB
            $this->db->execute(
                "UPDATE modules SET active = 0 WHERE name = ?",
                [$moduleName]
            );
            
            $this->logger->info("Module deactivated: {$moduleName}");
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Module deactivation failed: {$moduleName}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Obtenir module chargé
     */
    public function getModule(string $moduleName): ?ModuleInterface
    {
        return $this->loadedModules[$moduleName] ?? null;
    }
    
    /**
     * Obtenir tous les modules chargés
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }
    
    /**
     * Obtenir version du CMS
     */
    private function getCMSVersion(): string
    {
        return '4.0.0'; // TODO: Charger depuis config
    }
}