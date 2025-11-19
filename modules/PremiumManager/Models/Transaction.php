<?php
declare(strict_types=1);

namespace PremiumManager\Models;

/**
 * Model Transaction
 * 
 * Représente une transaction de paiement
 * 
 * @author Guillaume
 */
class Transaction
{
    public int $id;
    public int $userId;
    public string $transactionType; // subscription, one_time, refund, upgrade, downgrade
    public float $amount;
    public string $currency;
    public string $status; // pending, completed, failed, refunded, cancelled
    public string $paymentProvider; // stripe, paypal, manual, bank_transfer
    public ?string $providerTransactionId;
    public ?string $providerCustomerId;
    public ?string $contentType;
    public ?int $contentId;
    public ?int $planId;
    public ?int $subscriptionId;
    public ?string $couponCode;
    public float $discountAmount;
    public ?int $invoiceId;
    public array $metadata;
    public ?string $failureReason;
    public ?string $refundedAt;
    public string $createdAt;
    public string $updatedAt;
    
    /**
     * Créer depuis tableau
     */
    public static function fromArray(array $data): self
    {
        $transaction = new self();
        
        $transaction->id = (int)$data['id'];
        $transaction->userId = (int)$data['user_id'];
        $transaction->transactionType = $data['transaction_type'];
        $transaction->amount = (float)$data['amount'];
        $transaction->currency = $data['currency'];
        $transaction->status = $data['status'];
        $transaction->paymentProvider = $data['payment_provider'];
        $transaction->providerTransactionId = $data['provider_transaction_id'] ?? null;
        $transaction->providerCustomerId = $data['provider_customer_id'] ?? null;
        $transaction->contentType = $data['content_type'] ?? null;
        $transaction->contentId = isset($data['content_id']) ? (int)$data['content_id'] : null;
        $transaction->planId = isset($data['plan_id']) ? (int)$data['plan_id'] : null;
        $transaction->subscriptionId = isset($data['subscription_id']) ? (int)$data['subscription_id'] : null;
        $transaction->couponCode = $data['coupon_code'] ?? null;
        $transaction->discountAmount = (float)($data['discount_amount'] ?? 0);
        $transaction->invoiceId = isset($data['invoice_id']) ? (int)$data['invoice_id'] : null;
        $transaction->metadata = json_decode($data['metadata'] ?? '{}', true);
        $transaction->failureReason = $data['failure_reason'] ?? null;
        $transaction->refundedAt = $data['refunded_at'] ?? null;
        $transaction->createdAt = $data['created_at'];
        $transaction->updatedAt = $data['updated_at'];
        
        return $transaction;
    }
    
    /**
     * Est complétée ?
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
    
    /**
     * Est échouée ?
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
    
    /**
     * Est remboursée ?
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
    
    /**
     * Obtenir montant formaté
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
    
    /**
     * Obtenir montant final (après réduction)
     */
    public function getFinalAmount(): float
    {
        return max(0, $this->amount - $this->discountAmount);
    }
    
    /**
     * Obtenir montant final formaté
     */
    public function getFormattedFinalAmount(): string
    {
        return number_format($this->getFinalAmount(), 2) . ' ' . $this->currency;
    }
    
    /**
     * Obtenir statut formaté
     */
    public function getFormattedStatus(): string
    {
        return match($this->status) {
            'completed' => '✅ Payé',
            'pending' => '⏳ En attente',
            'failed' => '❌ Échoué',
            'refunded' => '↩️ Remboursé',
            'cancelled' => '🚫 Annulé',
            default => $this->status
        };
    }
    
    /**
     * Obtenir type formaté
     */
    public function getFormattedType(): string
    {
        return match($this->transactionType) {
            'subscription' => 'Abonnement',
            'one_time' => 'Achat unique',
            'refund' => 'Remboursement',
            'upgrade' => 'Upgrade',
            'downgrade' => 'Downgrade',
            default => $this->transactionType
        };
    }
    
    /**
     * Obtenir provider formaté
     */
    public function getFormattedProvider(): string
    {
        return match($this->paymentProvider) {
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'manual' => 'Manuel',
            'bank_transfer' => 'Virement bancaire',
            default => $this->paymentProvider
        };
    }
    
    /**
     * Peut être remboursé ?
     */
    public function canBeRefunded(): bool
    {
        return $this->isCompleted() 
            && !$this->isRefunded()
            && in_array($this->paymentProvider, ['stripe', 'paypal']);
    }
}
