<?php
declare(strict_types=1);

namespace PremiumManager\Hooks;

use Framework\Services\Database;
use Framework\Security\SessionManager;
use PremiumManager\Services\AccessControlService;

/**
 * Hooks AccessHooks
 * 
 * Contrôle d'accès premium avant affichage contenu
 * Affiche paywall si nécessaire
 * 
 * @author Guillaume
 */
class AccessHooks
{
    /**
     * Vérifier accès premium avant affichage contenu
     */
    public static function checkPremiumAccess(array $context): mixed
    {
        $contentType = $context['type']; // 'article', 'page', 'module'
        $contentId = $context['id'];
        $content = $context['content'];
        
        $db = new Database(
            require __DIR__ . '/../../framework/config/database.php',
            require __DIR__ . '/../../framework/config/environment.php'
        );
        
        $session = new SessionManager(
            require __DIR__ . '/../../framework/config/security.php'
        );
        
        $accessControl = new AccessControlService($db);
        
        // Vérifier si contenu est premium
        $paywallInfo = $accessControl->getPaywallInfo($contentType, $contentId);
        
        if (!$paywallInfo) {
            // Pas premium, afficher normalement
            return $content;
        }
        
        // Vérifier si utilisateur a accès
        $userId = $session->getUserId();
        
        if (!$userId) {
            // Non connecté → afficher preview + paywall
            return self::renderPaywall($paywallInfo, $content, null);
        }
        
        $hasAccess = $accessControl->hasAccess($userId, $contentType, $contentId);
        
        if ($hasAccess) {
            // Accès OK, afficher contenu complet
            return $content;
        }
        
        // Pas d'accès → afficher preview + paywall
        return self::renderPaywall($paywallInfo, $content, $userId);
    }
    
    /**
     * Rendre paywall
     */
    private static function renderPaywall(array $paywallInfo, string $content, ?int $userId): string
    {
        $db = new Database(
            require __DIR__ . '/../../framework/config/database.php',
            require __DIR__ . '/../../framework/config/environment.php'
        );
        
        $accessControl = new AccessControlService($db);
        
        // Obtenir preview
        $preview = null;
        if ($paywallInfo['preview_enabled']) {
            $previewData = $accessControl->getPreviewContent(
                $content,
                $paywallInfo['preview_length']
            );
            $preview = $previewData['preview'];
        }
        
        // Récupérer plans disponibles
        $plans = $db->query("
            SELECT * FROM premium_plans 
            WHERE active = 1 
            ORDER BY price ASC
        ");
        
        ob_start();
        include __DIR__ . '/../Views/front/paywall.php';
        return ob_get_clean();
    }
}
