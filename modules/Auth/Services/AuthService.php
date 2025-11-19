<?php
declare(strict_types=1);

namespace Auth\Services;

use Framework\Services\Database;

/**
 * Service d'authentification
 * 
 * Gère toutes les opérations liées à l'authentification :
 * - Login / Logout
 * - Inscription
 * - Remember Me
 * - Gestion des sessions
 * - Vérification des permissions
 */
class AuthService
{
    private Database $db;
    private int $maxLoginAttempts = 5;
    private int $lockoutDuration = 900; // 15 minutes
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    /**
     * Connexion utilisateur
     */
    public function login(string $identifier, string $password, bool $rememberMe = false): array
    {
        // Vérifier le rate limiting
        if ($this->isLockedOut($identifier)) {
            return [
                'success' => false,
                'error' => 'Trop de tentatives échouées. Réessayez dans 15 minutes.'
            ];
        }
        
        // Récupérer l'utilisateur
        $user = $this->getUserByIdentifier($identifier);
        
        if (!$user) {
            $this->recordLoginAttempt($identifier, false);
            return [
                'success' => false,
                'error' => 'Identifiants incorrects.'
            ];
        }
        
        // Vérifier le mot de passe
        if (!password_verify($password, $user['password'])) {
            $this->recordLoginAttempt($identifier, false);
            return [
                'success' => false,
                'error' => 'Identifiants incorrects.'
            ];
        }
        
        // Vérifier le statut du compte
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'error' => 'Votre compte est ' . $user['status'] . '.'
            ];
        }
        
        // Connexion réussie
        $this->recordLoginAttempt($identifier, true);
        $this->createUserSession($user);
        
        // Remember Me
        if ($rememberMe) {
            $this->createRememberToken($user['id']);
        }
        
        // Mettre à jour dernière connexion
        $this->updateLastLogin($user['id']);
        
        return [
            'success' => true,
            'user' => $user,
            'redirect' => $this->getRedirectUrl($user['role'])
        ];
    }
    
    /**
     * Déconnexion utilisateur
     */
    public function logout(): void
    {
        // Supprimer token Remember Me
        if (isset($_COOKIE['remember_token'])) {
            $this->deleteRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Supprimer la session
        if (isset($_SESSION['session_id'])) {
            $this->deleteUserSession($_SESSION['session_id']);
        }
        
        // Détruire la session PHP
        session_destroy();
    }
    
    /**
     * Inscription utilisateur
     */
    public function register(array $data): array
    {
        // Validation
        $validation = $this->validateRegistration($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        // Vérifier si username/email existe déjà
        if ($this->usernameExists($data['username'])) {
            return [
                'success' => false,
                'errors' => ['username' => 'Ce nom d\'utilisateur existe déjà.']
            ];
        }
        
        if ($this->emailExists($data['email'])) {
            return [
                'success' => false,
                'errors' => ['email' => 'Cette adresse email est déjà utilisée.']
            ];
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID);
        
        // Créer l'utilisateur
        $userId = $this->createUser([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'role' => 'member',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$userId) {
            return [
                'success' => false,
                'errors' => ['general' => 'Erreur lors de la création du compte.']
            ];
        }
        
        // Récupérer l'utilisateur créé
        $user = $this->getUserById($userId);
        
        // Créer la session
        $this->createUserSession($user);
        
        return [
            'success' => true,
            'user' => $user,
            'redirect' => $this->getRedirectUrl($user['role'])
        ];
    }
    
    /**
     * Connexion depuis Remember Token
     */
    public function loginFromRememberToken(string $token): bool
    {
        $stmt = $this->db->prepare("
            SELECT rt.*, u.*
            FROM remember_tokens rt
            INNER JOIN users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'
        ");
        
        $stmt->execute([$token]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result) {
            $this->createUserSession($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Créer session utilisateur
     */
    private function createUserSession(array $user): void
    {
        // Regénérer ID de session (sécurité)
        session_regenerate_id(true);
        
        // Stocker infos utilisateur en session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['session_id'] = session_id();
        
        // Enregistrer en BDD pour tracking
        $this->recordUserSession($user['id']);
    }
    
    /**
     * Créer token Remember Me
     */
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000); // 30 jours
        
        $stmt = $this->db->prepare("
            INSERT INTO remember_tokens (user_id, token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $token,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        // Définir le cookie
        setcookie('remember_token', $token, time() + 2592000, '/', '', true, true);
    }
    
    /**
     * Supprimer token Remember Me
     */
    private function deleteRememberToken(string $token): void
    {
        $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$token]);
    }
    
    /**
     * Enregistrer session utilisateur en BDD
     */
    private function recordUserSession(int $userId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            session_id(),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Supprimer session utilisateur
     */
    private function deleteUserSession(string $sessionId): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }
    
    /**
     * Enregistrer tentative de connexion
     */
    private function recordLoginAttempt(string $identifier, bool $success): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (identifier, ip_address, user_agent, success)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $identifier,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $success ? 1 : 0
        ]);
    }
    
    /**
     * Vérifier si l'utilisateur est lockout
     */
    private function isLockedOut(string $identifier): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts
            WHERE identifier = ? 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$identifier, $this->lockoutDuration]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['attempts'] >= $this->maxLoginAttempts;
    }
    
    /**
     * Mettre à jour dernière connexion
     */
    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login = NOW(), login_count = login_count + 1
            WHERE id = ?
        ");
        
        $stmt->execute([$userId]);
    }
    
    /**
     * Récupérer utilisateur par identifiant (username ou email)
     */
    private function getUserByIdentifier(string $identifier): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE username = ? OR email = ?
            LIMIT 1
        ");
        
        $stmt->execute([$identifier, $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Récupérer utilisateur par ID
     */
    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Créer utilisateur
     */
    private function createUser(array $data): ?int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password'],
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['role'],
            $data['status'],
            $data['created_at']
        ]);
        
        return $success ? (int)$this->db->lastInsertId() : null;
    }
    
    /**
     * Vérifier si username existe
     */
    public function usernameExists(string $username): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE username = ?",
            [$username]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }
    
    /**
     * Vérifier si email existe
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE email = ?",
            [$email]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }
    
    /**
     * Validation inscription
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];
        
        // Username
        if (empty($data['username'])) {
            $errors['username'] = 'Le nom d\'utilisateur est requis.';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
            $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, _ et -.';
        }
        
        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'L\'adresse email est requise.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'adresse email n\'est pas valide.';
        }
        
        // Password
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        
        // Confirmation password
        if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Obtenir URL de redirection selon le rôle
     */
    private function getRedirectUrl(string $role): string
    {
        $path = match($role) {
            'admin', 'superadmin' => '/admin/dashboard',
            'moderator' => '/admin/dashboard',
            'member' => '/member/dashboard',
            default => '/member/dashboard'
        };
        
        // Calculer le base path (gère Windows et Linux)
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $basePath = ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;
        
        return $basePath . $path;
    }

    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle
     */
    public function hasRole(string $role): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('superadmin');
    }	
	
	
}