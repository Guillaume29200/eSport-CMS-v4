<?php
declare(strict_types=1);

namespace Auth\Controllers;

use Auth\Services\AuthService;
use Framework\Services\Database;

/**
 * Contrôleur Membre
 * 
 * Gère l'espace membre (dashboard, profil, paramètres)
 */
class MemberController
{
    private AuthService $authService;
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->authService = new AuthService($db);
        
        // Vérifier authentification
        if (!$this->authService->isLoggedIn()) {
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * Dashboard membre
     */
    public function dashboard(): void
    {
        // Récupérer utilisateur connecté
        $user = $this->authService->getUserById($_SESSION['user_id']);
        
        // Récupérer sessions récentes
        $stmt = $this->db->prepare("
            SELECT * FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY last_activity DESC 
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $recentSessions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Afficher vue
        require __DIR__ . '/../Views/member/dashboard.php';
    }
    
    /**
     * Profil utilisateur
     */
    public function profile(): void
    {
        // Récupérer utilisateur connecté
        $user = $this->authService->getUserById($_SESSION['user_id']);
        
        // Afficher vue
        require __DIR__ . '/../Views/member/profile.php';
    }
    
    /**
     * Mettre à jour profil
     */
    public function updateProfile(): void
    {
        $userId = $_SESSION['user_id'];
        
        // Récupérer données
        $firstName = $_POST['first_name'] ?? null;
        $lastName = $_POST['last_name'] ?? null;
        $email = $_POST['email'] ?? null;
        
        // Vérifier si email existe déjà (sauf pour l'utilisateur actuel)
        if ($email) {
            $stmt = $this->db->prepare("
                SELECT id FROM users 
                WHERE email = ? AND id != ?
            ");
            $stmt->execute([$email, $userId]);
            
            if ($stmt->fetch()) {
                header('Location: /member/profile?error=email_exists');
                exit;
            }
        }
        
        // Mise à jour
        $stmt = $this->db->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$firstName, $lastName, $email, $userId]);
        
        // Mettre à jour session
        if ($email) {
            $_SESSION['email'] = $email;
        }
        
        // Redirection
        if ($success) {
            header('Location: /member/profile?updated=1');
        } else {
            header('Location: /member/profile?error=1');
        }
        exit;
    }
    
    /**
     * Changer mot de passe
     */
    public function changePassword(): void
    {
        $userId = $_SESSION['user_id'];
        
        // Récupérer données
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if ($newPassword !== $confirmPassword) {
            header('Location: /member/profile?error=password_mismatch');
            exit;
        }
        
        if (strlen($newPassword) < 8) {
            header('Location: /member/profile?error=password_too_short');
            exit;
        }
        
        // Récupérer utilisateur
        $user = $this->authService->getUserById($userId);
        
        // Vérifier mot de passe actuel
        if (!password_verify($currentPassword, $user['password'])) {
            header('Location: /member/profile?error=wrong_password');
            exit;
        }
        
        // Hasher nouveau mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        // Mise à jour
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$hashedPassword, $userId]);
        
        // Redirection
        if ($success) {
            header('Location: /member/profile?password_updated=1');
        } else {
            header('Location: /member/profile?error=1');
        }
        exit;
    }
    
    /**
     * Paramètres
     */
    public function settings(): void
    {
        $user = $this->authService->getUserById($_SESSION['user_id']);
        
        // Afficher vue
        require __DIR__ . '/../Views/member/settings.php';
    }
    
    /**
     * Mettre à jour paramètres
     */
    public function updateSettings(): void
    {
        // À implémenter selon les besoins
        header('Location: /member/settings?updated=1');
        exit;
    }
}
