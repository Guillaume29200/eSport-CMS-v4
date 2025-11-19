<?php
/**
 * eSport-CMS V4 - Routes Principales (Système)
 * 
 * Ce fichier contient UNIQUEMENT les routes système et globales.
 * Chaque module enregistre ses propres routes via son fichier routes.php
 * 
 * @author Guillaume
 * @version 4.0.0
 */

// ============================================
// PAGE D'ACCUEIL
// ============================================
$router->get('/', function() use ($moduleManager, $sessionManager) {
    // Vérifier si l'utilisateur est connecté
    $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    
    if ($isLoggedIn) {
        // Rediriger selon le rôle
        $role = $_SESSION['role'] ?? 'member';
        $redirect = match($role) {
            'admin', 'superadmin' => '/admin/dashboard',
            'moderator' => '/admin/dashboard',
            default => '/member/dashboard'
        };
        header("Location: $redirect");
        exit;
    }
    
    // Page d'accueil pour visiteurs
    require ROOT_PATH . '/front/views/home.php';
});

// ============================================
// ROUTES D'INSTALLATION
// ============================================
$router->group('/install', function($router) {
    // Vérifier si déjà installé
    $installedFile = ROOT_PATH . '/.installed';
    
    if (file_exists($installedFile)) {
        // Déjà installé, rediriger
        $router->get('/', function() {
            header('Location: /');
            exit;
        });
        return;
    }
    
    // Processus d'installation
    $router->get('/', function() {
        require ROOT_PATH . '/install/views/index.php';
    });
    
    $router->get('/step/{step}', function($step) {
        require ROOT_PATH . '/install/views/step-' . (int)$step . '.php';
    });
    
    $router->post('/process', function() {
        require ROOT_PATH . '/install/process.php';
    });
});

// ============================================
// ROUTES DE MAINTENANCE
// ============================================
$router->group('/maintenance', function($router) {
    // Page de maintenance
    $router->get('/', function() {
        http_response_code(503);
        require ROOT_PATH . '/framework/Views/maintenance.php';
    });
});

// ============================================
// ROUTES API SYSTÈME
// ============================================
$router->group('/api/system', function($router) use ($db) {
    // Statut du système
    $router->get('/status', function() {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'version' => '4.0.0',
            'timestamp' => time(),
            'environment' => getenv('APP_ENV') ?: 'production'
        ]);
    });
    
    // Health check (pour monitoring)
    $router->get('/health', function() use ($db) {
        header('Content-Type: application/json');
        
        $health = [
            'status' => 'healthy',
            'checks' => []
        ];
        
        // Check database
        try {
            $db->query("SELECT 1");
            $health['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = 'error';
        }
        
        // Check sessions
        $health['checks']['sessions'] = session_status() === PHP_SESSION_ACTIVE ? 'ok' : 'error';
        
        // Check uploads directory
        $health['checks']['uploads'] = is_writable(ROOT_PATH . '/uploads') ? 'ok' : 'error';
        
        http_response_code($health['status'] === 'healthy' ? 200 : 503);
        echo json_encode($health);
    });
});

// ============================================
// ROUTES UTILITAIRES
// ============================================

// Favicon
$router->get('/favicon.ico', function() {
    $favicon = ROOT_PATH . '/public/favicon.ico';
    if (file_exists($favicon)) {
        header('Content-Type: image/x-icon');
        readfile($favicon);
    } else {
        http_response_code(404);
    }
    exit;
});

// Robots.txt
$router->get('/robots.txt', function() {
    header('Content-Type: text/plain');
    echo "User-agent: *\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /api/\n";
    echo "Disallow: /install/\n";
    echo "Allow: /\n";
    exit;
});

// Sitemap (optionnel)
$router->get('/sitemap.xml', function() use ($db) {
    header('Content-Type: application/xml');
    // Générer sitemap dynamiquement
    require ROOT_PATH . '/framework/Services/SitemapGenerator.php';
});