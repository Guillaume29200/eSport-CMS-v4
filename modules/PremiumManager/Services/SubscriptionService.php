<?php
declare(strict_types=1);

namespace PremiumManager\Services;

use Framework\Services\Database;
use Framework\Services\Logger;

/**
 * Service SubscriptionService
 * 
 * Gestion des abonnements utilisateurs
 * Création, mise à jour, annulation, renouvellement
 * 
 * @author Guillaume
 */
class SubscriptionService
{
    private Database $db;
    private Logger $logger;
    
    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    /**
     * Créer abonnement
     */
    public function createSubscription(array $data): int
    {
        $subscription = [
            'user_id' => $data['user_id'],
            'plan_id' => $data['plan_id'],
            'status' => $data['trial_days'] > 0 ? 'trialing' : 'active',
            'stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
            'paypal_subscription_id' => $data['paypal_subscription_id'] ?? null,
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => $this->calculatePeriodEnd($data['billing_period']),
            'trial_ends_at' => $data['trial_days'] > 0 
                ? date('Y-m-d H:i:s', strtotime("+{$data['trial_days']} days"))
                : null,
            'auto_renew' => true,
        ];
        
        $subscriptionId = $this->db->insert('user_subscriptions', $subscription);
        
        $this->logger->info("Subscription created", [
            'subscription_id' => $subscriptionId,
            'user_id' => $data['user_id'],
            'plan_id' => $data['plan_id']
        ]);
        
        return $subscriptionId;
    }
    
    /**
     * Calculer fin de période
     */
    private function calculatePeriodEnd(string $billingPeriod): string
    {
        return match($billingPeriod) {
            'monthly' => date('Y-m-d H:i:s', strtotime('+1 month')),
            'yearly' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'lifetime' => date('Y-m-d H:i:s', strtotime('+100 years')),
            default => date('Y-m-d H:i:s', strtotime('+1 month'))
        };
    }
    
    /**
     * Obtenir abonnement actif d'un utilisateur
     */
    public function getUserSubscription(int $userId): ?array
    {
        return $this->db->queryOne(
            "SELECT s.*, p.name as plan_name, p.price, p.billing_period
             FROM user_subscriptions s
             JOIN premium_plans p ON s.plan_id = p.id
             WHERE s.user_id = ?
               AND s.status IN ('active', 'trialing')
             ORDER BY s.created_at DESC
             LIMIT 1",
            [$userId]
        );
    }
    
    /**
     * Mettre à jour statut abonnement
     */
    public function updateStatus(int $subscriptionId, string $status): void
    {
        $this->db->update(
            'user_subscriptions',
            ['status' => $status],
            ['id' => $subscriptionId]
        );
        
        $this->logger->info("Subscription status updated", [
            'subscription_id' => $subscriptionId,
            'status' => $status
        ]);
    }
    
    /**
     * Annuler abonnement
     */
    public function cancelSubscription(int $subscriptionId, bool $immediately = false): bool
    {
        $subscription = $this->db->queryOne(
            "SELECT * FROM user_subscriptions WHERE id = ?",
            [$subscriptionId]
        );
        
        if (!$subscription) {
            return false;
        }
        
        try {
            // Annuler chez le provider
            if ($subscription['stripe_subscription_id']) {
                $paymentService = new PaymentService($this->db, $this->logger);
                $paymentService->cancelStripeSubscription(
                    $subscription['stripe_subscription_id'],
                    $immediately
                );
            }
            
            // Mettre à jour en DB
            $updateData = [
                'cancelled_at' => date('Y-m-d H:i:s'),
            ];
            
            if ($immediately) {
                $updateData['status'] = 'cancelled';
                $updateData['current_period_end'] = date('Y-m-d H:i:s');
            } else {
                $updateData['cancel_at_period_end'] = true;
                $updateData['auto_renew'] = false;
            }
            
            $this->db->update(
                'user_subscriptions',
                $updateData,
                ['id' => $subscriptionId]
            );
            
            $this->logger->security("Subscription cancelled", [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription['user_id'],
                'immediately' => $immediately
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Cancel subscription error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Renouveler abonnement (CRON)
     */
    public function renewSubscription(int $subscriptionId): bool
    {
        $subscription = $this->db->queryOne(
            "SELECT s.*, p.billing_period 
             FROM user_subscriptions s
             JOIN premium_plans p ON s.plan_id = p.id
             WHERE s.id = ?",
            [$subscriptionId]
        );
        
        if (!$subscription || !$subscription['auto_renew']) {
            return false;
        }
        
        try {
            // Calculer nouvelle période
            $newPeriodEnd = $this->calculatePeriodEnd($subscription['billing_period']);
            
            $this->db->update(
                'user_subscriptions',
                [
                    'current_period_start' => $subscription['current_period_end'],
                    'current_period_end' => $newPeriodEnd,
                    'status' => 'active',
                ],
                ['id' => $subscriptionId]
            );
            
            $this->logger->info("Subscription renewed", [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription['user_id']
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Renew subscription error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Upgrader/Downgrader plan
     */
    public function changePlan(int $subscriptionId, int $newPlanId): bool
    {
        $subscription = $this->db->queryOne(
            "SELECT * FROM user_subscriptions WHERE id = ?",
            [$subscriptionId]
        );
        
        if (!$subscription) {
            return false;
        }
        
        $newPlan = $this->db->queryOne(
            "SELECT * FROM premium_plans WHERE id = ?",
            [$newPlanId]
        );
        
        if (!$newPlan) {
            return false;
        }
        
        try {
            // TODO: Gérer prorata avec Stripe
            
            $this->db->update(
                'user_subscriptions',
                ['plan_id' => $newPlanId],
                ['id' => $subscriptionId]
            );
            
            $this->logger->info("Plan changed", [
                'subscription_id' => $subscriptionId,
                'old_plan' => $subscription['plan_id'],
                'new_plan' => $newPlanId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Change plan error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Obtenir abonnements expirant bientôt (pour relances)
     */
    public function getExpiringSubscriptions(int $days = 3): array
    {
        return $this->db->query(
            "SELECT s.*, u.email, p.name as plan_name
             FROM user_subscriptions s
             JOIN users u ON s.user_id = u.id
             JOIN premium_plans p ON s.plan_id = p.id
             WHERE s.status = 'active'
               AND s.auto_renew = 1
               AND s.current_period_end BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
             ORDER BY s.current_period_end ASC",
            [$days]
        );
    }
    
    /**
     * Obtenir statistiques abonnements
     */
    public function getStats(): array
    {
        // Total abonnés actifs
        $activeCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM user_subscriptions 
             WHERE status IN ('active', 'trialing')"
        )['count'];
        
        // Par plan
        $byPlan = $this->db->query(
            "SELECT p.name, COUNT(s.id) as count
             FROM user_subscriptions s
             JOIN premium_plans p ON s.plan_id = p.id
             WHERE s.status IN ('active', 'trialing')
             GROUP BY p.id, p.name"
        );
        
        // Churn rate (30 derniers jours)
        $churnData = $this->db->queryOne(
            "SELECT 
                (SELECT COUNT(*) FROM user_subscriptions 
                 WHERE status = 'cancelled' 
                   AND cancelled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as cancelled,
                (SELECT COUNT(*) FROM user_subscriptions 
                 WHERE status IN ('active', 'trialing')) as active"
        );
        
        $churnRate = $churnData['active'] > 0 
            ? ($churnData['cancelled'] / $churnData['active']) * 100 
            : 0;
        
        return [
            'active_subscriptions' => (int)$activeCount,
            'by_plan' => $byPlan,
            'churn_rate' => round($churnRate, 2),
        ];
    }
}
