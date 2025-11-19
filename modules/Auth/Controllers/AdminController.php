<?php
declare(strict_types=1);

namespace Auth\Controllers;

use Auth\Services\AuthService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

/**
 * Contrôleur Admin
 * 
 * Gère le dashboard administrateur et la gestion des utilisateurs
 */
class AdminController
{
    private AuthService $authService;
    private Database $db;
    private CSRFProtection $csrf;
    
    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->authService = new AuthService($db);
        
        // Vérifier authentification et rôle admin
        if (!$this->authService->isLoggedIn() || !$this->authService->isAdmin()) {
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * Dashboard admin
     */
    public function dashboard(): void
    {
        // Récupérer statistiques
        $stats = $this->getStats();
        
        // Récupérer utilisateur connecté
        $currentUser = $this->authService->getUserById($_SESSION['user_id']);
        
        // Afficher vue
        require __DIR__ . '/../Views/admin/dashboard.php';
    }
    
    /**
     * Liste des utilisateurs
     */
    public function users(): void
    {
        // Récupérer tous les utilisateurs
        $users = $this->db->query("
            SELECT id, username, email, role, status, last_login, created_at, login_count 
            FROM users 
            ORDER BY created_at DESC
        ");
        
        // Afficher vue
        require __DIR__ . '/../Views/admin/users.php';
    }
    
    /**
     * Afficher le formulaire de création d'utilisateur
     */
    public function showCreateUser(): void
    {
        // Générer token CSRF
        $csrfToken = $this->csrf->generateToken();
        
        // Afficher vue
        require __DIR__ . '/../Views/admin/users-create.php';
    }
    
    /**
     * Traiter la création d'utilisateur
     */
    public function createUser(): void
    {
        // Header JSON
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
                echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
                exit;
            }
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Erreur CSRF : ' . $e->getMessage()]);
            exit;
        }
        
        // Récupérer et valider les données
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $role = $_POST['role'] ?? 'member';
        $status = $_POST['status'] ?? 'active';
        $sendEmail = isset($_POST['send_email']);
        
        $errors = [];
        
        // Validation username
        if (empty($username)) {
            $errors['username'] = 'Le nom d\'utilisateur est requis';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, - et _';
        } elseif ($this->authService->usernameExists($username)) {
            $errors['username'] = 'Ce nom d\'utilisateur est déjà utilisé';
        }
        
        // Validation email
        if (empty($email)) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        } elseif ($this->authService->emailExists($email)) {
            $errors['email'] = 'Cet email est déjà utilisé';
        }
        
        // Validation password
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est requis';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
        }
        
        // Validation role
        $validRoles = ['member', 'moderator', 'admin', 'superadmin'];
        if (!in_array($role, $validRoles)) {
            $errors['role'] = 'Rôle invalide';
        }
        
        // Validation status
        $validStatuses = ['active', 'inactive', 'banned'];
        if (!in_array($status, $validStatuses)) {
            $errors['status'] = 'Statut invalide';
        }
        
        // Si erreurs, retourner
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        
        // Créer l'utilisateur
        try {
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
                'role' => $role,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$userId) {
                throw new \Exception('Erreur lors de la création de l\'utilisateur');
            }
            
            // TODO: Envoyer email si $sendEmail = true
            
            // Calculer le base path pour la redirection
            $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $basePath = ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;
            
            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur créé avec succès !',
                'redirect' => $basePath . '/admin/users',
                'user_id' => $userId
            ]);
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la création : ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Afficher détails utilisateur
     */
    public function showUser(int $id): void
    {
        $user = $this->authService->getUserById($id);
        
        if (!$user) {
            http_response_code(404);
            echo "Utilisateur non trouvé";
            return;
        }
        
        // Récupérer sessions de l'utilisateur
        $sessions = $this->db->query("
            SELECT * FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY last_activity DESC 
            LIMIT 10
        ", [$id]);
        
        // Afficher vue
        require __DIR__ . '/../Views/admin/user-detail.php';
    }
    
    /**
     * Mettre à jour utilisateur
     */
    public function updateUser(int $id): void
    {
        // Récupérer données POST
        $role = $_POST['role'] ?? 'member';
        $status = $_POST['status'] ?? 'active';
        
        // Mise à jour
        $success = $this->db->execute("
            UPDATE users 
            SET role = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ", [$role, $status, $id]);
        
        // Redirection
        if ($success) {
            header("Location: users/{$id}?updated=1");
        } else {
            header("Location: users/{$id}?error=1");
        }
        exit;
    }
    
	/**
     * Supprimer utilisateur
     */
    public function deleteUser(int $id): void
    {
        // Ne pas permettre la suppression de son propre compte
        if ($id === $_SESSION['user_id']) {
            header("Location: users?error=self_delete");
            exit;
        }
        
        try {
            // Récupérer l'utilisateur avant suppression (pour les logs)
            $user = $this->authService->getUserById($id);
            
            if (!$user) {
                header("Location: users?error=not_found");
                exit;
            }
            
            // Commencer une transaction
            $this->db->getPDO()->beginTransaction();
            
            // 1. Supprimer les sessions de l'utilisateur
            $this->db->execute("DELETE FROM user_sessions WHERE user_id = ?", [$id]);
            
            // 2. Supprimer les tokens "remember me"
            $this->db->execute("DELETE FROM remember_tokens WHERE user_id = ?", [$id]);
            
            // 3. Supprimer les logs liés à l'utilisateur (optionnel - garder pour l'audit)
            // $this->db->execute("DELETE FROM logs WHERE user_id = ?", [$id]);
            
            // 4. Anonymiser les logs plutôt que de les supprimer (meilleure pratique)
            $this->db->execute(
                "UPDATE logs SET user_id = NULL WHERE user_id = ?", 
                [$id]
            );
            
            // 5. Supprimer l'utilisateur
            $success = $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
            
            if (!$success) {
                throw new \Exception("Erreur lors de la suppression");
            }
            
            // Commit de la transaction
            $this->db->getPDO()->commit();
            
            // Calculer le base path
            $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $basePath = ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;
            
            // Redirection avec message de succès
            header("Location: {$basePath}/admin/users?deleted=1&username=" . urlencode($user['username']));
            exit;
            
        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollBack();
            }
            
            // Calculer le base path
            $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $basePath = ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;
            
            header("Location: {$basePath}/admin/users?error=delete_failed&reason=" . urlencode($e->getMessage()));
            exit;
        }
    }
    
    /**
     * Statistiques admin
     */
    public function stats(): void
    {
        $stats = $this->getStats();
        
        // Afficher vue JSON ou HTML
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
    
    /**
     * Récupérer statistiques
     */
    private function getStats(): array
    {
        // Total utilisateurs
        $result = $this->db->query("SELECT COUNT(*) as total FROM users");
        $totalUsers = $result[0]['total'] ?? 0;
        
        // Nouveaux utilisateurs (30 derniers jours)
        $result = $this->db->query("
            SELECT COUNT(*) as total 
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $newUsers = $result[0]['total'] ?? 0;
        
        // Utilisateurs actifs (connectés dans les 7 derniers jours)
        $result = $this->db->query("
            SELECT COUNT(*) as total 
            FROM users 
            WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $activeUsers = $result[0]['total'] ?? 0;
        
        // Par rôle
        $usersByRole = $this->db->query("
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role
        ");
        
        // Par statut
        $usersByStatus = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM users 
            GROUP BY status
        ");
        
        return [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'active_users' => $activeUsers,
            'users_by_role' => $usersByRole,
            'users_by_status' => $usersByStatus
        ];
    }
}