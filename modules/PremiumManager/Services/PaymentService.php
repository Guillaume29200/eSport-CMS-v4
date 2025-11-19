<?php
declare(strict_types=1);

namespace PremiumManager\Services;

use Framework\Services\Database;
use Framework\Services\Logger;

/**
 * Service PaymentService
 * 
 * Gestion des paiements multi-providers
 * Support: Stripe, PayPal, Virement bancaire
 * 
 * @author Guillaume
 */
class PaymentService
{
    private Database $db;
    private Logger $logger;
    private string $defaultCurrency = 'EUR';
    
    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    /**
     * Créer une transaction
     */
    public function createTransaction(array $data): int
    {
        $transaction = [
            'user_id' => $data['user_id'],
            'transaction_type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? $this->defaultCurrency,
            'status' => 'pending',
            'payment_provider' => $data['provider'],
            'content_type' => $data['content_type'] ?? null,
            'content_id' => $data['content_id'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'coupon_code' => $data['coupon_code'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? 0.00,
            'metadata' => json_encode($data['metadata'] ?? []),
        ];
        
        return $this->db->insert('premium_transactions', $transaction);
    }
    
    /**
     * Mettre à jour statut transaction
     */
    public function updateTransactionStatus(
        int $transactionId,
        string $status,
        ?string $providerTransactionId = null
    ): void {
        $data = ['status' => $status];
        
        if ($providerTransactionId) {
            $data['provider_transaction_id'] = $providerTransactionId;
        }
        
        $this->db->update(
            'premium_transactions',
            $data,
            ['id' => $transactionId]
        );
        
        $this->logger->info("Transaction {$transactionId} updated to {$status}");
    }
    
    /**
     * Process paiement Stripe
     */
    public function processStripePayment(array $paymentData): array
    {
        try {
            \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
            
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int)($paymentData['amount'] * 100), // Centimes
                'currency' => strtolower($paymentData['currency']),
                'customer' => $paymentData['stripe_customer_id'] ?? null,
                'metadata' => [
                    'user_id' => $paymentData['user_id'],
                    'transaction_id' => $paymentData['transaction_id'],
                    'content_type' => $paymentData['content_type'] ?? null,
                    'content_id' => $paymentData['content_id'] ?? null,
                ],
                'automatic_payment_methods' => ['enabled' => true],
            ]);
            
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Stripe payment error', [
                'error' => $e->getMessage(),
                'data' => $paymentData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Créer abonnement Stripe
     */
    public function createStripeSubscription(array $subscriptionData): array
    {
        try {
            \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
            
            // Créer ou récupérer customer Stripe
            $customerId = $this->getOrCreateStripeCustomer($subscriptionData['user_id']);
            
            // Créer subscription
            $subscription = \Stripe\Subscription::create([
                'customer' => $customerId,
                'items' => [
                    ['price' => $subscriptionData['stripe_price_id']],
                ],
                'trial_period_days' => $subscriptionData['trial_days'] ?? null,
                'metadata' => [
                    'user_id' => $subscriptionData['user_id'],
                    'plan_id' => $subscriptionData['plan_id'],
                ],
            ]);
            
            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Stripe subscription error', [
                'error' => $e->getMessage(),
                'data' => $subscriptionData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Annuler abonnement Stripe
     */
    public function cancelStripeSubscription(string $subscriptionId, bool $immediately = false): bool
    {
        try {
            \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
            
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            
            if ($immediately) {
                $subscription->cancel();
            } else {
                $subscription->cancel_at_period_end = true;
                $subscription->save();
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Stripe cancel error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Obtenir ou créer customer Stripe
     */
    private function getOrCreateStripeCustomer(int $userId): string
    {
        // Vérifier si customer existe en DB
        $user = $this->db->queryOne(
            "SELECT stripe_customer_id, email FROM users WHERE id = ?",
            [$userId]
        );
        
        if ($user['stripe_customer_id']) {
            return $user['stripe_customer_id'];
        }
        
        // Créer customer Stripe
        try {
            \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
            
            $customer = \Stripe\Customer::create([
                'email' => $user['email'],
                'metadata' => ['user_id' => $userId],
            ]);
            
            // Sauvegarder ID dans users
            $this->db->update(
                'users',
                ['stripe_customer_id' => $customer->id],
                ['id' => $userId]
            );
            
            return $customer->id;
            
        } catch (\Exception $e) {
            $this->logger->error('Stripe customer creation error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Failed to create Stripe customer');
        }
    }
    
    /**
     * Process remboursement
     */
    public function processRefund(int $transactionId, ?string $reason = null): bool
    {
        $transaction = $this->db->queryOne(
            "SELECT * FROM premium_transactions WHERE id = ?",
            [$transactionId]
        );
        
        if (!$transaction || $transaction['status'] !== 'completed') {
            return false;
        }
        
        try {
            // Remboursement selon provider
            $refunded = match($transaction['payment_provider']) {
                'stripe' => $this->refundStripe($transaction['provider_transaction_id'], $reason),
                'paypal' => $this->refundPayPal($transaction['provider_transaction_id'], $reason),
                default => false
            };
            
            if ($refunded) {
                $this->db->update(
                    'premium_transactions',
                    [
                        'status' => 'refunded',
                        'refunded_at' => date('Y-m-d H:i:s')
                    ],
                    ['id' => $transactionId]
                );
                
                // Révoquer accès si one-time
                if ($transaction['transaction_type'] === 'one_time') {
                    $this->revokeAccess($transaction['user_id'], $transaction['content_type'], $transaction['content_id']);
                }
                
                $this->logger->security("Refund processed", [
                    'transaction_id' => $transactionId,
                    'user_id' => $transaction['user_id'],
                    'amount' => $transaction['amount']
                ]);
            }
            
            return $refunded;
            
        } catch (\Exception $e) {
            $this->logger->error('Refund error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Remboursement Stripe
     */
    private function refundStripe(string $paymentIntentId, ?string $reason): bool
    {
        try {
            \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
            
            \Stripe\Refund::create([
                'payment_intent' => $paymentIntentId,
                'reason' => $reason ? 'requested_by_customer' : null,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Stripe refund error', [
                'payment_intent' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Remboursement PayPal (placeholder)
     */
    private function refundPayPal(string $transactionId, ?string $reason): bool
    {
        // TODO: Implémenter PayPal refund
        return false;
    }
    
    /**
     * Révoquer accès premium
     */
    private function revokeAccess(int $userId, string $contentType, int $contentId): void
    {
        $this->db->delete('user_premium_access', [
            'user_id' => $userId,
            'content_type' => $contentType,
            'content_id' => $contentId
        ]);
    }
    
    /**
     * Obtenir statistiques revenus
     */
    public function getRevenueStats(int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // Revenu total
        $totalRevenue = $this->db->queryOne("
            SELECT 
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as transaction_count
            FROM premium_transactions
            WHERE status = 'completed'
              AND DATE(created_at) >= ?
        ", [$startDate]);
        
        // Par type
        $byType = $this->db->query("
            SELECT 
                transaction_type,
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
            FROM premium_transactions
            WHERE status = 'completed'
              AND DATE(created_at) >= ?
            GROUP BY transaction_type
        ", [$startDate]);
        
        // Par jour
        $daily = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
            FROM premium_transactions
            WHERE status = 'completed'
              AND DATE(created_at) >= ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$startDate]);
        
        return [
            'total_revenue' => (float)$totalRevenue['total'],
            'transaction_count' => (int)$totalRevenue['transaction_count'],
            'by_type' => $byType,
            'daily' => $daily,
        ];
    }
}
