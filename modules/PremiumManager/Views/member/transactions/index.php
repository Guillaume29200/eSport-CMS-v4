<?php if (!defined('ESPORT_CMS')) die('Access denied'); ?>
<!DOCTYPE html>
<html><head><title>Mes Paiements</title></head><body>
<div class="member-container">
    <h1>💳 Historique des paiements</h1>
    
    <div class="stats-summary">
        <div class="stat">
            <span class="label">Total dépensé:</span>
            <span class="value"><?= number_format($stats['total_spent'], 2) ?>€</span>
        </div>
        <div class="stat">
            <span class="label">Transactions:</span>
            <span class="value"><?= $stats['transaction_count'] ?></span>
        </div>
    </div>
    
    <div class="transactions-list">
        <?php if (empty($transactions)): ?>
            <p class="text-muted">Aucune transaction pour le moment</p>
        <?php else: ?>
            <?php foreach ($transactions as $t): ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-type"><?= $t->getFormattedType() ?></div>
                        <div class="transaction-date"><?= date('d/m/Y H:i', strtotime($t->createdAt)) ?></div>
                    </div>
                    <div class="transaction-amount">
                        <?= $t->getFormattedAmount() ?>
                    </div>
                    <div class="transaction-status">
                        <?= $t->getFormattedStatus() ?>
                    </div>
                    <?php if ($t->invoiceNumber): ?>
                        <div class="transaction-invoice">
                            <a href="/member/premium/invoices/<?= $t->invoiceId ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-file-pdf"></i> Facture
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.stats-summary {
    display: flex;
    gap: 30px;
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}
.stat .label {
    display: block;
    font-size: 14px;
    color: #6c757d;
}
.stat .value {
    display: block;
    font-size: 28px;
    font-weight: bold;
    color: #212529;
}
.transaction-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px;
    margin: 10px 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
.transaction-type {
    font-weight: bold;
}
.transaction-date {
    font-size: 14px;
    color: #6c757d;
}
.transaction-amount {
    font-size: 20px;
    font-weight: bold;
    color: #28a745;
}
</style>
</body></html>
