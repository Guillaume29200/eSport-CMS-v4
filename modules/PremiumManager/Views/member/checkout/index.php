<?php if (!defined('ESPORT_CMS')) die('Access denied'); ?>
<!DOCTYPE html>
<html><head>
<title>Paiement Sécurisé</title>
<script src="https://js.stripe.com/v3/"></script>
</head><body>
<div class="checkout-container">
    <h1>Finaliser le paiement</h1>
    
    <div class="checkout-grid">
        <!-- Récapitulatif -->
        <div class="order-summary">
            <h2>Récapitulatif</h2>
            
            <?php if ($type === 'subscription'): ?>
                <div class="summary-item">
                    <span>Plan:</span>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                </div>
                <div class="summary-item">
                    <span>Prix:</span>
                    <strong><?= $item['price'] ?>€ / <?= $item['billing_period'] === 'monthly' ? 'mois' : 'an' ?></strong>
                </div>
                <?php if ($item['trial_days'] > 0): ?>
                    <div class="alert alert-info">
                        🎁 Essai gratuit de <?= $item['trial_days'] ?> jours
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="summary-item">
                    <span>Article:</span>
                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                </div>
                <div class="summary-item">
                    <span>Prix:</span>
                    <strong><?= $item['price'] ?>€</strong>
                </div>
            <?php endif; ?>
            
            <?php if ($coupon): ?>
                <div class="summary-item discount">
                    <span>Réduction:</span>
                    <strong>-<?= $coupon['discount'] ?>€</strong>
                </div>
            <?php endif; ?>
            
            <hr>
            <div class="summary-item total">
                <span>Total:</span>
                <strong><?= $finalAmount ?>€</strong>
            </div>
        </div>
        
        <!-- Formulaire paiement -->
        <div class="payment-form">
            <h2>💳 Informations de paiement</h2>
            
            <form id="payment-form">
                <div id="card-element"></div>
                <div id="card-errors" role="alert"></div>
                
                <div class="coupon-field">
                    <input type="text" id="coupon-code" placeholder="Code promo (optionnel)">
                    <button type="button" id="apply-coupon" class="btn btn-secondary">Appliquer</button>
                </div>
                
                <button type="submit" id="submit-button" class="btn btn-primary btn-lg">
                    <span id="button-text">Payer <?= $finalAmount ?>€</span>
                    <span id="spinner" class="spinner" style="display:none;"></span>
                </button>
            </form>
            
            <div class="security-badges">
                <span>🔒 Paiement sécurisé</span>
                <span>✓ Stripe</span>
                <span>✓ SSL</span>
            </div>
        </div>
    </div>
</div>

<script>
const stripe = Stripe('<?= htmlspecialchars($stripePublicKey) ?>');
const elements = stripe.elements();
const cardElement = elements.create('card');
cardElement.mount('#card-element');

const form = document.getElementById('payment-form');
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    
    submitButton.disabled = true;
    buttonText.style.display = 'none';
    spinner.style.display = 'inline-block';
    
    const {error, paymentMethod} = await stripe.createPaymentMethod({
        type: 'card',
        card: cardElement,
    });
    
    if (error) {
        document.getElementById('card-errors').textContent = error.message;
        submitButton.disabled = false;
        buttonText.style.display = 'inline';
        spinner.style.display = 'none';
    } else {
        // Envoyer au serveur
        const response = await fetch('/member/premium/checkout/process', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                payment_method_id: paymentMethod.id,
                type: '<?= $type ?>',
                id: <?= $itemId ?>,
                coupon: document.getElementById('coupon-code').value
            })
        });
        
        const result = await response.json();
        
        if (result.requires_action) {
            const {error: confirmError} = await stripe.confirmCardPayment(result.client_secret);
            if (confirmError) {
                alert('Paiement échoué: ' + confirmError.message);
            } else {
                window.location.href = '/member/premium/success';
            }
        } else if (result.success) {
            window.location.href = '/member/premium/success';
        } else {
            alert('Erreur: ' + result.error);
        }
        
        submitButton.disabled = false;
        buttonText.style.display = 'inline';
        spinner.style.display = 'none';
    }
});
</script>

<style>
.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.order-summary {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    height: fit-content;
}
.summary-item {
    display: flex;
    justify-content: space-between;
    margin: 15px 0;
}
.summary-item.total {
    font-size: 24px;
    font-weight: bold;
    color: #212529;
}
.payment-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
#card-element {
    padding: 15px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    margin: 20px 0;
}
#card-errors {
    color: #dc3545;
    margin: 10px 0;
}
.security-badges {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
    font-size: 14px;
    color: #6c757d;
}
.spinner {
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</body></html>
