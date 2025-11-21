<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - eSport-CMS</title>
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
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
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
        
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
        }
        
        .forgot-password a:hover {
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🎮 eSport-CMS</h1>
            <p>Connectez-vous à votre compte</p>
        </div>
        
        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success">
                Vous avez été déconnecté avec succès.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">
                Inscription réussie ! Vous pouvez maintenant vous connecter.
            </div>
        <?php endif; ?>
        
        <div id="alert-container"></div>
        
        <form id="loginForm" method="POST" action="login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            <!-- Champ caché pour la résolution d'écran -->
            <input type="hidden" name="screen_resolution" id="screen_resolution" value="">
            
            <div class="form-group">
                <label for="identifier">Nom d'utilisateur ou Email</label>
                <input type="text" id="identifier" name="identifier" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="forgot-password">
                <a href="forgot-password">Mot de passe oublié ?</a>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Se souvenir de moi</label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <span class="btn-text">Se connecter</span>
                <span class="loading">Connexion en cours...</span>
            </button>
        </form>
        
        <div class="divider">
            <span>OU</span>
        </div>
        
        <div class="register-link">
            Pas encore de compte ? <a href="register">S'inscrire</a>
        </div>
    </div>
    
    <script>
        // Récupérer la résolution d'écran au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const screenResolution = window.screen.width + 'x' + window.screen.height;
            document.getElementById('screen_resolution').value = screenResolution;
            console.log('Résolution détectée:', screenResolution);
        });
        
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const alertContainer = document.getElementById('alert-container');
            
            // Désactiver le bouton
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Récupérer les données
            const formData = new FormData(form);
            
            // DEBUG: Afficher ce qui est envoyé
            console.log('=== LOGIN DEBUG ===');
            console.log('Form data:', {
                identifier: formData.get('identifier'),
                password: formData.get('password') ? '***' : 'EMPTY',
                csrf_token: formData.get('csrf_token'),
                remember_me: formData.get('remember_me'),
                screen_resolution: formData.get('screen_resolution')
            });
            
            try {
                console.log('Sending request to:', form.action);
                
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                console.log('Response OK:', response.ok);
                
                // Parser directement en JSON
                const data = await response.json();
                console.log('Parsed JSON:', data);
                
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
                    alertContainer.innerHTML = `
                        <div class="alert alert-error">
                            ${data.error}
                        </div>
                    `;
                    
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('=== LOGIN ERROR ===');
                console.error('Error type:', error.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        Une erreur s'est produite. Veuillez réessayer.<br>
                        <small style="color: #999; font-size: 12px;">Détails: ${error.message}</small>
                    </div>
                `;
                
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>