<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Admin;

use Framework\Services\Database;
use Framework\Services\Logger;
use Framework\Security\CSRF;
use Framework\Security\InputValidator;

/**
 * Controller SettingsController (Admin)
 * 
 * Configuration du module Premium
 * - Paramètres généraux
 * - Configuration Stripe/PayPal
 * - Options de facturation
 * - Templates emails
 * 
 * @author Guillaume
 */
class SettingsController
{
    private Database $db;
    private Logger $logger;
    private CSRF $csrf;
    private InputValidator $validator;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logger = new Logger($db, []);
        $this->csrf = new CSRF();
        $this->validator = new InputValidator();
    }
    
    /**
     * Afficher configuration
     * 
     * GET /admin/premium/settings
     */
    public function index(): void
    {
        $this->requireAdmin();
        
        // Charger configuration actuelle
        $settings = $this->getSettings();
        
        // CSRF token
        $csrfToken = $this->csrf->generate();
        
        // Liste des devises supportées
        $currencies = ['EUR', 'USD', 'GBP', 'CAD', 'CHF'];
        
        // Test connexion Stripe
        $stripeStatus = $this->testStripeConnection();
        
        include __DIR__ . '/../../Views/admin/settings.php';
    }
    
    /**
     * Sauvegarder configuration
     * 
     * POST /admin/premium/settings/save
     */
    public function save(): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on settings save", [
                'admin_id' => $_SESSION['user_id']
            ]);
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        try {
            $section = $_POST['section'] ?? 'general';
            
            switch ($section) {
                case 'general':
                    $this->saveGeneralSettings($_POST);
                    break;
                    
                case 'stripe':
                    $this->saveStripeSettings($_POST);
                    break;
                    
                case 'paypal':
                    $this->savePayPalSettings($_POST);
                    break;
                    
                case 'invoicing':
                    $this->saveInvoicingSettings($_POST);
                    break;
                    
                case 'emails':
                    $this->saveEmailSettings($_POST);
                    break;
                    
                default:
                    $_SESSION['error'] = "Section inconnue";
                    header('Location: /admin/premium/settings');
                    exit;
            }
            
            $this->logger->security("Premium settings updated", [
                'section' => $section,
                'admin_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Configuration sauvegardée avec succès";
            
        } catch (\Exception $e) {
            $this->logger->error('Settings save failed', [
                'error' => $e->getMessage(),
                'section' => $section ?? 'unknown'
            ]);
            
            $_SESSION['error'] = "Erreur lors de la sauvegarde";
        }
        
        header('Location: /admin/premium/settings#' . ($section ?? 'general'));
        exit;
    }
    
    /**
     * Sauvegarder paramètres généraux
     */
    private function saveGeneralSettings(array $data): void
    {
        $settings = [
            'currency' => $this->validator->sanitize($data['currency'] ?? 'EUR'),
            'trial_enabled' => isset($data['trial_enabled']),
            'trial_days' => (int)($data['trial_days'] ?? 14),
            'auto_invoice' => isset($data['auto_invoice']),
            'allow_downgrades' => isset($data['allow_downgrades']),
            'allow_upgrades' => isset($data['allow_upgrades']),
            'prorate_upgrades' => isset($data['prorate_upgrades']),
        ];
        
        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }
    }
    
    /**
     * Sauvegarder paramètres Stripe
     */
    private function saveStripeSettings(array $data): void
    {
        $settings = [
            'stripe_enabled' => isset($data['stripe_enabled']),
            'stripe_public_key' => $this->validator->sanitize($data['stripe_public_key'] ?? ''),
            'stripe_secret_key' => $this->validator->sanitize($data['stripe_secret_key'] ?? ''),
            'stripe_webhook_secret' => $this->validator->sanitize($data['stripe_webhook_secret'] ?? ''),
            'stripe_test_mode' => isset($data['stripe_test_mode']),
        ];
        
        // Valider clés Stripe
        if ($settings['stripe_enabled']) {
            if (empty($settings['stripe_public_key']) || empty($settings['stripe_secret_key'])) {
                throw new \Exception("Les clés Stripe sont requises");
            }
            
            if (!str_starts_with($settings['stripe_public_key'], 'pk_')) {
                throw new \Exception("Clé publique Stripe invalide");
            }
            
            if (!str_starts_with($settings['stripe_secret_key'], 'sk_')) {
                throw new \Exception("Clé secrète Stripe invalide");
            }
        }
        
        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }
    }
    
    /**
     * Sauvegarder paramètres PayPal
     */
    private function savePayPalSettings(array $data): void
    {
        $settings = [
            'paypal_enabled' => isset($data['paypal_enabled']),
            'paypal_client_id' => $this->validator->sanitize($data['paypal_client_id'] ?? ''),
            'paypal_client_secret' => $this->validator->sanitize($data['paypal_client_secret'] ?? ''),
            'paypal_sandbox_mode' => isset($data['paypal_sandbox_mode']),
        ];
        
        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }
    }
    
    /**
     * Sauvegarder paramètres facturation
     */
    private function saveInvoicingSettings(array $data): void
    {
        $settings = [
            'invoice_prefix' => $this->validator->sanitize($data['invoice_prefix'] ?? 'INV-'),
            'tax_rate' => (float)($data['tax_rate'] ?? 0.20),
            'tax_label' => $this->validator->sanitize($data['tax_label'] ?? 'TVA'),
            'company_name' => $this->validator->sanitize($data['company_name'] ?? ''),
            'company_address' => $this->validator->sanitize($data['company_address'] ?? ''),
            'company_vat' => $this->validator->sanitize($data['company_vat'] ?? ''),
            'invoice_footer' => $this->validator->sanitize($data['invoice_footer'] ?? ''),
        ];
        
        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }
    }
    
    /**
     * Sauvegarder paramètres emails
     */
    private function saveEmailSettings(array $data): void
    {
        $settings = [
            'email_payment_success' => isset($data['email_payment_success']),
            'email_payment_failed' => isset($data['email_payment_failed']),
            'email_subscription_created' => isset($data['email_subscription_created']),
            'email_subscription_cancelled' => isset($data['email_subscription_cancelled']),
            'email_trial_ending' => isset($data['email_trial_ending']),
            'email_trial_ending_days' => (int)($data['email_trial_ending_days'] ?? 3),
            'email_payment_reminder' => isset($data['email_payment_reminder']),
        ];
        
        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }
    }
    
    /**
     * Sauvegarder un paramètre
     */
    private function saveSetting(string $key, mixed $value): void
    {
        $this->db->query("
            INSERT INTO module_settings (module, setting_key, setting_value)
            VALUES ('PremiumManager', ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ", [$key, json_encode($value), json_encode($value)]);
    }
    
    /**
     * Charger tous les paramètres
     */
    private function getSettings(): array
    {
        $settings = $this->db->query("
            SELECT setting_key, setting_value
            FROM module_settings
            WHERE module = 'PremiumManager'
        ");
        
        $config = [];
        foreach ($settings as $setting) {
            $config[$setting['setting_key']] = json_decode($setting['setting_value'], true);
        }
        
        // Valeurs par défaut
        return array_merge([
            // General
            'currency' => 'EUR',
            'trial_enabled' => true,
            'trial_days' => 14,
            'auto_invoice' => true,
            'allow_downgrades' => true,
            'allow_upgrades' => true,
            'prorate_upgrades' => true,
            
            // Stripe
            'stripe_enabled' => false,
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'stripe_webhook_secret' => '',
            'stripe_test_mode' => true,
            
            // PayPal
            'paypal_enabled' => false,
            'paypal_client_id' => '',
            'paypal_client_secret' => '',
            'paypal_sandbox_mode' => true,
            
            // Invoicing
            'invoice_prefix' => 'INV-',
            'tax_rate' => 0.20,
            'tax_label' => 'TVA',
            'company_name' => '',
            'company_address' => '',
            'company_vat' => '',
            'invoice_footer' => '',
            
            // Emails
            'email_payment_success' => true,
            'email_payment_failed' => true,
            'email_subscription_created' => true,
            'email_subscription_cancelled' => true,
            'email_trial_ending' => true,
            'email_trial_ending_days' => 3,
            'email_payment_reminder' => true,
        ], $config);
    }
    
    /**
     * Tester connexion Stripe
     */
    private function testStripeConnection(): array
    {
        $settings = $this->getSettings();
        
        if (!$settings['stripe_enabled'] || empty($settings['stripe_secret_key'])) {
            return ['status' => 'disabled', 'message' => 'Stripe non configuré'];
        }
        
        try {
            \Stripe\Stripe::setApiKey($settings['stripe_secret_key']);
            $account = \Stripe\Account::retrieve();
            
            return [
                'status' => 'success',
                'message' => 'Connexion OK',
                'account_id' => $account->id,
                'business_name' => $account->business_profile->name ?? 'N/A',
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Vérifier permissions admin
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
