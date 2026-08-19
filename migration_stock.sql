-- Migration: Inventory (Stock) + Material Issues
-- Run: C:\xampp\mysql\bin\mysql.exe -u root property_erp < migration_stock.sql

-- 1. Add stock columns to products
ALTER TABLE products ADD COLUMN stock_qty DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER unit;
ALTER TABLE products ADD COLUMN avg_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER stock_qty;

-- 2. Stock movements (audit trail)
CREATE TABLE IF NOT EXISTS stock_movements (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  product_id int(10) unsigned NOT NULL,
  movement_type enum('purchase','issue','adjustment') NOT NULL,
  quantity decimal(10,2) NOT NULL,
  unit_cost decimal(14,2) NOT NULL DEFAULT 0.00,
  total_cost decimal(14,2) NOT NULL DEFAULT 0.00,
  reference_type varchar(40) DEFAULT NULL,
  reference_id int(10) unsigned DEFAULT NULL,
  project_id int(10) unsigned DEFAULT NULL,
  contractor_id int(10) unsigned DEFAULT NULL,
  created_date date NOT NULL,
  created_time time NOT NULL,
  PRIMARY KEY (id),
  KEY idx_sm_product (product_id),
  KEY idx_sm_project (project_id),
  CONSTRAINT fk_sm_product FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_sm_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_sm_contractor FOREIGN KEY (contractor_id) REFERENCES contractors(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Material issues header
CREATE TABLE IF NOT EXISTS material_issues (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  issue_no varchar(40) NOT NULL,
  issue_date date NOT NULL,
  project_id int(10) unsigned NOT NULL,
  contractor_id int(10) unsigned NOT NULL,
  narration varchar(255) DEFAULT NULL,
  total_amount decimal(14,2) NOT NULL DEFAULT 0.00,
  voucher_id int(10) unsigned DEFAULT NULL,
  created_date date NOT NULL,
  created_time time NOT NULL,
  updated_date date NOT NULL,
  updated_time time NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mi_no (issue_no),
  KEY idx_mi_project (project_id),
  KEY idx_mi_contractor (contractor_id),
  CONSTRAINT fk_mi_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_mi_contractor FOREIGN KEY (contractor_id) REFERENCES contractors(id),
  CONSTRAINT fk_mi_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Material issue line items
CREATE TABLE IF NOT EXISTS material_issue_items (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  material_issue_id int(10) unsigned NOT NULL,
  product_id int(10) unsigned NOT NULL,
  quantity decimal(10,2) NOT NULL DEFAULT 0,
  unit_cost decimal(14,2) NOT NULL DEFAULT 0.00,
  total_cost decimal(14,2) NOT NULL DEFAULT 0.00,
  created_date date NOT NULL,
  created_time time NOT NULL,
  updated_date date NOT NULL,
  updated_time time NOT NULL,
  PRIMARY KEY (id),
  KEY idx_mii_issue (material_issue_id),
  CONSTRAINT fk_mii_issue FOREIGN KEY (material_issue_id) REFERENCES material_issues(id) ON DELETE CASCADE,
  CONSTRAINT fk_mii_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. COA: Stock in Hand (Asset)
INSERT IGNORE INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, status, created_date, created_time, updated_date, updated_time)
VALUES ('1200', 'Stock in Hand', 'asset', 0, 0.00, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- 6. Permissions
INSERT IGNORE INTO permissions (module, slug, name) VALUES
('Inventory', 'inventory.view', 'View Inventory'),
('Inventory', 'inventory.manage', 'Manage Inventory');
