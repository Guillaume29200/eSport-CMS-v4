<?php
/**
 * Routes du module PremiumManager
 * 
 * Définit toutes les routes du système de gestion premium
 * Ce fichier est chargé automatiquement par PremiumManager.php
 */

return function($router) {
    
    // ============================================
    // ROUTES ADMIN - Gestion Premium
    // ============================================
    $router->group('/admin/premium', function($router) {
        
        // Dashboard Premium
        $router->get('/', 'PremiumManager\\Controllers\\Admin\\DashboardController@index');
        
        // ─────────────────────────────────────────
        // Gestion des Plans
        // ─────────────────────────────────────────
        $router->get('/plans', 'PremiumManager\\Controllers\\Admin\\PlansController@index');
        $router->get('/plans/create', 'PremiumManager\\Controllers\\Admin\\PlansController@create');
        $router->post('/plans/store', 'PremiumManager\\Controllers\\Admin\\PlansController@store');
        $router->get('/plans/{id}/edit', 'PremiumManager\\Controllers\\Admin\\PlansController@edit');
        $router->post('/plans/{id}/update', 'PremiumManager\\Controllers\\Admin\\PlansController@update');
        $router->post('/plans/{id}/delete', 'PremiumManager\\Controllers\\Admin\\PlansController@delete');
        
        // ─────────────────────────────────────────
        // Gestion des Transactions
        // ─────────────────────────────────────────
        $router->get('/transactions', 'PremiumManager\\Controllers\\Admin\\TransactionsController@index');
        $router->get('/transactions/{id}', 'PremiumManager\\Controllers\\Admin\\TransactionsController@show');
        $router->post('/transactions/{id}/refund', 'PremiumManager\\Controllers\\Admin\\TransactionsController@refund');
        
        // ─────────────────────────────────────────
        // Gestion des Abonnements
        // ─────────────────────────────────────────
        $router->get('/subscriptions', 'PremiumManager\\Controllers\\Admin\\SubscriptionsController@index');
        $router->get('/subscriptions/{id}', 'PremiumManager\\Controllers\\Admin\\SubscriptionsController@show');
        $router->post('/subscriptions/{id}/cancel', 'PremiumManager\\Controllers\\Admin\\SubscriptionsController@cancel');
        
        // ─────────────────────────────────────────
        // Gestion des Coupons
        // ─────────────────────────────────────────
        $router->get('/coupons', 'PremiumManager\\Controllers\\Admin\\CouponsController@index');
        $router->post('/coupons/create', 'PremiumManager\\Controllers\\Admin\\CouponsController@store');
        $router->get('/coupons/{id}/edit', 'PremiumManager\\Controllers\\Admin\\CouponsController@edit');
        $router->post('/coupons/{id}/update', 'PremiumManager\\Controllers\\Admin\\CouponsController@update');
        $router->post('/coupons/{id}/delete', 'PremiumManager\\Controllers\\Admin\\CouponsController@delete');
        
        // ─────────────────────────────────────────
        // Configuration Premium
        // ─────────────────────────────────────────
        $router->get('/settings', 'PremiumManager\\Controllers\\Admin\\SettingsController@index');
        $router->post('/settings/save', 'PremiumManager\\Controllers\\Admin\\SettingsController@save');
    });
    
    // ============================================
    // ROUTES MEMBRE - Espace Premium
    // ============================================
    $router->group('/member/premium', function($router) {
        
        // ─────────────────────────────────────────
        // Mon Abonnement
        // ─────────────────────────────────────────
        $router->get('/subscription', 'PremiumManager\\Controllers\\Front\\SubscriptionController@index');
        $router->post('/subscription/upgrade', 'PremiumManager\\Controllers\\Front\\SubscriptionController@upgrade');
        $router->post('/subscription/downgrade', 'PremiumManager\\Controllers\\Front\\SubscriptionController@downgrade');
        $router->post('/subscription/cancel', 'PremiumManager\\Controllers\\Front\\SubscriptionController@cancel');
        $router->post('/subscription/reactivate', 'PremiumManager\\Controllers\\Front\\SubscriptionController@reactivate');
        
        // ─────────────────────────────────────────
        // Paiement / Checkout
        // ─────────────────────────────────────────
        $router->get('/checkout/{type}/{id}', 'PremiumManager\\Controllers\\Front\\CheckoutController@show');
        $router->post('/checkout/process', 'PremiumManager\\Controllers\\Front\\CheckoutController@process');
        $router->get('/checkout/success', 'PremiumManager\\Controllers\\Front\\CheckoutController@success');
        $router->get('/checkout/cancel', 'PremiumManager\\Controllers\\Front\\CheckoutController@cancel');
        
        // ─────────────────────────────────────────
        // Historique des Transactions
        // ─────────────────────────────────────────
        $router->get('/transactions', 'PremiumManager\\Controllers\\Front\\TransactionsController@index');
        $router->get('/transactions/{id}', 'PremiumManager\\Controllers\\Front\\TransactionsController@show');
        
        // ─────────────────────────────────────────
        // Factures
        // ─────────────────────────────────────────
        $router->get('/invoices', 'PremiumManager\\Controllers\\Front\\InvoiceController@index');
        $router->get('/invoices/{id}', 'PremiumManager\\Controllers\\Front\\InvoiceController@show');
        $router->get('/invoices/{id}/download', 'PremiumManager\\Controllers\\Front\\InvoiceController@download');
        $router->get('/invoices/{id}/pdf', 'PremiumManager\\Controllers\\Front\\InvoiceController@pdf');
    });
    
    // ============================================
    // API WEBHOOKS - Providers de Paiement
    // ============================================
    $router->group('/api/premium/webhook', function($router) {
        
        // Stripe Webhooks
        $router->post('/stripe', 'PremiumManager\\Controllers\\API\\WebhookController@stripe');
        
        // PayPal Webhooks
        $router->post('/paypal', 'PremiumManager\\Controllers\\API\\WebhookController@paypal');
        
        // Autres providers (à implémenter si besoin)
        // $router->post('/mollie', 'PremiumManager\\Controllers\\API\\WebhookController@mollie');
        // $router->post('/square', 'PremiumManager\\Controllers\\API\\WebhookController@square');
    });
    
    // ============================================
    // API REST - Endpoints publics (optionnel)
    // ============================================
    $router->group('/api/premium', function($router) {
        
        // Plans disponibles (lecture seule)
        $router->get('/plans', 'PremiumManager\\Controllers\\API\\PlansController@index');
        $router->get('/plans/{id}', 'PremiumManager\\Controllers\\API\\PlansController@show');
        
        // Vérification d'accès (pour intégrations externes)
        $router->post('/check-access', 'PremiumManager\\Controllers\\API\\AccessController@check');
    });
};
