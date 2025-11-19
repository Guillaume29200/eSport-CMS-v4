<?php if (!defined('ESPORT_CMS')) die('Access denied'); ?>
<!DOCTYPE html>
<html>
<head><title>Mon Abonnement Premium</title></head>
<body>
<div class="member-container">
    <h1>💎 Mon Abonnement Premium</h1>
    
    <?php if ($subscription): ?>
        <div class="subscription-card">
            <div class="plan-badge <?= strtolower($plan['slug']) ?>">
                <?= htmlspecialchars($plan['name']) ?>
            </div>
            
            <div class="subscription-info">
                <h3><?= htmlspecialchars($plan['name']) ?></h3>
                <p class="price"><?= $plan['price'] ?>€ / <?= $plan['billing_period'] === 'monthly' ? 'mois' : 'an' ?></p>
                
                <div class="status">
                    Statut: <strong><?= htmlspecialchars($subscription->status) ?></strong>
                </div>
                
                <div class="period">
                    Période en cours: <?= date('d/m/Y', strtotime($subscription->currentPeriodStart)) ?> 
                    - <?= date('d/m/Y', strtotime($subscription->currentPeriodEnd)) ?>
                </div>
                
                <?php if ($subscription->status === 'trialing' && $subscription->trialEndsAt): ?>
                    <div class="alert alert-info">
                        Période d'essai jusqu'au <?= date('d/m/Y', strtotime($subscription->trialEndsAt)) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="features">
                <h4>Fonctionnalités incluses:</h4>
                <ul>
                    <?php foreach ($plan['features'] as $feature): ?>
                        <li>✓ <?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if ($subscription->status === 'active'): ?>
                <div class="actions">
                    <?php if (!$subscription->cancelAtPeriodEnd): ?>
                        <form method="POST" action="/member/premium/subscription/cancel" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <button type="submit" class="btn btn-danger">Annuler l'abonnement</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Votre abonnement sera annulé le <?= date('d/m/Y', strtotime($subscription->currentPeriodEnd)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="upgrade-section">
            <h3>Changer de plan</h3>
            <div class="plans-grid">
                <?php foreach ($availablePlans as $avPlan): ?>
                    <?php if ($avPlan['id'] !== $subscription->planId): ?>
                        <div class="plan-card">
                            <h4><?= htmlspecialchars($avPlan['name']) ?></h4>
                            <p class="price"><?= $avPlan['price'] ?>€/<?= $avPlan['billing_period'] === 'monthly' ? 'mois' : 'an' ?></p>
                            <form method="POST" action="/member/premium/subscription/upgrade">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="plan_id" value="<?= $avPlan['id'] ?>">
                                <button type="submit" class="btn btn-primary">Changer</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
    <?php else: ?>
        <div class="no-subscription">
            <h2>Vous n'avez pas d'abonnement actif</h2>
            <p>Découvrez nos offres premium pour accéder à des contenus exclusifs !</p>
            
            <div class="plans-grid">
                <?php foreach ($availablePlans as $plan): ?>
                    <div class="plan-card">
                        <h3><?= htmlspecialchars($plan['name']) ?></h3>
                        <p class="price"><?= $plan['price'] ?>€/<?= $plan['billing_period'] === 'monthly' ? 'mois' : 'an' ?></p>
                        <ul>
                            <?php foreach (json_decode($plan['features'], true) as $feature): ?>
                                <li>✓ <?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="/member/premium/checkout/subscription/<?= $plan['id'] ?>" class="btn btn-primary">
                            S'abonner
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.subscription-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin: 20px 0;
}
.plan-badge {
    display: inline-block;
    padding: 8px 20px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-weight: bold;
}
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.plan-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.price {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
}
</style>
</body>
</html>
