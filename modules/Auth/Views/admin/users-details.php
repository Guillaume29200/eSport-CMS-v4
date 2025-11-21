<?php
/**
 * Détails Utilisateur
 * 
 * Variables passées par le contrôleur :
 * - $user : Données de l'utilisateur
 * - $logins : Historique des connexions
 * - $registrationData : Données d'inscription
 * - $lastLogin : Dernière connexion
 * - $mapData : Données pour la carte
 * - $totalLogins, $uniqueIPs, $devices, $browsers : Stats
 */

$pageTitle = 'Détails Utilisateur - ' . htmlspecialchars($user['username']);

require __DIR__ . '/includes/header.php';
?>

<!-- Leaflet CSS via jsDelivr (autorisé par CSP) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />

<style>
    .user-header {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }
    
    .user-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: white;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    .user-info {
        flex: 1;
    }
    
    .user-info h1 {
        color: #333;
        font-size: 28px;
        margin-bottom: 10px;
    }
    
    .user-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 15px;
    }
    
    .user-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #666;
        font-size: 14px;
    }
    
    .user-actions {
        display: flex;
        gap: 10px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .stat-card h3 {
        color: #666;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 8px;
    }
    
    .stat-card .value {
        font-size: 28px;
        font-weight: 700;
        color: #667eea;
    }
    
    .section {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .section h2 {
        color: #333;
        font-size: 20px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    /* CARTE - IMPORTANT */
    #map {
        height: 400px;
        width: 100%;
        border-radius: 8px;
        margin-top: 15px;
        z-index: 1;
        background: #e0e0e0;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .info-item label {
        font-size: 12px;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .info-item .value {
        font-size: 15px;
        color: #333;
        font-weight: 500;
    }
    
    .logins-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .logins-table thead {
        background: #f8f9fa;
    }
    
    .logins-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        font-size: 13px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .logins-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }
    
    .logins-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .device-icon {
        display: inline-block;
        width: 20px;
        text-align: center;
    }
    
    .ip-badge {
        display: inline-block;
        padding: 4px 8px;
        background: #e3f2fd;
        color: #1976d2;
        border-radius: 4px;
        font-family: monospace;
        font-size: 12px;
    }
    
    .location-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        background: #f3e5f5;
        color: #7b1fa2;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .no-data .icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    /* Fix pour les tiles de la carte */
    .leaflet-container {
        background: #e0e0e0;
    }
    
    /* Bouton Voir carte */
    .btn-view-map {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-view-map:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-view-map:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
        animation: modalSlideIn 0.3s ease;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 25px;
        border-bottom: 1px solid #e0e0e0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modal-header h3 {
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .modal-body {
        padding: 25px;
        overflow-y: auto;
        max-height: calc(90vh - 80px);
    }
    
    .modal-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .modal-info-item {
        background: #f8f9fa;
        padding: 12px 15px;
        border-radius: 8px;
    }
    
    .modal-info-item label {
        display: block;
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    
    .modal-info-item .value {
        font-size: 14px;
        font-weight: 600;
        color: #333;
    }
    
    #modal-map {
        height: 400px;
        width: 100%;
        border-radius: 12px;
        background: #e0e0e0;
        margin-top: 15px;
    }
    
    .modal-no-map {
        text-align: center;
        padding: 60px 20px;
        background: #f8f9fa;
        border-radius: 12px;
        margin-top: 15px;
    }
    
    .modal-no-map .icon {
        font-size: 64px;
        margin-bottom: 15px;
    }
</style>

<!-- Bouton retour -->
<a href="<?= $basePath ?>/admin/users" class="btn" style="margin-bottom: 20px; background: #6c757d; color: white;">
    ← Retour à la liste
</a>

<!-- En-tête utilisateur -->
<div class="user-header">
    <div class="user-avatar">
        <?= strtoupper(substr($user['username'], 0, 2)) ?>
    </div>
    
    <div class="user-info">
        <h1><?= htmlspecialchars($user['username']) ?></h1>
        <p style="color: #666; margin: 5px 0;">
            <?= htmlspecialchars($user['email']) ?>
        </p>
        
        <div class="user-meta">
            <div class="user-meta-item">
                <span>🎭</span>
                <?php
                $roleClass = match($user['role']) {
                    'admin', 'superadmin' => 'badge-admin',
                    'moderator' => 'badge-moderator',
                    default => 'badge-member'
                };
                $roleLabel = match($user['role']) {
                    'superadmin' => 'Super Admin',
                    'admin' => 'Admin',
                    'moderator' => 'Modérateur',
                    default => 'Membre'
                };
                ?>
                <span class="badge <?= $roleClass ?>"><?= $roleLabel ?></span>
            </div>
            
            <div class="user-meta-item">
                <span>📊</span>
                <?php
                $statusClass = match($user['status']) {
                    'active' => 'badge-active',
                    'banned' => 'badge-banned',
                    default => 'badge-inactive'
                };
                $statusLabel = match($user['status']) {
                    'active' => 'Actif',
                    'banned' => 'Banni',
                    default => 'Inactif'
                };
                ?>
                <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
            </div>
            
            <div class="user-meta-item">
                <span>📅</span>
                Membre depuis le <?= date('d/m/Y', strtotime($user['created_at'])) ?>
            </div>
            
            <div class="user-meta-item">
                <span>🔐</span>
                <?= number_format($user['login_count'] ?? 0) ?> connexions
            </div>
            
            <?php if (!empty($user['last_login'])): ?>
                <div class="user-meta-item">
                    <span>⏰</span>
                    Dernière connexion : <?= date('d/m/Y à H:i', strtotime($user['last_login'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="user-actions">
        <a href="<?= $basePath ?>/admin/users/<?= $user['id'] ?>/edit" class="btn btn-primary">
            ✏️ Éditer
        </a>
        <?php if ($user['id'] !== ($_SESSION['user_id'] ?? 0)): ?>
            <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')" 
                    class="btn" style="background: #dc3545; color: white;">
                🗑️ Supprimer
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Connexions</h3>
        <div class="value"><?= number_format($totalLogins) ?></div>
    </div>
    
    <div class="stat-card">
        <h3>IPs Uniques</h3>
        <div class="value"><?= number_format($uniqueIPs) ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Appareils</h3>
        <div class="value"><?= number_format(count($devices)) ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Navigateurs</h3>
        <div class="value"><?= number_format(count($browsers)) ?></div>
    </div>
</div>

<!-- Carte de localisation (dernière connexion) -->
<div class="section">
    <h2>🌍 Dernière Localisation</h2>
    
    <?php if (!empty($mapData) && isset($mapData['lat']) && isset($mapData['lng']) && $mapData['lat'] && $mapData['lng']): ?>
        <div class="info-grid" style="margin-bottom: 20px;">
            <div class="info-item">
                <label>IP Address</label>
                <span class="value ip-badge"><?= htmlspecialchars($mapData['ip'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Ville</label>
                <span class="value"><?= htmlspecialchars($mapData['city'] ?? 'Inconnue') ?></span>
            </div>
            <div class="info-item">
                <label>Pays</label>
                <span class="value"><?= htmlspecialchars($mapData['country'] ?? 'Inconnu') ?></span>
            </div>
            <div class="info-item">
                <label>Coordonnées</label>
                <span class="value"><?= $mapData['lat'] ?>, <?= $mapData['lng'] ?></span>
            </div>
        </div>
        
        <!-- Conteneur de la carte -->
        <div id="map"></div>
        
    <?php elseif (!empty($mapData)): ?>
        <!-- On a des données mais pas de coordonnées (IP locale) -->
        <div class="info-grid" style="margin-bottom: 20px;">
            <div class="info-item">
                <label>IP Address</label>
                <span class="value ip-badge"><?= htmlspecialchars($mapData['ip'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Ville</label>
                <span class="value"><?= htmlspecialchars($mapData['city'] ?? 'Inconnue') ?></span>
            </div>
            <div class="info-item">
                <label>Pays</label>
                <span class="value"><?= htmlspecialchars($mapData['country'] ?? 'Inconnu') ?></span>
            </div>
        </div>
        <div class="no-data">
            <div class="icon">🗺️</div>
            <p><strong>Carte non disponible</strong></p>
            <p style="font-size: 14px;">L'IP locale ne peut pas être géolocalisée sur une carte.</p>
        </div>
    <?php else: ?>
        <div class="no-data">
            <div class="icon">🗺️</div>
            <p><strong>Aucune donnée de localisation disponible</strong></p>
            <p style="font-size: 14px;">Les données de géolocalisation seront disponibles après la prochaine connexion de l'utilisateur.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Informations d'inscription -->
<div class="section">
    <h2>📋 Données d'Inscription</h2>
    
    <?php if ($registrationData): ?>
        <div class="info-grid">
            <div class="info-item">
                <label>IP d'inscription</label>
                <span class="value ip-badge"><?= htmlspecialchars($registrationData['registration_ip'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Lieu</label>
                <span class="value">
                    <?= htmlspecialchars($registrationData['registration_city'] ?? 'N/A') ?>, 
                    <?= htmlspecialchars($registrationData['registration_country'] ?? 'N/A') ?>
                </span>
            </div>
            <div class="info-item">
                <label>Navigateur</label>
                <span class="value">
                    <?= htmlspecialchars($registrationData['registration_browser'] ?? 'N/A') ?> 
                    <?= htmlspecialchars($registrationData['registration_browser_version'] ?? '') ?>
                </span>
            </div>
            <div class="info-item">
                <label>Système</label>
                <span class="value"><?= htmlspecialchars($registrationData['registration_os'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Appareil</label>
                <span class="value"><?= ucfirst($registrationData['registration_device'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Résolution</label>
                <span class="value"><?= htmlspecialchars($registrationData['registration_screen_resolution'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>ISP</label>
                <span class="value"><?= htmlspecialchars($registrationData['registration_isp'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Timezone</label>
                <span class="value"><?= htmlspecialchars($registrationData['registration_timezone'] ?? 'N/A') ?></span>
            </div>
        </div>
    <?php else: ?>
        <div class="no-data">
            <div class="icon">📋</div>
            <p><strong>Aucune donnée d'inscription disponible</strong></p>
            <p style="font-size: 14px;">L'utilisateur a été créé avant l'activation du tracking ou via l'administration.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Historique des connexions -->
<div class="section">
    <h2>🔐 Historique des Connexions (<?= $totalLogins ?> dernières)</h2>
    
    <?php if (empty($logins)): ?>
        <div class="no-data">
            <div class="icon">🔐</div>
            <p><strong>Aucune connexion enregistrée</strong></p>
            <p style="font-size: 14px;">L'historique des connexions sera disponible après la prochaine connexion de l'utilisateur.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="logins-table">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>IP</th>
                        <th>Localisation</th>
                        <th>Appareil</th>
                        <th>Navigateur</th>
                        <th>OS</th>
                        <th>Résolution</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logins as $index => $login): ?>
                        <tr>
                            <td>
                                <strong><?= date('d/m/Y', strtotime($login['created_at'])) ?></strong><br>
                                <small style="color: #666;"><?= date('H:i:s', strtotime($login['created_at'])) ?></small>
                            </td>
                            <td>
                                <span class="ip-badge"><?= htmlspecialchars($login['ip_address'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($login['city']) && !empty($login['country_name'])): ?>
                                    <span class="location-badge">
                                        📍 <?= htmlspecialchars($login['city']) ?>, <?= htmlspecialchars($login['country_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $deviceIcon = match($login['device_type'] ?? 'unknown') {
                                    'mobile' => '📱',
                                    'tablet' => '📱',
                                    'desktop' => '💻',
                                    'bot' => '🤖',
                                    default => '❓'
                                };
                                ?>
                                <span class="device-icon"><?= $deviceIcon ?></span>
                                <?= ucfirst($login['device_type'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($login['browser'] ?? 'N/A') ?> 
                                <small style="color: #666;"><?= htmlspecialchars($login['browser_version'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($login['os'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($login['screen_resolution'])): ?>
                                    <small style="color: #666;"><?= htmlspecialchars($login['screen_resolution']) ?></small>
                                <?php else: ?>
                                    <span style="color: #999;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($login['login_success'] ?? true): ?>
                                    <span class="badge-success">✓ Succès</span>
                                <?php else: ?>
                                    <span class="badge-danger">✗ Échec</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-view-map" 
                                        onclick="openLoginModal(<?= $index ?>)"
                                        <?= (empty($login['latitude']) || empty($login['longitude'])) ? 'disabled title="Pas de coordonnées GPS"' : '' ?>>
                                    🗺️ Voir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal pour afficher la carte d'une connexion -->
<div class="modal-overlay" id="loginModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🌍 Détails de la connexion</h3>
            <button class="modal-close" onclick="closeLoginModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-info-grid" id="modal-info">
                <!-- Infos injectées par JS -->
            </div>
            <div id="modal-map-container">
                <div id="modal-map"></div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet JS via jsDelivr (autorisé par CSP) -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Données des connexions pour le JavaScript
const loginsData = <?= json_encode(array_map(function($login) {
    return [
        'date' => date('d/m/Y à H:i:s', strtotime($login['created_at'])),
        'ip' => $login['ip_address'] ?? 'N/A',
        'city' => $login['city'] ?? 'Inconnue',
        'country' => $login['country_name'] ?? 'Inconnu',
        'lat' => $login['latitude'] ?? null,
        'lng' => $login['longitude'] ?? null,
        'browser' => ($login['browser'] ?? 'N/A') . ' ' . ($login['browser_version'] ?? ''),
        'os' => $login['os'] ?? 'N/A',
        'device' => $login['device_type'] ?? 'N/A',
        'resolution' => $login['screen_resolution'] ?? 'N/A',
        'isp' => $login['isp'] ?? 'N/A',
        'timezone' => $login['timezone'] ?? 'N/A',
        'success' => $login['login_success'] ?? true
    ];
}, $logins)) ?>;

let modalMap = null;

// Ouvrir la modal avec les détails d'une connexion
function openLoginModal(index) {
    const login = loginsData[index];
    if (!login) return;
    
    const modal = document.getElementById('loginModal');
    const infoContainer = document.getElementById('modal-info');
    const mapContainer = document.getElementById('modal-map-container');
    
    // Construire les infos
    infoContainer.innerHTML = `
        <div class="modal-info-item">
            <label>Date & Heure</label>
            <span class="value">${login.date}</span>
        </div>
        <div class="modal-info-item">
            <label>Adresse IP</label>
            <span class="value"><span class="ip-badge">${login.ip}</span></span>
        </div>
        <div class="modal-info-item">
            <label>Localisation</label>
            <span class="value">📍 ${login.city}, ${login.country}</span>
        </div>
        <div class="modal-info-item">
            <label>Navigateur</label>
            <span class="value">${login.browser}</span>
        </div>
        <div class="modal-info-item">
            <label>Système</label>
            <span class="value">${login.os}</span>
        </div>
        <div class="modal-info-item">
            <label>Appareil</label>
            <span class="value">${login.device}</span>
        </div>
        <div class="modal-info-item">
            <label>Résolution</label>
            <span class="value">${login.resolution}</span>
        </div>
        <div class="modal-info-item">
            <label>FAI / ISP</label>
            <span class="value">${login.isp}</span>
        </div>
        <div class="modal-info-item">
            <label>Timezone</label>
            <span class="value">${login.timezone}</span>
        </div>
        <div class="modal-info-item">
            <label>Statut</label>
            <span class="value">${login.success ? '<span class="badge-success">✓ Succès</span>' : '<span class="badge-danger">✗ Échec</span>'}</span>
        </div>
    `;
    
    // Afficher la modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Initialiser ou mettre à jour la carte
    setTimeout(() => {
        if (login.lat && login.lng) {
            mapContainer.innerHTML = '<div id="modal-map"></div>';
            
            if (modalMap) {
                modalMap.remove();
            }
            
            modalMap = L.map('modal-map').setView([login.lat, login.lng], 12);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(modalMap);
            
            const marker = L.marker([login.lat, login.lng]).addTo(modalMap);
            marker.bindPopup(`
                <div style="text-align: center; min-width: 150px;">
                    <strong style="font-size: 14px;">${login.city}</strong><br>
                    <small style="color: #666;">${login.country}</small><br>
                    <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-top: 5px; display: inline-block;">
                        ${login.ip}
                    </code>
                </div>
            `).openPopup();
            
            // Forcer le redimensionnement
            setTimeout(() => {
                modalMap.invalidateSize();
            }, 100);
        } else {
            mapContainer.innerHTML = `
                <div class="modal-no-map">
                    <div class="icon">🗺️</div>
                    <p style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">Carte non disponible</p>
                    <p style="color: #666;">L'IP locale (${login.ip}) ne peut pas être géolocalisée.</p>
                </div>
            `;
        }
    }, 100);
}

// Fermer la modal
function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Nettoyer la carte
    if (modalMap) {
        modalMap.remove();
        modalMap = null;
    }
}

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLoginModal();
    }
});

// Fermer en cliquant sur l'overlay
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLoginModal();
    }
});

// Initialisation de la carte principale (dernière localisation)
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($mapData) && isset($mapData['lat']) && isset($mapData['lng']) && $mapData['lat'] && $mapData['lng']): ?>
    
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('Élément #map non trouvé');
        return;
    }
    
    try {
        const map = L.map('map').setView([<?= $mapData['lat'] ?>, <?= $mapData['lng'] ?>], 10);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        const marker = L.marker([<?= $mapData['lat'] ?>, <?= $mapData['lng'] ?>]).addTo(map);
        
        marker.bindPopup(`
            <div style="text-align: center; min-width: 150px;">
                <strong style="font-size: 14px;"><?= htmlspecialchars($mapData['city'] ?? 'Inconnu', ENT_QUOTES) ?></strong><br>
                <small style="color: #666;"><?= htmlspecialchars($mapData['country'] ?? 'Inconnu', ENT_QUOTES) ?></small><br>
                <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-top: 5px; display: inline-block;">
                    <?= htmlspecialchars($mapData['ip'] ?? 'N/A', ENT_QUOTES) ?>
                </code>
            </div>
        `).openPopup();
        
        setTimeout(function() {
            map.invalidateSize();
        }, 100);
        
    } catch (error) {
        console.error('Erreur carte principale:', error);
    }
    
    <?php endif; ?>
});

// Fonction de suppression utilisateur
function confirmDelete(userId, username) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${username}" ?\n\nCette action est irréversible !`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?= $basePath ?>/admin/users/${userId}/delete`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>