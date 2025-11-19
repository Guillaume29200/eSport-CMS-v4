<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Admin;

use Framework\Services\Database;
use Framework\Services\Logger;
use Framework\Security\CSRF;
use PremiumManager\Services\PaymentService;
use PremiumManager\Models\Transaction;

/**
 * Controller TransactionsController (Admin)
 * 
 * Gestion administrative des transactions
 * - Liste et recherche
 * - Détails transaction
 * - Remboursements
 * - Export CSV
 * 
 * @author Guillaume
 */
class TransactionsController
{
    private Database $db;
    private Logger $logger;
    private CSRF $csrf;
    private PaymentService $paymentService;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logger = new Logger($db, []);
        $this->csrf = new CSRF();
        $this->paymentService = new PaymentService($db, $this->logger);
    }
    
    /**
     * Liste des transactions
     * 
     * GET /admin/premium/transactions
     */
    public function index(): void
    {
        $this->requireAdmin();
        
        // Filtres
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        $provider = $_GET['provider'] ?? null;
        $search = $_GET['search'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $where = [];
        $params = [];
        
        if ($status) {
            $where[] = "t.status = ?";
            $params[] = $status;
        }
        
        if ($type) {
            $where[] = "t.transaction_type = ?";
            $params[] = $type;
        }
        
        if ($provider) {
            $where[] = "t.payment_provider = ?";
            $params[] = $provider;
        }
        
        if ($search) {
            $where[] = "(u.username LIKE ? OR u.email LIKE ? OR t.provider_transaction_id LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($dateFrom) {
            $where[] = "DATE(t.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $where[] = "DATE(t.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Récupérer transactions
        $transactions = $this->db->query("
            SELECT t.*, u.username, u.email
            FROM premium_transactions t
            JOIN users u ON t.user_id = u.id
            {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ", [...$params, $perPage, $offset]);
        
        // Total pour pagination
        $total = $this->db->queryOne("
            SELECT COUNT(*) as count
            FROM premium_transactions t
            JOIN users u ON t.user_id = u.id
            {$whereClause}
        ", $params)['count'];
        
        // Stats rapides
        $stats = $this->getQuickStats();
        
        // Convertir en objets Transaction
        $transactions = array_map(fn($t) => Transaction::fromArray($t), $transactions);
        
        include __DIR__ . '/../../Views/admin/transactions/index.php';
    }
    
    /**
     * Détail d'une transaction
     * 
     * GET /admin/premium/transactions/{id}
     */
    public function show(int $id): void
    {
        $this->requireAdmin();
        
        // Récupérer transaction complète
        $data = $this->db->queryOne("
            SELECT t.*, 
                   u.username, u.email, u.company_name,
                   p.name as plan_name,
                   s.status as subscription_status,
                   i.invoice_number
            FROM premium_transactions t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN premium_plans p ON t.plan_id = p.id
            LEFT JOIN user_subscriptions s ON t.subscription_id = s.id
            LEFT JOIN premium_invoices i ON t.invoice_id = i.id
            WHERE t.id = ?
        ", [$id]);
        
        if (!$data) {
            http_response_code(404);
            die('Transaction not found');
        }
        
        $transaction = Transaction::fromArray($data);
        
        // Données supplémentaires
        $user = [
            'username' => $data['username'],
            'email' => $data['email'],
            'company_name' => $data['company_name'] ?? null,
        ];
        
        $plan = $data['plan_name'] ? ['name' => $data['plan_name']] : null;
        $subscription = $data['subscription_status'] ? ['status' => $data['subscription_status']] : null;
        $invoice = $data['invoice_number'] ? ['number' => $data['invoice_number']] : null;
        
        // CSRF token pour actions
        $csrfToken = $this->csrf->generate();
        
        include __DIR__ . '/../../Views/admin/transactions/show.php';
    }
    
    /**
     * Effectuer un remboursement
     * 
     * POST /admin/premium/transactions/{id}/refund
     */
    public function refund(int $id): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on transaction refund", [
                'transaction_id' => $id,
                'admin_id' => $_SESSION['user_id']
            ]);
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        $reason = $_POST['reason'] ?? '';
        
        try {
            $success = $this->paymentService->processRefund($id, $reason);
            
            if ($success) {
                $this->logger->security("Transaction refunded by admin", [
                    'transaction_id' => $id,
                    'admin_id' => $_SESSION['user_id'],
                    'reason' => $reason
                ]);
                
                $_SESSION['success'] = "Transaction remboursée avec succès";
            } else {
                $_SESSION['error'] = "Impossible de rembourser cette transaction";
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Refund failed', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors du remboursement";
        }
        
        header("Location: /admin/premium/transactions/{$id}");
        exit;
    }
    
    /**
     * Export CSV
     * 
     * GET /admin/premium/transactions/export
     */
    public function export(): void
    {
        $this->requireAdmin();
        
        // Mêmes filtres que index()
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        $where = [];
        $params = [];
        
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        if ($type) {
            $where[] = "transaction_type = ?";
            $params[] = $type;
        }
        
        if ($dateFrom) {
            $where[] = "DATE(created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $where[] = "DATE(created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $transactions = $this->db->query("
            SELECT t.*, u.username, u.email
            FROM premium_transactions t
            JOIN users u ON t.user_id = u.id
            {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT 5000
        ", $params);
        
        // Générer CSV
        $filename = 'transactions_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $output = fopen('php://output', 'w');
        
        // Headers CSV
        fputcsv($output, [
            'ID', 'Date', 'Utilisateur', 'Email', 'Type', 'Montant', 
            'Devise', 'Statut', 'Provider', 'Transaction ID Provider'
        ]);
        
        // Données
        foreach ($transactions as $t) {
            fputcsv($output, [
                $t['id'],
                $t['created_at'],
                $t['username'],
                $t['email'],
                $t['transaction_type'],
                $t['amount'],
                $t['currency'],
                $t['status'],
                $t['payment_provider'],
                $t['provider_transaction_id'] ?? '',
            ]);
        }
        
        fclose($output);
        
        $this->logger->info("Transactions exported", [
            'admin_id' => $_SESSION['user_id'],
            'count' => count($transactions)
        ]);
        
        exit;
    }
    
    /**
     * Obtenir statistiques rapides
     */
    private function getQuickStats(): array
    {
        return [
            'today_revenue' => $this->db->queryOne("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM premium_transactions
                WHERE status = 'completed'
                  AND DATE(created_at) = CURDATE()
            ")['total'],
            
            'today_count' => $this->db->queryOne("
                SELECT COUNT(*) as count
                FROM premium_transactions
                WHERE DATE(created_at) = CURDATE()
            ")['count'],
            
            'pending_count' => $this->db->queryOne("
                SELECT COUNT(*) as count
                FROM premium_transactions
                WHERE status = 'pending'
            ")['count'],
            
            'failed_today' => $this->db->queryOne("
                SELECT COUNT(*) as count
                FROM premium_transactions
                WHERE status = 'failed'
                  AND DATE(created_at) = CURDATE()
            ")['count'],
        ];
    }
    
    /**
     * Vérifier permissions admin
     */
    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->logger->security("Unauthorized admin access attempt", [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            
            http_response_code(403);
            die('Access denied');
        }
    }
}
