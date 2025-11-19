<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Front;

use Framework\Services\Database;
use Framework\Services\Logger;
use PremiumManager\Models\Transaction;

/**
 * Controller TransactionsController (Front)
 * 
 * Historique des transactions utilisateur
 * - Liste paiements
 * - Détails transaction
 * - Téléchargement facture
 * 
 * @author Guillaume
 */
class TransactionsController
{
    private Database $db;
    private Logger $logger;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logger = new Logger($db, []);
    }
    
    /**
     * Historique des transactions
     * 
     * GET /member/premium/transactions
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Récupérer transactions
        $transactions = $this->db->query("
            SELECT t.*, i.invoice_number, i.pdf_path
            FROM premium_transactions t
            LEFT JOIN premium_invoices i ON t.invoice_id = i.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ", [$userId, $perPage, $offset]);
        
        // Total
        $total = $this->db->queryOne("
            SELECT COUNT(*) as count
            FROM premium_transactions
            WHERE user_id = ?
        ", [$userId])['count'];
        
        // Stats utilisateur
        $stats = [
            'total_spent' => $this->db->queryOne("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM premium_transactions
                WHERE user_id = ? AND status = 'completed'
            ", [$userId])['total'],
            
            'transaction_count' => $total,
        ];
        
        $transactions = array_map(fn($t) => Transaction::fromArray($t), $transactions);
        
        include __DIR__ . '/../../Views/member/transactions/index.php';
    }
    
    /**
     * Vérifier authentification
     */
    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
}
