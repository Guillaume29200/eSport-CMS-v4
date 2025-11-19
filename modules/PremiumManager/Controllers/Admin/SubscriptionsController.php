<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Admin;

use Framework\Services\Database;
use Framework\Services\Logger;
use Framework\Security\CSRF;
use PremiumManager\Services\SubscriptionService;
use PremiumManager\Models\Subscription;

/**
 * Controller SubscriptionsController (Admin)
 * 
 * Gestion administrative des abonnements
 * - Liste et filtrage
 * - Détails abonnement
 * - Annulation manuelle
 * - Modification plans
 * 
 * @author Guillaume
 */
class SubscriptionsController
{
    private Database $db;
    private Logger $logger;
    private CSRF $csrf;
    private SubscriptionService $subscriptionService;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logger = new Logger($db, []);
        $this->csrf = new CSRF();
        $this->subscriptionService = new SubscriptionService($db, $this->logger);
    }
    
    /**
     * Liste des abonnements
     * 
     * GET /admin/premium/subscriptions
     */
    public function index(): void
    {
        $this->requireAdmin();
        
        // Filtres
        $status = $_GET['status'] ?? null;
        $planId = $_GET['plan'] ?? null;
        $search = $_GET['search'] ?? null;
        $expiringSoon = isset($_GET['expiring_soon']);
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $where = [];
        $params = [];
        
        if ($status) {
            $where[] = "s.status = ?";
            $params[] = $status;
        }
        
        if ($planId) {
            $where[] = "s.plan_id = ?";
            $params[] = $planId;
        }
        
        if ($search) {
            $where[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($expiringSoon) {
            $where[] = "s.current_period_end <= DATE_ADD(NOW(), INTERVAL 7 DAY)";
            $where[] = "s.status IN ('active', 'trialing')";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Récupérer abonnements
        $subscriptions = $this->db->query("
            SELECT s.*, 
                   u.username, u.email,
                   p.name as plan_name, p.price as plan_price, p.billing_period
            FROM user_subscriptions s
            JOIN users u ON s.user_id = u.id
            JOIN premium_plans p ON s.plan_id = p.id
            {$whereClause}
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ", [...$params, $perPage, $offset]);
        
        // Total
        $total = $this->db->queryOne("
            SELECT COUNT(*) as count
            FROM user_subscriptions s
            JOIN users u ON s.user_id = u.id
            {$whereClause}
        ", $params)['count'];
        
        // Plans pour filtre
        $plans = $this->db->query("
            SELECT id, name FROM premium_plans WHERE active = 1 ORDER BY price ASC
        ");
        
        // Stats
        $stats = $this->getSubscriptionStats();
        
        include __DIR__ . '/../../Views/admin/subscriptions/index.php';
    }
    
    /**
     * Détail d'un abonnement
     * 
     * GET /admin/premium/subscriptions/{id}
     */
    public function show(int $id): void
    {
        $this->requireAdmin();
        
        // Récupérer abonnement complet
        $data = $this->db->queryOne("
            SELECT s.*, 
                   u.username, u.email, u.stripe_customer_id,
                   p.name as plan_name, p.price, p.billing_period, p.features
            FROM user_subscriptions s
            JOIN users u ON s.user_id = u.id
            JOIN premium_plans p ON s.plan_id = p.id
            WHERE s.id = ?
        ", [$id]);
        
        if (!$data) {
            http_response_code(404);
            die('Subscription not found');
        }
        
        $subscription = Subscription::fromArray($data);
        
        // Infos user
        $user = [
            'username' => $data['username'],
            'email' => $data['email'],
            'stripe_customer_id' => $data['stripe_customer_id'] ?? null,
        ];
        
        // Infos plan
        $plan = [
            'name' => $data['plan_name'],
            'price' => $data['price'],
            'billing_period' => $data['billing_period'],
            'features' => json_decode($data['features'] ?? '[]', true),
        ];
        
        // Historique transactions
        $transactions = $this->db->query("
            SELECT * FROM premium_transactions
            WHERE subscription_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ", [$id]);
        
        // CSRF token
        $csrfToken = $this->csrf->generate();
        
        include __DIR__ . '/../../Views/admin/subscriptions/show.php';
    }
    
    /**
     * Annuler un abonnement
     * 
     * POST /admin/premium/subscriptions/{id}/cancel
     */
    public function cancel(int $id): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on subscription cancel", [
                'subscription_id' => $id,
                'admin_id' => $_SESSION['user_id']
            ]);
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        $immediately = isset($_POST['immediately']);
        $reason = $_POST['reason'] ?? 'Cancelled by admin';
        
        try {
            $success = $this->subscriptionService->cancelSubscription($id, $immediately, $reason);
            
            if ($success) {
                $this->logger->security("Subscription cancelled by admin", [
                    'subscription_id' => $id,
                    'admin_id' => $_SESSION['user_id'],
                    'immediately' => $immediately,
                    'reason' => $reason
                ]);
                
                $_SESSION['success'] = $immediately 
                    ? "Abonnement annulé immédiatement" 
                    : "Abonnement annulé à la fin de la période";
            } else {
                $_SESSION['error'] = "Impossible d'annuler cet abonnement";
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Subscription cancellation failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de l'annulation";
        }
        
        header("Location: /admin/premium/subscriptions/{$id}");
        exit;
    }
    
    /**
     * Réactiver un abonnement annulé
     * 
     * POST /admin/premium/subscriptions/{id}/reactivate
     */
    public function reactivate(int $id): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        try {
            // Vérifier que l'abonnement peut être réactivé
            $subscription = $this->db->queryOne("
                SELECT * FROM user_subscriptions WHERE id = ?
            ", [$id]);
            
            if (!$subscription || $subscription['status'] !== 'cancelled') {
                $_SESSION['error'] = "Cet abonnement ne peut pas être réactivé";
                header("Location: /admin/premium/subscriptions/{$id}");
                exit;
            }
            
            // Réactiver
            $this->db->update('user_subscriptions', [
                'status' => 'active',
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
            ], ['id' => $id]);
            
            $this->logger->security("Subscription reactivated by admin", [
                'subscription_id' => $id,
                'admin_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Abonnement réactivé avec succès";
            
        } catch (\Exception $e) {
            $this->logger->error('Subscription reactivation failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de la réactivation";
        }
        
        header("Location: /admin/premium/subscriptions/{$id}");
        exit;
    }
    
    /**
     * Obtenir statistiques abonnements
     */
    private function getSubscriptionStats(): array
    {
        $stats = $this->db->queryOne("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
                COUNT(CASE WHEN status = 'trialing' THEN 1 END) as trial_count,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_count,
                COUNT(CASE WHEN status = 'past_due' THEN 1 END) as past_due_count
            FROM user_subscriptions
        ");
        
        // MRR (Monthly Recurring Revenue)
        $mrr = $this->db->queryOne("
            SELECT COALESCE(SUM(
                CASE 
                    WHEN p.billing_period = 'monthly' THEN p.price
                    WHEN p.billing_period = 'yearly' THEN p.price / 12
                    ELSE 0
                END
            ), 0) as mrr
            FROM user_subscriptions s
            JOIN premium_plans p ON s.plan_id = p.id
            WHERE s.status IN ('active', 'trialing')
        ");
        
        // Abonnements expirant dans 7 jours
        $expiringSoon = $this->db->queryOne("
            SELECT COUNT(*) as count
            FROM user_subscriptions
            WHERE status IN ('active', 'trialing')
              AND current_period_end <= DATE_ADD(NOW(), INTERVAL 7 DAY)
              AND auto_renew = 0
        ")['count'];
        
        return [
            'total' => (int)$stats['total'],
            'active' => (int)$stats['active_count'],
            'trial' => (int)$stats['trial_count'],
            'cancelled' => (int)$stats['cancelled_count'],
            'expired' => (int)$stats['expired_count'],
            'past_due' => (int)$stats['past_due_count'],
            'mrr' => (float)$mrr['mrr'],
            'expiring_soon' => (int)$expiringSoon,
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
