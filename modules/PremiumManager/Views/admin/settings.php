<?php if (!defined('ESPORT_CMS')) die('Access denied'); ?>
<!DOCTYPE html>
<html><head><title>Configuration Premium</title></head><body>
<div class="admin-container">
    <h1>⚙️ Configuration Premium</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <div class="settings-tabs">
        <a href="#general" class="tab active">Général</a>
        <a href="#stripe" class="tab">Stripe</a>
        <a href="#paypal" class="tab">PayPal</a>
        <a href="#invoicing" class="tab">Facturation</a>
        <a href="#emails" class="tab">Emails</a>
    </div>
    
    <!-- GÉNÉRAL -->
    <div id="general" class="settings-section">
        <h2>Paramètres généraux</h2>
        <form method="POST" action="/admin/premium/settings/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="section" value="general">
            
            <div class="form-group">
                <label>Devise</label>
                <select name="currency">
                    <?php foreach ($currencies as $curr): ?>
                        <option value="<?= $curr ?>" <?= $settings['currency'] === $curr ? 'selected' : '' ?>><?= $curr ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" name="trial_enabled" <?= $settings['trial_enabled'] ? 'checked' : '' ?>> Activer période d'essai</label>
            </div>
            
            <div class="form-group">
                <label>Durée essai (jours)</label>
                <input type="number" name="trial_days" value="<?= $settings['trial_days'] ?>" min="1">
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" name="auto_invoice" <?= $settings['auto_invoice'] ? 'checked' : '' ?>> Générer factures automatiquement</label>
            </div>
            
            <button type="submit" class="btn btn-primary">Sauvegarder</button>
        </form>
    </div>
    
    <!-- STRIPE -->
    <div id="stripe" class="settings-section" style="display:none;">
        <h2>Configuration Stripe</h2>
        <form method="POST" action="/admin/premium/settings/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="section" value="stripe">
            
            <div class="form-group">
                <label><input type="checkbox" name="stripe_enabled" <?= $settings['stripe_enabled'] ? 'checked' : '' ?>> Activer Stripe</label>
            </div>
            
            <div class="form-group">
                <label>Clé publique</label>
                <input type="text" name="stripe_public_key" value="<?= htmlspecialchars($settings['stripe_public_key'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Clé secrète</label>
                <input type="password" name="stripe_secret_key" value="<?= htmlspecialchars($settings['stripe_secret_key'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Webhook secret</label>
                <input type="text" name="stripe_webhook_secret" value="<?= htmlspecialchars($settings['stripe_webhook_secret'] ?? '') ?>">
                <small>URL webhook: <?= $_SERVER['HTTP_HOST'] ?>/api/premium/webhook/stripe</small>
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" name="stripe_test_mode" <?= $settings['stripe_test_mode'] ? 'checked' : '' ?>> Mode test</label>
            </div>
            
            <?php if (isset($stripeStatus)): ?>
                <div class="alert alert-<?= $stripeStatus['status'] === 'success' ? 'success' : 'danger' ?>">
                    <?= htmlspecialchars($stripeStatus['message']) ?>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">Sauvegarder</button>
        </form>
    </div>
    
    <!-- FACTURATION -->
    <div id="invoicing" class="settings-section" style="display:none;">
        <h2>Facturation</h2>
        <form method="POST" action="/admin/premium/settings/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="section" value="invoicing">
            
            <div class="form-group">
                <label>Préfixe factures</label>
                <input type="text" name="invoice_prefix" value="<?= htmlspecialchars($settings['invoice_prefix']) ?>">
            </div>
            
            <div class="form-group">
                <label>Taux TVA (%)</label>
                <input type="number" name="tax_rate" step="0.01" value="<?= $settings['tax_rate'] * 100 ?>">
            </div>
            
            <div class="form-group">
                <label>Nom entreprise</label>
                <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>">
            </div>
            
            <div class="form-group">
                <label>Adresse entreprise</label>
                <textarea name="company_address" rows="3"><?= htmlspecialchars($settings['company_address']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Numéro TVA</label>
                <input type="text" name="company_vat" value="<?= htmlspecialchars($settings['company_vat']) ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Sauvegarder</button>
        </form>
    </div>
    
    <!-- EMAILS -->
    <div id="emails" class="settings-section" style="display:none;">
        <h2>Notifications Email</h2>
        <form method="POST" action="/admin/premium/settings/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="section" value="emails">
            
            <div class="form-group">
                <label><input type="checkbox" name="email_payment_success" <?= $settings['email_payment_success'] ? 'checked' : '' ?>> Paiement réussi</label>
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" name="email_payment_failed" <?= $settings['email_payment_failed'] ? 'checked' : '' ?>> Paiement échoué</label>
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" name="email_subscription_created" <?= $settings['email_subscription_created'] ? 'checked' : '' ?>> Nouvel abonnement</label>
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" name="email_trial_ending" <?= $settings['email_trial_ending'] ? 'checked' : '' ?>> Fin période d'essai</label>
                <input type="number" name="email_trial_ending_days" value="<?= $settings['email_trial_ending_days'] ?>" min="1"> jours avant
            </div>
            
            <button type="submit" class="btn btn-primary">Sauvegarder</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.settings-tabs .tab').forEach(tab => {
    tab.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.settings-section').forEach(s => s.style.display = 'none');
        tab.classList.add('active');
        document.querySelector(tab.getAttribute('href')).style.display = 'block';
    });
});
</script>

<style>
.settings-tabs {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #dee2e6;
    margin: 20px 0;
}
.tab {
    padding: 10px 20px;
    text-decoration: none;
    color: #495057;
    border-bottom: 3px solid transparent;
}
.tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
}
.settings-section {
    padding: 20px 0;
}
.form-group {
    margin: 15px 0;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="password"],
.form-group select,
.form-group textarea {
    width: 100%;
    max-width: 500px;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}
</style>
</body></html>
