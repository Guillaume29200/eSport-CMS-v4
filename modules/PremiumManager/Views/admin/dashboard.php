<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Dashboard - Admin</title>
    <style>
        .premium-dashboard {
            padding: 20px;
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stat-change.positive {
            color: #4ade80;
        }
        
        .stat-change.negative {
            color: #f87171;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        
        .card-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .card-link:hover {
            text-decoration: underline;
        }
        
        .transaction-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-info {
            flex: 1;
        }
        
        .transaction-user {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .transaction-type {
            font-size: 13px;
            color: #64748b;
        }
        
        .transaction-amount {
            font-weight: bold;
            font-size: 18px;
            color: #10b981;
        }
        
        .transaction-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .subscriber-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .subscriber-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .subscriber-item:last-child {
            border-bottom: none;
        }
        
        .subscriber-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .subscriber-info {
            flex: 1;
        }
        
        .subscriber-name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .subscriber-plan {
            font-size: 12px;
            color: #64748b;
        }
        
        .subscriber-date {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .chart-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 14px;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="premium-dashboard">
        <div class="dashboard-header">
            <h1>💎 Premium Dashboard</h1>
            <p>Vue d'ensemble des revenus et abonnements</p>
        </div>
        
        <!-- Stats principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Revenus ce mois</div>
                <div class="stat-value">€<?= number_format($monthlyRevenue['total'], 2) ?></div>
                <div class="stat-change <?= $monthlyRevenue['growth'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $monthlyRevenue['growth'] >= 0 ? '↗' : '↘' ?> 
                    <?= abs($monthlyRevenue['growth']) ?>% vs mois dernier
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">Abonnés actifs</div>
                <div class="stat-value"><?= $subscriptionStats['active_subscriptions'] ?></div>
                <div class="stat-change">
                    <?= count($newSubscribers) ?> nouveaux cette semaine
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">Taux de désabonnement</div>
                <div class="stat-value"><?= $subscriptionStats['churn_rate'] ?>%</div>
                <div class="stat-change">30 derniers jours</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-label">Transactions ce mois</div>
                <div class="stat-value"><?= $monthlyRevenue['transaction_count'] ?></div>
                <div class="stat-change">
                    Moy: €<?= $monthlyRevenue['transaction_count'] > 0 ? number_format($monthlyRevenue['total'] / $monthlyRevenue['transaction_count'], 2) : '0.00' ?>
                </div>
            </div>
        </div>
        
        <!-- Graphique revenus -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">📊 Revenus (30 derniers jours)</h2>
            </div>
            <div class="chart-placeholder">
                📈 Graphique à implémenter avec Chart.js
                <br><small>Données disponibles: <?= count($revenueStats['daily']) ?> jours</small>
            </div>
        </div>
        
        <!-- Transactions récentes + Nouveaux abonnés -->
        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">💳 Transactions récentes</h2>
                    <a href="/admin/premium/transactions" class="card-link">Voir tout →</a>
                </div>
                
                <?php if (empty($recentTransactions)): ?>
                    <p style="text-align: center; color: #94a3b8;">Aucune transaction</p>
                <?php else: ?>
                    <ul class="transaction-list">
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <li class="transaction-item">
                            <div class="transaction-info">
                                <div class="transaction-user">
                                    <?= htmlspecialchars($transaction['username']) ?>
                                </div>
                                <div class="transaction-type">
                                    <?= ucfirst($transaction['transaction_type']) ?> • 
                                    <?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?>
                                </div>
                            </div>
                            <div>
                                <span class="transaction-amount">
                                    €<?= number_format($transaction['amount'], 2) ?>
                                </span>
                                <span class="transaction-status status-<?= $transaction['status'] ?>">
                                    <?= $transaction['status'] ?>
                                </span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🆕 Nouveaux abonnés (7j)</h2>
                    <a href="/admin/premium/subscriptions" class="card-link">Voir tout →</a>
                </div>
                
                <?php if (empty($newSubscribers)): ?>
                    <p style="text-align: center; color: #94a3b8;">Aucun nouvel abonné</p>
                <?php else: ?>
                    <ul class="subscriber-list">
                        <?php foreach ($newSubscribers as $subscriber): ?>
                        <li class="subscriber-item">
                            <div class="subscriber-avatar">
                                <?= strtoupper(substr($subscriber['username'], 0, 1)) ?>
                            </div>
                            <div class="subscriber-info">
                                <div class="subscriber-name">
                                    <?= htmlspecialchars($subscriber['username']) ?>
                                </div>
                                <div class="subscriber-plan">
                                    Plan: <?= htmlspecialchars($subscriber['plan_name']) ?>
                                </div>
                            </div>
                            <div class="subscriber-date">
                                <?= date('d/m/Y', strtotime($subscriber['created_at'])) ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Abonnements expirant bientôt -->
        <?php if (!empty($expiringSubscriptions)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">⚠️ Abonnements expirant bientôt</h2>
                <span style="color: #f59e0b; font-size: 14px;">
                    <?= count($expiringSubscriptions) ?> abonnement(s) à renouveler
                </span>
            </div>
            
            <ul class="subscriber-list">
                <?php foreach (array_slice($expiringSubscriptions, 0, 5) as $expiring): ?>
                <li class="subscriber-item">
                    <div class="subscriber-avatar">
                        <?= strtoupper(substr($expiring['username'], 0, 1)) ?>
                    </div>
                    <div class="subscriber-info">
                        <div class="subscriber-name">
                            <?= htmlspecialchars($expiring['username']) ?>
                        </div>
                        <div class="subscriber-plan">
                            <?= htmlspecialchars($expiring['plan_name']) ?> • 
                            <?= htmlspecialchars($expiring['email']) ?>
                        </div>
                    </div>
                    <div class="subscriber-date" style="color: #f59e0b;">
                        Expire: <?= date('d/m/Y', strtotime($expiring['current_period_end'])) ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
