<?php
declare(strict_types=1);

namespace PremiumManager\Services;

use Framework\Services\Database;

/**
 * Service AccessControlService
 * 
 * Contrôle d'accès aux contenus premium
 * Vérifie si utilisateur a accès à un contenu payant
 * 
 * @author Guillaume
 */
class AccessControlService
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    /**
     * Vérifier si utilisateur a accès à un contenu
     */
    public function hasAccess(int $userId, string $contentType, int $contentId): bool
    {
        // Vérifier si contenu est premium
        $premiumContent = $this->db->queryOne(
            "SELECT * FROM premium_content 
             WHERE content_type = ? AND content_id = ? AND active = 1",
            [$contentType, $contentId]
        );
        
        // Si pas premium, accès libre
        if (!$premiumContent) {
            return true;
        }
        
        // Vérifier selon type d'accès
        return match($premiumContent['access_type']) {
            'one_time' => $this->hasOneTimeAccess($userId, $contentType, $contentId),
            'subscription' => $this->hasActiveSubscription($userId),
            'plan_required' => $this->hasRequiredPlan($userId, $premiumContent['required_plan_ids']),
            default => false
        };
    }
    
    /**
     * Vérifier accès one-time (achat unique)
     */
    private function hasOneTimeAccess(int $userId, string $contentType, int $contentId): bool
    {
        $access = $this->db->queryOne(
            "SELECT * FROM user_premium_access
             WHERE user_id = ? 
               AND content_type = ? 
               AND content_id = ?
               AND (expires_at IS NULL OR expires_at > NOW())",
            [$userId, $contentType, $contentId]
        );
        
        return $access !== null;
    }
    
    /**
     * Vérifier abonnement actif
     */
    private function hasActiveSubscription(int $userId): bool
    {
        $subscription = $this->db->queryOne(
            "SELECT * FROM user_subscriptions
             WHERE user_id = ?
               AND status IN ('active', 'trialing')
               AND current_period_end > NOW()",
            [$userId]
        );
        
        return $subscription !== null;
    }
    
    /**
     * Vérifier plan requis
     */
    private function hasRequiredPlan(int $userId, ?string $requiredPlanIds): bool
    {
        if (!$requiredPlanIds) {
            return $this->hasActiveSubscription($userId);
        }
        
        $planIds = json_decode($requiredPlanIds, true);
        
        if (empty($planIds)) {
            return $this->hasActiveSubscription($userId);
        }
        
        $placeholders = implode(',', array_fill(0, count($planIds), '?'));
        
        $subscription = $this->db->queryOne(
            "SELECT * FROM user_subscriptions
             WHERE user_id = ?
               AND plan_id IN ({$placeholders})
               AND status IN ('active', 'trialing')
               AND current_period_end > NOW()",
            array_merge([$userId], $planIds)
        );
        
        return $subscription !== null;
    }
    
    /**
     * Débloquer accès (après paiement)
     */
    public function grantAccess(
        int $userId,
        string $contentType,
        int $contentId,
        string $accessMethod = 'one_time',
        ?int $transactionId = null,
        ?string $expiresAt = null
    ): void {
        $data = [
            'user_id' => $userId,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'access_method' => $accessMethod,
            'transaction_id' => $transactionId,
            'expires_at' => $expiresAt,
        ];
        
        // Vérifier si existe déjà
        $existing = $this->db->queryOne(
            "SELECT id FROM user_premium_access 
             WHERE user_id = ? AND content_type = ? AND content_id = ?",
            [$userId, $contentType, $contentId]
        );
        
        if ($existing) {
            $this->db->update(
                'user_premium_access',
                $data,
                ['id' => $existing['id']]
            );
        } else {
            $this->db->insert('user_premium_access', $data);
        }
    }
    
    /**
     * Révoquer accès
     */
    public function revokeAccess(int $userId, string $contentType, int $contentId): void
    {
        $this->db->delete('user_premium_access', [
            'user_id' => $userId,
            'content_type' => $contentType,
            'content_id' => $contentId
        ]);
    }
    
    /**
     * Obtenir contenu preview (aperçu gratuit)
     */
    public function getPreviewContent(string $content, int $previewLength = 300): array
    {
        $cleanContent = strip_tags($content);
        
        if (mb_strlen($cleanContent) <= $previewLength) {
            return [
                'preview' => $content,
                'has_more' => false
            ];
        }
        
        $preview = mb_substr($cleanContent, 0, $previewLength);
        $preview = mb_substr($preview, 0, mb_strrpos($preview, ' ')) . '...';
        
        return [
            'preview' => $preview,
            'has_more' => true
        ];
    }
    
    /**
     * Obtenir info paywall pour un contenu
     */
    public function getPaywallInfo(string $contentType, int $contentId): ?array
    {
        return $this->db->queryOne(
            "SELECT pc.*, 
                    (SELECT COUNT(*) FROM user_premium_access 
                     WHERE content_type = pc.content_type AND content_id = pc.content_id) as unlock_count
             FROM premium_content pc
             WHERE pc.content_type = ? AND pc.content_id = ? AND pc.active = 1",
            [$contentType, $contentId]
        );
    }
    
    /**
     * Obtenir tous les accès d'un utilisateur
     */
    public function getUserAccess(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM user_premium_access 
             WHERE user_id = ?
               AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY unlocked_at DESC",
            [$userId]
        );
    }
}
