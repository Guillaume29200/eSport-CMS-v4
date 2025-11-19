<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Admin;

use Framework\Services\Database;
use Framework\Services\Logger;
use Framework\Security\CSRF;
use Framework\Security\InputValidator;

/**
 * Controller CouponsController (Admin)
 * 
 * Gestion des coupons de réduction
 * - Création/édition/suppression
 * - Activation/désactivation
 * - Statistiques d'utilisation
 * 
 * @author Guillaume
 */
class CouponsController
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
     * Liste des coupons
     * 
     * GET /admin/premium/coupons
     */
    public function index(): void
    {
        $this->requireAdmin();
        
        // Filtres
        $active = $_GET['active'] ?? null;
        $type = $_GET['type'] ?? null;
        
        $where = [];
        $params = [];
        
        if ($active !== null) {
            $where[] = "active = ?";
            $params[] = (int)$active;
        }
        
        if ($type) {
            $where[] = "type = ?";
            $params[] = $type;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Récupérer coupons
        $coupons = $this->db->query("
            SELECT c.*,
                   u.username as created_by_username,
                   (SELECT COUNT(*) FROM premium_coupon_usage WHERE coupon_id = c.id) as usage_count
            FROM premium_coupons c
            LEFT JOIN users u ON c.created_by = u.id
            {$whereClause}
            ORDER BY c.created_at DESC
        ", $params);
        
        // CSRF token
        $csrfToken = $this->csrf->generate();
        
        include __DIR__ . '/../../Views/admin/coupons/index.php';
    }
    
    /**
     * Créer un coupon
     * 
     * POST /admin/premium/coupons/create
     */
    public function store(): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $this->logger->security("CSRF validation failed on coupon creation", [
                'admin_id' => $_SESSION['user_id']
            ]);
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        // Validation
        $errors = $this->validateCouponData($_POST);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/premium/coupons');
            exit;
        }
        
        try {
            // Sanitize
            $code = strtoupper($this->validator->sanitize($_POST['code']));
            $type = $this->validator->sanitize($_POST['type']);
            $value = (float)$_POST['value'];
            $currency = $this->validator->sanitize($_POST['currency'] ?? 'EUR');
            $maxUses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
            $validFrom = !empty($_POST['valid_from']) ? $_POST['valid_from'] : null;
            $validUntil = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
            $applicablePlans = !empty($_POST['applicable_plans']) 
                ? json_encode(array_map('intval', $_POST['applicable_plans']))
                : null;
            $firstPaymentOnly = isset($_POST['first_payment_only']);
            
            // Vérifier unicité du code
            $existing = $this->db->queryOne("SELECT id FROM premium_coupons WHERE code = ?", [$code]);
            if ($existing) {
                $_SESSION['error'] = "Ce code de coupon existe déjà";
                header('Location: /admin/premium/coupons');
                exit;
            }
            
            // Créer coupon
            $couponId = $this->db->insert('premium_coupons', [
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'currency' => $currency,
                'max_uses' => $maxUses,
                'used_count' => 0,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'applicable_plans' => $applicablePlans,
                'first_payment_only' => $firstPaymentOnly,
                'active' => true,
                'created_by' => $_SESSION['user_id'],
            ]);
            
            $this->logger->security("Coupon created", [
                'coupon_id' => $couponId,
                'code' => $code,
                'admin_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Coupon créé avec succès";
            
        } catch (\Exception $e) {
            $this->logger->error('Coupon creation failed', [
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de la création du coupon";
        }
        
        header('Location: /admin/premium/coupons');
        exit;
    }
    
    /**
     * Activer/désactiver un coupon
     * 
     * POST /admin/premium/coupons/{id}/toggle
     */
    public function toggle(int $id): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        try {
            $coupon = $this->db->queryOne("SELECT active FROM premium_coupons WHERE id = ?", [$id]);
            
            if (!$coupon) {
                $_SESSION['error'] = "Coupon introuvable";
                header('Location: /admin/premium/coupons');
                exit;
            }
            
            $newStatus = !$coupon['active'];
            
            $this->db->update('premium_coupons', [
                'active' => $newStatus
            ], ['id' => $id]);
            
            $this->logger->security("Coupon toggled", [
                'coupon_id' => $id,
                'new_status' => $newStatus,
                'admin_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = $newStatus ? "Coupon activé" : "Coupon désactivé";
            
        } catch (\Exception $e) {
            $this->logger->error('Coupon toggle failed', [
                'coupon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de la modification";
        }
        
        header('Location: /admin/premium/coupons');
        exit;
    }
    
    /**
     * Supprimer un coupon
     * 
     * POST /admin/premium/coupons/{id}/delete
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        
        // Vérifier CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        try {
            // Vérifier si coupon a été utilisé
            $used = $this->db->queryOne("
                SELECT COUNT(*) as count FROM premium_coupon_usage WHERE coupon_id = ?
            ", [$id])['count'];
            
            if ($used > 0) {
                // Ne pas supprimer, juste désactiver
                $this->db->update('premium_coupons', [
                    'active' => false
                ], ['id' => $id]);
                
                $_SESSION['info'] = "Coupon désactivé (impossible de supprimer car déjà utilisé)";
            } else {
                // Supprimer
                $this->db->delete('premium_coupons', ['id' => $id]);
                
                $_SESSION['success'] = "Coupon supprimé";
            }
            
            $this->logger->security("Coupon deleted/deactivated", [
                'coupon_id' => $id,
                'admin_id' => $_SESSION['user_id']
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Coupon deletion failed', [
                'coupon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['error'] = "Erreur lors de la suppression";
        }
        
        header('Location: /admin/premium/coupons');
        exit;
    }
    
    /**
     * Validation des données de coupon
     */
    private function validateCouponData(array $data): array
    {
        $errors = [];
        
        // Code
        if (empty($data['code'])) {
            $errors['code'] = "Le code est requis";
        } elseif (strlen($data['code']) < 3 || strlen($data['code']) > 50) {
            $errors['code'] = "Le code doit faire entre 3 et 50 caractères";
        } elseif (!preg_match('/^[A-Z0-9_-]+$/i', $data['code'])) {
            $errors['code'] = "Le code ne peut contenir que des lettres, chiffres, _ et -";
        }
        
        // Type
        if (empty($data['type'])) {
            $errors['type'] = "Le type est requis";
        } elseif (!in_array($data['type'], ['percentage', 'fixed_amount'])) {
            $errors['type'] = "Type invalide";
        }
        
        // Value
        if (empty($data['value']) || !is_numeric($data['value']) || $data['value'] <= 0) {
            $errors['value'] = "La valeur doit être un nombre positif";
        }
        
        if ($data['type'] === 'percentage' && $data['value'] > 100) {
            $errors['value'] = "Le pourcentage ne peut pas dépasser 100%";
        }
        
        // Dates
        if (!empty($data['valid_from']) && !empty($data['valid_until'])) {
            if (strtotime($data['valid_from']) > strtotime($data['valid_until'])) {
                $errors['valid_until'] = "La date de fin doit être après la date de début";
            }
        }
        
        return $errors;
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
