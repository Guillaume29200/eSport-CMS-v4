<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Admin;

use Framework\Services\Database;
use PremiumManager\Services\PaymentService;
use PremiumManager\Services\SubscriptionService;

/**
 * Controller DashboardController
 * 
 * Dashboard admin du module Premium
 * Vue d'ensemble: revenus, abonnements, stats
 * 
 * @author Guillaume
 */
class DashboardController
{
    private Database $db;
    private PaymentService $paymentService;
    private SubscriptionService $subscriptionService;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->paymentService = new PaymentService($db, new \Framework\Services\Logger($db, []));
        $this->subscriptionService = new SubscriptionService($db, new \Framework\Services\Logger($db, []));
    }
    
    /**
     * Afficher dashboard
     */
    public function index(): void
    {
        // Statistiques revenus (30 derniers jours)
        $revenueStats = $this->paymentService->getRevenueStats(30);
        
        // Statistiques abonnements
        $subscriptionStats = $this->subscriptionService->getStats();
        
        // Revenus ce mois
        $monthlyRevenue = $this->getMonthlyRevenue();
        
        // Transactions récentes
        $recentTransactions = $this->getRecentTransactions(10);
        
        // Nouveaux abonnés (7 derniers jours)
        $newSubscribers = $this->getNewSubscribers(7);
        
        // Abonnements expirant bientôt
        $expiringSubscriptions = $this->subscriptionService->getExpiringSubscriptions(7);
        
        // Rendre la vue
        include __DIR__ . '/../../Views/admin/dashboard.php';
    }
    
    /**
     * Obtenir revenus du mois en cours
     */
    private function getMonthlyRevenue(): array
    {
        $currentMonth = date('Y-m');
        
        $data = $this->db->queryOne("
            SELECT 
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as transaction_count
            FROM premium_transactions
            WHERE status = 'completed'
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ", [$currentMonth]);
        
        // Comparer au mois dernier
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        $lastMonthData = $this->db->queryOne("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM premium_transactions
            WHERE status = 'completed'
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ", [$lastMonth]);
        
        $growth = 0;
        if ($lastMonthData['total'] > 0) {
            $growth = (($data['total'] - $lastMonthData['total']) / $lastMonthData['total']) * 100;
        }
        
        return [
            'total' => (float)$data['total'],
            'transaction_count' => (int)$data['transaction_count'],
            'growth' => round($growth, 1),
        ];
    }
    
    /**
     * Obtenir transactions récentes
     */
    private function getRecentTransactions(int $limit = 10): array
    {
        return $this->db->query("
            SELECT t.*, u.username, u.email
            FROM premium_transactions t
            JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at DESC
            LIMIT ?
        ", [$limit]);
    }
    
    /**
     * Obtenir nouveaux abonnés
     */
    private function getNewSubscribers(int $days = 7): array
    {
        return $this->db->query("
            SELECT s.*, u.username, u.email, p.name as plan_name
            FROM user_subscriptions s
            JOIN users u ON s.user_id = u.id
            JOIN premium_plans p ON s.plan_id = p.id
            WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND s.status IN ('active', 'trialing')
            ORDER BY s.created_at DESC
        ", [$days]);
    }
}
