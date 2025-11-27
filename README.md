# 🎮 eSport-CMS V4

> Framework PHP moderne et modulaire pour la création de sites eSport

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-Proprietary-red)]()
[![Version](https://img.shields.io/badge/Version-4.0.0-green)]()

---

## 📋 Table des matières

- [Description](#-description)
- [Caractéristiques](#-caractéristiques)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Modules](#-modules)
- [Sécurité](#-sécurité)
- [Développement](#-développement)
- [Déploiement](#-déploiement)

---

## 🎯 Description

**eSport-CMS V4** est un framework PHP moderne conçu spécifiquement pour les sites eSport et gaming. Il combine performance, sécurité et extensibilité grâce à une architecture modulaire innovante.

### Points forts

- ✅ **Architecture modulaire** - Chaque module est autonome avec son propre routing
- ✅ **Sécurité native** - CSRF, XSS, Rate Limiting, Sessions sécurisées
- ✅ **Developer-friendly** - Debug Bar, logs détaillés, environnements multiples
- ✅ **Production-ready** - Optimisé pour la performance et la scalabilité
- ✅ **Tracking avancé** - Géolocalisation, device detection, analytics

---

## ⚡ Caractéristiques

### Core Features

| Feature | Description |
|---------|-------------|
| **Routing dynamique** | Système de routes avec groupes, middlewares et paramètres |
| **Modules autonomes** | Chaque module a son propre `routes.php`, contrôleurs, services |
| **Debug Bar** | Console de développement avec SQL queries, performance, logs |
| **Multi-environnement** | Dev, Staging, Production avec configs différenciées |
| **Session Management** | Sessions sécurisées avec tracking IP, device, géolocalisation |
| **Rate Limiting** | Protection contre le brute force et DDoS |
| **Logging avancé** | Niveaux multiples (debug, info, warning, error, critical) |
| **CSRF Protection** | Tokens automatiques sur tous les formulaires |
| **XSS Prevention** | Filtrage automatique des entrées utilisateur |

### Modules Inclus

#### 🔐 **Auth** (Système d'authentification)
- Inscription / Connexion / Déconnexion
- Gestion des rôles (superadmin, admin, moderator, member)
- Dashboard admin complet
- Tracking des connexions (IP, device, géoloc)
- Reset password
- Remember me
- Rate limiting sur login

#### 💎 **PremiumManager** (Système premium)
- Gestion des plans d'abonnement (Bronze, Silver, Gold)
- Paiements Stripe & PayPal
- Gestion des transactions
- Système de coupons
- Contenus premium verrouillés
- Dashboard analytics
- Webhooks pour paiements automatiques

---

## 🏗️ Architecture

### Structure du projet

```
esport-cms-v4/
│
├── index.php                    # Point d'entrée unique
├── routes.php                   # Routes système (accueil, pages globales)
├── .htaccess                    # Configuration Apache
├── .env                         # Variables d'environnement
│
├── /framework/                  # Cœur du framework
│   ├── /config/                 # Configurations
│   │   ├── database.php
│   │   ├── environment.php
│   │   └── security.php
│   │
│   ├── /Services/               # Services centraux
│   │   ├── Database.php         # Gestionnaire BDD
│   │   ├── Router.php           # Système de routing
│   │   ├── Logger.php           # Système de logs
│   │   ├── DebugBar.php         # Console debug
│   │   └── AuthTracker.php      # Tracking authentification
│   │
│   ├── /Security/               # Couche sécurité
│   │   ├── CSRFProtection.php   # Anti-CSRF
│   │   ├── XSSProtection.php    # Anti-XSS
│   │   ├── RateLimiter.php      # Rate limiting
│   │   └── InputValidator.php   # Validation entrées
│   │
│   ├── /ModuleManager/          # Gestionnaire de modules
│   │   └── ModuleManager.php
│   │
│   ├── /Interfaces/             # Contrats pour modules
│   │   └── BaseModule.php
│   │
│   └── /logs/                   # Logs système
│
├── /modules/                    # Modules (plugins)
│   │
│   ├── /Auth/                   # Module authentification
│   │   ├── module.json          # Métadonnées du module
│   │   ├── Auth.php             # Classe principale
│   │   ├── routes.php           # Routes du module ⭐
│   │   ├── /Controllers/
│   │   ├── /Services/
│   │   ├── /Views/
│   │   ├── /Middleware/
│   │   └── /Hooks/
│   │
│   └── /PremiumManager/         # Module premium
│       ├── module.json
│       ├── PremiumManager.php
│       ├── routes.php           # Routes du module ⭐
│       ├── /Controllers/
│       │   ├── /Admin/          # Controllers admin
│       │   ├── /Front/          # Controllers frontend
│       │   └── /API/            # Controllers API
│       ├── /Services/
│       ├── /Models/
│       └── /Views/
│
├── /themes/                     # Thèmes (templates)
│
└── /install/                    # Installation
    └── install.php
```

### Flow de routing

```
┌─────────────────────────────────────────────┐
│  index.php (Point d'entrée unique)          │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Autoloader + Configuration                 │
│  - Charge .env                              │
│  - Charge configs (DB, Security)            │
│  - Init services (Database, Router, Logger) │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  ModuleManager                              │
│  - Scanne /modules/                         │
│  - Charge tous les modules actifs           │
│  - Init chaque module                       │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Enregistrement des routes                  │
│  1. Routes modules (Auth, Premium...)       │
│  2. Routes système (/routes.php)            │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Router->dispatch($method, $uri)            │
│  - Match la route                           │
│  - Exécute le contrôleur                    │
│  - Retourne la réponse                      │
└─────────────────────────────────────────────┘
```

**🔑 Clé de l'architecture :**
- Chaque module a son propre fichier `routes.php`
- Les modules sont **totalement autonomes**
- Le framework charge automatiquement tous les modules
- Les routes système dans `/routes.php` sont pour les pages globales uniquement

---

## 🚀 Installation

### Prérequis

- **PHP** >= 8.4
- **Extensions PHP** : PDO, mbstring, curl, gd, zip, openssl, intl
- **Base de données** : MySQL >= 5.7, PostgreSQL >= 10, ou SQLite 3
- **Serveur Web** : Apache (mod_rewrite) ou Nginx

### Étape 1 : Télécharger

```bash
git clone https://github.com/Guillaume29200/eSport-CMS-V4.git
cd eSport-CMS-V4
```

### Étape 2 : Configuration

Copier le fichier exemple :
```bash
cp exemple.env .env
```

Éditer `.env` :
```env
# Application
APP_NAME="eSport-CMS"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Europe/Paris

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecms_v4
DB_USERNAME=root
DB_PASSWORD=

# Sécurité
SECURITY_KEY=votre_clé_secrète_unique_ici
SESSION_LIFETIME=7200
CSRF_TOKEN_LENGTH=32
```

### Étape 3 : Base de données

Importer le schéma SQL :
```bash
mysql -u root -p ecms_v4 < install/database.sql
```

### Étape 4 : Permissions

```bash
chmod 755 framework/logs
chmod 755 modules/*/uploads
```

### Étape 5 : Apache

Vérifier que `mod_rewrite` est activé :
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

Le fichier `.htaccess` est déjà configuré.

### Étape 6 : Nginx (optionnel)

Configuration Nginx :
```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/esport-cms-v4;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## ⚙️ Configuration

### Environnements

Le CMS supporte 3 environnements :

#### 🛠️ Development
```env
APP_ENV=development
APP_DEBUG=true
```
- Erreurs affichées en détail
- Debug Bar activée
- Queries SQL loggées
- Cache désactivé

#### 🧪 Staging
```env
APP_ENV=staging
APP_DEBUG=true
```
- Environnement de test pré-production
- Debug Bar activée pour tests
- Queries loggées
- Cache activé

#### 🚀 Production
```env
APP_ENV=production
APP_DEBUG=false
```
- Erreurs masquées (loggées uniquement)
- Debug Bar désactivée
- Performance optimale
- Cache activé

### Base de données

Configurations supportées dans `/framework/config/database.php` :

```php
// MySQL
'connection' => 'mysql',
'host' => '127.0.0.1',
'port' => 3306,

// PostgreSQL
'connection' => 'pgsql',
'host' => '127.0.0.1',
'port' => 5432,

// SQLite
'connection' => 'sqlite',
'database' => ROOT_PATH . '/database/database.sqlite',
```

### Sécurité

Configuration dans `/framework/config/security.php` :

```php
'csrf' => [
    'token_name' => 'csrf_token',
    'token_length' => 32,
    'expire' => 3600,
],
'xss' => [
    'allowed_tags' => '<b><i><u><strong><em><a><br><p>',
],
'rate_limiting' => [
    'enabled' => true,
    'limits' => [
        'login' => ['max' => 5, 'window' => 900],
        'api' => ['max' => 100, 'window' => 60],
    ],
],
```

---

## 🧩 Modules

### Créer un module

#### 1. Structure de base

```
/modules/MonModule/
├── module.json              # Métadonnées
├── MonModule.php            # Classe principale
├── routes.php               # Routes du module ⭐
├── /Controllers/
│   ├── MainController.php
│   └── /Admin/
├── /Services/
├── /Models/
├── /Views/
└── /assets/
```

#### 2. module.json

```json
{
  "name": "MonModule",
  "version": "1.0.0",
  "description": "Description de mon module",
  "author": "Votre Nom",
  "class": "MonModule\\MonModule",
  "requires": {
    "cms_version": ">=4.0.0",
    "php_version": ">=8.4"
  },
  "permissions": [
    "monmodule.view",
    "monmodule.edit"
  ],
  "hooks": [
    "admin.menu"
  ]
}
```

#### 3. Classe principale (MonModule.php)

```php
<?php
namespace MonModule;

use Framework\Interfaces\BaseModule;

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
        // Initialisation du module
    }
    
    /**
     * Enregistre les routes du module
     * Le fichier routes.php est chargé automatiquement
     */
    public function registerRoutes($router): void
    {
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }
    
    public function getHooks(): array
    {
        return [
            'admin.menu' => [[$this, 'addAdminMenu'], 10],
        ];
    }
    
    public function addAdminMenu(array $menu): array
    {
        $menu[] = [
            'title' => 'Mon Module',
            'url' => '/mon-module',
            'icon' => 'bi-puzzle',
        ];
        return $menu;
    }
    
    public function install(): bool
    {
        // Créer les tables, etc.
        return true;
    }
    
    public function uninstall(): bool
    {
        // Nettoyer les tables, etc.
        return true;
    }
}
```

#### 4. Fichier de routes (routes.php) ⭐

```php
<?php
/**
 * Routes du module MonModule
 * 
 * Ce fichier est chargé automatiquement par le ModuleManager
 * Toutes les routes ici sont préfixées par le namespace du module
 */

// Routes publiques
$router->get('/mon-module', 'MonModule\\Controllers\\MainController@index');
$router->get('/mon-module/about', 'MonModule\\Controllers\\MainController@about');

// Routes avec paramètres
$router->get('/mon-module/{id}', 'MonModule\\Controllers\\MainController@show');

// Routes avec groupe (préfixe)
$router->group('/mon-module/admin', function($router) {
    $router->get('/', 'MonModule\\Controllers\\Admin\\AdminController@dashboard');
    $router->get('/settings', 'MonModule\\Controllers\\Admin\\AdminController@settings');
    $router->post('/settings/save', 'MonModule\\Controllers\\Admin\\AdminController@save');
});

// Routes API
$router->group('/api/mon-module', function($router) {
    $router->get('/data', 'MonModule\\Controllers\\API\\ApiController@getData');
    $router->post('/data', 'MonModule\\Controllers\\API\\ApiController@postData');
});
```

#### 5. Contrôleur

```php
<?php
namespace MonModule\Controllers;

use Framework\Services\Database;

class MainController
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    public function index(): void
    {
        $data = ['message' => 'Hello from MonModule!'];
        include __DIR__ . '/../Views/index.php';
    }
    
    public function show(int $id): void
    {
        $item = $this->db->queryOne(
            "SELECT * FROM items WHERE id = ?",
            [$id]
        );
        
        if (!$item) {
            http_response_code(404);
            echo "Item not found";
            return;
        }
        
        include __DIR__ . '/../Views/show.php';
    }
}
```

---

## 🔐 Sécurité

### CSRF Protection

Tous les formulaires POST doivent inclure un token CSRF :

```php
<?php
use Framework\Security\CSRFProtection;

// Dans le contrôleur
$csrf = new CSRFProtection($securityConfig);
$csrfToken = $csrf->getToken('my_form');
?>

<!-- Dans la vue -->
<form method="POST" action="/save">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <!-- autres champs -->
    <button type="submit">Envoyer</button>
</form>
```

Validation côté serveur :

```php
// Méthode 1 : Validation automatique depuis $_POST
try {
    $csrf->validateRequest('my_form');
    // Token valide, continuer
} catch (\Exception $e) {
    http_response_code(403);
    die('CSRF validation failed');
}

// Méthode 2 : Validation manuelle
$token = $_POST['csrf_token'] ?? '';
if (!$csrf->validateToken($token, 'my_form')) {
    http_response_code(403);
    die('Invalid CSRF token');
}
```

### XSS Protection

```php
use Framework\Security\XSSProtection;

$xss = new XSSProtection($securityConfig);

// Échapper output
echo $xss->escape($userInput);

// Nettoyer HTML (permet tags autorisés)
echo $xss->clean($htmlContent);
```

### Rate Limiting

```php
use Framework\Security\RateLimiter;

$rateLimiter = new RateLimiter($db, $securityConfig);
$identifier = $_SERVER['REMOTE_ADDR']; // ou user ID

try {
    $rateLimiter->check($identifier, 'login');
    // OK, continuer
    $rateLimiter->increment($identifier, 'login');
} catch (\Exception $e) {
    http_response_code(429);
    die('Too many attempts. Please wait.');
}
```

### Sessions Sécurisées

Les sessions sont automatiquement sécurisées :
- Regénération ID après login
- HttpOnly cookies
- SameSite=Strict
- Expiration configurableTracking IP/Device
- Détection connexions suspectes

---

## 🛠️ Développement

### Debug Bar

En mode **development**, une console s'affiche en bas de page :

- ⏱️ **Performance** : Temps d'exécution, mémoire
- 🗄️ **SQL Queries** : Toutes les requêtes avec temps d'exécution
- 🔒 **Security** : Vérifications CSRF, session status
- 📝 **Logs** : Tous les logs de la requête
- 📦 **Modules** : Modules chargés
- 🔧 **Files** : Fichiers inclus

### Logging

```php
use Framework\Services\Logger;

$logger = new Logger($db, $config);

// Différents niveaux
$logger->debug('Debug info', ['var' => $value]);
$logger->info('Something happened');
$logger->warning('Warning message');
$logger->error('Error occurred', ['error' => $e->getMessage()]);
$logger->critical('Critical error!');
$logger->security('Security event', ['ip' => $ip]);

// Logger activité utilisateur
$logger->logActivity($userId, 'user.login', [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'success' => true
]);
```

### Tracking Auth

```php
use Framework\Services\AuthTracker;

$tracker = new AuthTracker($db);

// Tracker inscription
$tracker->trackRegistration($userId, $screenResolution);

// Tracker connexion
$success = true; // ou false si échec
$tracker->trackLogin($userId, $success, $screenResolution);

// Détecter connexions suspectes
$suspicious = $tracker->detectSuspiciousLogin($userId);
if ($suspicious) {
    // Envoyer email alerte, demander 2FA, etc.
}

// Obtenir historique
$history = $tracker->getLoginHistory($userId, 50);
```

### Routing avancé

```php
// Route simple
$router->get('/page', 'Controller@method');

// Route avec paramètre
$router->get('/user/{id}', 'UserController@show');

// Route avec paramètre optionnel
$router->get('/posts/{id?}', 'PostController@index');

// Routes multiples méthodes
$router->match(['GET', 'POST'], '/form', 'FormController@handle');

// Groupe de routes (préfixe commun)
$router->group('/admin', function($router) {
    $router->get('/users', 'Admin\\UserController@index');
    $router->get('/settings', 'Admin\\SettingsController@index');
});

// Groupes imbriqués
$router->group('/api', function($router) {
    $router->group('/v1', function($router) {
        $router->get('/users', 'API\\V1\\UserController@index');
    });
});
```

---

## 🚀 Déploiement

### Checklist Production

- [ ] `APP_ENV=production` dans `.env`
- [ ] `APP_DEBUG=false`
- [ ] Générer clé sécurité unique
- [ ] Configurer HTTPS
- [ ] Activer cache OPcache
- [ ] Configurer cron jobs (si nécessaire)
- [ ] Backup automatique BDD
- [ ] Monitoring erreurs (Sentry, etc.)

### Optimisations

```bash
# OPcache PHP
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000

# Répertoires en cache
chmod 755 -R framework/cache/
```

### Sécurité Production

```apache
# .htaccess
<FilesMatch "\.(env|log|json|md)$">
    Require all denied
</FilesMatch>

# Empêcher listage dossiers
Options -Indexes

# Protection .git
RedirectMatch 404 /\.git
```

---

## 📚 Documentation des modules

### Auth Module

Voir [modules/Auth/README.md](modules/Auth/README.md)

- Inscription/Connexion
- Gestion utilisateurs
- Rôles et permissions
- Dashboard admin
- Tracking avancé

### PremiumManager Module

Voir [modules/PremiumManager/README.md](modules/PremiumManager/README.md)

- Plans d'abonnement
- Paiements Stripe/PayPal
- Gestion transactions
- Système de coupons
- Contenus premium

---

## 🤝 Contribution

Ce projet est **propriétaire** et n'accepte pas de contributions externes.

Pour toute question : contact@esport-cms.com

---

## 📄 Licence

Copyright © 2025 Guillaume - eSport-CMS  
Tous droits réservés.

Ce logiciel est propriétaire et ne peut être copié, modifié ou distribué sans autorisation écrite.

---

## 🔗 Liens

- 🌐 **Site web** : [esport-cms.com](https://esport-cms.com)
- 📧 **Support** : contact@esport-cms.com
- 📖 **Documentation** : [docs.esport-cms.com](https://docs.esport-cms.com)

---

**Made with 💙 by Guillaume**
