-- Seed initial : rôles, permissions, association rôle-permissions, super admin

INSERT INTO roles (name, label, is_system) VALUES
('super_admin', 'Super Administrateur', 1),
('admin', 'Administrateur', 0),
('manager', 'Gestionnaire', 0),
('readonly', 'Lecture seule', 0);

INSERT INTO permissions (code, label, category) VALUES
('dashboard.view', 'Voir le tableau de bord', 'dashboard'),
('product.view', 'Voir les produits', 'product'),
('product.create', 'Créer un produit', 'product'),
('product.edit', 'Modifier un produit', 'product'),
('product.archive', 'Archiver un produit', 'product'),
('stock.entry.view', 'Voir les entrées de stock', 'stock'),
('stock.entry.create', 'Créer une entrée de stock', 'stock'),
('stock.exit.view', 'Voir les sorties de stock', 'stock'),
('stock.exit.create', 'Effectuer une sortie de stock', 'stock'),
('user.manage', 'Gérer les utilisateurs', 'user'),
('role.manage', 'Gérer les rôles et permissions', 'user'),
('invitation.manage', 'Gérer les invitations', 'user'),
('log.view', 'Voir le journal d\'audit', 'system'),
('export.csv', 'Exporter en CSV', 'export'),
('export.xlsx', 'Exporter en Excel', 'export'),
('print.manage', 'Imprimer / générer des étiquettes', 'print'),
('settings.manage', 'Gérer les paramètres et champs personnalisés', 'system'),
('backup.manage', 'Gérer les sauvegardes', 'system');

-- Super Admin : toutes les permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'super_admin'), id FROM permissions;

-- Administrateur : tout sauf role.manage
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'admin'), id FROM permissions
WHERE code <> 'role.manage';

-- Gestionnaire : produits et stock, exports et impressions, pas d'utilisateurs/rôles/logs/réglages
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'manager'), id FROM permissions
WHERE category IN ('dashboard','product','stock','export','print');

-- Lecture seule : uniquement les vues
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'readonly'), id FROM permissions
WHERE code IN ('dashboard.view','product.view','stock.entry.view','stock.exit.view');

-- Compte Super Admin initial — mot de passe par défaut "ChangeMe123!" à modifier immédiatement
-- Hash généré avec password_hash('ChangeMe123!', PASSWORD_DEFAULT)
INSERT INTO users (email, password_hash, first_name, last_name, status, role_id, must_change_password)
VALUES (
    'admin@example.org',
    '$2y$10$JzJrQipkBVjnF9SYUlBZ/.//UtUISDxpnulG8sHH3r6aax5UDLh2i',
    'Super',
    'Admin',
    'active',
    (SELECT id FROM roles WHERE name = 'super_admin'),
    1
);
