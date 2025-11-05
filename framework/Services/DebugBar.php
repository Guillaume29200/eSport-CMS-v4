<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Service DebugBar - Console de debug
 * 
 * Affiche en bas de page (mode DEV uniquement):
 * - Temps de chargement
 * - Mémoire utilisée
 * - Requêtes SQL
 * - Checks de sécurité
 * - Logs
 * - Fichiers inclus
 */
class DebugBar
{
    private array $queries = [];
    private array $logs = [];
    private float $startTime;
    private array $securityChecks = [];
    private bool $enabled;
    
    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
        $this->startTime = microtime(true);
    }
    
    /**
     * Enregistrer une requête SQL
     */
    public function logQuery(string $query, float $executionTime, array $params = []): void
    {
        if (!$this->enabled) return;
        
        $this->queries[] = [
            'query' => $query,
            'time' => $executionTime,
            'params' => $params,
            'slow' => $executionTime > 0.1 // > 100ms = slow
        ];
    }
    
    /**
     * Enregistrer un log
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) return;
        
        $this->logs[] = [
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'time' => microtime(true)
        ];
    }
    
    /**
     * Enregistrer un check de sécurité
     */
    public function securityCheck(string $check, bool $passed, ?string $message = null): void
    {
        if (!$this->enabled) return;
        
        $this->securityChecks[] = [
            'check' => $check,
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    /**
     * Importer queries depuis Database service
     */
    public function importQueries(array $queries): void
    {
        if (!$this->enabled) return;
        
        foreach ($queries as $query) {
            $this->queries[] = $query;
        }
    }
    
    /**
     * Générer le HTML de la debug bar
     */
    public function render(): string
    {
        if (!$this->enabled) {
            return '';
        }
        
        $loadTime = microtime(true) - $this->startTime;
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $memoryLimit = ini_get('memory_limit');
        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;
        
        $queriesCount = count($this->queries);
        $slowQueries = array_filter($this->queries, fn($q) => $q['slow']);
        $totalQueryTime = array_sum(array_column($this->queries, 'time'));
        
        $includedFiles = get_included_files();
        $filesCount = count($includedFiles);
        
        ob_start();
        include __DIR__ . '/../Views/debug-bar.php';
        return ob_get_clean();
    }
    
    /**
     * Obtenir stats rapides
     */
    public function getStats(): array
    {
        return [
            'load_time' => microtime(true) - $this->startTime,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'queries_count' => count($this->queries),
            'slow_queries' => count(array_filter($this->queries, fn($q) => $q['slow'])),
            'logs_count' => count($this->logs),
            'files_count' => count(get_included_files()),
        ];
    }
}
