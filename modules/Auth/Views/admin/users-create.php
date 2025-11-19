<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Utilisateur - eSport-CMS</title>
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
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
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
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        label .required {
            color: #dc3545;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .error-message {
            font-size: 12px;
            color: #dc3545;
            margin-top: 5px;
            display: none;
        }
        
        .form-group.has-error input,
        .form-group.has-error select {
            border-color: #dc3545;
        }
        
        .form-group.has-error .error-message {
            display: block;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            text-decoration: none;
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            color: #1976d2;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .info-box p {
            color: #1565c0;
            font-size: 13px;
            margin: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🎮 eSport-CMS - Admin</h1>
        <div style="display: flex; gap: 15px;">
            <a href="../users">← Retour aux utilisateurs</a>
            <a href="dashboard">Dashboard</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>➕ Créer un Nouvel Utilisateur</h1>
            <p>Ajoutez un nouveau compte utilisateur à la plateforme</p>
        </div>
        
        <div id="alert-container"></div>
        
        <div class="form-card">
            <form id="createUserForm" method="POST" action="create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <!-- Informations de base -->
                <div class="form-section">
                    <h2>📋 Informations de Base</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur <span class="required">*</span></label>
                            <input type="text" id="username" name="username" required minlength="3" maxlength="50" autocomplete="off">
                            <div class="help-text">3-50 caractères, lettres, chiffres, - et _ uniquement</div>
                            <div class="error-message">Ce champ est requis</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required autocomplete="off">
                            <div class="help-text">Adresse email valide</div>
                            <div class="error-message">Email invalide</div>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" maxlength="100">
                        </div>
                    </div>
                </div>
                
                <!-- Mot de passe -->
                <div class="form-section">
                    <h2>🔒 Mot de Passe</h2>
                    <div class="info-box">
                        <h3>💡 Conseil</h3>
                        <p>Utilisez un mot de passe fort avec au moins 8 caractères, incluant majuscules, minuscules, chiffres et caractères spéciaux.</p>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password">Mot de passe <span class="required">*</span></label>
                            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="help-text" id="strengthText">Minimum 8 caractères</div>
                        </div>
                        <div class="form-group">
                            <label for="password_confirm">Confirmer le mot de passe <span class="required">*</span></label>
                            <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                            <div class="error-message" id="passwordMatchError">Les mots de passe ne correspondent pas</div>
                        </div>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="send_email" name="send_email" checked>
                        <label for="send_email">Envoyer les identifiants par email à l'utilisateur</label>
                    </div>
                </div>
                
                <!-- Rôle et statut -->
                <div class="form-section">
                    <h2>👤 Rôle et Permissions</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="role">
                                Rôle <span class="required">*</span>
                            </label>
                            <select id="role" name="role" required>
                                <option value="member">Membre</option>
                                <option value="moderator">Modérateur</option>
                                <option value="admin">Administrateur</option>
                                <option value="superadmin">Super Administrateur</option>
                            </select>
                            <div class="help-text">Définit les permissions de l'utilisateur</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Statut <span class="required">*</span></label>
                            <select id="status" name="status" required>
                                <option value="active" selected>Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                            <div class="help-text">Un compte inactif ne peut pas se connecter</div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="form-actions">
                    <a href="users" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">✅ Créer l'Utilisateur</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Vérification de la force du mot de passe
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Mot de passe faible';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Mot de passe moyen';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Mot de passe fort';
                strengthText.style.color = '#28a745';
            }
            
            // Vérifier si les mots de passe correspondent
            checkPasswordMatch();
        });
        
        // Vérifier la correspondance des mots de passe
        document.getElementById('password_confirm').addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            const confirmGroup = document.getElementById('password_confirm').closest('.form-group');
            
            if (confirm && password !== confirm) {
                confirmGroup.classList.add('has-error');
            } else {
                confirmGroup.classList.remove('has-error');
            }
        }
        
        // Soumission du formulaire
        document.getElementById('createUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = document.getElementById('submitBtn');
            const alertContainer = document.getElementById('alert-container');
            
            // Vérifier que les mots de passe correspondent
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            
            if (password !== confirm) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        ❌ Les mots de passe ne correspondent pas
                    </div>
                `;
                return;
            }
            
            // Désactiver le bouton
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Création en cours...';
            
            // Récupérer les données
            const formData = new FormData(form);
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            ✅ ${data.message}
                        </div>
                    `;
                    
                    // Rediriger après 2 secondes
                    setTimeout(() => {
                        window.location.href = data.redirect || 'users';
                    }, 2000);
                } else {
                    // Afficher les erreurs
                    let errorHtml = '<div class="alert alert-error"><strong>❌ Erreurs détectées :</strong><ul style="margin: 10px 0 0 20px;">';
                    
                    if (data.errors) {
                        for (const [field, error] of Object.entries(data.errors)) {
                            errorHtml += `<li>${error}</li>`;
                            
                            // Marquer le champ en erreur
                            const input = document.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.closest('.form-group').classList.add('has-error');
                            }
                        }
                    } else {
                        errorHtml += `<li>${data.error || 'Une erreur est survenue'}</li>`;
                    }
                    
                    errorHtml += '</ul></div>';
                    alertContainer.innerHTML = errorHtml;
                    
                    submitBtn.disabled = false;
                    submitBtn.textContent = '✅ Créer l\'Utilisateur';
                    
                    // Scroll vers le haut
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch (error) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        ❌ Une erreur est survenue : ${error.message}
                    </div>
                `;
                
                submitBtn.disabled = false;
                submitBtn.textContent = '✅ Créer l\'Utilisateur';
            }
        });
        
        // Retirer l'erreur quand on modifie un champ
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', function() {
                this.closest('.form-group').classList.remove('has-error');
            });
        });
    </script>
</body>
</html>