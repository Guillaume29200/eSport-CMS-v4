<?php
declare(strict_types=1);

namespace PremiumManager\Hooks;

use Framework\Services\Database;

/**
 * Hooks AdminHooks
 * 
 * Intégration du module Premium dans l'admin
 * Menu, widgets, etc.
 * 
 * @author Guillaume
 */
class AdminHooks
{
    /**
     * Ajouter entrée menu admin
     */
    public static function addAdminMenu(array $menu): array
    {
        $menu['premium'] = [
            'title' => '💎 Premium',
            'icon' => 'diamond',
            'priority' => 50,
            'items' => [
                [
                    'title' => 'Dashboard',
                    'url' => '/admin/premium',
                    'icon' => 'dashboard'
                ],
                [
                    'title' => 'Plans',
                    'url' => '/admin/premium/plans',
                    'icon' => 'list'
                ],
                [
                    'title' => 'Abonnements',
                    'url' => '/admin/premium/subscriptions',
                    'icon' => 'users'
                ],
                [
                    'title' => 'Transactions',
                    'url' => '/admin/premium/transactions',
                    'icon' => 'credit-card'
                ],
                [
                    'title' => 'Coupons',
                    'url' => '/admin/premium/coupons',
                    'icon' => 'tag'
                ],
                [
                    'title' => 'Configuration',
                    'url' => '/admin/premium/settings',
                    'icon' => 'settings'
                ],
            ]
        ];
        
        return $menu;
    }
    
    /**
     * Ajouter widget dashboard admin
     */
    public static function addDashboardWidget(array $widgets): array
    {
        $db = new Database(
            require __DIR__ . '/../../framework/config/database.php',
            require __DIR__ . '/../../framework/config/environment.php'
        );
        
        // Stats rapides
        $stats = self::getQuickStats($db);
        
        $widgets[] = [
            'title' => '💰 Revenus Premium',
            'priority' => 10,
            'content' => self::renderRevenueWidget($stats)
        ];
        
        return $widgets;
    }
    
    /**
     * Obtenir statistiques rapides
     */
    private static function getQuickStats(Database $db): array
    {
        // Revenus ce mois
        $currentMonth = date('Y-m');
        $monthlyRevenue = $db->queryOne("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM premium_transactions
            WHERE status = 'completed'
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ", [$currentMonth])['total'];
        
        // Abonnés actifs
        $activeSubscribers = $db->queryOne("
            SELECT COUNT(*) as count
            FROM user_subscriptions
            WHERE status IN ('active', 'trialing')
        ")['count'];
        
        // Nouveaux abonnés (7j)
        $newSubscribers = $db->queryOne("
            SELECT COUNT(*) as count
            FROM user_subscriptions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")['count'];
        
        return [
            'monthly_revenue' => (float)$monthlyRevenue,
            'active_subscribers' => (int)$activeSubscribers,
            'new_subscribers' => (int)$newSubscribers,
        ];
    }
    
    /**
     * Rendre widget revenus
     */
    private static function renderRevenueWidget(array $stats): string
    {
        ob_start();
        ?>
        <div class="premium-widget">
            <div class="stat-grid">
                <div class="stat-item">
                    <div class="stat-value">€<?= number_format($stats['monthly_revenue'], 2) ?></div>
                    <div class="stat-label">Revenus ce mois</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $stats['active_subscribers'] ?></div>
                    <div class="stat-label">Abonnés actifs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">+<?= $stats['new_subscribers'] ?></div>
                    <div class="stat-label">Nouveaux (7j)</div>
                </div>
            </div>
            <div class="widget-actions">
                <a href="/admin/premium" class="btn btn-primary btn-sm">Voir détails →</a>
            </div>
        </div>
        <style>
            .premium-widget { padding: 15px; }
            .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; }
            .stat-item { text-align: center; padding: 10px; background: #f8f9fa; border-radius: 8px; }
            .stat-value { font-size: 24px; font-weight: bold; color: #28a745; }
            .stat-label { font-size: 12px; color: #6c757d; margin-top: 5px; }
            .widget-actions { text-align: center; }
        </style>
        <?php
        return ob_get_clean();
    }
}
