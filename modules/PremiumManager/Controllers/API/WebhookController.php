<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\API;

use Framework\Services\Database;
use Framework\Services\Logger;
use PremiumManager\Services\PaymentService;
use PremiumManager\Services\SubscriptionService;
use PremiumManager\Services\InvoiceService;

/**
 * Controller WebhookController (API)
 * 
 * Réception et traitement des webhooks
 * - Stripe (paiements, abonnements, échecs)
 * - PayPal
 * - Validation signatures
 * - Logs complets
 * 
 * @author Guillaume
 */
class WebhookController
{
    private Database $db;
    private Logger $logger;
    private PaymentService $paymentService;
    private SubscriptionService $subscriptionService;
    private InvoiceService $invoiceService;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logger = new Logger($db, []);
        $this->paymentService = new PaymentService($db, $this->logger);
        $this->subscriptionService = new SubscriptionService($db, $this->logger);
        $this->invoiceService = new InvoiceService($db, $this->logger);
    }
    
    /**
     * Webhook Stripe
     * 
     * POST /api/premium/webhook/stripe
     */
    public function stripe(): void
    {
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        // Logger le webhook
        $webhookLogId = $this->logWebhook('stripe', 'unknown', $payload);
        
        try {
            // Vérifier signature
            $webhookSecret = $this->getStripeWebhookSecret();
            
            if (empty($webhookSecret)) {
                throw new \Exception("Webhook secret not configured");
            }
            
            \Stripe\Stripe::setApiKey($this->getStripeSe cretKey());
            
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
            
            // Mettre à jour le log avec le type d'événement
            $this->db->update('premium_webhook_logs', [
                'event_type' => $event->type
            ], ['id' => $webhookLogId]);
            
            // Traiter événement
            $this->handleStripeEvent($event);
            
            // Marquer comme traité
            $this->db->update('premium_webhook_logs', [
                'status' => 'processed',
                'processed_at' => date('Y-m-d H:i:s')
            ], ['id' => $webhookLogId]);
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
            
        } catch (\Exception $e) {
            $this->logger->error('Stripe webhook error', [
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLogId
            ]);
            
            $this->db->update('premium_webhook_logs', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], ['id' => $webhookLogId]);
            
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Traiter événement Stripe
     */
    private function handleStripeEvent(\Stripe\Event $event): void
    {
        switch ($event->type) {
            // Paiement réussi
            case 'payment_intent.succeeded':
                $this->handlePaymentSuccess($event->data->object);
                break;
                
            // Paiement échoué
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
                
            // Abonnement créé
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;
                
            // Abonnement mis à jour
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
                
            // Abonnement supprimé
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
                
            // Paiement de facture réussi
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
                
            // Paiement de facture échoué
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;
                
            default:
                $this->logger->info("Unhandled Stripe event", ['type' => $event->type]);
        }
    }
    
    /**
     * Paiement réussi
     */
    private function handlePaymentSuccess($paymentIntent): void
    {
        $transactionId = $paymentIntent->metadata->transaction_id ?? null;
        
        if (!$transactionId) {
            $this->logger->warning("Payment success without transaction_id", [
                'payment_intent' => $paymentIntent->id
            ]);
            return;
        }
        
        // Mettre à jour transaction
        $this->paymentService->updateTransactionStatus(
            (int)$transactionId,
            'completed',
            $paymentIntent->id
        );
        
        // Générer facture
        $this->invoiceService->createInvoiceForTransaction((int)$transactionId);
        
        $this->logger->info("Payment succeeded", [
            'transaction_id' => $transactionId,
            'payment_intent' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100
        ]);
    }
    
    /**
     * Paiement échoué
     */
    private function handlePaymentFailed($paymentIntent): void
    {
        $transactionId = $paymentIntent->metadata->transaction_id ?? null;
        
        if (!$transactionId) {
            return;
        }
        
        $this->db->update('premium_transactions', [
            'status' => 'failed',
            'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown error'
        ], ['id' => (int)$transactionId]);
        
        $this->logger->warning("Payment failed", [
            'transaction_id' => $transactionId,
            'payment_intent' => $paymentIntent->id,
            'reason' => $paymentIntent->last_payment_error->message ?? 'Unknown'
        ]);
    }
    
    /**
     * Abonnement créé
     */
    private function handleSubscriptionCreated($subscription): void
    {
        $userId = $subscription->metadata->user_id ?? null;
        $planId = $subscription->metadata->plan_id ?? null;
        
        if (!$userId || !$planId) {
            $this->logger->warning("Subscription created without metadata", [
                'subscription_id' => $subscription->id
            ]);
            return;
        }
        
        // Créer abonnement en DB
        $this->db->insert('user_subscriptions', [
            'user_id' => $userId,
            'plan_id' => $planId,
            'status' => $subscription->status,
            'stripe_subscription_id' => $subscription->id,
            'current_period_start' => date('Y-m-d H:i:s', $subscription->current_period_start),
            'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
            'trial_ends_at' => $subscription->trial_end ? date('Y-m-d H:i:s', $subscription->trial_end) : null,
        ]);
        
        $this->logger->info("Subscription created", [
            'user_id' => $userId,
            'plan_id' => $planId,
            'subscription_id' => $subscription->id
        ]);
    }
    
    /**
     * Abonnement mis à jour
     */
    private function handleSubscriptionUpdated($subscription): void
    {
        $dbSubscription = $this->db->queryOne("
            SELECT id FROM user_subscriptions 
            WHERE stripe_subscription_id = ?
        ", [$subscription->id]);
        
        if (!$dbSubscription) {
            $this->logger->warning("Subscription update for unknown subscription", [
                'subscription_id' => $subscription->id
            ]);
            return;
        }
        
        $this->db->update('user_subscriptions', [
            'status' => $subscription->status,
            'current_period_start' => date('Y-m-d H:i:s', $subscription->current_period_start),
            'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
        ], ['id' => $dbSubscription['id']]);
    }
    
    /**
     * Abonnement supprimé
     */
    private function handleSubscriptionDeleted($subscription): void
    {
        $this->db->update('user_subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ], ['stripe_subscription_id' => $subscription->id]);
        
        $this->logger->info("Subscription deleted", [
            'subscription_id' => $subscription->id
        ]);
    }
    
    /**
     * Facture payée
     */
    private function handleInvoicePaymentSucceeded($invoice): void
    {
        $this->logger->info("Invoice payment succeeded", [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription ?? null
        ]);
    }
    
    /**
     * Paiement facture échoué
     */
    private function handleInvoicePaymentFailed($invoice): void
    {
        // Marquer abonnement en retard
        if ($invoice->subscription) {
            $this->db->update('user_subscriptions', [
                'status' => 'past_due'
            ], ['stripe_subscription_id' => $invoice->subscription]);
        }
        
        $this->logger->warning("Invoice payment failed", [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription ?? null
        ]);
    }
    
    /**
     * Webhook PayPal
     * 
     * POST /api/premium/webhook/paypal
     */
    public function paypal(): void
    {
        $payload = @file_get_contents('php://input');
        
        // Logger le webhook
        $webhookLogId = $this->logWebhook('paypal', 'unknown', $payload);
        
        try {
            $data = json_decode($payload, true);
            
            if (!$data) {
                throw new \Exception("Invalid JSON payload");
            }
            
            // TODO: Vérifier signature PayPal
            // TODO: Implémenter traitement événements PayPal
            
            $this->db->update('premium_webhook_logs', [
                'status' => 'processed',
                'processed_at' => date('Y-m-d H:i:s')
            ], ['id' => $webhookLogId]);
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
            
        } catch (\Exception $e) {
            $this->logger->error('PayPal webhook error', [
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLogId
            ]);
            
            $this->db->update('premium_webhook_logs', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], ['id' => $webhookLogId]);
            
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Logger webhook
     */
    private function logWebhook(string $provider, string $eventType, string $payload): int
    {
        return $this->db->insert('premium_webhook_logs', [
            'provider' => $provider,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'received'
        ]);
    }
    
    /**
     * Obtenir secret webhook Stripe
     */
    private function getStripeWebhookSecret(): string
    {
        $setting = $this->db->queryOne("
            SELECT setting_value FROM module_settings
            WHERE module = 'PremiumManager' AND setting_key = 'stripe_webhook_secret'
        ");
        
        return $setting ? json_decode($setting['setting_value'], true) : '';
    }
    
    /**
     * Obtenir clé secrète Stripe
     */
    private function getStripeSecretKey(): string
    {
        $setting = $this->db->queryOne("
            SELECT setting_value FROM module_settings
            WHERE module = 'PremiumManager' AND setting_key = 'stripe_secret_key'
        ");
        
        return $setting ? json_decode($setting['setting_value'], true) : '';
    }
}
