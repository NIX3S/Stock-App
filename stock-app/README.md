# Application de gestion de stock

Application PHP 8.4 · MariaDB · Bootstrap 5 · JavaScript ES6 — sans framework lourd.
Conçue pour une association, déployable sur Raspberry Pi ou hébergement mutualisé.

---

## Installation rapide

### 1 — Base de données

```bash
mysql -u root -p -e "CREATE DATABASE stock_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p stock_app < database/migrations/001_auth_users.sql
mysql -u root -p stock_app < database/migrations/002_products_stock.sql
mysql -u root -p stock_app < database/migrations/003_custom_fields_prefs_audit.sql
mysql -u root -p stock_app < database/seeds/001_roles_permissions_admin.sql
```

Compte Super Admin créé par le seed :
| Champ | Valeur |
|---|---|
| E-mail | `admin@example.org` |
| Mot de passe | `ChangeMe123!` |

Le compte a `must_change_password = 1` : le changement est demandé dès la première connexion.
**Changez immédiatement l'e-mail et le mot de passe en production.**

---

### 2 — Configuration

```bash
cp .env.example .env
# Éditez .env : DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_URL, APP_ENV
```

En développement, positionner `APP_ENV=development` : les e-mails sont alors écrits dans
`storage/logs/mail.log` au lieu d'être envoyés (pas besoin de sendmail/SMTP).

---

### 3 — Serveur web Apache

Pointez le `DocumentRoot` vers le dossier `public/` uniquement.
Tout le reste (`app/`, `database/`, `.env`) reste **hors** du DocumentRoot.

**Exemple de VirtualHost :**

```apache
<VirtualHost *:80>
    ServerName stock.local
    DocumentRoot "/chemin/vers/stock-app/public"

    <Directory "/chemin/vers/stock-app/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Puis :

```bash
sudo a2ensite stock-app.conf
sudo a2enmod rewrite headers
sudo systemctl reload apache2
```

> **Erreur `client denied by server configuration`** : Apache refuse l'accès si
> `AllowOverride None` est actif dans la configuration globale (défaut Ubuntu/Debian).
> Le bloc `<Directory>` ci-dessus avec `AllowOverride All` + `Require all granted` corrige cela.

---

### 4 — Droits sur les dossiers

```bash
chmod -R 775 public/uploads storage database/backups
chown -R www-data:www-data public/uploads storage database/backups
```

---

### 5 — Première connexion

1. Ouvrez `/login`
2. Connectez-vous avec `admin@example.org` / `ChangeMe123!`
3. Changez le mot de passe (obligatoire au premier login)
4. Modifiez votre e-mail dans la fiche utilisateur
5. Invitez les autres membres depuis **Utilisateurs → Inviter un utilisateur**

Aucune inscription publique n'existe dans l'application, par conception.

---

## Architecture

```
public/              ← seul dossier exposé au serveur web
  index.php          ← front controller unique, toutes les requêtes y passent
  assets/
    css/
    js/modules/      ← scanner.js, datatable.js, label-printer.js, dashboard-charts.js
  uploads/           ← photos produits (écriture requise)

app/
  Config/config.php  ← chargé via App\Core\Config (singleton, 1 seule lecture)
  Core/              ← Database, Session, Router, Request, Response, Csrf, Validator,
                        Logger, RateLimiter, Mailer, Config
  Auth/              ← AuthService, InvitationService, PasswordResetService + middlewares
  Domain/            ← logique métier par module, sans SQL brut exposé
    User/   Role/   Product/   Stock/   CustomField/   Barcode/   Label/   Backup/
  Http/
    Controllers/     ← contrôleurs HTTP (fins, délèguent aux services)
    Api/             ← endpoints JSON (datatable, scanner, préférences colonnes)
  View/
    layouts/         ← app.php (connecté), guest.php (login/invitation), print.php
    components/      ← datatable.php (composant réutilisable)
    [pages]/

database/
  migrations/        ← 001, 002, 003 — à exécuter dans l'ordre
  seeds/             ← rôles, permissions, super admin
  backups/           ← dumps générés par BackupService (écriture requise)

storage/
  logs/              ← logs PHP + mail.log (dev) (écriture requise)
```

---

## Fonctionnalités

### Authentification
- Aucune page publique (hors login/invitation/reset)
- Sessions sécurisées (httponly, samesite, régénération d'id)
- Protection CSRF sur tous les formulaires POST
- Anti-brute-force par IP (5 tentatives / 15 min)

### Invitations
- Seul un administrateur peut créer des comptes
- Workflow : admin → invitation → lien sécurisé (UUID) → création compte → lien invalidé
- Durée configurable (`.env : INVITATION_DEFAULT_EXPIRY_DAYS`)
- En dev : le lien est écrit dans `storage/logs/mail.log`

### Rôles et permissions
- 4 rôles par défaut : Super Admin, Administrateur, Gestionnaire, Lecture seule
- **Les permissions sont indépendantes des rôles** : créez de nouveaux profils depuis
  l'interface Rôles, sans toucher au code
- Vérification par code de permission (`product.create`, `stock.exit`, etc.)

### Produits
- Code-barres fabricant (scan à la création) ou **code interne auto-généré** si vide
- Scanner intégré sur le formulaire (caméra) — Android + iOS
- Champs personnalisés dynamiques (voir section dédiée)

### Entrées de stock
- Scan du produit directement depuis le formulaire d'entrée
- Saisie manuelle possible en parallèle
- Chaque entrée a sa propre DDM/DLC (plusieurs entrées du même produit → dates différentes)
- **Bouton "Enregistrer et imprimer l'étiquette"** pour enchaîner directement à l'impression

### Sorties de stock (scanner)
- Scan ou saisie manuelle du code-barres
- **Toutes les entrées disponibles** du produit sont affichées avec leurs dates d'expiration
- L'utilisateur choisit **explicitement** quelle entrée décrémenter (pas de FEFO automatique)
- Badge coloré : périmé (rouge), < 7 jours (orange), OK (vert)
- Avertissement si une autre entrée expire plus tôt → l'utilisateur reste libre de continuer
- Toast de confirmation après chaque sortie, sans rechargement de page

### Tableaux
- Server-side processing (SQL paginé) → performant à plusieurs milliers de lignes
- Colonnes masquables, réorganisables par glisser-déposer
- Préférences persistées par utilisateur et par tableau (`user_table_preferences`)
- Recherche instantanée (debounce 300 ms)
- Export CSV, Excel, impression

### Champs personnalisés extensibles
**Point clé de l'architecture** : ajoutez des champs à une entrée de stock ou à un produit
(ex: Personne responsable, Température, Emplacement, N° lot interne) depuis **Paramètres**,
sans migration SQL ni modification de code. Les formulaires et tableaux les intègrent
automatiquement.

### Impressions
- Inventaire complet, liste produits, liste périmés (30 jours)
- **Générateur d'étiquettes A4** configurable : colonnes × lignes × copies par produit
- Code-barres SVG généré côté serveur (Code128)
- Impression rapide d'une étiquette depuis le formulaire d'entrée de stock

### Journal d'audit
- Toute action significative est tracée (connexion, ajout, modification, suppression,
  export, impression, changement de rôle, réinitialisation de mot de passe…)
- Le journal est **en lecture seule** par design (aucune route de modification)

### Sauvegardes
- Via `mysqldump` si disponible, sinon export SQL pur PHP (compatible mutualisé sans shell)
- Téléchargement depuis l'interface Paramètres

---

## Scanner de codes-barres

Le module `public/assets/js/modules/scanner.js` utilise :

1. **`BarcodeDetector` natif** (Chrome ≥ 83, Edge, Android Chrome, Samsung Internet) — pas de
   chargement externe, le plus rapide
2. **ZXing-js** (chargé dynamiquement depuis CDN si `BarcodeDetector` absent) — assure la
   compatibilité avec **Safari iOS 16+, Firefox, anciens Android**

En production sur un réseau isolé (Raspberry Pi hors internet), téléchargez ZXing-js en local
et adaptez la constante `ZXING_CDN` dans `scanner.js`.

Formats supportés : EAN-13, EAN-8, Code128, Code39, QR, UPC-A, UPC-E, ITF, Codabar.

---

## Notes de production

| Sujet | Recommandation |
|---|---|
| **Code-barres** | `BarcodeGenerator` implémente Code128 maison (suffisant pour codes internes). Pour des codes fabricants complexes, branchez `picqer/php-barcode-generator` (Composer) — seule `BarcodeGenerator::toSvg()` est à modifier. |
| **Export Excel natif** | L'export actuel génère un `.xls` HTML (lisible par Excel sans dépendance). Pour du `.xlsx` natif, ajoutez PhpSpreadsheet derrière `ExportController`. |
| **Sauvegardes automatiques** | Planifiez un cron appelant un petit script CLI qui instancie `BackupService`. |
| **Mails** | En dev, les mails sont dans `storage/logs/mail.log`. En prod, `mail()` suffit sur un mutualisé ; sinon remplacez le corps de `Mailer::send()` par SMTP (une lib Composer) sans toucher au reste. |
| **HTTPS** | Activez HTTPS avant la mise en production (Let's Encrypt). La session passe automatiquement en `secure` si la requête est HTTPS. |
| **WSL / dev** | Pointez le VirtualHost Apache vers `/mnt/d/…/public` et assurez-vous que `AllowOverride All` est actif (voir section Installation). |

---

## Roadmap suggérée

- [ ] Gestion des catégories (CRUD — table `categories` déjà présente)
- [ ] Statistiques de mouvements par période (graphiques dashboard enrichis)
- [ ] Import CSV de produits en masse
- [ ] Notifications e-mail automatiques pour les produits sous le seuil minimum
- [ ] API REST complète pour intégration avec d'autres outils
