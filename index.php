<?php
declare(strict_types=1);

/**
 * eSport-CMS V4 - Point d'entrée principal
 * 
 * Architecture modulaire avec système de routing automatique :
 * - Chaque module a son propre fichier routes.php
 * - Le ModuleManager charge automatiquement tous les modules
 * - Les routes système sont dans /routes.php (racine)
 * 
 * @author Guillaume
 * @version 4.0.0
 */

// ============================================
// CONFIGURATION INITIALE
// ============================================

// Définir racine du projet
define('ROOT_PATH', __DIR__);

// Charger autoloader Composer (si installé)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

// Charger variables d'environnement (.env)
if (file_exists(ROOT_PATH . '/.env')) {
    $env = parse_ini_file(ROOT_PATH . '/.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }
}

// ============================================
// CHARGER CONFIGURATIONS
// ============================================

$envConfig = require ROOT_PATH . '/framework/config/environment.php';
$dbConfig = require ROOT_PATH . '/framework/config/database.php';
$securityConfig = require ROOT_PATH . '/framework/config/security.php';

// Obtenir configuration environnement actuel
$currentEnv = $envConfig['environment'];
$config = $envConfig[$currentEnv];

// Configuration PHP selon environnement
ini_set('display_errors', $config['display_errors'] ? '1' : '0');
error_reporting($config['error_reporting']);

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Paris');

// ============================================
// AUTOLOADER FRAMEWORK
// ============================================

spl_autoload_register(function ($class) {
    // Convertir namespace en chemin
    $class = str_replace('\\', '/', $class);
    
    // Gérer Framework
    if (strpos($class, 'Framework/') === 0) {
        $class = str_replace('Framework/', 'framework/', $class);
        $file = ROOT_PATH . '/' . $class . '.php';
    }
    // Gérer les modules (Auth, PremiumManager, etc.)
    else {
        // Le namespace est le nom du module
        // Auth\Auth -> modules/Auth/Auth.php
        // Auth\Controllers\AuthController -> modules/Auth/Controllers/AuthController.php
        $file = ROOT_PATH . '/modules/' . $class . '.php';
    }
    
    if (file_exists($file)) {
        require $file;
    }
});

// ============================================
// GESTION D'ERREURS
// ============================================

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) use ($config) {
    // Logger l'erreur
    error_log(sprintf(
        "[ERROR] %s in %s:%d",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
    
    // Affichage selon environnement
    if ($config['display_errors']) {
        echo "<h1>Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . ":" . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        echo "<p>An error occurred. Please try again later.</p>";
    }
    exit;
});

// ============================================
// INITIALISATION DES SERVICES
// ============================================

use Framework\Services\Database;
use Framework\Services\DebugBar;
use Framework\Services\Logger;
use Framework\Services\Router;
use Framework\Security\SessionManager;
use Framework\Security\CSRFProtection;
use Framework\Security\XSSProtection;
use Framework\Security\RateLimiter;
use Framework\ModuleManager\ModuleManager;

try {
    // ─────────────────────────────────────────
    // Database
    // ─────────────────────────────────────────
    $db = new Database($dbConfig, $config);
    
    // ─────────────────────────────────────────
    // Logger
    // ─────────────────────────────────────────
    $logger = new Logger($db, $config);
    
    // ─────────────────────────────────────────
    // Debug Bar (mode DEV uniquement)
    // ─────────────────────────────────────────
    $debugBar = new DebugBar($config['debug_bar'] ?? false);
    
    // ─────────────────────────────────────────
    // Security Services
    // ─────────────────────────────────────────
    $sessionManager = new SessionManager($securityConfig);
    $sessionManager->start();
    
    $csrfProtection = new CSRFProtection($securityConfig);
    $xssProtection = new XSSProtection($securityConfig);
    $rateLimiter = new RateLimiter($db, $securityConfig);
    
    // Filtrer XSS sur superglobales
    $xssProtection->filterGlobals();
    
    // Appliquer les headers de sécurité
    foreach ($securityConfig['headers'] as $header => $value) {
        header("{$header}: {$value}");
    }
    
    // ─────────────────────────────────────────
    // Router
    // ─────────────────────────────────────────
    $router = new Router();
    
    // Injecter les dépendances pour les contrôleurs
    $router->setDependencies([
        'Database' => $db,
        'CSRFProtection' => $csrfProtection,
        'XSSProtection' => $xssProtection,
        'RateLimiter' => $rateLimiter,
        'SessionManager' => $sessionManager,
        'Logger' => $logger,
        'DebugBar' => $debugBar,
        // Avec namespaces complets aussi
        'Framework\Services\Database' => $db,
        'Framework\Security\CSRFProtection' => $csrfProtection,
        'Framework\Security\XSSProtection' => $xssProtection,
        'Framework\Security\RateLimiter' => $rateLimiter,
        'Framework\Security\SessionManager' => $sessionManager,
        'Framework\Services\Logger' => $logger,
        'Framework\Services\DebugBar' => $debugBar,
    ]);
    
    // ─────────────────────────────────────────
    // Module Manager
    // ─────────────────────────────────────────
    $moduleManager = new ModuleManager(
        $db,
        $logger,
        ROOT_PATH . '/modules'
    );
    
    // Charger tous les modules
    $moduleManager->loadModules();
    
    // ============================================
    // ENREGISTREMENT DES ROUTES
    // ============================================
    
    // 1. Routes des modules (chargées automatiquement)
    //    Chaque module a son propre fichier routes.php
    foreach ($moduleManager->getLoadedModules() as $module) {
        $module->registerRoutes($router);
    }
    
    // 2. Routes système (racine)
    //    Routes globales et système uniquement
    require ROOT_PATH . '/routes.php';
    
    // ============================================
    // DEBUG BAR (si activée)
    // ============================================
    
    if ($config['debug_bar']) {
        $debugBar->importQueries($db->getQueryLog());
    }
    
    // ============================================
    // DISPATCH DE LA REQUÊTE
    // ============================================
    
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    
    // Retirer le base path si l'application est dans un sous-dossier
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
        $uri = substr($uri, strlen($scriptName));
    }
    
    // S'assurer que l'URI commence par /
    if (empty($uri) || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }
    
    // Buffer de sortie
    ob_start();
    $router->dispatch($method, $uri);
    $output = ob_get_clean();
    
    // ============================================
    // INJECTION DEBUG BAR (mode DEV)
    // ============================================

	if ($config['debug_bar']) {
		// Ne pas injecter la debug bar pour les réponses JSON
		$contentType = '';
		foreach (headers_list() as $header) {
			if (stripos($header, 'Content-Type') !== false) {
				$contentType = $header;
				break;
			}
		}
		
		// Si ce n'est pas du JSON, injecter la debug bar
		if (stripos($contentType, 'application/json') === false) {
			$debugBarHtml = $debugBar->render();
			
			// Injecter avant </body> si présent
			if (strpos($output, '</body>') !== false) {
				$output = str_replace('</body>', $debugBarHtml . '</body>', $output);
			} else {
				$output .= $debugBarHtml;
			}
		}
	}
    
    // ============================================
    // SORTIE FINALE
    // ============================================
    
    echo $output;
    
} catch (\Exception $e) {
    // ============================================
    // GESTION EXCEPTION NON GÉRÉE
    // ============================================
    
    $logger->critical('Unhandled exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    throw $e;
}