<?php
declare(strict_types=1);

namespace Auth\Controllers;

use Auth\Services\AuthService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

/**
 * Contrôleur d'authentification
 * 
 * Gère :
 * - Affichage et traitement du login
 * - Affichage et traitement du register
 * - Logout
 * - Mot de passe oublié
 */
class AuthController
{
    private AuthService $authService;
    private CSRFProtection $csrf;
    
    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->authService = new AuthService($db);
        $this->csrf = $csrf;
    }
    
    /**
     * Afficher formulaire de connexion
     */
    public function showLogin(): void
    {
        // Rediriger si déjà connecté
        if ($this->authService->isLoggedIn()) {
            $role = $_SESSION['role'] ?? 'member';
            $redirect = $role === 'admin' ? '/admin/dashboard' : '/member/dashboard';
            header("Location: $redirect");
            exit;
        }
        
        // Générer token CSRF
        $csrfToken = $this->csrf->generateToken();
        
        // Afficher vue
        require __DIR__ . '/../Views/auth/login.php';
    }
    
    /**
     * Traiter la connexion
     */
    public function login(): void
    {
        // Header JSON dès le début
        header('Content-Type: application/json');
        
        // Vérifier méthode POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            exit;
        }
        
        // Vérifier token CSRF
        try {
            if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Token CSRF invalide. Veuillez recharger la page.']);
                exit;
            }
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Erreur de validation CSRF : ' . $e->getMessage()]);
            exit;
        }
        
        // Récupérer données
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        $screenResolution = $_POST['screen_resolution'] ?? null;
        
        // Validation basique
        if (empty($identifier) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis.']);
            exit;
        }
        
        // Tentative de connexion
        try {
            $result = $this->authService->login($identifier, $password, $rememberMe, $screenResolution);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Connexion réussie !',
                    'redirect' => $result['redirect']
                ]);
                exit;
            } else {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
                exit;
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Une erreur est survenue : ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Afficher formulaire d'inscription
     */
    public function showRegister(): void
    {
        // Rediriger si déjà connecté
        if ($this->authService->isLoggedIn()) {
            $role = $_SESSION['role'] ?? 'member';
            $redirect = $role === 'admin' ? '/admin/dashboard' : '/member/dashboard';
            header("Location: $redirect");
            exit;
        }
        
        // Générer token CSRF
        $csrfToken = $this->csrf->generateToken();
        
        // Afficher vue
        require __DIR__ . '/../Views/auth/register.php';
    }
    
    /**
     * Traiter l'inscription
     */
    public function register(): void
    {
        // Vérifier méthode POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }
        
        // Vérifier token CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            return;
        }
        
        // Récupérer données
        $data = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
        ];
        
        // Récupérer résolution écran
        $screenResolution = $_POST['screen_resolution'] ?? null;
        
        // Tentative d'inscription
        $result = $this->authService->register($data, $screenResolution);
        
        // Retourner JSON
        header('Content-Type: application/json');
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Inscription réussie ! Bienvenue !',
                'redirect' => $result['redirect']
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'errors' => $result['errors']
            ]);
        }
    }
    
    /**
     * Déconnexion
     */
    public function logout(): void
    {
        $this->authService->logout();
        
        // Redirection
        header('Location: login?logout=1');
        exit;
    }
    
    /**
     * Afficher formulaire mot de passe oublié
     */
    public function showForgotPassword(): void
    {
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/auth/forgot-password.php';
    }
    
    /**
     * Traiter demande mot de passe oublié
     */
    public function forgotPassword(): void
    {
        // À implémenter : envoi email avec lien de réinitialisation
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Si cet email existe, vous recevrez un lien de réinitialisation.'
        ]);
    }
    
    /**
     * Afficher formulaire réinitialisation mot de passe
     */
    public function showResetPassword(string $token): void
    {
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/auth/reset-password.php';
    }
    
    /**
     * Traiter réinitialisation mot de passe
     */
    public function resetPassword(): void
    {
        // À implémenter
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé.'
        ]);
    }
}