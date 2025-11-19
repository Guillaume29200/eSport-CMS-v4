<?php
/** View: Édition contenu premium | Variables: $content, $csrfToken, $plans */
if (!defined('ESPORT_CMS')) die('Access denied');
?>
<!DOCTYPE html>
<html><head><title>Éditer contenu premium</title></head><body>
<div class="admin-container">
    <h1>Éditer contenu premium #<?= $content->id ?></h1>

    <form method="POST" action="/admin/premium/content/<?= $content->id ?>/update">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="form-group">
            <label>Type d'accès</label>
            <select name="access_type" id="access_type" required>
                <option value="one_time" <?= $content->accessType === 'one_time' ? 'selected' : '' ?>>💰 Achat unique</option>
                <option value="subscription" <?= $content->accessType === 'subscription' ? 'selected' : '' ?>>🔄 Abonnement</option>
                <option value="plan_required" <?= $content->accessType === 'plan_required' ? 'selected' : '' ?>>👑 Plan spécifique</option>
            </select>
        </div>

        <div class="form-group" id="price_group">
            <label>Prix</label>
            <input type="number" name="price" step="0.01" value="<?= $content->price ?>">
            <select name="currency">
                <option value="EUR" <?= $content->currency === 'EUR' ? 'selected' : '' ?>>EUR</option>
                <option value="USD" <?= $content->currency === 'USD' ? 'selected' : '' ?>>USD</option>
            </select>
        </div>

        <div class="form-group" id="plans_group">
            <label>Plans autorisés</label>
            <?php foreach ($plans as $plan): ?>
                <label>
                    <input type="checkbox" name="required_plans[]" value="<?= $plan['id'] ?>"
                           <?= in_array($plan['id'], $content->requiredPlanIds ?? []) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($plan['name']) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="preview_enabled" <?= $content->previewEnabled ? 'checked' : '' ?>> Prévisualisation</label>
        </div>

        <div class="form-group">
            <label>Longueur preview</label>
            <input type="number" name="preview_length" value="<?= $content->previewLength ?>">
        </div>

        <div class="form-group">
            <label>Message paywall</label>
            <textarea name="custom_message" rows="3"><?= htmlspecialchars($content->customPaywallMessage ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Sauvegarder</button>
        <a href="/admin/premium/content" class="btn btn-secondary">Annuler</a>
    </form>
</div>
<script>
document.getElementById('access_type').addEventListener('change', function() {
    document.getElementById('price_group').style.display = this.value === 'one_time' ? 'block' : 'none';
    document.getElementById('plans_group').style.display = this.value === 'plan_required' ? 'block' : 'none';
});
document.getElementById('access_type').dispatchEvent(new Event('change'));
</script>
</body></html>
