<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - eSport-CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
        }
        .navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            color: #667eea;
            font-size: 24px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .btn-logout {
            padding: 8px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            margin-bottom: 30px;
        }
        .welcome-card h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
            font-weight: 500;
        }
        .info-value {
            color: #333;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .action-btn {
            padding: 20px;
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            font-weight: 600;
        }
        .action-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }
        .sessions-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .session-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .session-item:last-child {
            border-bottom: none;
        }
        .current-session {
            background: #e8f5e9;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🎮 eSport-CMS</h1>
        <div class="user-info">
            <strong><?= htmlspecialchars($user['username']) ?></strong>
            <a href="/auth/logout" class="btn-logout">Déconnexion</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h2>👋 Bonjour <?= htmlspecialchars($user['first_name'] ?: $user['username']) ?> !</h2>
            <p>Bienvenue dans votre espace membre</p>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>📋 Mes Informations</h3>
                <div class="info-row">
                    <span class="info-label">Nom d'utilisateur</span>
                    <span class="info-value"><?= htmlspecialchars($user['username']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rôle</span>
                    <span class="info-value"><?= htmlspecialchars($user['role']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Membre depuis</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
            
            <div class="info-card">
                <h3>📊 Statistiques</h3>
                <div class="info-row">
                    <span class="info-label">Dernière connexion</span>
                    <span class="info-value">
                        <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Connexions totales</span>
                    <span class="info-value"><?= number_format($user['login_count']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="info-value"><?= htmlspecialchars($user['status']) ?></span>
                </div>
            </div>
        </div>
        
        <div class="info-card" style="margin-bottom: 30px;">
            <h3>⚡ Actions Rapides</h3>
            <div class="quick-actions">
                <a href="/member/profile" class="action-btn">👤 Mon Profil</a>
                <a href="/member/settings" class="action-btn">⚙️ Paramètres</a>
                <a href="/member/premium/subscription" class="action-btn">💎 Abonnement</a>
                <?php if ($user['role'] === 'admin' || $user['role'] === 'superadmin'): ?>
                    <a href="/admin/dashboard" class="action-btn">🔐 Administration</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($recentSessions)): ?>
        <div class="sessions-card">
            <h3>🔒 Sessions Récentes</h3>
            <?php foreach ($recentSessions as $session): ?>
                <div class="session-item <?= $session['session_id'] === session_id() ? 'current-session' : '' ?>">
                    <div>
                        <div><strong><?= htmlspecialchars($session['device_type'] ?: 'Appareil inconnu') ?></strong></div>
                        <div style="font-size: 13px; color: #666;">
                            <?= htmlspecialchars($session['ip_address']) ?> • 
                            <?= date('d/m/Y H:i', strtotime($session['last_activity'])) ?>
                        </div>
                    </div>
                    <?php if ($session['session_id'] === session_id()): ?>
                        <span style="color: #28a745; font-weight: 600;">Session actuelle</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
