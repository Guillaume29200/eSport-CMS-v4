<?php
declare(strict_types=1);

namespace PremiumManager\Hooks;

use Framework\Services\Database;

/**
 * Hooks UserHooks
 * 
 * Ajoute des éléments à l'interface utilisateur
 * - Onglet premium dans profil
 * - Badge membre premium
 * 
 * @author Guillaume
 */
class UserHooks
{
    /**
     * Ajouter onglet Premium dans profil utilisateur
     */
    public static function addProfileTab(array $tabs): array
    {
        $tabs['premium'] = [
            'title' => '💎 Premium',
            'icon' => 'fas fa-crown',
            'url' => '/member/premium/subscription',
            'order' => 50,
            'badge' => self::getPremiumBadge(),
        ];
        
        return $tabs;
    }
    
    /**
     * Obtenir badge premium utilisateur
     */
    private static function getPremiumBadge(): ?string
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $db = new Database(
            require __DIR__ . '/../../framework/config/database.php',
            require __DIR__ . '/../../framework/config/environment.php'
        );
        
        // Vérifier si user a abonnement actif
        $subscription = $db->queryOne("
            SELECT s.*, p.name as plan_name
            FROM user_subscriptions s
            JOIN premium_plans p ON s.plan_id = p.id
            WHERE s.user_id = ?
              AND s.status IN ('active', 'trialing')
            ORDER BY s.created_at DESC
            LIMIT 1
        ", [$_SESSION['user_id']]);
        
        if ($subscription) {
            return '<span class="badge badge-gold">' . htmlspecialchars($subscription['plan_name']) . '</span>';
        }
        
        return null;
    }
    
    /**
     * Ajouter indicateur premium dans header utilisateur
     */
    public static function addUserHeaderBadge(array $context): string
    {
        $html = $context['html'] ?? '';
        
        if (!isset($_SESSION['user_id'])) {
            return $html;
        }
        
        $db = new Database(
            require __DIR__ . '/../../framework/config/database.php',
            require __DIR__ . '/../../framework/config/environment.php'
        );
        
        $subscription = $db->queryOne("
            SELECT p.name, p.slug
            FROM user_subscriptions s
            JOIN premium_plans p ON s.plan_id = p.id
            WHERE s.user_id = ?
              AND s.status IN ('active', 'trialing')
            ORDER BY s.created_at DESC
            LIMIT 1
        ", [$_SESSION['user_id']]);
        
        if ($subscription) {
            $planName = htmlspecialchars($subscription['name']);
            $badgeClass = 'badge-' . strtolower($subscription['slug']);
            
            $html .= <<<HTML
            <span class="premium-badge {$badgeClass}" title="Abonné {$planName}">
                <i class="fas fa-crown"></i> {$planName}
            </span>
HTML;
        }
        
        return $html;
    }
    
    /**
     * Modifier menu utilisateur pour ajouter lien Premium
     */
    public static function modifyUserMenu(array $menu): array
    {
        // Ajouter item premium dans menu
        $menu['premium'] = [
            'title' => '💎 Mon Premium',
            'url' => '/member/premium/subscription',
            'icon' => 'fas fa-crown',
            'order' => 10,
        ];
        
        return $menu;
    }
}
