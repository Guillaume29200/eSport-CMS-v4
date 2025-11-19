<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - eSport-CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 32px;
            font-weight: 700;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        input.error {
            border-color: #dc3545;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #666;
            font-size: 14px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: none;
            text-align: center;
            color: white;
        }
        
        .btn-primary.loading .loading {
            display: block;
        }
        
        .btn-primary.loading .btn-text {
            display: none;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>🎮 eSport-CMS</h1>
            <p>Créez votre compte</p>
        </div>
        
        <div id="alert-container"></div>
        
        <form id="registerForm" method="POST" action="register">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name">
                    <div id="error-first_name" class="error-message"></div>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name">
                    <div id="error-last_name" class="error-message"></div>
                </div>
            </div>
            <div class="form-row">
				<div class="form-group">
					<label for="username">Nom d'utilisateur *</label>
					<input type="text" id="username" name="username" required>
					<div id="error-username" class="error-message"></div>
				</div>
				<div class="form-group">
					<label for="email">Adresse email *</label>
					<input type="email" id="email" name="email" required>
					<div id="error-email" class="error-message"></div>
				</div>
            </div>
			<div class="form-row">
				<div class="form-group">
					<label for="password">Mot de passe *</label>
					<input type="password" id="password" name="password" required>
					<div id="error-password" class="error-message"></div>
				</div>
				<div class="form-group">
					<label for="password_confirm">Confirmer le mot de passe *</label>
					<input type="password" id="password_confirm" name="password_confirm" required>
					<div id="error-password_confirm" class="error-message"></div>
				</div>
			</div>
            <button type="submit" class="btn btn-primary">
                <span class="btn-text">Créer mon compte</span>
                <span class="loading">Création en cours...</span>
            </button>
        </form>
        
        <div class="divider">
            <span>OU</span>
        </div>
        
        <div class="login-link">Déjà un compte ? <a href="login">Se connecter</a></div>
    </div>
    
    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const alertContainer = document.getElementById('alert-container');
            
            // Réinitialiser les erreurs
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
            alertContainer.innerHTML = '';
            
            // Désactiver le bouton
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Récupérer les données
            const formData = new FormData(form);
            
            try {
                const response = await fetch('/auth/register', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            ${data.message}
                        </div>
                    `;
                    
                    // Rediriger après 1 seconde
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    // Afficher les erreurs
                    if (data.errors) {
                        for (const [field, message] of Object.entries(data.errors)) {
                            const errorEl = document.getElementById(`error-${field}`);
                            const inputEl = document.getElementById(field);
                            
                            if (errorEl) {
                                errorEl.textContent = message;
                            }
                            if (inputEl) {
                                inputEl.classList.add('error');
                            }
                        }
                    }
                    
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            } catch (error) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        Une erreur s'est produite. Veuillez réessayer.
                    </div>
                `;
                
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
