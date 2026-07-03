# Architecture — Application de gestion de stock associative

Document de conception, avant tout développement. Objectif : valider la structure avant d'écrire le moindre code métier.

---

## 1. Principes directeurs

Trois idées gouvernent toutes les décisions qui suivent :

- **Extensibilité par configuration plutôt que par modification.** Ajouter un champ à une entrée de stock, une colonne à un tableau, ou une permission ne doit jamais nécessiter de toucher au cœur du code. Ce point conditionne le schéma de base de données (voir EAV ciblé, section 3.4) et l'architecture des tableaux (section 7).
- **Séparation stricte des responsabilités.** Quatre couches : Domain (logique métier, classes PHP pures), Repository (accès SQL via PDO, requêtes préparées uniquement), Controller (orchestration, validation, sécurité), View (HTML/Bootstrap, zéro logique métier, zéro SQL).
- **Permissions atomiques indépendantes des rôles.** Un rôle est un simple regroupement nommé de permissions, stocké en base, modifiable sans toucher au code. Créer un nouveau profil = une opération de configuration, pas un déploiement.

---

## 2. Arborescence du projet

```
/stock-app
├── public/                          # Seul dossier exposé au serveur web (DocumentRoot)
│   ├── index.php                    # Front controller unique
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   │   ├── modules/             # scanner.js, datatable.js, label-printer.js...
│   │   └── img/
│   └── uploads/                     # Photos produits (accès contrôlé via script PHP, pas direct)
│
├── app/                             # Hors DocumentRoot — jamais accessible directement
│   ├── Config/
│   │   ├── config.php                # Constantes, chemins, env loading
│   │   └── database.php
│   │
│   ├── Core/                         # Socle technique, ne dépend jamais du métier
│   │   ├── Database.php              # Wrapper PDO, singleton, requêtes préparées
│   │   ├── Router.php                # Routeur simple basé sur tableau de routes
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Session.php               # Sessions sécurisées (régénération id, httponly, samesite)
│   │   ├── Csrf.php
│   │   ├── Validator.php
│   │   ├── Logger.php                # Écrit dans audit_logs, append-only
│   │   ├── RateLimiter.php           # Anti brute-force (tentatives par IP/compte)
│   │   └── Mailer.php
│   │
│   ├── Auth/
│   │   ├── AuthService.php           # login/logout, vérification session
│   │   ├── PasswordResetService.php
│   │   ├── InvitationService.php
│   │   └── Middleware/
│   │       ├── AuthMiddleware.php    # bloque toute page sans session valide
│   │       └── PermissionMiddleware.php
│   │
│   ├── Domain/                       # Logique métier pure, indépendante du framework maison
│   │   ├── User/
│   │   │   ├── User.php
│   │   │   ├── UserRepository.php
│   │   │   └── UserService.php
│   │   ├── Role/
│   │   │   ├── Role.php
│   │   │   ├── Permission.php
│   │   │   └── PermissionRepository.php
│   │   ├── Product/
│   │   │   ├── Product.php
│   │   │   ├── ProductRepository.php
│   │   │   └── ProductService.php
│   │   ├── Stock/
│   │   │   ├── StockEntry.php
│   │   │   ├── StockEntryRepository.php
│   │   │   ├── StockMovement.php     # entrées + sorties = mouvements
│   │   │   ├── StockMovementRepository.php
│   │   │   └── StockService.php      # règle "décrémente seulement l'entrée scannée"
│   │   ├── CustomField/              # Cœur du système extensible (section 3.4)
│   │   │   ├── CustomFieldDefinition.php
│   │   │   ├── CustomFieldRepository.php
│   │   │   └── CustomFieldService.php
│   │   ├── Barcode/
│   │   │   ├── BarcodeGenerator.php  # génère codes internes uniques
│   │   │   └── BarcodeService.php
│   │   ├── Label/
│   │   │   ├── LabelTemplate.php
│   │   │   └── LabelPrintService.php
│   │   └── Backup/
│   │       └── BackupService.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── InvitationController.php
│   │   │   ├── UserController.php
│   │   │   ├── RoleController.php
│   │   │   ├── ProductController.php
│   │   │   ├── StockEntryController.php
│   │   │   ├── StockExitController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ScannerController.php
│   │   │   ├── PrintController.php
│   │   │   ├── ExportController.php
│   │   │   └── SettingsController.php
│   │   └── Api/                      # endpoints JSON consommés par le JS (datatable, scanner)
│   │       ├── ProductApiController.php
│   │       ├── StockApiController.php
│   │       └── UserPreferenceApiController.php
│   │
│   └── View/
│       ├── layouts/
│       │   ├── app.php               # layout principal connecté
│       │   └── guest.php             # layout login/reset
│       ├── components/               # Composants réutilisables (partials)
│       │   ├── datatable.php
│       │   ├── alert.php
│       │   ├── modal.php
│       │   ├── pagination.php
│       │   └── column-config.php
│       ├── auth/
│       ├── dashboard/
│       ├── products/
│       ├── stock-entries/
│       ├── stock-exits/
│       ├── users/
│       ├── roles/
│       ├── logs/
│       └── print/
│
├── database/
│   ├── migrations/                   # fichiers SQL numérotés, exécutés séquentiellement
│   ├── seeds/                        # rôles et permissions par défaut, admin initial
│   └── backups/                      # dumps générés par BackupService
│
├── storage/
│   ├── logs/                         # logs techniques PHP (distincts des audit_logs SQL)
│   └── cache/
│
├── vendor/                           # uniquement librairies front/back légères si besoin
│   └── (ex: picqer/php-barcode-generator via composer, en local, pas de CDN obligatoire)
│
├── .env                              # identifiants DB, clés, hors du dépôt git
├── .env.example
├── composer.json                     # facultatif, pour 1-2 libs ciblées (barcode, PDF)
└── README.md
```

**Pourquoi ce découpage.** `public/` est le seul dossier accessible par Apache : tout le reste (`app/`, `.env`, `database/`) est hors DocumentRoot, ce qui élimine une classe entière de failles (accès direct aux fichiers PHP de config, aux dumps SQL, etc.) — particulièrement important sur un hébergement mutualisé où on ne maîtrise pas toujours la configuration Apache. `index.php` agit comme front controller : toutes les requêtes y passent, le routeur dispatch vers le bon contrôleur après application des middlewares (session, permissions).

---

## 3. Schéma de base de données

### 3.1 Authentification, invitations, utilisateurs

```sql
users
  id, email, password_hash, first_name, last_name,
  status ENUM('active','suspended'), role_id,
  must_change_password BOOLEAN,
  created_at, updated_at, last_login_at

invitations
  id, uuid (unique, indexé), email, role_id,
  created_by (FK users), created_at, expires_at,
  max_uses, uses_count, status ENUM('pending','used','expired','revoked')

password_resets
  id, user_id, token_hash, created_at, expires_at, used_at

login_attempts                       -- support du RateLimiter
  id, identifier (email ou IP), attempted_at, success BOOLEAN
```

### 3.2 Rôles et permissions indépendants

```sql
roles
  id, name, label, is_system BOOLEAN   -- is_system protège Super Admin de la suppression

permissions
  id, code (ex: 'product.create', 'stock.exit', 'user.manage', 'log.view'), label, category

role_permissions
  role_id, permission_id              -- table de liaison N-N : cœur du système flexible
```

Un nouveau profil ("Bénévole entrepôt", par exemple) se crée en ajoutant une ligne dans `roles` et des lignes dans `role_permissions` — zéro déploiement de code. Le `PermissionMiddleware` vérifie `user.role.permissions` contre le code requis par la route, jamais contre un nom de rôle en dur.

### 3.3 Produits et mouvements de stock

```sql
categories
  id, name, parent_id (nullable, pour sous-catégories)

products
  id, name, reference, barcode (unique, nullable),
  barcode_type ENUM('manufacturer','internal'),  -- voir section 3.5
  category_id, description, photo_path, unit,
  min_stock_threshold,
  status ENUM('active','archived'),
  created_at, updated_at

stock_entries
  id, product_id, quantity, entry_date,
  expiry_date, expiry_type ENUM('DDM','DLC'),
  origin (texte libre), comment,
  remaining_quantity,                 -- décrémenté par les sorties, jamais recalculé a posteriori
  created_by, created_at

stock_movements                       -- journal unifié entrées/sorties, base des stats et du dashboard
  id, type ENUM('entry','exit'),
  product_id, stock_entry_id (FK, l'entrée précisément scannée pour une sortie),
  quantity, performed_by, performed_at, comment
```

La règle métier « le logiciel décrémente uniquement l'entrée scannée » se traduit simplement : une sortie crée toujours une ligne `stock_movements` liée à un `stock_entry_id` précis, et c'est `StockService` (pas le SQL) qui vérifie s'il existe une autre entrée du même produit avec une échéance plus proche, pour déclencher l'alerte côté contrôleur.

### 3.4 Champs personnalisés extensibles (point central de votre demande)

C'est le mécanisme qui permet d'ajouter "Personne responsable", "Température", "Emplacement" sans toucher au schéma ni au code :

```sql
custom_field_definitions
  id, entity ENUM('stock_entry','product'),  -- extensible à d'autres entités plus tard
  field_key (slug unique), label,
  field_type ENUM('text','number','date','select','boolean'),
  options_json (pour les select),
  is_required, display_order, is_active

custom_field_values
  id, definition_id (FK), entity_id (id de l'entrée ou du produit concerné),
  value_text, value_number, value_date   -- colonnes typées, une seule remplie selon field_type
```

C'est un EAV (Entity-Attribute-Value) **volontairement restreint** à deux entités, pas un EAV généralisé à toute la base — ce qui évite le piège classique de l'EAV (requêtes illisibles, perte d'intégrité). `CustomFieldService` lit les définitions actives pour une entité et fusionne automatiquement les valeurs dans les formulaires et les tableaux. Ajouter un champ = une ligne insérée via une interface d'administration, pas une migration SQL ni un déploiement.

### 3.5 Codes-barres internes

```sql
-- déjà couvert par products.barcode_type
-- BarcodeGenerator produit un identifiant unique (ex: préfixe + UUID court) pour les produits
-- sans code-barres fabricant, imprimable via LabelPrintService
```

### 3.6 Préférences d'affichage des tableaux (deuxième amélioration demandée)

```sql
user_table_preferences
  id, user_id, table_key (ex: 'products_list', 'stock_entries_list'),
  visible_columns_json, column_order_json, filters_json, sort_json,
  updated_at
```

Une ligne par utilisateur et par tableau. Le composant `datatable.php` + `modules/datatable.js` lit ces préférences au chargement (via `UserPreferenceApiController`) et les réenregistre à chaque changement, en arrière-plan (debounce JS), sans rechargement de page.

### 3.7 Journal d'audit

```sql
audit_logs
  id, user_id, action_code (ex: 'login','product.create','export.csv'),
  entity_type, entity_id, details_json, ip_address, created_at
```

Aucune route de modification ou de suppression n'existe sur cette table au niveau applicatif : `Logger.php` n'expose qu'une méthode `record()`, jamais d'update/delete. Le rôle MySQL applicatif peut même se voir retirer les droits UPDATE/DELETE sur cette table spécifiquement, en plus de la garantie applicative.

### 3.8 Relations — vue d'ensemble

```
roles 1──N users 1──N invitations(created_by)
roles N──N permissions (via role_permissions)
categories 1──N products
products 1──N stock_entries
products 1──N stock_movements
stock_entries 1──N stock_movements (type='exit')
custom_field_definitions 1──N custom_field_values
users 1──N user_table_preferences
users 1──N audit_logs
```

---

## 4. Architecture des classes — flux type

Exemple représentatif : une sortie de stock scannée.

```
ScannerController::scanExit()
  → AuthMiddleware (déjà passé en amont)
  → PermissionMiddleware::check('stock.exit')
  → StockExitController::handle($barcode, $quantity)
      → ProductRepository::findByBarcode()
      → StockService::recordExit(product, entry, quantity, user)
          → StockEntryRepository::findEarliestExpiry(product)   // pour l'alerte
          → StockMovementRepository::insert()                   // requête préparée
          → StockEntryRepository::decrementRemaining()
          → Logger::record('stock.exit', ...)
      ← retourne un DTO StockExitResult (avec éventuel warning)
  → Response JSON (consommée par scanner.js)
```

Chaque Repository n'expose que des méthodes métier nommées (`findByBarcode`, `decrementRemaining`) — jamais de SQL brut exposé hors de la classe Repository elle-même. Chaque Service orchestre plusieurs repositories et applique les règles métier ; les contrôleurs restent fins (validation des entrées, appel du service, formatage de la réponse).

---

## 5. Système de permissions — détail pratique

- Table `permissions` peuplée par un seed initial : `product.view`, `product.create`, `product.edit`, `product.archive`, `stock.entry.create`, `stock.exit`, `user.manage`, `role.manage`, `log.view`, `export.*`, `print.*`, `settings.manage`, etc.
- Rôles par défaut (seedés, modifiables ensuite) : **Super Admin** (toutes permissions, protégé), **Administrateur** (gestion utilisateurs/produits/stock, pas de gestion des rôles système), **Gestionnaire** (produits + stock, pas d'utilisateurs), **Lecture seule** (`*.view` uniquement).
- `PermissionMiddleware` est déclaré sur chaque route avec le(s) code(s) de permission requis ; en cas d'échec, redirection contrôlée plutôt qu'erreur brute.
- Les vues peuvent aussi interroger `$user->can('product.edit')` pour masquer/afficher des boutons, sans dupliquer la logique de contrôle d'accès (la vérification serveur reste seule autoritaire).

---

## 6. Flux de navigation

```
Visiteur non connecté → toute URL → AuthMiddleware → redirection /login
/login → AuthService::attempt() → succès → /dashboard
                                 → échec → RateLimiter incrémente, message générique

/invitation/{uuid} → InvitationService::validate() → formulaire création compte
                    → uuid invalide/expiré/épuisé → page d'erreur dédiée (pas de formulaire)

/password/forgot → email → PasswordResetService::createToken() → mail
/password/reset/{token} → vérification expiration → nouveau mot de passe → suppression token

Dashboard → Produits / Entrées / Sorties (scanner) / Utilisateurs (si permission) /
            Rôles (si permission) / Logs (si permission) / Impressions / Paramètres
```

Aucune route n'est atteignable sans passer par `AuthMiddleware`, y compris les endpoints `Api/*` (vérification de session sur chaque requête JSON, pas seulement sur les pages HTML).

---

## 7. Tableaux performants (produits, entrées, utilisateurs, logs)

Avec plusieurs milliers de lignes, tout charger côté client devient lent : l'approche retenue est **server-side processing**. `modules/datatable.js` (vanilla JS ES6, pas de dépendance lourde type DataTables.net si vous voulez rester 100% "aucun framework", ou DataTables.net en JS pur si vous l'acceptez comme simple librairie JS) envoie les paramètres de tri/filtre/pagination à `Api/ProductApiController::list()`, qui construit la requête SQL paginée (LIMIT/OFFSET, index sur les colonnes filtrables). Les préférences de colonnes (section 3.6) sont injectées au rendu initial. Export CSV/Excel/impression réutilisent la même requête sans pagination, avec une limite raisonnable et un log d'audit (`export.csv`, `export.xlsx`, `print.*`).

---

## 8. Choix techniques justifiés

| Choix | Raison |
|---|---|
| PDO + requêtes préparées partout, encapsulées dans des Repository | Élimine les injections SQL par construction, pas par discipline |
| Pas de framework lourd | Conforme à la contrainte ; compatible Raspberry Pi et mutualisé sans dépendances serveur exotiques |
| Composer limité à 1-2 libs ciblées (génération de codes-barres, génération PDF pour étiquettes) | Évite de réinventer des algorithmes EAN/Code128 ou la mise en page PDF, sans alourdir l'architecture |
| EAV restreint à 2 entités plutôt que generalisé | Donne l'extensibilité demandée sans les inconvénients classiques d'un EAV total (perte de typage, requêtes illisibles) |
| Permissions en table plutôt qu'en constantes PHP | Nouveau profil = configuration, pas déploiement |
| `public/` unique DocumentRoot | Sécurité par construction sur hébergement mutualisé où la configuration Apache n'est pas toujours modifiable |
| Server-side processing des tableaux | Seule approche qui reste performante à plusieurs milliers de lignes sans librairie JS lourde |
| Logs en table SQL append-only séparée du `storage/logs` technique | Distingue audit métier (jamais modifiable, valeur légale/traçabilité) des logs d'erreurs techniques |

---

## 9. Ce qui reste à trancher avant le développement

Quelques décisions vous appartiennent et changeront légèrement l'implémentation :

1. Génération de PDF pour les étiquettes/inventaires : librairie Composer (ex: dompdf, tcpdf) ou génération HTML imprimable via CSS print (`@media print`), plus simple mais moins contrôlée pour le placement précis des étiquettes A4.
2. Lecture de code-barres caméra : librairie JS légère (ex: QuaggaJS ou ZXing-js) à charger en local plutôt que CDN, pour rester fonctionnel hors-ligne sur Raspberry Pi.
3. Fréquence et mécanisme des sauvegardes (cron système vs déclenchement manuel depuis l'interface) selon que vous avez accès au cron sur l'hébergement mutualisé.

Une fois ces points validés, je propose qu'on démarre par le socle (Core + Auth + migrations de base), puis qu'on avance module par module : Produits → Entrées → Sorties/Scanner → Dashboard → Tableaux avancés → Impressions/Étiquettes → Logs/Sauvegardes.
