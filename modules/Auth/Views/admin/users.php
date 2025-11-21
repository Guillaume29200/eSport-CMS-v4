<?php
	$pageTitle = 'Gestion des utilisateurs';
	require __DIR__ . '/includes/header.php';
?>
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
        
        .navbar a {
            color: #667eea;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .navbar a:hover {
            background: #f0f0f0;
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .users-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-admin {
            background: #dc3545;
            color: white;
        }
        
        .badge-moderator {
            background: #ff9800;
            color: white;
        }
        
        .badge-member {
            background: #4caf50;
            color: white;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-banned {
            background: #721c24;
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm:hover {
            opacity: 0.8;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-box .label {
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .stat-box .value {
            color: #667eea;
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Modal de confirmation */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .modal-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #fee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .modal-header h2 {
            color: #333;
            font-size: 22px;
        }
        
        .modal-body {
            margin-bottom: 25px;
        }
        
        .modal-body p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .modal-body .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .modal-body .user-info strong {
            color: #dc3545;
        }
        
        .modal-body .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            border-radius: 4px;
            color: #856404;
            font-size: 14px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .btn-confirm-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-confirm-delete:hover {
            background: #c82333;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
    <div class="container">
        <div class="page-header">
            <div>
                <h1>👥 Gestion des Utilisateurs</h1>
                <p style="color: #666; margin-top: 5px;">Gérez les comptes utilisateurs de votre plateforme</p>
            </div>
            <a href="<?= $basePath ?>/admin/users/create" class="btn btn-primary">+ Créer un utilisateur</a>
        </div>
         <!-- Statistiques rapides -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="label">Total utilisateurs</div>
                <div class="value"><?= count($users) ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Admins</div>
                <div class="value"><?= count(array_filter($users, fn($u) => in_array($u['role'], ['admin', 'superadmin']))) ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Membres actifs</div>
                <div class="value"><?= count(array_filter($users, fn($u) => $u['status'] === 'active')) ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Bannis</div>
                <div class="value"><?= count(array_filter($users, fn($u) => $u['status'] === 'banned')) ?></div>
            </div>
        </div>
        
        <!-- Filtres -->
        <div class="filters">
            <div class="filter-group">
                <label>Rechercher</label>
                <input type="text" id="searchInput" placeholder="Nom d'utilisateur ou email..." style="min-width: 250px;">
            </div>
            
            <div class="filter-group">
                <label>Rôle</label>
                <select id="filterRole">
                    <option value="">Tous les rôles</option>
                    <option value="superadmin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="moderator">Modérateur</option>
                    <option value="member">Membre</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Statut</label>
                <select id="filterStatus">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                    <option value="banned">Banni</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>&nbsp;</label>
                <button class="btn btn-primary" onclick="applyFilters()">Filtrer</button>
            </div>
        </div>       
		
		
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                ✅ L'utilisateur <strong><?= htmlspecialchars($_GET['username'] ?? 'inconnu') ?></strong> a été supprimé avec succès.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $errorMsg = match($_GET['error']) {
                    'self_delete' => '❌ Vous ne pouvez pas supprimer votre propre compte.',
                    'not_found' => '❌ Utilisateur introuvable.',
                    'delete_failed' => '❌ Erreur lors de la suppression : ' . ($_GET['reason'] ?? 'inconnue'),
                    default => '❌ Une erreur est survenue.'
                };
                echo $errorMsg;
                ?>
            </div>
        <?php endif; ?>
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Connexions</th>
                        <th>Dernière connexion</th>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php foreach ($users as $user): ?>
                        <tr data-search="<?= strtolower(htmlspecialchars($user['username'] . ' ' . $user['email'])) ?>" 
                            data-role="<?= htmlspecialchars($user['role']) ?>" 
                            data-status="<?= htmlspecialchars($user['status']) ?>">
                            <td><strong>#<?= $user['id'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
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
                            </td>
                            <td>
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
                            </td>
                            <td><?= number_format($user['login_count'] ?? 0) ?></td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?= date('d/m/Y à H:i', strtotime($user['last_login'])) ?>
                                <?php else: ?>
                                    <span style="color: #999;">Jamais</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="<?= $basePath ?>/admin/users/<?= $user['id'] ?>" class="btn-sm btn-info">Voir</a>
                                    <a href="<?= $basePath ?>/admin/users/<?= $user['id'] ?>/edit" class="btn-sm btn-warning">Éditer</a>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>')" 
                                                class="btn-sm btn-danger">Supprimer</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal de confirmation -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">⚠️</div>
                <h2>Confirmer la suppression</h2>
            </div>
            <div class="modal-body">
                <p>Êtes-vous absolument certain de vouloir supprimer cet utilisateur ?</p>
                
                <div class="user-info">
                    <p><strong>Utilisateur :</strong> <span id="deleteUsername"></span></p>
                    <p><strong>Email :</strong> <span id="deleteEmail"></span></p>
                </div>
                
                <div class="warning">
                    <strong>⚠️ Attention :</strong> Cette action est irréversible !
                    <br>Les données suivantes seront supprimées :
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Le compte utilisateur</li>
                        <li>Toutes les sessions actives</li>
                        <li>Les tokens "Se souvenir de moi"</li>
                        <li>Les logs seront anonymisés</li>
                    </ul>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeModal()">Annuler</button>
                <button class="btn btn-confirm-delete" onclick="executeDelete()">Oui, supprimer définitivement</button>
            </div>
        </div>
    </div>
    
    <script>
        let userToDelete = null;
        
        function confirmDelete(userId, username, email) {
            userToDelete = userId;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteEmail').textContent = email;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('deleteModal').classList.remove('show');
            userToDelete = null;
        }
        
        function executeDelete() {
            if (!userToDelete) return;
            
            // Créer et soumettre le formulaire
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `<?= $basePath ?>/admin/users/${userToDelete}/delete`;
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Fermer avec Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
<script>
        // Filtrage en temps réel
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('filterRole').addEventListener('change', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);
        
        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const role = document.getElementById('filterRole').value;
            const status = document.getElementById('filterStatus').value;
            
            const rows = document.querySelectorAll('#usersTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const rowSearch = row.dataset.search;
                const rowRole = row.dataset.role;
                const rowStatus = row.dataset.status;
                
                const matchSearch = !search || rowSearch.includes(search);
                const matchRole = !role || rowRole === role;
                const matchStatus = !status || rowStatus === status;
                
                if (matchSearch && matchRole && matchStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Afficher/masquer le message "Aucun résultat"
            let noResultMsg = document.getElementById('noResultMessage');
            
            if (visibleCount === 0 && rows.length > 0) {
                if (!noResultMsg) {
                    noResultMsg = document.createElement('tr');
                    noResultMsg.id = 'noResultMessage';
                    noResultMsg.innerHTML = `
                        <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                            <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
                            <strong style="font-size: 18px; display: block; margin-bottom: 5px;">Aucun résultat trouvé</strong>
                            <span style="font-size: 14px;">Essayez de modifier vos critères de recherche</span>
                        </td>
                    `;
                    document.getElementById('usersTableBody').appendChild(noResultMsg);
                }
                noResultMsg.style.display = '';
            } else {
                if (noResultMsg) {
                    noResultMsg.style.display = 'none';
                }
            }
            
            // Mettre à jour le compteur (optionnel)
            updateResultCount(visibleCount, rows.length);
        }
        
        function updateResultCount(visible, total) {
            let countElement = document.getElementById('resultCount');
            
            if (!countElement) {
                // Créer l'élément compteur s'il n'existe pas
                countElement = document.createElement('div');
                countElement.id = 'resultCount';
                countElement.style.cssText = 'padding: 10px 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px; color: #666; font-size: 14px;';
                
                const filtersDiv = document.querySelector('.filters');
                filtersDiv.parentNode.insertBefore(countElement, filtersDiv.nextSibling);
            }
            
            if (visible !== total) {
                countElement.textContent = `📊 Affichage de ${visible} utilisateur(s) sur ${total}`;
                countElement.style.display = 'block';
            } else {
                countElement.style.display = 'none';
            }
        }
    </script>
<?php require __DIR__ . '/includes/footer.php'; ?>	