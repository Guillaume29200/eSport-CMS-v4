<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Front;

use Framework\Services\Database;
use Framework\Security\CSRFProtection;
use Framework\Security\SessionManager;
use PremiumManager\Services\PaymentService;
use PremiumManager\Services\AccessControlService;

/**
 * Controller CheckoutController
 * 
 * Process de paiement côté utilisateur
 * Gestion checkout pour contenus premium et abonnements
 * 
 * @author Guillaume
 */
class CheckoutController
{
    private Database $db;
    private CSRFProtection $csrf;
    private SessionManager $session;
    private PaymentService $paymentService;
    private AccessControlService $accessControl;
    
    public function __construct(
        Database $db,
        CSRFProtection $csrf,
        SessionManager $session
    ) {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->session = $session;
        $this->paymentService = new PaymentService($db, new \Framework\Services\Logger($db, []));
        $this->accessControl = new AccessControlService($db);
    }
    
    /**
     * Afficher page de paiement
     */
    public function show(array $params): void
    {
        // Vérifier si utilisateur connecté
        if (!$this->session->isLoggedIn()) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        $type = $params['type']; // 'plan', 'article', 'page', 'module'
        $id = (int)$params['id'];
        $userId = $this->session->getUserId();
        
        // Vérifier si déjà accès
        if ($type !== 'plan') {
            if ($this->accessControl->hasAccess($userId, $type, $id)) {
                header('Location: /' . $type . '/' . $id);
                exit;
            }
        }
        
        // Récupérer infos selon type
        $itemData = $this->getItemData($type, $id);
        
        if (!$itemData) {
            http_response_code(404);
            echo "Contenu non trouvé";
            return;
        }
        
        // CSRF token
        $csrfToken = $this->csrf->getToken('checkout');
        
        // Stripe publishable key
        $stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY');
        
        include __DIR__ . '/../../Views/front/checkout.php';
    }
    
    /**
     * Process paiement
     */
    public function process(): void
    {
        // Valider CSRF
        $this->csrf->validateRequest('checkout');
        
        // Vérifier utilisateur connecté
        if (!$this->session->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $userId = $this->session->getUserId();
        $type = $_POST['type'];
        $itemId = (int)$_POST['item_id'];
        $provider = $_POST['provider'] ?? 'stripe';
        $paymentMethodId = $_POST['payment_method_id'] ?? null;
        
        try {
            // Récupérer infos item
            $itemData = $this->getItemData($type, $itemId);
            
            if (!$itemData) {
                throw new \Exception('Item non trouvé');
            }
            
            // Créer transaction
            $transactionId = $this->paymentService->createTransaction([
                'user_id' => $userId,
                'type' => $type === 'plan' ? 'subscription' : 'one_time',
                'amount' => $itemData['price'],
                'currency' => $itemData['currency'] ?? 'EUR',
                'provider' => $provider,
                'content_type' => $type !== 'plan' ? $type : null,
                'content_id' => $type !== 'plan' ? $itemId : null,
                'plan_id' => $type === 'plan' ? $itemId : null,
                'metadata' => [
                    'item_name' => $itemData['name'] ?? $itemData['title'],
                ]
            ]);
            
            // Process paiement selon type
            if ($type === 'plan') {
                // Abonnement
                $result = $this->processSubscription($userId, $itemId, $itemData, $transactionId);
            } else {
                // Achat unique
                $result = $this->processOneTimePayment($userId, $type, $itemId, $itemData, $transactionId);
            }
            
            if ($result['success']) {
                $this->paymentService->updateTransactionStatus($transactionId, 'completed', $result['transaction_id'] ?? null);
                
                // Débloquer accès si one-time
                if ($type !== 'plan') {
                    $this->accessControl->grantAccess($userId, $type, $itemId, 'one_time', $transactionId);
                }
                
                echo json_encode([
                    'success' => true,
                    'redirect' => $type === 'plan' 
                        ? '/member/premium/subscription' 
                        : '/' . $type . '/' . $itemId
                ]);
            } else {
                $this->paymentService->updateTransactionStatus($transactionId, 'failed');
                
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Paiement échoué'
                ]);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Process abonnement
     */
    private function processSubscription(int $userId, int $planId, array $planData, int $transactionId): array
    {
        $subscriptionService = new \PremiumManager\Services\SubscriptionService(
            $this->db,
            new \Framework\Services\Logger($this->db, [])
        );
        
        // Créer abonnement Stripe
        $stripeResult = $this->paymentService->createStripeSubscription([
            'user_id' => $userId,
            'plan_id' => $planId,
            'stripe_price_id' => $planData['stripe_price_id'],
            'trial_days' => $planData['trial_days'] ?? 0,
        ]);
        
        if (!$stripeResult['success']) {
            return $stripeResult;
        }
        
        // Créer abonnement en DB
        $subscriptionService->createSubscription([
            'user_id' => $userId,
            'plan_id' => $planId,
            'stripe_subscription_id' => $stripeResult['subscription_id'],
            'billing_period' => $planData['billing_period'],
            'trial_days' => $planData['trial_days'] ?? 0,
        ]);
        
        return [
            'success' => true,
            'transaction_id' => $stripeResult['subscription_id']
        ];
    }
    
    /**
     * Process paiement unique
     */
    private function processOneTimePayment(
        int $userId,
        string $type,
        int $itemId,
        array $itemData,
        int $transactionId
    ): array {
        // Process paiement Stripe
        return $this->paymentService->processStripePayment([
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'amount' => $itemData['price'],
            'currency' => $itemData['currency'] ?? 'EUR',
            'content_type' => $type,
            'content_id' => $itemId,
        ]);
    }
    
    /**
     * Récupérer données item
     */
    private function getItemData(string $type, int $id): ?array
    {
        return match($type) {
            'plan' => $this->db->queryOne("SELECT * FROM premium_plans WHERE id = ? AND active = 1", [$id]),
            'article' => $this->db->queryOne("
                SELECT a.title as name, pc.price, pc.currency
                FROM articles a
                JOIN premium_content pc ON pc.content_type = 'article' AND pc.content_id = a.id
                WHERE a.id = ?
            ", [$id]),
            'page' => $this->db->queryOne("
                SELECT p.title as name, pc.price, pc.currency
                FROM pages p
                JOIN premium_content pc ON pc.content_type = 'page' AND pc.content_id = p.id
                WHERE p.id = ?
            ", [$id]),
            'module' => $this->db->queryOne("
                SELECT m.name, pc.price, pc.currency
                FROM modules m
                JOIN premium_content pc ON pc.content_type = 'module' AND pc.content_id = m.id
                WHERE m.id = ?
            ", [$id]),
            default => null
        };
    }
}
