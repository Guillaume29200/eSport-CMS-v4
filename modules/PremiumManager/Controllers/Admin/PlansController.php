<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Admin;

use Framework\Services\Database;
use Framework\Security\CSRFProtection;
use PremiumManager\Models\PremiumPlan;

/**
 * Controller PlansController
 * 
 * Gestion des plans d'abonnement premium (CRUD)
 * 
 * @author Guillaume
 */
class PlansController
{
    private Database $db;
    private CSRFProtection $csrf;
    
    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->db = $db;
        $this->csrf = $csrf;
    }
    
    /**
     * Liste des plans
     */
    public function index(): void
    {
        $plans = $this->db->query("
            SELECT p.*,
                   (SELECT COUNT(*) FROM user_subscriptions 
                    WHERE plan_id = p.id AND status IN ('active', 'trialing')) as subscribers_count
            FROM premium_plans p
            ORDER BY p.sort_order ASC, p.price ASC
        ");
        
        $plansObjects = array_map(fn($p) => PremiumPlan::fromArray($p), $plans);
        
        include __DIR__ . '/../../Views/admin/plans/index.php';
    }
    
    /**
     * Formulaire création
     */
    public function create(): void
    {
        $csrfToken = $this->csrf->getToken('create_plan');
        
        include __DIR__ . '/../../Views/admin/plans/create.php';
    }
    
    /**
     * Enregistrer nouveau plan
     */
    public function store(): void
    {
        // Valider CSRF
        $this->csrf->validateRequest('create_plan');
        
        // Valider données
        $data = $this->validatePlanData($_POST);
        
        // Créer plan
        $planId = $this->db->insert('premium_plans', [
            'name' => $data['name'],
            'slug' => $this->generateSlug($data['name']),
            'description' => $data['description'],
            'price' => $data['price'],
            'currency' => $data['currency'],
            'billing_period' => $data['billing_period'],
            'trial_days' => $data['trial_days'],
            'features' => json_encode($data['features']),
            'max_articles' => $data['max_articles'] ?: null,
            'max_pages' => $data['max_pages'] ?: null,
            'max_modules' => $data['max_modules'] ?: null,
            'active' => $data['active'],
            'sort_order' => $data['sort_order'],
        ]);
        
        // TODO: Créer plan chez Stripe si activé
        
        // Rediriger
        header('Location: /admin/premium/plans?success=created');
        exit;
    }
    
    /**
     * Formulaire édition
     */
    public function edit(array $params): void
    {
        $planId = (int)$params['id'];
        
        $planData = $this->db->queryOne(
            "SELECT * FROM premium_plans WHERE id = ?",
            [$planId]
        );
        
        if (!$planData) {
            http_response_code(404);
            echo "Plan non trouvé";
            return;
        }
        
        $plan = PremiumPlan::fromArray($planData);
        $csrfToken = $this->csrf->getToken('edit_plan');
        
        include __DIR__ . '/../../Views/admin/plans/edit.php';
    }
    
    /**
     * Mettre à jour plan
     */
    public function update(array $params): void
    {
        $planId = (int)$params['id'];
        
        // Valider CSRF
        $this->csrf->validateRequest('edit_plan');
        
        // Valider données
        $data = $this->validatePlanData($_POST);
        
        // Mettre à jour
        $this->db->update('premium_plans', [
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'currency' => $data['currency'],
            'billing_period' => $data['billing_period'],
            'trial_days' => $data['trial_days'],
            'features' => json_encode($data['features']),
            'max_articles' => $data['max_articles'] ?: null,
            'max_pages' => $data['max_pages'] ?: null,
            'max_modules' => $data['max_modules'] ?: null,
            'active' => $data['active'],
            'sort_order' => $data['sort_order'],
        ], ['id' => $planId]);
        
        // Rediriger
        header('Location: /admin/premium/plans?success=updated');
        exit;
    }
    
    /**
     * Supprimer plan
     */
    public function delete(array $params): void
    {
        $planId = (int)$params['id'];
        
        // Valider CSRF
        $this->csrf->validateRequest('delete_plan');
        
        // Vérifier si plan a des abonnés actifs
        $subscribersCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM user_subscriptions 
             WHERE plan_id = ? AND status IN ('active', 'trialing')",
            [$planId]
        )['count'];
        
        if ($subscribersCount > 0) {
            header('Location: /admin/premium/plans?error=has_subscribers');
            exit;
        }
        
        // Supprimer (ou désactiver)
        $this->db->update('premium_plans', ['active' => false], ['id' => $planId]);
        
        // Rediriger
        header('Location: /admin/premium/plans?success=deleted');
        exit;
    }
    
    /**
     * Valider données plan
     */
    private function validatePlanData(array $data): array
    {
        $errors = [];
        
        // Nom requis
        if (empty($data['name'])) {
            $errors[] = 'Le nom est requis';
        }
        
        // Prix requis et positif
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors[] = 'Le prix doit être positif';
        }
        
        // Features (textarea → array)
        $features = [];
        if (!empty($data['features'])) {
            $features = array_filter(
                array_map('trim', explode("\n", $data['features']))
            );
        }
        
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
        
        return [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'currency' => $data['currency'] ?? 'EUR',
            'billing_period' => $data['billing_period'] ?? 'monthly',
            'trial_days' => (int)($data['trial_days'] ?? 0),
            'features' => $features,
            'max_articles' => !empty($data['max_articles']) ? (int)$data['max_articles'] : null,
            'max_pages' => !empty($data['max_pages']) ? (int)$data['max_pages'] : null,
            'max_modules' => !empty($data['max_modules']) ? (int)$data['max_modules'] : null,
            'active' => isset($data['active']),
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ];
    }
    
    /**
     * Générer slug depuis nom
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        return trim($slug, '-');
    }
}
