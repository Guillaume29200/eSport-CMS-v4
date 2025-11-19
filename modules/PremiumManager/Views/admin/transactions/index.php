<?php if (!defined('ESPORT_CMS')) die('Access denied'); ?>
<div class="admin-container">
    <h1>💳 Transactions</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Aujourd'hui</div>
            <div class="stat-value"><?= number_format($stats['today_revenue'], 2) ?>€</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Transactions</div>
            <div class="stat-value"><?= $stats['today_count'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">En attente</div>
            <div class="stat-value"><?= $stats['pending_count'] ?></div>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= $t->id ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($t->createdAt)) ?></td>
                    <td><?= htmlspecialchars($t->username ?? 'N/A') ?></td>
                    <td><?= $t->getFormattedType() ?></td>
                    <td><strong><?= $t->getFormattedAmount() ?></strong></td>
                    <td><?= $t->getFormattedStatus() ?></td>
                    <td>
                        <a href="/admin/premium/transactions/<?= $t->id ?>" class="btn btn-sm btn-primary">
                            Voir
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
