-- Migration 003 : champs personnalisés extensibles, préférences utilisateur, audit

CREATE TABLE custom_field_definitions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity ENUM('stock_entry','product') NOT NULL,
    field_key VARCHAR(80) NOT NULL,
    label VARCHAR(150) NOT NULL,
    field_type ENUM('text','number','date','select','boolean') NOT NULL,
    options_json TEXT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_entity_key (entity, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE custom_field_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    definition_id INT UNSIGNED NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    value_text VARCHAR(255) NULL,
    value_number DECIMAL(18,4) NULL,
    value_date DATE NULL,
    FOREIGN KEY (definition_id) REFERENCES custom_field_definitions(id) ON DELETE CASCADE,
    INDEX idx_cfv_entity (definition_id, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_table_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    table_key VARCHAR(60) NOT NULL,
    visible_columns_json TEXT NULL,
    column_order_json TEXT NULL,
    filters_json TEXT NULL,
    sort_json TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_table (user_id, table_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action_code VARCHAR(60) NOT NULL,
    entity_type VARCHAR(60) NULL,
    entity_id INT UNSIGNED NULL,
    details_json TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_user (user_id),
    INDEX idx_audit_logs_action (action_code),
    INDEX idx_audit_logs_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
