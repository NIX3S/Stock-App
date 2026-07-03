-- Migration 002 : catégories, produits, entrées et mouvements de stock

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    parent_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    reference VARCHAR(100) NULL,
    barcode VARCHAR(100) NULL UNIQUE,
    barcode_type ENUM('manufacturer','internal') NOT NULL DEFAULT 'manufacturer',
    category_id INT UNSIGNED NULL,
    description TEXT NULL,
    photo_path VARCHAR(255) NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'unité',
    min_stock_threshold INT NOT NULL DEFAULT 0,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_products_status (status),
    INDEX idx_products_name (name),
    FULLTEXT INDEX ft_products_search (name, reference, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    remaining_quantity INT NOT NULL,
    entry_date DATE NOT NULL,
    expiry_date DATE NULL,
    expiry_type ENUM('DDM','DLC') NULL,
    origin VARCHAR(255) NULL,
    comment TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_stock_entries_product (product_id),
    INDEX idx_stock_entries_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('entry','exit') NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    stock_entry_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    performed_by INT UNSIGNED NOT NULL,
    performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    comment TEXT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (stock_entry_id) REFERENCES stock_entries(id),
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_stock_movements_product (product_id),
    INDEX idx_stock_movements_type_date (type, performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
