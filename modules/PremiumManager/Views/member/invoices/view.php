<?php if (!defined('ESPORT_CMS')) die('Access denied'); ?>
<!DOCTYPE html>
<html><head><title>Facture <?= htmlspecialchars($invoice->invoiceNumber) ?></title></head><body>
<div class="invoice-container">
    <div class="invoice-header">
        <h1>FACTURE</h1>
        <div class="invoice-number">N° <?= htmlspecialchars($invoice->invoiceNumber) ?></div>
    </div>
    
    <div class="invoice-meta">
        <div class="invoice-date">
            <strong>Date:</strong> <?= $invoice->getFormattedIssuedDate() ?>
        </div>
        <div class="invoice-status">
            <?= $invoice->getStatusBadge() ?>
        </div>
    </div>
    
    <div class="invoice-parties">
        <div class="party">
            <h3>Émetteur</h3>
            <p><strong><?= htmlspecialchars($_ENV['SITE_NAME'] ?? 'eSport-CMS') ?></strong></p>
            <p><?= nl2br(htmlspecialchars($_ENV['SITE_ADDRESS'] ?? '')) ?></p>
        </div>
        
        <div class="party">
            <h3>Client</h3>
            <p><strong><?= htmlspecialchars($invoice->user['username']) ?></strong></p>
            <p><?= htmlspecialchars($invoice->user['email']) ?></p>
            <?php if ($invoice->user['company_name']): ?>
                <p><?= htmlspecialchars($invoice->user['company_name']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?php if ($invoice->transaction): ?>
                        Paiement <?= htmlspecialchars($invoice->transaction['type'] ?? 'Premium') ?><br>
                        <small>Transaction: <?= htmlspecialchars($invoice->transaction['provider_id'] ?? '-') ?></small>
                    <?php else: ?>
                        Service Premium
                    <?php endif; ?>
                </td>
                <td class="text-right"><?= $invoice->getFormattedAmount() ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td><strong>Sous-total</strong></td>
                <td class="text-right"><strong><?= $invoice->getFormattedAmount() ?></strong></td>
            </tr>
            <tr>
                <td>TVA (<?= number_format($invoice->getTaxPercentage(), 1) ?>%)</td>
                <td class="text-right"><?= $invoice->getFormattedTax() ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td class="text-right"><strong><?= $invoice->getFormattedTotal() ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <?php if ($invoice->notes): ?>
        <div class="invoice-notes">
            <h4>Notes</h4>
            <p><?= nl2br(htmlspecialchars($invoice->notes)) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="invoice-actions">
        <a href="/member/premium/invoices/<?= $invoice->id ?>" class="btn btn-primary" download>
            <i class="fas fa-download"></i> Télécharger PDF
        </a>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Imprimer
        </button>
    </div>
    
    <div class="invoice-footer">
        Facture générée automatiquement par <?= htmlspecialchars($_ENV['SITE_NAME'] ?? 'eSport-CMS') ?>
    </div>
</div>

<style>
.invoice-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 40px;
    background: white;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px solid #007bff;
    padding-bottom: 20px;
    margin-bottom: 30px;
}
.invoice-number {
    font-size: 18px;
    font-weight: bold;
    color: #6c757d;
}
.invoice-meta {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
}
.invoice-parties {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin: 30px 0;
}
.party h3 {
    font-size: 14px;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 10px;
}
.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin: 30px 0;
}
.invoice-table th,
.invoice-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}
.invoice-table th {
    background: #f8f9fa;
    font-weight: bold;
}
.text-right {
    text-align: right;
}
.total-row {
    font-size: 18px;
    background: #f8f9fa;
}
.invoice-actions {
    display: flex;
    gap: 15px;
    margin: 30px 0;
}
.invoice-footer {
    text-align: center;
    color: #6c757d;
    font-size: 12px;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}
@media print {
    .invoice-actions {
        display: none;
    }
    .invoice-container {
        box-shadow: none;
    }
}
</style>
</body></html>
