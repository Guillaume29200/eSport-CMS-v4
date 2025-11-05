<?php
declare(strict_types=1);

namespace Framework\Security;

class CSRFProtection
{
    private array $config;
    private string $tokenName;
    
    public function __construct(array $config)
    {
        $this->config = $config['csrf'];
        $this->tokenName = $this->config['token_name'];
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
    }
    
    public function generateToken(string $action = 'default'): string
    {
        $token = bin2hex(random_bytes($this->config['token_length']));
        
        $_SESSION['csrf_tokens'][$action] = [
            'token' => $token,
            'expire' => time() + $this->config['expire']
        ];
        
        return $token;
    }
    
    public function getToken(string $action = 'default'): string
    {
        $this->cleanExpiredTokens();
        
        if (isset($_SESSION['csrf_tokens'][$action])) {
            $tokenData = $_SESSION['csrf_tokens'][$action];
            
            if ($tokenData['expire'] > time()) {
                return $tokenData['token'];
            }
        }
        
        return $this->generateToken($action);
    }
    
    public function validateToken(string $token, string $action = 'default'): bool
    {
        $this->cleanExpiredTokens();
        
        if (!isset($_SESSION['csrf_tokens'][$action])) {
            throw new CSRFException('CSRF token not found');
        }
        
        $storedToken = $_SESSION['csrf_tokens'][$action]['token'];
        $expire = $_SESSION['csrf_tokens'][$action]['expire'];
        
        if ($expire < time()) {
            unset($_SESSION['csrf_tokens'][$action]);
            throw new CSRFException('CSRF token expired');
        }
        
        if (!hash_equals($storedToken, $token)) {
            throw new CSRFException('CSRF token mismatch');
        }
        
        return true;
    }
    
    public function validateRequest(string $action = 'default'): bool
    {
        $token = $_POST[$this->tokenName] ?? null;
        
        if (!$token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        if (!$token) {
            throw new CSRFException('CSRF token not provided');
        }
        
        return $this->validateToken($token, $action);
    }
    
    public function getTokenInput(string $action = 'default'): string
    {
        $token = $this->getToken($action);
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($this->tokenName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    public function getTokenMeta(string $action = 'default'): string
    {
        $token = $this->getToken($action);
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    private function cleanExpiredTokens(): void
    {
        $now = time();
        
        foreach ($_SESSION['csrf_tokens'] as $action => $data) {
            if ($data['expire'] < $now) {
                unset($_SESSION['csrf_tokens'][$action]);
            }
        }
    }
    
    public function regenerateTokens(): void
    {
        $_SESSION['csrf_tokens'] = [];
    }
}

class CSRFException extends \Exception
{
    public function __construct(string $message = 'CSRF validation failed')
    {
        parent::__construct($message, 403);
    }
}
