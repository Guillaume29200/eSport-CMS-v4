# 🎮 eSport-CMS V4 - Framework

**Build:** Dev  
**Version:** 4.0.0  
**Auteur:** Guillaume  
**Licence:** Propriétaire
---

## 📋 Description

Framework moderne et sécurisé pour le CMS eSport-CMS V4.

**Caractéristiques:**
- ✅ **Sécurité native** (CSRF, XSS, Rate Limiting, Sessions sécurisées)
- ✅ **Debug Bar** pour développement
- ✅ **Système de modules** extensible
- ✅ **Tracking auth** complet (IP, device, géoloc)
- ✅ **Multi-environnement** (dev/staging/prod)
- ✅ **Logging** avancé

---

## 🚀 Installation

### 1. Prérequis

- PHP >= 8.4
- Extensions: PDO, mbstring, curl, gd, zip, openssl
- MySQL >= 5.7 ou PostgreSQL >= 10 ou SQLite 3
- Apache (mod_rewrite) ou Nginx

### 3. Configuration Apache

Le fichier `.htaccess` est fourni. Vérifier que `mod_rewrite` est activé:

```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### 4. Configuration Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}

location ~ /\. {
    deny all;
}
```

---

## 🏗️ Structure

```
esport-cms-v4/
├── index.php                    # Point d'entrée
├── routes.php                   # Routes Principales (Système)
├── .htaccess                    # Config Apache
├── .env                         # Configuration
│
├── /framework/                  # Framework core
│   ├── /logs/                   # Logs
│   ├── /cache/                  # cache
│   ├── /config/                 # Configurations
│   ├── /Interfaces/             # Contrats modules
│   ├── /Services/               # Services centraux
│   ├── /Security/               # Couche sécurité
│   ├── /ModuleManager/          # Gestionnaire modules
│   └── /Views/                  # Templates framework
│
├── /modules/                    # Modules
├── /themes/                     # Thèmes
```

---

## 🔐 Sécurité

### CSRF Protection

Tous les formulaires doivent inclure un token CSRF:

```php
<?php
use Framework\Security\CSRFProtection;

$csrf = new CSRFProtection($securityConfig);
?>

<form method="POST">
    <?= $csrf->getTokenInput() ?>
    <!-- vos champs -->
</form>
```

Validation côté serveur:

```php
$csrf->validateRequest(); // Throw exception si invalide
```

### XSS Protection

Toutes les entrées sont automatiquement filtrées. Pour afficher:

```php
<?php
use Framework\Security\XSSProtection;

$xss = new XSSProtection($securityConfig);
echo $xss->escape($userInput); // Sécurisé
?>
```

### Rate Limiting

Protéger actions sensibles:

```php
<?php
use Framework\Security\RateLimiter;

$rateLimiter = new RateLimiter($db, $securityConfig);

// Vérifier limite
$rateLimiter->check($_SERVER['REMOTE_ADDR'], 'login');

// Incrémenter compteur
$rateLimiter->increment($_SERVER['REMOTE_ADDR'], 'login');
?>
```

---

## 🧩 Créer un Module

### 1. Structure

```
/modules/MonModule/
├── module.json              # Métadonnées
├── MonModule.php            # Classe principale
├── /Controllers/
├── /Services/
├── /Views/
└── /assets/
```

### 2. module.json

```json
{
  "name": "MonModule",
  "version": "1.0.0",
  "description": "Description du module",
  "author": "Votre nom",
  "class": "MonModule\\MonModule",
  "requires": {
    "cms_version": ">=4.0.0",
    "php_version": ">=8.4"
  }
}
```

### 3. Classe principale

```php
<?php
namespace MonModule;

use Framework\Interfaces\BaseModule;
use Framework\Services\Router;

class MonModule extends BaseModule
{
    public function getName(): string
    {
        return 'MonModule';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function getDescription(): string
    {
        return 'Mon super module';
    }
    
    public function init(): void
    {
        // Initialisation
    }
    
    public function registerRoutes($router): void
    {
        $router->get('/mon-module', 'MonModule\\Controllers\\MainController@index');
    }
    
    public function getHooks(): array
    {
        return [
            'admin.menu' => [[$this, 'addMenu'], 10],
        ];
    }
    
    public function install(): bool
    {
        // Créer tables, etc.
        return true;
    }
}
```

---

## 🐛 Debug Bar

En mode **development**, une console de debug s'affiche en bas de page avec:

- ⏱️ Temps de chargement
- 💾 Mémoire utilisée
- 🗄️ Requêtes SQL (avec détection slow queries)
- 🔒 Checks de sécurité
- 📝 Logs
- 🔧 Fichiers inclus

Pour désactiver: `APP_ENV=production` dans `.env`

---

## 📊 Tracking Auth

Le framework track automatiquement:

- IP + géolocalisation (pays, ville, lat/long)
- Navigateur + version
- OS
- Type device (desktop/mobile/tablet)
- Résolution écran (via JS)
- Timezone

Utilisation:

```php
<?php
use Framework\Services\AuthTracker;

$tracker = new AuthTracker($db);

// Tracker inscription
$tracker->trackRegistration($userId, $screenResolution);

// Tracker connexion
$tracker->trackLogin($userId, true, $screenResolution);

// Détecter connexion suspecte
$suspicious = $tracker->detectSuspiciousLogin($userId);
?>
```

---

## 📝 Logging

```php
<?php
use Framework\Services\Logger;

$logger = new Logger($db, $config);

// Niveaux
$logger->debug('Message debug');
$logger->info('Message info');
$logger->warning('Message warning');
$logger->error('Message erreur');
$logger->critical('Message critique');
$logger->security('Événement sécurité');

// Logger activité utilisateur
$logger->logActivity($userId, 'action_name', ['detail' => 'value']);
?>
```

---

## 🔄 Environnements

### Development
- Erreurs affichées
- Debug bar activée
- Queries loggées
- Cache désactivé

### Staging
- Erreurs affichées
- Debug bar activée (tests)
- Queries loggées
- Cache activé

### Production
- Erreurs masquées
- Debug bar désactivée
- Queries non loggées
- Cache activé

Changer via `.env`: `APP_ENV=production`

---

**Made with 💙 by Guillaume - eSport-CMS V4**
