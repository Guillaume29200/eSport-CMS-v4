<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Plans Premium - Admin</title>
    <style>
        .plans-page {
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 32px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .plan-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .plan-card.inactive {
            opacity: 0.6;
        }
        
        .plan-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .plan-header {
            margin-bottom: 20px;
        }
        
        .plan-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        
        .plan-price {
            display: flex;
            align-items: baseline;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .price-amount {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        
        .price-currency {
            font-size: 20px;
            color: #94a3b8;
        }
        
        .price-period {
            font-size: 16px;
            color: #64748b;
        }
        
        .plan-description {
            color: #64748b;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .plan-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        
        .plan-features li {
            padding: 8px 0;
            color: #475569;
            font-size: 14px;
        }
        
        .plan-features li::before {
            content: '✓';
            color: #10b981;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .plan-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-danger:hover {
            background: #fecaca;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .plans-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="plans-page">
        <div class="page-header">
            <h1>📋 Gestion des Plans</h1>
            <a href="/admin/premium/plans/create" class="btn btn-primary">
                ➕ Créer un plan
            </a>
        </div>
        
        <?php if (empty($plansObjects)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h2>Aucun plan créé</h2>
            <p>Commencez par créer votre premier plan d'abonnement premium</p>
            <a href="/admin/premium/plans/create" class="btn btn-primary">
                Créer le premier plan
            </a>
        </div>
        <?php else: ?>
        <div class="plans-grid">
            <?php foreach ($plansObjects as $plan): ?>
            <div class="plan-card <?= $plan->active ? '' : 'inactive' ?>">
                <div class="plan-badge badge-<?= $plan->active ? 'active' : 'inactive' ?>">
                    <?= $plan->active ? '✓ Actif' : '✗ Inactif' ?>
                </div>
                
                <div class="plan-header">
                    <h2 class="plan-name"><?= htmlspecialchars($plan->name) ?></h2>
                    
                    <div class="plan-price">
                        <span class="price-amount"><?= number_format($plan->price, 2) ?></span>
                        <span class="price-currency"><?= $plan->currency ?></span>
                        <span class="price-period">/ <?= $plan->getFormattedPeriod() ?></span>
                    </div>
                    
                    <?php if ($plan->description): ?>
                    <div class="plan-description">
                        <?= htmlspecialchars($plan->description) ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="plan-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $plan->subscribers_count ?? 0 ?></div>
                        <div class="stat-label">Abonnés</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $plan->trialDays ?></div>
                        <div class="stat-label">Jours d'essai</div>
                    </div>
                </div>
                
                <?php if (!empty($plan->features)): ?>
                <ul class="plan-features">
                    <?php foreach (array_slice($plan->features, 0, 5) as $feature): ?>
                        <li><?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                    <?php if (count($plan->features) > 5): ?>
                        <li><em>+ <?= count($plan->features) - 5 ?> autres features</em></li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
                
                <div style="margin-bottom: 15px;">
                    <?php if ($plan->isUnlimited()): ?>
                        <span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                            ∞ Accès illimité
                        </span>
                    <?php else: ?>
                        <div style="font-size: 12px; color: #64748b;">
                            <?php if ($plan->maxArticles): ?>
                                📝 <?= $plan->maxArticles ?> articles •
                            <?php endif; ?>
                            <?php if ($plan->maxPages): ?>
                                📄 <?= $plan->maxPages ?> pages •
                            <?php endif; ?>
                            <?php if ($plan->maxModules): ?>
                                🧩 <?= $plan->maxModules ?> modules
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="plan-actions">
                    <a href="/admin/premium/plans/<?= $plan->id ?>/edit" class="btn btn-secondary btn-sm">
                        ✏️ Modifier
                    </a>
                    
                    <?php if ($plan->subscribers_count == 0): ?>
                    <form method="POST" action="/admin/premium/plans/<?= $plan->id ?>/delete" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce plan ?')">
                            🗑️ Supprimer
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
