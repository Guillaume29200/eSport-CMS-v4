<?php
declare(strict_types=1);

/**
 * eSport-CMS V4 - Point d'entrée principal
 * 
 * @author Guillaume
 * @version 4.0.0
 */

// Définir racine du projet
define('ROOT_PATH', __DIR__);

// Charger autoloader Composer (si installé)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

// Charger .env si existe
if (file_exists(ROOT_PATH . '/.env')) {
    $env = parse_ini_file(ROOT_PATH . '/.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }
}

// Charger configurations
$envConfig = require ROOT_PATH . '/framework/config/environment.php';
$dbConfig = require ROOT_PATH . '/framework/config/database.php';
$securityConfig = require ROOT_PATH . '/framework/config/security.php';

// Obtenir config environnement actuel
$currentEnv = $envConfig['environment'];
$config = $envConfig[$currentEnv];

// Configuration PHP selon environnement
ini_set('display_errors', $config['display_errors'] ? '1' : '0');
error_reporting($config['error_reporting']);

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Paris');

// Autoloader simple pour framework (si pas Composer)
spl_autoload_register(function ($class) {
    // Convertir namespace en chemin
    $class = str_replace('\\', '/', $class);
    $class = str_replace('Framework/', 'framework/', $class);
    
    $file = ROOT_PATH . '/' . $class . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Démarrer gestion d'erreurs
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

// Initialiser services
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
    // Database
    $db = new Database($dbConfig, $config);
    
    // Logger
    $logger = new Logger($db, $config);
    
    // Debug Bar (mode DEV uniquement)
    $debugBar = new DebugBar($config['debug_bar'] ?? false);
    
    // Security
    $sessionManager = new SessionManager($securityConfig);
    $sessionManager->start();
    
    $csrfProtection = new CSRFProtection($securityConfig);
    $xssProtection = new XSSProtection($securityConfig);
    $rateLimiter = new RateLimiter($db, $securityConfig);
    
    // Filtrer XSS sur superglobales
    $xssProtection->filterGlobals();
    
    // Security headers
    foreach ($securityConfig['headers'] as $header => $value) {
        header("{$header}: {$value}");
    }
    
    // Router
    $router = new Router();
    
    // Module Manager
    $moduleManager = new ModuleManager(
        $db,
        $logger,
        ROOT_PATH . '/modules'
    );
    
    // Charger modules
    $moduleManager->loadModules();
    
    // Importer queries dans debug bar
    if ($config['debug_bar']) {
        $debugBar->importQueries($db->getQueryLog());
    }
    
    // Enregistrer routes des modules
    foreach ($moduleManager->getLoadedModules() as $module) {
        $module->registerRoutes($router);
    }
    
    // Routes principales
    require ROOT_PATH . '/routes.php';
    
    // Dispatcher la requête
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    
    ob_start();
    $router->dispatch($method, $uri);
    $output = ob_get_clean();
    
    // Injecter debug bar si mode DEV
    if ($config['debug_bar']) {
        $debugBarHtml = $debugBar->render();
        $output = str_replace('</body>', $debugBarHtml . '</body>', $output);
    }
    
    echo $output;
    
} catch (\Exception $e) {
    // Exception non gérée
    $logger->critical('Unhandled exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    throw $e;
}
