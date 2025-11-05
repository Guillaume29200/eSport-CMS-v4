<?php
declare(strict_types=1);

namespace Framework\Interfaces;

use Framework\Services\Router;

/**
 * Classe abstraite BaseModule
 * 
 * Implémentation de base pour simplifier création de modules
 * Les modules peuvent étendre cette classe au lieu d'implémenter l'interface
 */
abstract class BaseModule implements ModuleInterface
{
    protected array $config = [];
    
    /**
     * Constructeur
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    /**
     * {@inheritdoc}
     */
    abstract public function getName(): string;
    
    /**
     * {@inheritdoc}
     */
    abstract public function getVersion(): string;
    
    /**
     * {@inheritdoc}
     */
    abstract public function getDescription(): string;
    
    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return 'Unknown';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        // Par défaut: rien à faire
    }
    
    /**
     * {@inheritdoc}
     */
    public function registerRoutes($router): void
    {
        // Par défaut: pas de routes
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHooks(): array
    {
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        // Par défaut: installation réussie
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        // Par défaut: désinstallation réussie
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isCompatible(string $cmsVersion): bool
    {
        // Par défaut: compatible avec toutes versions
        return true;
    }
    
    /**
     * Obtenir chemin du module
     */
    protected function getModulePath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }
    
    /**
     * Charger vue du module
     */
    protected function loadView(string $viewName, array $data = []): string
    {
        $viewPath = $this->getModulePath() . '/Views/' . $viewName . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$viewName}");
        }
        
        extract($data);
        
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
    
    /**
     * Obtenir configuration du module
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
