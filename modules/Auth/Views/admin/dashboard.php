<?php
	$pageTitle = 'Dashboard Admin';
	require __DIR__ . '/includes/header.php';
?>
<div class="welcome-card">
    <h2>👋 Bienvenue, <?= htmlspecialchars($currentUser['username']) ?> !</h2>
    <p>Vous êtes connecté en tant qu'administrateur. Voici un aperçu de votre système.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Utilisateurs</h3>
        <div class="value"><?= number_format($stats['total_users']) ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Nouveaux (30j)</h3>
        <div class="value"><?= number_format($stats['new_users']) ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Actifs (7j)</h3>
        <div class="value"><?= number_format($stats['active_users']) ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Taux d'activité</h3>
        <div class="value">
            <?= $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0 ?>%
        </div>
    </div>
</div>

<div class="welcome-card">
    <h2>🚀 Accès Rapide</h2>
    <div class="quick-links">
        <a href="<?= $basePath ?>/admin/users" class="quick-link">
            <div style="font-size: 24px; margin-bottom: 10px;">👥</div>
            Gérer les utilisateurs
        </a>
        <a href="<?= $basePath ?>/admin/premium" class="quick-link">
            <div style="font-size: 24px; margin-bottom: 10px;">💎</div>
            Premium Manager
        </a>
        <a href="<?= $basePath ?>/admin/stats" class="quick-link">
            <div style="font-size: 24px; margin-bottom: 10px;">📊</div>
            Statistiques
        </a>
        <a href="<?= $basePath ?>/member/dashboard" class="quick-link">
            <div style="font-size: 24px; margin-bottom: 10px;">👤</div>
            Mon profil
        </a>
    </div>
</div>

<style>
    /* Styles spécifiques au dashboard */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .stat-card h3 {
        color: #666;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 10px;
    }
    
    .stat-card .value {
        font-size: 32px;
        font-weight: 700;
        color: #667eea;
    }
    
    .welcome-card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .welcome-card h2 {
        color: #333;
        margin-bottom: 15px;
    }
    
    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .quick-link {
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        text-decoration: none;
        text-align: center;
        transition: transform 0.3s;
    }
    
    .quick-link:hover {
        transform: translateY(-5px);
    }
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>