<?php
declare(strict_types=1);

namespace PremiumManager\Models;

/**
 * Model Subscription
 * 
 * Représente un abonnement utilisateur
 * 
 * @author Guillaume
 */
class Subscription
{
    public int $id;
    public int $userId;
    public int $planId;
    public string $status; // active, trialing, cancelled, expired, past_due
    public ?string $stripeSubscriptionId;
    public ?string $paypalSubscriptionId;
    public string $currentPeriodStart;
    public string $currentPeriodEnd;
    public bool $cancelAtPeriodEnd;
    public ?string $cancelledAt;
    public ?string $trialEndsAt;
    public bool $autoRenew;
    public string $createdAt;
    public string $updatedAt;
    
    // Relations
    public ?PremiumPlan $plan = null;
    
    /**
     * Créer depuis tableau
     */
    public static function fromArray(array $data): self
    {
        $subscription = new self();
        
        $subscription->id = (int)$data['id'];
        $subscription->userId = (int)$data['user_id'];
        $subscription->planId = (int)$data['plan_id'];
        $subscription->status = $data['status'];
        $subscription->stripeSubscriptionId = $data['stripe_subscription_id'] ?? null;
        $subscription->paypalSubscriptionId = $data['paypal_subscription_id'] ?? null;
        $subscription->currentPeriodStart = $data['current_period_start'];
        $subscription->currentPeriodEnd = $data['current_period_end'];
        $subscription->cancelAtPeriodEnd = (bool)$data['cancel_at_period_end'];
        $subscription->cancelledAt = $data['cancelled_at'] ?? null;
        $subscription->trialEndsAt = $data['trial_ends_at'] ?? null;
        $subscription->autoRenew = (bool)$data['auto_renew'];
        $subscription->createdAt = $data['created_at'];
        $subscription->updatedAt = $data['updated_at'];
        
        return $subscription;
    }
    
    /**
     * Est actif ?
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }
    
    /**
     * Est en période d'essai ?
     */
    public function isTrialing(): bool
    {
        return $this->status === 'trialing';
    }
    
    /**
     * Est annulé ?
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelAtPeriodEnd;
    }
    
    /**
     * Jours restants
     */
    public function getDaysRemaining(): int
    {
        $now = time();
        $end = strtotime($this->currentPeriodEnd);
        
        return max(0, (int)ceil(($end - $now) / 86400));
    }
    
    /**
     * Obtenir statut formaté
     */
    public function getFormattedStatus(): string
    {
        return match($this->status) {
            'active' => '✅ Actif',
            'trialing' => '🎁 Essai gratuit',
            'cancelled' => '❌ Annulé',
            'expired' => '⏰ Expiré',
            'past_due' => '⚠️ Paiement en attente',
            default => $this->status
        };
    }
    
    /**
     * Obtenir message d'état
     */
    public function getStatusMessage(): string
    {
        if ($this->isTrialing() && $this->trialEndsAt) {
            $daysLeft = (int)ceil((strtotime($this->trialEndsAt) - time()) / 86400);
            return "Essai gratuit - {$daysLeft} jours restants";
        }
        
        if ($this->cancelAtPeriodEnd) {
            $daysLeft = $this->getDaysRemaining();
            return "Annulé - Actif jusqu'au " . date('d/m/Y', strtotime($this->currentPeriodEnd)) . " ({$daysLeft} jours)";
        }
        
        if ($this->isActive()) {
            return "Prochain renouvellement: " . date('d/m/Y', strtotime($this->currentPeriodEnd));
        }
        
        return $this->getFormattedStatus();
    }
}
