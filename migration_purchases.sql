CREATE TABLE IF NOT EXISTS purchases (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  purchase_no VARCHAR(40) NOT NULL,
  vendor_id INT UNSIGNED NOT NULL,
  purchase_date DATE NOT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  narration VARCHAR(255) DEFAULT NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_mode ENUM('cash','bank','credit') NOT NULL DEFAULT 'cash',
  bank_id INT UNSIGNED DEFAULT NULL,
  reference VARCHAR(80) DEFAULT NULL,
  voucher_id INT UNSIGNED DEFAULT NULL,
  payment_voucher_id INT UNSIGNED DEFAULT NULL,
  status ENUM('pending','partial','paid','cancelled') NOT NULL DEFAULT 'pending',
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_purchases_no (purchase_no),
  KEY idx_purchases_vendor (vendor_id),
  KEY idx_purchases_project (project_id),
  KEY idx_purchases_date (purchase_date),
  CONSTRAINT fk_purchases_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT,
  CONSTRAINT fk_purchases_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
  CONSTRAINT fk_purchases_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE SET NULL,
  CONSTRAINT fk_purchases_pvoucher FOREIGN KEY (payment_voucher_id) REFERENCES vouchers (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  purchase_id INT UNSIGNED NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  expense_account_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pi_purchase (purchase_id),
  CONSTRAINT fk_pi_purchase FOREIGN KEY (purchase_id) REFERENCES purchases (id) ON DELETE CASCADE,
  CONSTRAINT fk_pi_account FOREIGN KEY (expense_account_id) REFERENCES chart_of_accounts (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT IGNORE INTO permissions (id, module, slug, name, created_date, created_time, updated_date, updated_time) VALUES
(39, 'Purchases', 'purchases.view', 'View Purchases', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(40, 'Purchases', 'purchases.manage', 'Manage Purchases', CURDATE(), CURTIME(), CURDATE(), CURTIME());
