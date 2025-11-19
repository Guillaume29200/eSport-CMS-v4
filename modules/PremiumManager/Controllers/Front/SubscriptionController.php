<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Front;

use Framework\Services\Database;
use Framework\Security\SessionManager;
use PremiumManager\Services\SubscriptionService;
use PremiumManager\Services\AccessControlService;

/**
 * Controller SubscriptionController
 * 
 * Gestion abonnement depuis espace membre
 * Affichage, upgrade, annulation
 * 
 * @author Guillaume
 */
class SubscriptionController
{
    private Database $db;
    private SessionManager $session;
    private SubscriptionService $subscriptionService;
    private AccessControlService $accessControl;
    
    public function __construct(Database $db, SessionManager $session)
    {
        $this->db = $db;
        $this->session = $session;
        $this->subscriptionService = new SubscriptionService($db, new \Framework\Services\Logger($db, []));
        $this->accessControl = new AccessControlService($db);
    }
    
    /**
     * Afficher abonnement actuel
     */
    public function index(): void
    {
        // Vérifier authentification
        if (!$this->session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        $userId = $this->session->getUserId();
        
        // Récupérer abonnement actif
        $subscription = $this->subscriptionService->getUserSubscription($userId);
        
        // Récupérer tous les plans (pour upgrade)
        $allPlans = $this->db->query("
            SELECT * FROM premium_plans 
            WHERE active = 1 
            ORDER BY price ASC
        ");
        
        // Récupérer accès débloqués
        $unlockedContent = $this->accessControl->getUserAccess($userId);
        
        // Récupérer historique transactions
        $transactions = $this->db->query("
            SELECT * FROM premium_transactions
            WHERE user_id = ?
              AND status = 'completed'
            ORDER BY created_at DESC
            LIMIT 10
        ", [$userId]);
        
        include __DIR__ . '/../../Views/front/subscription.php';
    }
    
    /**
     * Upgrade/Downgrade plan
     */
    public function upgrade(): void
    {
        if (!$this->session->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $userId = $this->session->getUserId();
        $newPlanId = (int)$_POST['plan_id'];
        
        try {
            // Récupérer abonnement actuel
            $subscription = $this->subscriptionService->getUserSubscription($userId);
            
            if (!$subscription) {
                throw new \Exception('Aucun abonnement actif');
            }
            
            // Changer plan
            $success = $this->subscriptionService->changePlan(
                $subscription['id'],
                $newPlanId
            );
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Plan mis à jour avec succès'
                ]);
            } else {
                throw new \Exception('Échec du changement de plan');
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Annuler abonnement
     */
    public function cancel(): void
    {
        if (!$this->session->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $userId = $this->session->getUserId();
        $immediately = isset($_POST['immediately']) && $_POST['immediately'] === '1';
        
        try {
            // Récupérer abonnement actuel
            $subscription = $this->subscriptionService->getUserSubscription($userId);
            
            if (!$subscription) {
                throw new \Exception('Aucun abonnement actif');
            }
            
            // Annuler
            $success = $this->subscriptionService->cancelSubscription(
                $subscription['id'],
                $immediately
            );
            
            if ($success) {
                $message = $immediately 
                    ? 'Abonnement annulé immédiatement'
                    : 'Abonnement annulé. Actif jusqu\'à la fin de la période';
                
                echo json_encode([
                    'success' => true,
                    'message' => $message
                ]);
            } else {
                throw new \Exception('Échec de l\'annulation');
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
