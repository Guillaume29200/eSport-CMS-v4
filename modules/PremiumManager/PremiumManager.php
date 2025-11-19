<?php
declare(strict_types=1);

namespace PremiumManager;

use Framework\Interfaces\BaseModule;
use Framework\Services\Router;

/**
 * Module PremiumManager
 * 
 * Système complet de gestion premium et paiements
 * 
 * Features:
 * - Gestion plans d'abonnement
 * - Paiements one-time et récurrents
 * - Contrôle d'accès granulaire (articles, pages, modules)
 * - Multi-providers (Stripe, PayPal, etc.)
 * - Coupons de réduction
 * - Facturation automatique
 * - Webhooks temps réel
 * - Dashboard revenus
 * 
 * @author Guillaume
 * @version 1.0.0
 * @license Propriétaire
 */
class PremiumManager extends BaseModule
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'PremiumManager';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Système de gestion premium avec paiements - Contrôle d\'accès payant pour contenus';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return 'Guillaume - eSport-CMS';
    }
    
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        // Charger configuration
        $this->loadConfig();
        
        // Initialiser providers de paiement
        $this->initPaymentProviders();
    }
    
    /**
     * {@inheritdoc}
     */
    public function registerRoutes($router): void
    {
        // Charger le fichier routes.php du module
        $routesFile = __DIR__ . '/routes.php';
        
        if (file_exists($routesFile)) {
            $registerRoutes = require $routesFile;
            $registerRoutes($router);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHooks(): array
    {
        return [
            // Ajouter menu admin
            'admin.menu' => [
                ['\PremiumManager\Hooks\AdminHooks', 'addAdminMenu'],
                10
            ],
            
            // Widget dashboard admin
            'admin.dashboard.widgets' => [
                ['\PremiumManager\Hooks\AdminHooks', 'addDashboardWidget'],
                10
            ],
            
            // Contrôle d'accès avant affichage contenu
            'content.before_display' => [
                ['\PremiumManager\Hooks\AccessHooks', 'checkPremiumAccess'],
                5 // Priorité haute
            ],
            
            // Onglet dans profil membre
            'user.profile.tabs' => [
                ['\PremiumManager\Hooks\UserHooks', 'addProfileTab'],
                10
            ],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        try {
            // Créer tables
            $this->createTables();
            
            // Créer plans par défaut
            $this->createDefaultPlans();
            
            // Créer configuration
            $this->createDefaultConfig();
            
            return true;
            
        } catch (\Exception $e) {
            error_log("PremiumManager install error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        try {
            // ATTENTION: Suppression des données !
            // En production, préférer une désactivation
            
            // Supprimer tables (commenté par sécurité)
            // $this->dropTables();
            
            return true;
            
        } catch (\Exception $e) {
            error_log("PremiumManager uninstall error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charger configuration du module
     */
    private function loadConfig(): void
    {
        // Configuration chargée depuis module.json et DB
    }
    
    /**
     * Initialiser providers de paiement
     */
    private function initPaymentProviders(): void
    {
        // Stripe
        if (getenv('STRIPE_SECRET_KEY')) {
            \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
        }
        
        // PayPal
        // Initialisation PayPal SDK si nécessaire
    }
    
    /**
     * Créer tables SQL
     */
    private function createTables(): void
    {
        $sql = file_get_contents(__DIR__ . '/install/schema.sql');
        
        // Exécuter via Database service
        // $db->exec($sql);
    }
    
    /**
     * Créer plans par défaut
     */
    private function createDefaultPlans(): void
    {
        $plans = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'price' => 4.99,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    '5 articles premium',
                    '2 modules premium',
                    'Support email'
                ]),
                'active' => true
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'price' => 9.99,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    '20 articles premium',
                    '10 modules premium',
                    'Support prioritaire',
                    'Badge membre Silver'
                ]),
                'active' => true
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'price' => 19.99,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'Accès illimité',
                    'Tous les modules',
                    'Support VIP 24/7',
                    'Badge Gold',
                    'Early access nouvelles features'
                ]),
                'active' => true
            ],
        ];
        
        // Insérer via Database service
        // foreach ($plans as $plan) { $db->insert('premium_plans', $plan); }
    }
    
    /**
     * Créer configuration par défaut
     */
    private function createDefaultConfig(): void
    {
        $config = [
            'currency' => 'EUR',
            'trial_enabled' => true,
            'trial_days' => 14,
            'auto_invoice' => true,
            'invoice_prefix' => 'INV-',
        ];
        
        // Sauvegarder en DB
    }
}
