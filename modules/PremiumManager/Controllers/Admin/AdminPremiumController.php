<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Admin;

use Framework\Services\Database;
use Framework\Services\Logger;
use Framework\Security\CSRF;
use Framework\Security\InputValidator;
use PremiumManager\Services\PaymentService;
use PremiumManager\Services\SubscriptionService;
use PremiumManager\Services\InvoiceService;
use PremiumManager\Services\AccessControlService;
use PremiumManager\Models\PremiumContent;

/**
 * Controller AdminPremiumController
 * 
 * Controller principal de l'administration Premium
 * Gestion des contenus premium, configuration, statistiques globales
 * 
 * SÉCURITÉ:
 * - Tous les endpoints requièrent authentification admin
 * - CSRF tokens sur toutes les actions POST
 * - Validation stricte des inputs
 * - Rate limiting sur actions sensibles
 * - Logs de toutes les modifications
 * 
 * @author Guillaume
 */
class AdminPremiumController
{
    private Database $db;
    private Logger $logger;
    private CSRF $csrf;
    private InputValidator $validator;
    private PaymentService $paymentService;
    private SubscriptionService $subscriptionService;
    private InvoiceService $invoiceService;
    private AccessControlService $accessControl;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logger = new Logger($db, []);
        $this->csrf = new CSRF();
        $this->validator = new InputValidator();
        
        $this->paymentService = new PaymentService($db, $this->logger);
        $this->subscriptionService = new SubscriptionService($db, $this->logger);
        $this->invoiceService = new InvoiceService($db, $this->logger);
        $this->accessControl = new AccessControlService($db, $this->logger);
    }
    
    /**
     * Liste des contenus premium
     * 
     * GET /admin/premium/content
     */
    public function index(): void
    {
        // Vérifier permissions admin
        $this->requireAdmin();
        
        // Filtres
        $contentType = $_GET['type'] ?? null;
        $accessType = $_GET['access'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Query avec filtres
        $where = ['active' => true];
        if ($contentType) {
            $where['content_type'] = $contentType;
        }
        if ($accessType) {
            $where['access_type'] = $accessType;
        }
        
        // Récupérer contenus
        $contents = $this->db->query(
            "SELECT pc.*, 
                    CASE 
                        WHEN pc.content_type = 'article' THEN a.title
                        WHEN pc.content_type = 'page' THEN p.title
                        WHEN pc.content_type = 'module' THEN m.name
                        ELSE 'Unknown'
                    END as content_title
             FROM premium_content pc
             LEFT JOIN articles a ON pc.content_type = 'article' AND pc.content_id = a.id
             LEFT JOIN pages p ON pc.content_type = 'page' AND pc.content_id = p.id
             LEFT JOIN modules m ON pc.content_type = 'module' AND pc.content_id = m.id
             WHERE " . $this->buildWhereClause($where) . "
             ORDER BY pc.created_at DESC
             LIMIT ? OFFSET ?",
            [...array_values($where), $perPage, $offset]
        );
        
        // Total pour pagination
        $total = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM premium_content WHERE " . $this->buildWhereClause($where),
            array_values($where)
        )['count'];
        
        // Stats rapides
        $stats = [
            'total_premium_content' => $total,
            'one_time_count' => $this->db->queryOne("SELECT COUNT(*) as c FROM premium_content WHERE access_type = 'one_time'")['c'],
            'subscription_count' => $this->db->queryOne("SELECT COUNT(*) as c FROM premium_content WHERE access_type = 'subscription'")['c'],
        ];
        
        // Rendre la vue
        include __DIR__ . '/../../Views/admin/content/index.php';
    }
    
    /**
     * Formulaire de création de contenu premium
     * 
     * GET /admin/premium/content/create
     */
    public function create(): void
    {
        $this->requireAdmin();
        
        // Générer CSRF token
        $csrfToken = $this->csrf->generate();
        
        // Liste des plans disponibles
        $plans = $this->db->query("SELECT * FROM premium_plans WHERE active = 1 ORDER BY price ASC");
        
        include __DIR__ . '/../../Views/admin/content/create.php';
    }
    
    /**
     * Enregistrer nouveau contenu premium
     * 
     * POST /admin/premium/content/store
     */
    public function store(): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on premium content creation", [
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        // Validation des données
        $errors = $this->validateContentData($_POST);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/premium/content/create');
            exit;
        }
        
        // Sanitize inputs
        $contentType = $this->validator->sanitize($_POST['content_type']);
        $contentId = (int)$_POST['content_id'];
        $accessType = $this->validator->sanitize($_POST['access_type']);
        $price = isset($_POST['price']) ? (float)$_POST['price'] : null;
        $currency = $this->validator->sanitize($_POST['currency'] ?? 'EUR');
        $requiredPlanIds = isset($_POST['required_plans']) && is_array($_POST['required_plans'])
            ? array_map('intval', $_POST['required_plans'])
            : null;
        $previewEnabled = isset($_POST['preview_enabled']);
        $previewLength = (int)($_POST['preview_length'] ?? 300);
        $customMessage = $this->validator->sanitize($_POST['custom_message'] ?? '');
        
        try {
            // Vérifier que le contenu n'est pas déjà premium
            $existing = $this->db->queryOne(
                "SELECT id FROM premium_content WHERE content_type = ? AND content_id = ?",
                [$contentType, $contentId]
            );
            
            if ($existing) {
                $_SESSION['error'] = "Ce contenu est déjà configuré comme premium";
                header('Location: /admin/premium/content/create');
                exit;
            }
            
            // Créer contenu premium
            $contentId = $this->db->insert('premium_content', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'access_type' => $accessType,
                'price' => $price,
                'currency' => $currency,
                'required_plan_ids' => $requiredPlanIds ? json_encode($requiredPlanIds) : null,
                'preview_enabled' => $previewEnabled,
                'preview_length' => $previewLength,
                'custom_paywall_message' => $customMessage ?: null,
                'active' => true,
            ]);
            
            $this->logger->security("Premium content created", [
                'content_id' => $contentId,
                'type' => $contentType,
                'access_type' => $accessType,
                'admin_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Contenu premium créé avec succès";
            header('Location: /admin/premium/content');
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error('Premium content creation failed', [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            
            $_SESSION['error'] = "Erreur lors de la création du contenu premium";
            header('Location: /admin/premium/content/create');
            exit;
        }
    }
    
    /**
     * Formulaire d'édition
     * 
     * GET /admin/premium/content/{id}/edit
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();
        
        // Récupérer contenu
        $content = $this->db->queryOne(
            "SELECT * FROM premium_content WHERE id = ?",
            [$id]
        );
        
        if (!$content) {
            http_response_code(404);
            die('Content not found');
        }
        
        $content = PremiumContent::fromArray($content);
        
        // CSRF token
        $csrfToken = $this->csrf->generate();
        
        // Plans disponibles
        $plans = $this->db->query("SELECT * FROM premium_plans WHERE active = 1 ORDER BY price ASC");
        
        include __DIR__ . '/../../Views/admin/content/edit.php';
    }
    
    /**
     * Mettre à jour contenu premium
     * 
     * POST /admin/premium/content/{id}/update
     */
    public function update(int $id): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on premium content update", [
                'content_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        // Validation
        $errors = $this->validateContentData($_POST);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: /admin/premium/content/{$id}/edit");
            exit;
        }
        
        try {
            // Sanitize inputs
            $accessType = $this->validator->sanitize($_POST['access_type']);
            $price = isset($_POST['price']) ? (float)$_POST['price'] : null;
            $currency = $this->validator->sanitize($_POST['currency'] ?? 'EUR');
            $requiredPlanIds = isset($_POST['required_plans']) && is_array($_POST['required_plans'])
                ? array_map('intval', $_POST['required_plans'])
                : null;
            $previewEnabled = isset($_POST['preview_enabled']);
            $previewLength = (int)($_POST['preview_length'] ?? 300);
            $customMessage = $this->validator->sanitize($_POST['custom_message'] ?? '');
            
            // Mettre à jour
            $this->db->update('premium_content', [
                'access_type' => $accessType,
                'price' => $price,
                'currency' => $currency,
                'required_plan_ids' => $requiredPlanIds ? json_encode($requiredPlanIds) : null,
                'preview_enabled' => $previewEnabled,
                'preview_length' => $previewLength,
                'custom_paywall_message' => $customMessage ?: null,
            ], ['id' => $id]);
            
            $this->logger->security("Premium content updated", [
                'content_id' => $id,
                'admin_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Contenu premium mis à jour";
            header('Location: /admin/premium/content');
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error('Premium content update failed', [
                'content_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de la mise à jour";
            header("Location: /admin/premium/content/{$id}/edit");
            exit;
        }
    }
    
    /**
     * Supprimer contenu premium
     * 
     * POST /admin/premium/content/{id}/delete
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on premium content deletion", [
                'content_id' => $id
            ]);
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        try {
            // Désactiver au lieu de supprimer (soft delete)
            $this->db->update('premium_content', [
                'active' => false
            ], ['id' => $id]);
            
            $this->logger->security("Premium content deleted", [
                'content_id' => $id,
                'admin_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Contenu premium supprimé";
            header('Location: /admin/premium/content');
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error('Premium content deletion failed', [
                'content_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de la suppression";
            header('Location: /admin/premium/content');
            exit;
        }
    }
    
    /**
     * Configuration générale du module
     * 
     * GET /admin/premium/settings
     */
    public function settings(): void
    {
        $this->requireAdmin();
        
        // Charger configuration actuelle
        $config = $this->getModuleConfig();
        
        // CSRF token
        $csrfToken = $this->csrf->generate();
        
        include __DIR__ . '/../../Views/admin/settings.php';
    }
    
    /**
     * Sauvegarder configuration
     * 
     * POST /admin/premium/settings/save
     */
    public function saveSettings(): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on settings update");
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        try {
            // Sanitize et valider
            $settings = [
                'currency' => $this->validator->sanitize($_POST['currency'] ?? 'EUR'),
                'trial_enabled' => isset($_POST['trial_enabled']),
                'trial_days' => (int)($_POST['trial_days'] ?? 14),
                'auto_invoice' => isset($_POST['auto_invoice']),
                'invoice_prefix' => $this->validator->sanitize($_POST['invoice_prefix'] ?? 'INV-'),
                'tax_rate' => (float)($_POST['tax_rate'] ?? 0.20),
                'stripe_enabled' => isset($_POST['stripe_enabled']),
                'paypal_enabled' => isset($_POST['paypal_enabled']),
            ];
            
            // Sauvegarder dans DB (table de configuration)
            foreach ($settings as $key => $value) {
                $this->db->query(
                    "INSERT INTO module_settings (module, setting_key, setting_value) 
                     VALUES ('PremiumManager', ?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = ?",
                    [$key, json_encode($value), json_encode($value)]
                );
            }
            
            $this->logger->security("Premium settings updated", [
                'admin_id' => $_SESSION['user_id'],
                'settings' => $settings
            ]);
            
            $_SESSION['success'] = "Configuration sauvegardée avec succès";
            header('Location: /admin/premium/settings');
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error('Settings save failed', [
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de la sauvegarde";
            header('Location: /admin/premium/settings');
            exit;
        }
    }
    
    /**
     * Statistiques globales (endpoint AJAX)
     * 
     * GET /admin/premium/api/stats
     */
    public function getStats(): void
    {
        $this->requireAdmin();
        
        header('Content-Type: application/json');
        
        try {
            $days = (int)($_GET['days'] ?? 30);
            
            $stats = [
                'revenue' => $this->paymentService->getRevenueStats($days),
                'subscriptions' => $this->subscriptionService->getStats(),
                'invoices' => $this->invoiceService->getInvoiceStats($days),
            ];
            
            echo json_encode($stats);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch stats']);
        }
    }
    
    /**
     * Validation des données de contenu premium
     */
    private function validateContentData(array $data): array
    {
        $errors = [];
        
        // Content type
        if (empty($data['content_type'])) {
            $errors['content_type'] = "Le type de contenu est requis";
        } elseif (!in_array($data['content_type'], ['article', 'page', 'module', 'forum_section', 'download'])) {
            $errors['content_type'] = "Type de contenu invalide";
        }
        
        // Content ID
        if (empty($data['content_id']) || !is_numeric($data['content_id'])) {
            $errors['content_id'] = "L'ID du contenu est requis";
        }
        
        // Access type
        if (empty($data['access_type'])) {
            $errors['access_type'] = "Le type d'accès est requis";
        } elseif (!in_array($data['access_type'], ['one_time', 'subscription', 'plan_required'])) {
            $errors['access_type'] = "Type d'accès invalide";
        }
        
        // Price pour one_time
        if ($data['access_type'] === 'one_time') {
            if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
                $errors['price'] = "Le prix est requis pour un achat one-time";
            }
        }
        
        // Required plans pour plan_required
        if ($data['access_type'] === 'plan_required') {
            if (empty($data['required_plans']) || !is_array($data['required_plans'])) {
                $errors['required_plans'] = "Au moins un plan doit être sélectionné";
            }
        }
        
        // Preview length
        if (isset($data['preview_length']) && (!is_numeric($data['preview_length']) || $data['preview_length'] < 0)) {
            $errors['preview_length'] = "La longueur de prévisualisation doit être un nombre positif";
        }
        
        return $errors;
    }
    
    /**
     * Charger configuration du module
     */
    private function getModuleConfig(): array
    {
        $settings = $this->db->query(
            "SELECT setting_key, setting_value FROM module_settings WHERE module = 'PremiumManager'"
        );
        
        $config = [];
        foreach ($settings as $setting) {
            $config[$setting['setting_key']] = json_decode($setting['setting_value'], true);
        }
        
        // Valeurs par défaut
        return array_merge([
            'currency' => 'EUR',
            'trial_enabled' => true,
            'trial_days' => 14,
            'auto_invoice' => true,
            'invoice_prefix' => 'INV-',
            'tax_rate' => 0.20,
            'stripe_enabled' => false,
            'paypal_enabled' => false,
        ], $config);
    }
    
    /**
     * Build WHERE clause from array
     */
    private function buildWhereClause(array $conditions): string
    {
        $clauses = [];
        foreach ($conditions as $key => $value) {
            $clauses[] = "$key = ?";
        }
        return implode(' AND ', $clauses);
    }
    
    /**
     * Vérifier que l'utilisateur est admin
     */
    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->logger->security("Unauthorized admin access attempt", [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            
            http_response_code(403);
            die('Access denied');
        }
    }
}
