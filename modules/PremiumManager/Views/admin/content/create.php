<?php
/**
 * View: Création contenu premium (Admin)
 * Variables: $csrfToken, $plans
 */
if (!defined('ESPORT_CMS')) die('Access denied');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nouveau contenu premium</title>
</head>
<body>
<div class="admin-container">
    <h1>Créer un contenu premium</h1>

    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert alert-danger">
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <form method="POST" action="/admin/premium/content/store" class="form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="form-group">
            <label>Type de contenu *</label>
            <select name="content_type" required>
                <option value="">Sélectionner...</option>
                <option value="article">Article</option>
                <option value="page">Page</option>
                <option value="module">Module</option>
                <option value="forum_section">Section Forum</option>
                <option value="download">Téléchargement</option>
            </select>
        </div>

        <div class="form-group">
            <label>ID du contenu *</label>
            <input type="number" name="content_id" required min="1">
            <small>L'ID du contenu dans sa table respective</small>
        </div>

        <div class="form-group">
            <label>Type d'accès *</label>
            <select name="access_type" id="access_type" required>
                <option value="one_time">💰 Achat unique</option>
                <option value="subscription">🔄 Abonnement requis</option>
                <option value="plan_required">👑 Plan spécifique</option>
            </select>
        </div>

        <div class="form-group" id="price_group">
            <label>Prix *</label>
            <input type="number" name="price" step="0.01" min="0">
            <select name="currency">
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
            </select>
        </div>

        <div class="form-group" id="plans_group" style="display:none;">
            <label>Plans autorisés *</label>
            <?php foreach ($plans as $plan): ?>
                <label>
                    <input type="checkbox" name="required_plans[]" value="<?= $plan['id'] ?>">
                    <?= htmlspecialchars($plan['name']) ?> (<?= $plan['price'] ?>€)
                </label>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="preview_enabled" checked>
                Activer la prévisualisation
            </label>
        </div>

        <div class="form-group">
            <label>Longueur de la prévisualisation (caractères)</label>
            <input type="number" name="preview_length" value="300" min="0">
        </div>

        <div class="form-group">
            <label>Message paywall personnalisé</label>
            <textarea name="custom_message" rows="3"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="/admin/premium/content" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<script>
document.getElementById('access_type').addEventListener('change', function() {
    const priceGroup = document.getElementById('price_group');
    const plansGroup = document.getElementById('plans_group');
    
    if (this.value === 'one_time') {
        priceGroup.style.display = 'block';
        plansGroup.style.display = 'none';
    } else if (this.value === 'plan_required') {
        priceGroup.style.display = 'none';
        plansGroup.style.display = 'block';
    } else {
        priceGroup.style.display = 'none';
        plansGroup.style.display = 'none';
    }
});
</script>
</body>
</html>
