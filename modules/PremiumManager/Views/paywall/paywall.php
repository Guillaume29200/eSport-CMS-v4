<!-- PremiumManager - Paywall -->
<div class="premium-content-wrapper">
    
    <?php if ($preview): ?>
    <!-- Preview gratuit -->
    <div class="content-preview">
        <?= $preview ?>
    </div>
    <?php endif; ?>
    
    <!-- Paywall -->
    <div class="premium-paywall">
        <div class="paywall-overlay"></div>
        
        <div class="paywall-content">
            <div class="paywall-icon">
                🔒
            </div>
            
            <h2>Contenu Premium</h2>
            
            <?php if ($paywallInfo['custom_paywall_message']): ?>
                <p class="paywall-message">
                    <?= htmlspecialchars($paywallInfo['custom_paywall_message']) ?>
                </p>
            <?php else: ?>
                <p class="paywall-message">
                    Accédez à ce contenu exclusif et à bien plus encore avec un abonnement Premium.
                </p>
            <?php endif; ?>
            
            <?php if ($paywallInfo['access_type'] === 'one_time'): ?>
                <!-- Achat unique -->
                <div class="paywall-option paywall-onetime">
                    <div class="option-header">
                        <span class="option-title">Débloquez ce contenu</span>
                        <span class="option-price">
                            <?= number_format($paywallInfo['price'], 2) ?> <?= $paywallInfo['currency'] ?>
                        </span>
                    </div>
                    <a href="/member/premium/checkout/<?= $paywallInfo['content_type'] ?>/<?= $paywallInfo['content_id'] ?>" 
                       class="btn btn-primary btn-block">
                        💳 Acheter maintenant
                    </a>
                </div>
                
                <div class="paywall-divider">
                    <span>ou</span>
                </div>
            <?php endif; ?>
            
            <!-- Plans d'abonnement -->
            <div class="paywall-plans">
                <p class="plans-intro">
                    Accédez à tout le contenu premium avec un abonnement :
                </p>
                
                <div class="plans-grid">
                    <?php foreach ($plans as $plan): ?>
                    <div class="plan-card <?= $plan['sort_order'] === 1 ? 'plan-featured' : '' ?>">
                        <?php if ($plan['sort_order'] === 1): ?>
                            <div class="plan-badge">⭐ Populaire</div>
                        <?php endif; ?>
                        
                        <div class="plan-header">
                            <h3><?= htmlspecialchars($plan['name']) ?></h3>
                            <div class="plan-price">
                                <span class="price-amount"><?= number_format($plan['price'], 2) ?>€</span>
                                <span class="price-period">/<?= $plan['billing_period'] === 'monthly' ? 'mois' : 'an' ?></span>
                            </div>
                        </div>
                        
                        <ul class="plan-features">
                            <?php 
                            $features = json_decode($plan['features'], true);
                            foreach ($features as $feature): 
                            ?>
                                <li>✅ <?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <a href="/member/premium/checkout/plan/<?= $plan['id'] ?>" 
                           class="btn <?= $plan['sort_order'] === 1 ? 'btn-primary' : 'btn-secondary' ?> btn-block">
                            <?= $plan['trial_days'] > 0 ? "Essai {$plan['trial_days']} jours gratuit" : 'Choisir ce plan' ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (!$userId): ?>
            <div class="paywall-login">
                <p>Déjà membre ? <a href="/login?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Se connecter</a></p>
            </div>
            <?php endif; ?>
            
            <div class="paywall-guarantees">
                <p>
                    ✅ Annulation à tout moment<br>
                    ✅ Paiement 100% sécurisé<br>
                    ✅ Accès immédiat
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.premium-content-wrapper {
    position: relative;
}

.content-preview {
    position: relative;
    max-height: 300px;
    overflow: hidden;
    margin-bottom: 20px;
}

.content-preview::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 100px;
    background: linear-gradient(transparent, white);
}

.premium-paywall {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 40px;
    color: white;
    text-align: center;
}

.paywall-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.paywall-content h2 {
    margin-bottom: 15px;
    font-size: 32px;
}

.paywall-message {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.9;
}

.paywall-onetime {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.option-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.option-title {
    font-weight: bold;
    font-size: 18px;
}

.option-price {
    font-size: 24px;
    font-weight: bold;
}

.paywall-divider {
    margin: 30px 0;
    position: relative;
}

.paywall-divider::before,
.paywall-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 45%;
    height: 1px;
    background: rgba(255, 255, 255, 0.3);
}

.paywall-divider::before { left: 0; }
.paywall-divider::after { right: 0; }

.paywall-divider span {
    background: rgba(255, 255, 255, 0.2);
    padding: 5px 15px;
    border-radius: 20px;
}

.plans-intro {
    margin-bottom: 25px;
    font-size: 16px;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.plan-card {
    background: white;
    color: #333;
    border-radius: 12px;
    padding: 25px;
    position: relative;
    transition: transform 0.3s;
}

.plan-card:hover {
    transform: translateY(-5px);
}

.plan-featured {
    border: 3px solid #FFD700;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.plan-badge {
    position: absolute;
    top: -10px;
    right: 20px;
    background: #FFD700;
    color: #333;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.plan-header h3 {
    margin-bottom: 10px;
    color: #333;
}

.plan-price {
    margin-bottom: 20px;
}

.price-amount {
    font-size: 36px;
    font-weight: bold;
    color: #667eea;
}

.price-period {
    font-size: 16px;
    color: #666;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    text-align: left;
}

.plan-features li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.paywall-login {
    margin-top: 20px;
    opacity: 0.9;
}

.paywall-login a {
    color: white;
    text-decoration: underline;
}

.paywall-guarantees {
    margin-top: 30px;
    font-size: 14px;
    opacity: 0.8;
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}

@media (max-width: 768px) {
    .premium-paywall {
        padding: 20px;
    }
    
    .plans-grid {
        grid-template-columns: 1fr;
    }
}
</style>
