<?php
declare(strict_types=1);

namespace PremiumManager\Models;

/**
 * Model PremiumContent
 * 
 * Représente un contenu premium (article, page, module, etc.)
 * 
 * @author Guillaume
 */
class PremiumContent
{
    public int $id;
    public string $contentType; // article, page, module, forum_section, download
    public int $contentId;
    public string $accessType; // one_time, subscription, plan_required
    public ?float $price;
    public string $currency;
    public ?array $requiredPlanIds;
    public bool $previewEnabled;
    public int $previewLength;
    public ?string $customPaywallMessage;
    public bool $active;
    public string $createdAt;
    public string $updatedAt;
    
    /**
     * Créer depuis tableau
     */
    public static function fromArray(array $data): self
    {
        $content = new self();
        
        $content->id = (int)$data['id'];
        $content->contentType = $data['content_type'];
        $content->contentId = (int)$data['content_id'];
        $content->accessType = $data['access_type'];
        $content->price = isset($data['price']) ? (float)$data['price'] : null;
        $content->currency = $data['currency'] ?? 'EUR';
        $content->requiredPlanIds = isset($data['required_plan_ids']) 
            ? json_decode($data['required_plan_ids'], true) 
            : null;
        $content->previewEnabled = (bool)$data['preview_enabled'];
        $content->previewLength = (int)($data['preview_length'] ?? 300);
        $content->customPaywallMessage = $data['custom_paywall_message'] ?? null;
        $content->active = (bool)$data['active'];
        $content->createdAt = $data['created_at'];
        $content->updatedAt = $data['updated_at'];
        
        return $content;
    }
    
    /**
     * Est un achat one-time ?
     */
    public function isOneTime(): bool
    {
        return $this->accessType === 'one_time';
    }
    
    /**
     * Nécessite un abonnement ?
     */
    public function requiresSubscription(): bool
    {
        return $this->accessType === 'subscription';
    }
    
    /**
     * Nécessite un plan spécifique ?
     */
    public function requiresPlan(): bool
    {
        return $this->accessType === 'plan_required';
    }
    
    /**
     * Obtenir prix formaté
     */
    public function getFormattedPrice(): string
    {
        if ($this->price === null) {
            return 'Gratuit';
        }
        
        return number_format($this->price, 2) . ' ' . $this->currency;
    }
    
    /**
     * Obtenir type de contenu formaté
     */
    public function getFormattedContentType(): string
    {
        return match($this->contentType) {
            'article' => 'Article',
            'page' => 'Page',
            'module' => 'Module',
            'forum_section' => 'Section Forum',
            'download' => 'Téléchargement',
            default => $this->contentType
        };
    }
    
    /**
     * Obtenir type d'accès formaté
     */
    public function getFormattedAccessType(): string
    {
        return match($this->accessType) {
            'one_time' => '💰 Achat unique',
            'subscription' => '🔄 Abonnement requis',
            'plan_required' => '👑 Plan spécifique',
            default => $this->accessType
        };
    }
    
    /**
     * Obtenir message paywall par défaut
     */
    public function getPaywallMessage(): string
    {
        if ($this->customPaywallMessage) {
            return $this->customPaywallMessage;
        }
        
        return match($this->accessType) {
            'one_time' => "Débloquez ce contenu pour seulement {$this->getFormattedPrice()}",
            'subscription' => "Ce contenu est réservé aux abonnés premium",
            'plan_required' => "Ce contenu nécessite un abonnement premium",
            default => "Ce contenu est premium"
        };
    }
    
    /**
     * Vérifier si un plan est autorisé
     */
    public function isPlanAllowed(int $planId): bool
    {
        if (!$this->requiresPlan() || $this->requiredPlanIds === null) {
            return true;
        }
        
        return in_array($planId, $this->requiredPlanIds);
    }
    
    /**
     * Obtenir prévisualisation
     */
    public function getPreviewText(string $fullContent): string
    {
        if (!$this->previewEnabled) {
            return '';
        }
        
        // Extraire texte brut (sans HTML)
        $text = strip_tags($fullContent);
        
        if (mb_strlen($text) <= $this->previewLength) {
            return $text;
        }
        
        // Couper au dernier espace pour éviter de couper un mot
        $preview = mb_substr($text, 0, $this->previewLength);
        $lastSpace = mb_strrpos($preview, ' ');
        
        if ($lastSpace !== false) {
            $preview = mb_substr($preview, 0, $lastSpace);
        }
        
        return $preview . '...';
    }
    
    /**
     * Convertir en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
            'access_type' => $this->accessType,
            'price' => $this->price,
            'currency' => $this->currency,
            'required_plan_ids' => $this->requiredPlanIds ? json_encode($this->requiredPlanIds) : null,
            'preview_enabled' => $this->previewEnabled,
            'preview_length' => $this->previewLength,
            'custom_paywall_message' => $this->customPaywallMessage,
            'active' => $this->active,
        ];
    }
}
