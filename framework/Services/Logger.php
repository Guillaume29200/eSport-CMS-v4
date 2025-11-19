<?php
declare(strict_types=1);

namespace Framework\Services;

use Framework\Services\Database;

/**
 * Service Logger - Système de logging
 * 
 * Logs de sécurité, erreurs, activités
 * Stockage en base de données + fichiers
 */
class Logger
{
    private Database $db;
    private string $logPath;
    private array $config;
    
    // Niveaux de log
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    public const SECURITY = 'SECURITY';
    
    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->logPath = __DIR__ . '/../logs/';
        
        // Créer dossier logs si n'existe pas
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Logger un message
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Vérifier si on doit logger ce niveau
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $logData = [
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'ip_address' => GeolocService::getClientIP(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ];
        
        // Logger en DB
        try {
            $this->db->insert('logs', $logData);
        } catch (\Exception $e) {
            // Fallback sur fichier si DB échoue
            $this->logToFile($level, $message, $context);
        }
        
        // Logger aussi en fichier pour les niveaux importants
        if (in_array($level, [self::ERROR, self::CRITICAL, self::SECURITY])) {
            $this->logToFile($level, $message, $context);
        }
    }
    
    /**
     * Vérifier si on doit logger ce niveau
     */
    private function shouldLog(string $level): bool
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
            self::SECURITY => 5,
        ];
        
        $configLevel = $this->config['log_level'] ?? self::INFO;
        $currentLevelValue = $levels[$level] ?? 1;
        $configLevelValue = $levels[$configLevel] ?? 1;
        
        return $currentLevelValue >= $configLevelValue;
    }
    
    /**
     * Logger dans fichier
     */
    private function logToFile(string $level, string $message, array $context = []): void
    {
        $filename = $this->logPath . strtolower($level) . '_' . date('Y-m-d') . '.log';
        
        $logLine = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Méthodes raccourcis
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }
    
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }
    
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }
    
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }
    
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    public function security(string $message, array $context = []): void
    {
        $this->log(self::SECURITY, $message, $context);
    }
    
    /**
     * Logger activité utilisateur
     */
    public function logActivity(int $userId, string $action, array $details = []): void
    {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'details' => json_encode($details),
            'ip_address' => GeolocService::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];
        
        try {
            $this->db->insert('user_activities', $data);
        } catch (\Exception $e) {
            $this->error("Failed to log activity: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir logs récents
     */
    public function getRecentLogs(int $limit = 100, ?string $level = null): array
    {
        $params = [];
        $whereClause = '';
        
        if ($level) {
            $whereClause = 'WHERE level = ?';
            $params[] = $level;
        }
        
        $params[] = $limit;
        
        return $this->db->query(
            "SELECT * FROM logs 
             {$whereClause}
             ORDER BY created_at DESC 
             LIMIT ?",
            $params
        );
    }
    
    /**
     * Obtenir logs de sécurité
     */
    public function getSecurityLogs(int $limit = 100): array
    {
        return $this->db->query(
            "SELECT * FROM logs 
             WHERE level = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [self::SECURITY, $limit]
        );
    }
    
    /**
     * Obtenir activités utilisateur
     */
    public function getUserActivities(int $userId, int $limit = 50): array
    {
        return $this->db->query(
            "SELECT * FROM user_activities 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }
    
    /**
     * Nettoyer anciens logs (CRON)
     */
    public function cleanup(int $days = 90): int
    {
        $deletedLogs = $this->db->execute(
            "DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        
        $deletedActivities = $this->db->execute(
            "DELETE FROM user_activities WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        
        // Nettoyer fichiers
        $this->cleanupFiles($days);
        
        return $deletedLogs + $deletedActivities;
    }
    
    /**
     * Nettoyer fichiers logs anciens
     */
    private function cleanupFiles(int $days): void
    {
        $files = glob($this->logPath . '*.log');
        $threshold = time() - ($days * 86400);
        
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
            }
        }
    }
}
