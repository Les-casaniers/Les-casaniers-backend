# 🚀 Setup & Installation - Authentification

## Prérequis

- Laravel 11+
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js (pour Sanctum)

## Installation

### 1. Installation des dépendances

```bash
# Installer Composer
composer install

if (Test-Path .\vendor\laravel\framework) { Remove-Item -Recurse -Force .\vendor\laravel\framework }; composer install

# Installer les dépendances npm
npm install
```

# Upload et stockage des images

composer require spatie/laravel-image-optimizer

### 2. Configuration de l'environnement

```bash
# Copier le fichier .env
cp .env.example .env

# Générer la clé APP
php artisan key:generate

# Configurer la base de données dans .env
DB_HOST=les-casaniers-tonny-e980.b.aivencloud.com
DB_PORT=27187
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=your_password
```



### 3. Configuration de Sanctum

```bash
# Publier la configuration de Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 4. Exécuter les migrations

```bash
# Créer toutes les tables de la base de données
php artisan migrate

# OU exécuter le script SQL complet
mysql -h les-casaniers-tonny-e980.b.aivencloud.com -P 27187 -u avnadmin -p defaultdb < database/script.sql
```

### 5. Créer un administrateur initial (seeders)

```bash
# Créer un seeder pour les administrateurs
php artisan make:seeder AdminSeeder

# Exécuter les seeders
php artisan db:seed
```

### 6. Démarrer le serveur

```bash
# En développement
php artisan serve

# Le serveur sera disponible sur http://localhost:8000
```
### 7. To start the WebSocket
```bash
php artisan websocket:serve --port=8090

---

## 📋 Architecture de l'authentification

### Fichiers principaux

```
app/
├── Models/
│   ├── User.php                          # Modèle utilisateur
│   ├── Admin.php                         # Modèle administrateur
│   └── PasswordReset.php                 # Modèle pour tokens de reset
├── Services/Auth/
│   ├── AuthService.php                   # Service d'authentification utilisateur
│   ├── AdminAuthService.php              # Service d'authentification admin
│   └── PasswordResetService.php          # Service de réinitialisation mot de passe
├── Http/
│   ├── Controllers/Api/Auth/
│   │   ├── UserAuthController.php        # Contrôleur authentification utilisateur
│   │   └── AdminAuthController.php       # Contrôleur authentification admin
│   └── Requests/Auth/
│       ├── RegisterRequest.php           # Validation inscription
│       ├── LoginRequest.php              # Validation connexion + rate limiting
│       ├── UpdateProfileRequest.php      # Validation mise à jour profil
│       └── ChangePasswordRequest.php     # Validation changement mot de passe
└── Exceptions/
    └── Handler.php                       # Gestion des exceptions

database/
├── migrations/
│   └── 2024_01_01_000000_create_password_resets_table.php
└── script.sql                            # Schéma complet de la base de données

routes/
└── api.php                               # Routes API d'authentification

documentation/
└── API_AUTHENTICATION.md                 # Documentation complète des endpoints
```

### Flux d'authentification

#### Inscription

```
[Frontend] POST /api/auth/users/register
    ↓ (prenom, nom, email, mot_de_passe)
[UserAuthController.register()]
    ↓
[AuthService.register()]
    ↓ Hash::make() - bcrypt password
[User.create()] - Insère dans la table 'utilisateurs'
    ↓
[Response 201] - User data + token optionnel
```

#### Connexion

```
[Frontend] POST /api/auth/users/login
    ↓ (email, mot_de_passe)
[LoginRequest::ensureIsNotRateLimited()] - Rate limiting 5 attempts/60s
    ↓
[UserAuthController.login()]
    ↓
[AuthService.login()]
    ↓ Valide email + Hash::check() mot de passe
[AuthService.createToken()] - Sanctum token
    ↓
[Response 200] - User data + token JWT
```

#### Token Management (Sanctum)

```
Laravel Sanctum génère des tokens stateless (JWT)
Chaque token est valide jusqu'à son expiration
Les tokens sont stockés en base de données (personal_access_tokens table)
Révocation immédiate possible en supprimant le token de la base
```

---

## 🔒 Sécurité

### Configurations appliquées

#### Rate Limiting (LoginRequest)
```php
$request->ensureIsNotRateLimited(); // 5 tentatives par 60 secondes par IP
```

#### Hachage des mots de passe
```php
Hash::make($password) // bcrypt avec salt unique
Hash::check($password, $hash) // Vérification sécurisée
```

#### Protection CSRF
```php
// Tous les endpoints POST/PUT/DELETE requièrent le token CSRF
// Sanctum gère le CSRF pour les APIs
```

#### HTTPS en production
```php
// app/Providers/AppServiceProvider.php
if ($this->app->isProduction()) {
    $url->forceScheme('https');
}
```

### Bonnes pratiques implémentées

1. ✅ Validation stricte des inputs (form requests)
2. ✅ Hachage bcrypt des mots de passe
3. ✅ Rate limiting sur le login
4. ✅ Sanctum pour authentification API stateless
5. ✅ Tokens révocables
6. ✅ Messages d'erreur génériques (sécurité)
7. ✅ Rôles et permissions pour les admins

---

## 🧪 Tests

### Avec Postman

1. Importer la collection Postman (voir postman_collection.json)
2. Configurer les variables d'environnement:
   - `baseUrl`: http://localhost:8000/api
   - `token`: Copier après login
3. Tester les endpoints

### Avec curl

```bash
# Inscription
curl -X POST http://localhost:8000/api/auth/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "prenom": "John",
    "nom": "Doe",
    "email": "john@example.com",
    "mot_de_passe": "SecurePass123!",
    "mot_de_passe_confirmation": "SecurePass123!"
  }'

# Connexion
curl -X POST http://localhost:8000/api/auth/users/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "mot_de_passe": "SecurePass123!"
  }'

# Profil (avec token)
curl -X GET http://localhost:8000/api/auth/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Tests unitaires

```bash
# Créer un test
php artisan make:test AuthServiceTest --unit

# Lancer les tests
php artisan test

# Tests spécifiques
php artisan test tests/Unit/AuthServiceTest.php
```

---

## 🐛 Dépannage

### Erreur: "SQLSTATE[HY000]: General error: 1030 Got error..."

**Cause:** Problème de connexion MySQL
**Solution:**
```bash
# Vérifier la connexion
mysql -h les-casaniers-tonny-e980.b.aivencloud.com -P 27187 -u avnadmin -p

# Vérifier les logs
tail -f storage/logs/laravel.log
```

### Erreur: "419 Page Expired"

**Cause:** Token CSRF invalide
**Solution:** Utiliser Sanctum avec `Authorization: Bearer` header

### Erreur: "401 Unauthenticated"

**Cause:** Token absent ou invalide
**Solution:**
```bash
# Vérifier le token est dans le header
Authorization: Bearer {token}

# Vérifier le token est valide en base de données
SELECT * FROM personal_access_tokens WHERE token = 'hash_du_token';
```

### Rate limiting bloqué

**Cause:** Trop de tentatives de connexion (5 en 60s)
**Solution:** Attendre 60 secondes ou passer par une autre adresse IP

---

## 📊 Monitoring

### Logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Logs d'erreur
grep -i "error\|exception" storage/logs/laravel.log
```

### Base de données

```sql
-- Voir les tokens actifs
SELECT id, user_id, token_able_id, last_used_at FROM personal_access_tokens WHERE revoked = 0;

-- Voir les tentatives de reset en attente
SELECT email, created_at FROM password_resets WHERE expires_at > NOW();

-- Voir les utilisateurs actifs
SELECT id, prenom, nom, email, statut FROM utilisateurs WHERE statut = 'actif';
```

---

## 🚀 Déploiement en production

### Checklist

- [ ] Configurer `.env` avec les variables production
- [ ] Générer une nouvelle `APP_KEY`
- [ ] Forcer HTTPS dans `AppServiceProvider`
- [ ] Configurer les CORS correctement
- [ ] Mettre à jour `SANCTUM_STATEFUL_DOMAINS`
- [ ] Configurer le domaine d'authentification
- [ ] Mettre en place les logs centralisés
- [ ] Configurer les sauvegardes MySQL
- [ ] Activer les rate limits appropriés

### Configuration Apache

```apache
<Directory /var/www/les-casaniers-backend/public>
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php/$1 [L]
    </IfModule>
</Directory>

# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📞 Support

Pour toute question ou problème, consultez:
- Documentation API: `documentation/API_AUTHENTICATION.md`
- Laravel Docs: https://laravel.com/docs/11
- Sanctum Docs: https://laravel.com/docs/11/sanctum
