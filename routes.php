<?php
/**
 * eSport-CMS V4 - Routes principales
 * 
 * Définition des routes du CMS
 * Les modules enregistrent leurs propres routes via registerRoutes()
 */

// Page d'accueil
$router->get('/', function() {
    echo "<h1>eSport-CMS V4</h1>";
    echo "<p>Bienvenue sur le CMS eSport moderne et sécurisé !</p>";
    echo "<ul>";
    echo "<li><a href='/admin'>Administration</a></li>";
    echo "<li><a href='/install'>Installation</a></li>";
    echo "</ul>";
});

// Routes admin (à implémenter dans /admin/routes.php)
$router->group('/admin', function($router) {
    // Dashboard
    $router->get('/', function() {
        echo "<h1>Admin Dashboard</h1>";
        echo "<p>Interface admin à implémenter</p>";
    });
    
    // Login
    $router->get('/login', function() {
        echo "<h1>Admin Login</h1>";
        echo "<p>Page de connexion à implémenter</p>";
    });
    
    // Logout
    $router->post('/logout', function() {
        session_destroy();
        header('Location: /admin/login');
        exit;
    });
});

// Routes front (à implémenter dans /front/routes.php)
$router->group('/front', function($router) {
    // Espace membre
    $router->get('/dashboard', function() {
        echo "<h1>Espace Membre</h1>";
        echo "<p>Dashboard membre à implémenter</p>";
    });
});

// API routes
$router->group('/api', function($router) {
    // Exemple API endpoint
    $router->get('/status', function() {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'version' => '4.0.0',
            'timestamp' => time()
        ]);
    });
});
