-- =====================================================================
-- Real Estate ERP - Pakistan
-- Core PHP + MySQLi | No Framework
-- Every table carries the standard columns:
--   id, created_date, created_time, updated_date, updated_time
-- =====================================================================

CREATE DATABASE IF NOT EXISTS property_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE property_erp;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================ USERS & ROLES ===========================

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  module VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  name VARCHAR(150) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_perm (role_id, permission_id),
  KEY idx_rp_permission (permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS branches (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(30) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  city_id INT UNSIGNED DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_branches_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id INT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED DEFAULT NULL,
  username VARCHAR(80) NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(120) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  remember_token VARCHAR(64) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role (role_id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB;

-- ============================ GEOGRAPHY ============================

CREATE TABLE IF NOT EXISTS countries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(5) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_countries_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_cities_country (country_id),
  CONSTRAINT fk_cities_country FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS areas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_areas_city (city_id),
  CONSTRAINT fk_areas_city FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS societies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_societies_city (city_id),
  CONSTRAINT fk_societies_city FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================ PROJECTS ============================

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(180) NOT NULL,
  developer VARCHAR(150) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  country_id INT UNSIGNED DEFAULT NULL,
  city_id INT UNSIGNED DEFAULT NULL,
  area_id INT UNSIGNED DEFAULT NULL,
  society_id INT UNSIGNED DEFAULT NULL,
  noc VARCHAR(80) DEFAULT NULL,
  noc_file VARCHAR(255) DEFAULT NULL,
  map_file VARCHAR(255) DEFAULT NULL,
  master_plan_file VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_name (name),
  CONSTRAINT fk_projects_city FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  image_file VARCHAR(255) NOT NULL,
  title VARCHAR(150) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pimg_project (project_id),
  CONSTRAINT fk_pimg_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pdoc_project (project_id),
  CONSTRAINT fk_pdoc_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS blocks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_blocks_project (project_id),
  CONSTRAINT fk_blocks_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_roads_project (project_id),
  CONSTRAINT fk_roads_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS streets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  block_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_streets_project (project_id),
  CONSTRAINT fk_streets_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== PROPERTY MASTER DATA =====================

CREATE TABLE IF NOT EXISTS property_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ptype_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pcat_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS amenities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  icon VARCHAR(60) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_amenity_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_amenities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  amenity_id INT UNSIGNED NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_prop_amenity (property_id, amenity_id),
  CONSTRAINT fk_pa_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_amenity FOREIGN KEY (amenity_id) REFERENCES amenities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== FINANCIAL MASTER DATA =====================

CREATE TABLE IF NOT EXISTS payment_methods (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_paymethod_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS banks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  account_title VARCHAR(150) DEFAULT NULL,
  account_no VARCHAR(80) DEFAULT NULL,
  iban VARCHAR(80) DEFAULT NULL,
  branch VARCHAR(150) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_banks_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expense_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_expcat_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS income_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_incat_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS document_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_doctype_name (name)
) ENGINE=InnoDB;

-- ===================== PARTIES =====================

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_no VARCHAR(40) NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  cnic VARCHAR(40) DEFAULT NULL,
  passport_no VARCHAR(60) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  city_id INT UNSIGNED DEFAULT NULL,
  nominee_name VARCHAR(180) DEFAULT NULL,
  nominee_cnic VARCHAR(40) DEFAULT NULL,
  nominee_relation VARCHAR(60) DEFAULT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_customers_no (customer_no),
  KEY idx_customers_city (city_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS owners (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_no VARCHAR(40) NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  cnic VARCHAR(40) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  bank_account_title VARCHAR(150) DEFAULT NULL,
  bank_account_no VARCHAR(80) DEFAULT NULL,
  commission_rate DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_owners_no (owner_no),
  CONSTRAINT fk_owners_bank FOREIGN KEY (bank_id) REFERENCES banks (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS owner_ledger (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ol_owner (owner_id),
  CONSTRAINT fk_ol_owner FOREIGN KEY (owner_id) REFERENCES owners (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dealers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dealer_no VARCHAR(40) NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  cnic VARCHAR(40) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  dealer_type ENUM('dealer','agent') NOT NULL DEFAULT 'dealer',
  commission_rate DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dealers_no (dealer_no)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dealer_ledger (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dealer_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_dl_dealer (dealer_id),
  CONSTRAINT fk_dl_dealer FOREIGN KEY (dealer_id) REFERENCES dealers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dealer_payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dealer_id INT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_method_id INT UNSIGNED DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  reference VARCHAR(80) DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_dp_dealer (dealer_id),
  CONSTRAINT fk_dp_dealer FOREIGN KEY (dealer_id) REFERENCES dealers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vendors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vendor_no VARCHAR(40) NOT NULL,
  business_name VARCHAR(180) DEFAULT NULL,
  contact_person VARCHAR(180) DEFAULT NULL,
  cnic VARCHAR(40) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  city_id INT UNSIGNED DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  bank_account_title VARCHAR(150) DEFAULT NULL,
  bank_account_no VARCHAR(80) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vendors_no (vendor_no),
  KEY idx_vendors_city (city_id),
  CONSTRAINT fk_vendors_bank FOREIGN KEY (bank_id) REFERENCES banks (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vendor_payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vendor_id INT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_method_id INT UNSIGNED DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  reference VARCHAR(80) DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_vp_vendor (vendor_id),
  CONSTRAINT fk_vp_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS general_parties (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  party_no VARCHAR(40) NOT NULL,
  party_name VARCHAR(180) NOT NULL,
  contact_person VARCHAR(180) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gp_no (party_no)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS general_party_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  party_id INT UNSIGNED NOT NULL,
  entry_no VARCHAR(40) NOT NULL,
  entry_date DATE NOT NULL,
  entry_type ENUM('payable','paid','receiving') NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  narration VARCHAR(255) DEFAULT NULL,
  account_id INT UNSIGNED DEFAULT NULL,
  voucher_id INT UNSIGNED DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gpe_no (entry_no),
  KEY idx_gpe_party (party_id),
  KEY idx_gpe_date (entry_date),
  KEY idx_gpe_type (entry_type),
  CONSTRAINT fk_gpe_party FOREIGN KEY (party_id) REFERENCES general_parties (id) ON DELETE CASCADE,
  CONSTRAINT fk_gpe_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== EMPLOYEES =====================

CREATE TABLE IF NOT EXISTS employees (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_no VARCHAR(40) NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  father_name VARCHAR(180) DEFAULT NULL,
  cnic VARCHAR(40) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  designation VARCHAR(120) DEFAULT NULL,
  department VARCHAR(120) DEFAULT NULL,
  joining_date DATE DEFAULT NULL,
  monthly_salary DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  bank_id INT UNSIGNED DEFAULT NULL,
  bank_account_title VARCHAR(150) DEFAULT NULL,
  bank_account_no VARCHAR(80) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employees_no (employee_no),
  KEY idx_employees_bank (bank_id),
  CONSTRAINT fk_employees_bank FOREIGN KEY (bank_id) REFERENCES banks (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employee_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id INT UNSIGNED NOT NULL,
  entry_no VARCHAR(40) NOT NULL,
  entry_date DATE NOT NULL,
  entry_type ENUM('payable','paid') NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  narration VARCHAR(255) DEFAULT NULL,
  account_id INT UNSIGNED DEFAULT NULL,
  voucher_id INT UNSIGNED DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_empe_no (entry_no),
  KEY idx_empe_employee (employee_id),
  KEY idx_empe_date (entry_date),
  KEY idx_empe_type (entry_type),
  CONSTRAINT fk_empe_employee FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE,
  CONSTRAINT fk_empe_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== CONTRACTORS =====================

CREATE TABLE IF NOT EXISTS contractors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  contractor_no VARCHAR(40) NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  company VARCHAR(180) DEFAULT NULL,
  specialty VARCHAR(120) DEFAULT NULL,
  cnic VARCHAR(40) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  bank_account_title VARCHAR(150) DEFAULT NULL,
  bank_account_no VARCHAR(80) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_contractors_no (contractor_no),
  KEY idx_contractors_bank (bank_id),
  CONSTRAINT fk_contractors_bank FOREIGN KEY (bank_id) REFERENCES banks (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contractor_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  contractor_id INT UNSIGNED NOT NULL,
  entry_no VARCHAR(40) NOT NULL,
  entry_date DATE NOT NULL,
  entry_type ENUM('payable','paid') NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  narration VARCHAR(255) DEFAULT NULL,
  account_id INT UNSIGNED DEFAULT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  voucher_id INT UNSIGNED DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_conte_no (entry_no),
  KEY idx_conte_contractor (contractor_id),
  KEY idx_conte_date (entry_date),
  KEY idx_conte_type (entry_type),
  KEY idx_conte_project (project_id),
  CONSTRAINT fk_conte_contractor FOREIGN KEY (contractor_id) REFERENCES contractors (id) ON DELETE CASCADE,
  CONSTRAINT fk_conte_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE SET NULL,
  CONSTRAINT fk_conte_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contractor_projects (
  contractor_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  PRIMARY KEY (contractor_id, project_id),
  KEY idx_cp_project (project_id),
  CONSTRAINT fk_cp_contractor FOREIGN KEY (contractor_id) REFERENCES contractors (id) ON DELETE CASCADE,
  CONSTRAINT fk_cp_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== PROPERTIES =====================

CREATE TABLE IF NOT EXISTS properties (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  property_no VARCHAR(40) NOT NULL,
  file_no VARCHAR(60) DEFAULT NULL,
  plot_no VARCHAR(60) DEFAULT NULL,
  house_no VARCHAR(60) DEFAULT NULL,
  apartment_no VARCHAR(60) DEFAULT NULL,
  office_no VARCHAR(60) DEFAULT NULL,
  shop_no VARCHAR(60) DEFAULT NULL,
  warehouse_no VARCHAR(60) DEFAULT NULL,
  factory_no VARCHAR(60) DEFAULT NULL,
  farm_house_no VARCHAR(60) DEFAULT NULL,
  hall_no VARCHAR(60) DEFAULT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  block_id INT UNSIGNED DEFAULT NULL,
  road_id INT UNSIGNED DEFAULT NULL,
  street_id INT UNSIGNED DEFAULT NULL,
  property_type_id INT UNSIGNED DEFAULT NULL,
  property_category_id INT UNSIGNED DEFAULT NULL,
  owner_id INT UNSIGNED DEFAULT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  size_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  size_unit ENUM('marla','kanal','sqft','sqy') NOT NULL DEFAULT 'marla',
  status ENUM('available','booked','reserved','sold','transferred','rental','occupied','vacant') NOT NULL DEFAULT 'available',
  corner TINYINT(1) NOT NULL DEFAULT 0,
  main_boulevard TINYINT(1) NOT NULL DEFAULT 0,
  park_facing TINYINT(1) NOT NULL DEFAULT 0,
  sale_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  rent_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  extra_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  possession_status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  possession_date DATE DEFAULT NULL,
  description TEXT DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_properties_no (property_no),
  KEY idx_prop_project (project_id),
  KEY idx_prop_type (property_type_id),
  KEY idx_prop_status (status),
  CONSTRAINT fk_prop_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_block FOREIGN KEY (block_id) REFERENCES blocks (id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_type FOREIGN KEY (property_type_id) REFERENCES property_types (id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_cat FOREIGN KEY (property_category_id) REFERENCES property_categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_owner FOREIGN KEY (owner_id) REFERENCES owners (id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  image_file VARCHAR(255) NOT NULL,
  title VARCHAR(150) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pri_property (property_id),
  CONSTRAINT fk_pri_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_prd_property (property_id),
  CONSTRAINT fk_prd_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== DOCUMENTS =====================

CREATE TABLE IF NOT EXISTS documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  related_type VARCHAR(50) NOT NULL,
  related_id INT UNSIGNED NOT NULL,
  document_type_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  uploaded_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_doc_related (related_type, related_id),
  KEY idx_doc_type (document_type_id),
  CONSTRAINT fk_doc_type FOREIGN KEY (document_type_id) REFERENCES document_types (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== SALES =====================

CREATE TABLE IF NOT EXISTS quotations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  quotation_no VARCHAR(40) NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED DEFAULT NULL,
  dealer_id INT UNSIGNED DEFAULT NULL,
  quotation_date DATE NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('draft','sent','accepted','rejected') NOT NULL DEFAULT 'draft',
  remarks VARCHAR(255) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_quotation_no (quotation_no),
  KEY idx_quo_customer (customer_id),
  CONSTRAINT fk_quo_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
  CONSTRAINT fk_quo_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_no VARCHAR(40) NOT NULL,
  quotation_id INT UNSIGNED DEFAULT NULL,
  property_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  dealer_id INT UNSIGNED DEFAULT NULL,
  booking_date DATE NOT NULL,
  sale_type ENUM('cash','installment','cash_installment') NOT NULL DEFAULT 'installment',
  total_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  token_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  booking_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  possession_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  transfer_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  installment_plan ENUM('monthly','quarterly','half_yearly','yearly','lump_sum') NOT NULL DEFAULT 'monthly',
  installment_years INT NOT NULL DEFAULT 1,
  installment_months INT NOT NULL DEFAULT 12,
  status ENUM('booking','active','completed','cancelled') NOT NULL DEFAULT 'booking',
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_booking_no (booking_no),
  KEY idx_book_property (property_id),
  KEY idx_book_customer (customer_id),
  CONSTRAINT fk_book_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE,
  CONSTRAINT fk_book_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_agreements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  agreement_no VARCHAR(40) NOT NULL,
  booking_id INT UNSIGNED NOT NULL,
  agreement_date DATE NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  status ENUM('draft','signed','registered') NOT NULL DEFAULT 'draft',
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sa_no (agreement_no),
  CONSTRAINT fk_sa_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS installments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  installment_no INT NOT NULL,
  installment_type ENUM('booking','possession','balloting','installment') NOT NULL DEFAULT 'installment',
  due_date DATE NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  penalty DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','partial','paid','overdue','waived') NOT NULL DEFAULT 'pending',
  paid_date DATE DEFAULT NULL,
  received_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_inst_booking (booking_id),
  KEY idx_inst_status (status),
  CONSTRAINT fk_inst_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS receipts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  receipt_no VARCHAR(40) NOT NULL,
  receipt_date DATE NOT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  customer_id INT UNSIGNED NOT NULL,
  booking_id INT UNSIGNED DEFAULT NULL,
  installment_id INT UNSIGNED DEFAULT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_method_id INT UNSIGNED DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  reference VARCHAR(80) DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  received_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_receipt_no (receipt_no),
  KEY idx_rec_customer (customer_id),
  KEY idx_rec_project (project_id),
  CONSTRAINT fk_rec_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
  CONSTRAINT fk_rec_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
  CONSTRAINT fk_rec_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE SET NULL,
  CONSTRAINT fk_rec_inst FOREIGN KEY (installment_id) REFERENCES installments (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== TENANTS / RENTALS =====================

CREATE TABLE IF NOT EXISTS tenants (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_no VARCHAR(40) NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  cnic VARCHAR(40) DEFAULT NULL,
  police_verification ENUM('pending','cleared','rejected') NOT NULL DEFAULT 'pending',
  emergency_contact VARCHAR(40) DEFAULT NULL,
  emergency_name VARCHAR(120) DEFAULT NULL,
  occupation VARCHAR(120) DEFAULT NULL,
  company VARCHAR(150) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tenants_no (tenant_no)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rental_agreements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  agreement_no VARCHAR(40) NOT NULL,
  property_id INT UNSIGNED NOT NULL,
  tenant_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED DEFAULT NULL,
  dealer_id INT UNSIGNED DEFAULT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  monthly_rent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  security_deposit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  advance_rent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  parking_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  maintenance_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  utility_included TINYINT(1) NOT NULL DEFAULT 0,
  rent_increase_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  notice_period_days INT NOT NULL DEFAULT 30,
  status ENUM('active','renewed','expired','terminated','vacated') NOT NULL DEFAULT 'active',
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rent_agr_no (agreement_no),
  KEY idx_ra_property (property_id),
  KEY idx_ra_tenant (tenant_id),
  CONSTRAINT fk_ra_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE,
  CONSTRAINT fk_ra_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rent_schedule (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  agreement_id INT UNSIGNED NOT NULL,
  period VARCHAR(20) NOT NULL,
  due_date DATE NOT NULL,
  rent_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  late_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
  paid_date DATE DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_rs_agreement (agreement_id),
  CONSTRAINT fk_rs_agreement FOREIGN KEY (agreement_id) REFERENCES rental_agreements (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rent_collections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  schedule_id INT UNSIGNED NOT NULL,
  agreement_id INT UNSIGNED NOT NULL,
  collection_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_method_id INT UNSIGNED DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  reference VARCHAR(80) DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_rc_schedule (schedule_id),
  CONSTRAINT fk_rc_schedule FOREIGN KEY (schedule_id) REFERENCES rent_schedule (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenant_ledger (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tl_tenant (tenant_id),
  CONSTRAINT fk_tl_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS owner_settlements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id INT UNSIGNED NOT NULL,
  agreement_id INT UNSIGNED DEFAULT NULL,
  settlement_date DATE NOT NULL,
  rent_income DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  deductions DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  settlement_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  payment_method_id INT UNSIGNED DEFAULT NULL,
  bank_id INT UNSIGNED DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_os_owner (owner_id),
  CONSTRAINT fk_os_owner FOREIGN KEY (owner_id) REFERENCES owners (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== UTILITIES =====================

CREATE TABLE IF NOT EXISTS utilities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  tenant_id INT UNSIGNED DEFAULT NULL,
  utility_type ENUM('electricity','gas','water','internet','maintenance','generator','lift') NOT NULL DEFAULT 'electricity',
  meter_no VARCHAR(60) DEFAULT NULL,
  connection_no VARCHAR(60) DEFAULT NULL,
  consumer_no VARCHAR(60) DEFAULT NULL,
  rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_util_property (property_id),
  CONSTRAINT fk_util_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS meter_readings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  utility_id INT UNSIGNED NOT NULL,
  reading_date DATE NOT NULL,
  previous_reading DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  current_reading DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  units DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_mr_utility (utility_id),
  CONSTRAINT fk_mr_utility FOREIGN KEY (utility_id) REFERENCES utilities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS utility_bills (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  utility_id INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED NOT NULL,
  tenant_id INT UNSIGNED DEFAULT NULL,
  billing_month VARCHAR(20) NOT NULL,
  bill_date DATE NOT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  penalty DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
  paid_date DATE DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ub_utility (utility_id),
  CONSTRAINT fk_ub_utility FOREIGN KEY (utility_id) REFERENCES utilities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== MAINTENANCE =====================

CREATE TABLE IF NOT EXISTS technicians (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  speciality VARCHAR(120) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS maintenance_complaints (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  complaint_no VARCHAR(40) NOT NULL,
  property_id INT UNSIGNED DEFAULT NULL,
  tenant_id INT UNSIGNED DEFAULT NULL,
  category ENUM('electric','plumbing','painting','structural','cleaning','other') NOT NULL DEFAULT 'other',
  description TEXT,
  priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  status ENUM('open','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
  reported_by VARCHAR(150) DEFAULT NULL,
  reported_date DATE NOT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_complaint_no (complaint_no),
  CONSTRAINT fk_mc_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS maintenance_tasks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL,
  technician_id INT UNSIGNED DEFAULT NULL,
  task_description VARCHAR(255) DEFAULT NULL,
  cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  completion_date DATE DEFAULT NULL,
  photos VARCHAR(255) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_mt_complaint (complaint_id),
  CONSTRAINT fk_mt_complaint FOREIGN KEY (complaint_id) REFERENCES maintenance_complaints (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== ACCOUNTING =====================

CREATE TABLE IF NOT EXISTS chart_of_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(180) NOT NULL,
  account_type ENUM('asset','liability','equity','income','expense') NOT NULL,
  parent_id INT UNSIGNED DEFAULT NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coa_code (code),
  CONSTRAINT fk_coa_parent FOREIGN KEY (parent_id) REFERENCES chart_of_accounts (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vouchers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_no VARCHAR(40) NOT NULL,
  voucher_date DATE NOT NULL,
  voucher_type ENUM('cash_payment','cash_receipt','bank_payment','bank_receipt','journal') NOT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  narration VARCHAR(255) DEFAULT NULL,
  status ENUM('draft','posted') NOT NULL DEFAULT 'posted',
  created_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_voucher_no (voucher_no),
  KEY idx_voucher_project (project_id),
  CONSTRAINT fk_voucher_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS voucher_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  item_description VARCHAR(255) DEFAULT NULL,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_vi_voucher (voucher_id),
  KEY idx_vi_account (account_id),
  CONSTRAINT fk_vi_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE CASCADE,
  CONSTRAINT fk_vi_account FOREIGN KEY (account_id) REFERENCES chart_of_accounts (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transfers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  transfer_no VARCHAR(40) NOT NULL,
  transfer_date DATE NOT NULL,
  transfer_type ENUM('customer_to_customer','bank_to_cash','bank_to_bank','customer_withdraw','owner_withdraw') NOT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  from_customer_id INT UNSIGNED DEFAULT NULL,
  to_customer_id INT UNSIGNED DEFAULT NULL,
  from_bank_id INT UNSIGNED DEFAULT NULL,
  to_bank_id INT UNSIGNED DEFAULT NULL,
  booking_id INT UNSIGNED DEFAULT NULL,
  account_id INT UNSIGNED DEFAULT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  narration VARCHAR(255) DEFAULT NULL,
  voucher_id INT UNSIGNED DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_transfer_no (transfer_no),
  KEY idx_tr_date (transfer_date),
  KEY idx_tr_type (transfer_type),
  KEY idx_tr_project (project_id),
  KEY idx_tr_from_customer (from_customer_id),
  KEY idx_tr_to_customer (to_customer_id),
  KEY idx_tr_booking (booking_id),
  CONSTRAINT fk_tr_from_customer FOREIGN KEY (from_customer_id) REFERENCES customers (id) ON DELETE SET NULL,
  CONSTRAINT fk_tr_to_customer FOREIGN KEY (to_customer_id) REFERENCES customers (id) ON DELETE SET NULL,
  CONSTRAINT fk_tr_from_bank FOREIGN KEY (from_bank_id) REFERENCES banks (id) ON DELETE SET NULL,
  CONSTRAINT fk_tr_to_bank FOREIGN KEY (to_bank_id) REFERENCES banks (id) ON DELETE SET NULL,
  CONSTRAINT fk_tr_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE SET NULL,
  CONSTRAINT fk_tr_account FOREIGN KEY (account_id) REFERENCES chart_of_accounts (id) ON DELETE SET NULL,
  CONSTRAINT fk_tr_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE SET NULL,
  CONSTRAINT fk_tr_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== CRM =====================

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_no VARCHAR(40) NOT NULL,
  name VARCHAR(180) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp VARCHAR(40) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  source ENUM('facebook','website','whatsapp','walk_in','referral','other') NOT NULL DEFAULT 'other',
  property_type_id INT UNSIGNED DEFAULT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  budget DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('new','contacted','qualified','proposal','follow_up','converted','lost') NOT NULL DEFAULT 'new',
  assigned_to INT UNSIGNED DEFAULT NULL,
  next_follow_up DATE DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lead_no (lead_no),
  KEY idx_leads_status (status),
  CONSTRAINT fk_leads_assigned FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lead_followups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  followup_date DATE NOT NULL,
  note VARCHAR(500) DEFAULT NULL,
  next_follow_up DATE DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_lf_lead (lead_id),
  CONSTRAINT fk_lf_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS call_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  call_date DATETIME NOT NULL,
  duration INT NOT NULL DEFAULT 0,
  direction ENUM('inbound','outbound') NOT NULL DEFAULT 'outbound',
  note VARCHAR(500) DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_cl_lead (lead_id),
  CONSTRAINT fk_cl_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS meetings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED DEFAULT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  meeting_date DATETIME NOT NULL,
  location VARCHAR(180) DEFAULT NULL,
  note VARCHAR(500) DEFAULT NULL,
  status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_meet_lead (lead_id),
  CONSTRAINT fk_meet_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  description VARCHAR(500) DEFAULT NULL,
  assigned_to INT UNSIGNED DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  related_type VARCHAR(50) DEFAULT NULL,
  related_id INT UNSIGNED DEFAULT NULL,
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tasks_assigned (assigned_to),
  CONSTRAINT fk_tasks_assigned FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== NOTIFICATIONS =====================

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  notification_type ENUM('installment','rent','agreement','lead','general') NOT NULL DEFAULT 'general',
  channel ENUM('sms','whatsapp','email','system') NOT NULL DEFAULT 'system',
  title VARCHAR(180) NOT NULL,
  message TEXT,
  recipient_type VARCHAR(40) DEFAULT NULL,
  recipient_id INT UNSIGNED DEFAULT NULL,
  scheduled_date DATE NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  created_date DATE NOT NULL,
  created_time TIME NOT NULL,
  updated_date DATE NOT NULL,
  updated_time TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_notif_status (status)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

DELETE FROM role_permissions;
DELETE FROM permissions;
DELETE FROM users;
DELETE FROM roles;
DELETE FROM settings;
DELETE FROM branches;
DELETE FROM countries;
DELETE FROM cities;
DELETE FROM societies;
DELETE FROM property_types;
DELETE FROM property_categories;
DELETE FROM amenities;
DELETE FROM payment_methods;
DELETE FROM banks;
DELETE FROM expense_categories;
DELETE FROM income_categories;
DELETE FROM document_types;
DELETE FROM chart_of_accounts;

INSERT INTO roles (id, name, description, is_super_admin, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Super Admin', 'Full system access', 1, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Director', 'Company director', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Manager', 'General manager', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Sales Manager', 'Manages sales team', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Recovery Officer', 'Installment recovery', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'Accounts', 'Accounting and finance', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'Reception', 'Front desk and CRM', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 'Rental Manager', 'Rental management', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 'Property Manager', 'Property maintenance', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 'Dealer', 'External dealer', 0, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO users (id, role_id, branch_id, username, password, full_name, email, phone, photo, status, last_login, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, NULL, 'admin', 'admin123', 'System Administrator', 'admin@example.com', '03001234567', NULL, 1, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO permissions (id, module, slug, name, created_date, created_time, updated_date, updated_time) VALUES
(1,  'Dashboard',   'dashboard.view',      'View Dashboard',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2,  'Master',      'master.view',         'View Master Data',    CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3,  'Projects',    'projects.view',       'View Projects',       CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4,  'Projects',    'projects.manage',     'Manage Projects',     CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5,  'Inventory',   'properties.view',     'View Properties',     CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6,  'Inventory',   'properties.manage',   'Manage Properties',   CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7,  'Customers',   'customers.view',      'View Customers',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8,  'Customers',   'customers.manage',    'Manage Customers',    CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9,  'Owners',      'owners.view',         'View Owners',         CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 'Owners',      'owners.manage',       'Manage Owners',       CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, 'Dealers',     'dealers.view',        'View Dealers',        CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(12, 'Dealers',     'dealers.manage',      'Manage Dealers',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(13, 'Sales',       'sales.view',          'View Sales',          CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(14, 'Sales',       'sales.manage',        'Manage Sales',        CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(15, 'Rentals',     'rentals.view',        'View Rentals',        CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(16, 'Rentals',     'rentals.manage',      'Manage Rentals',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(17, 'Tenants',     'tenants.view',        'View Tenants',        CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(18, 'Tenants',     'tenants.manage',      'Manage Tenants',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(19, 'Utilities',   'utilities.view',      'View Utilities',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(20, 'Utilities',   'utilities.manage',    'Manage Utilities',    CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(21, 'Maintenance', 'maintenance.view',    'View Maintenance',    CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(22, 'Maintenance', 'maintenance.manage',  'Manage Maintenance',  CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(23, 'Accounting',  'accounting.view',     'View Accounting',     CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(24, 'Accounting',  'accounting.manage',   'Manage Accounting',   CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(25, 'CRM',         'crm.view',            'View CRM',            CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(26, 'CRM',         'crm.manage',          'Manage CRM',          CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(27, 'Documents',   'documents.view',      'View Documents',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(28, 'Reports',     'reports.view',        'View Reports',        CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(29, 'Settings',    'settings.manage',     'Manage Settings',     CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(30, 'Notifications','notifications.view', 'View Notifications',  CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(31, 'Vendors',     'vendors.view',        'View Vendors',        CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(32, 'Vendors',     'vendors.manage',      'Manage Vendors',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(33, 'General Parties','general_parties.view',  'View General Parties',  CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(34, 'General Parties','general_parties.manage','Manage General Parties', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(35, 'Employees',     'employees.view',         'View Employees',        CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(36, 'Employees',     'employees.manage',       'Manage Employees',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(37, 'Contractors',   'contractors.view',       'View Contractors',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(38, 'Contractors',   'contractors.manage',     'Manage Contractors',    CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO role_permissions (role_id, permission_id, created_date, created_time, updated_date, updated_time)
SELECT 1, id, CURDATE(), CURTIME(), CURDATE(), CURTIME() FROM permissions;

INSERT INTO branches (id, name, code, address, phone, email, city_id, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Head Office', 'HO', 'Main Boulevard, Gulberg III, Lahore', '042-111111111', 'info@example.com', NULL, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO settings (id, setting_key, setting_value, created_date, created_time, updated_date, updated_time) VALUES
(1, 'company_name',  'Prime Estate Pvt Ltd',  CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'company_tagline','Real Estate ERP',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'company_address','Main Boulevard, Gulberg III, Lahore', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'company_phone', '042-111111111',         CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'company_email', 'info@example.com',      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'company_logo',  '',                      CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'currency',      'Rs.',                   CURDATE(), CURTIME(), CURTIME(), CURDATE()),
(8, 'session_timeout','60',                   CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO countries (id, name, code, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Pakistan', 'PK', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO cities (id, country_id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 'Karachi', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, 'Lahore',  CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, 'Islamabad', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 1, 'Rawalpindi', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 1, 'Faisalabad', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 1, 'Peshawar', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 1, 'Multan', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 1, 'Quetta', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO societies (id, city_id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 2, 'DHA Lahore', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, 'Bahria Town Lahore', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, 'DHA Karachi', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 3, 'Bahria Town Islamabad', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 2, 'Wapda City', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO property_types (id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Plot', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'House', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Apartment', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Shop', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Office', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'Warehouse', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'Factory', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 'Farm House', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 'Commercial Hall', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO property_categories (id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Residential', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Commercial', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Agricultural', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Mixed Use', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO amenities (id, name, icon, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Electricity', 'bi-lightning-charge', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Gas Connection', 'bi-fire', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Water Supply', 'bi-droplet', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Sewerage', 'bi-arrow-repeat', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Boundary Wall', 'bi-bricks', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'Gated Community', 'bi-shield-check', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'Park', 'bi-tree', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 'Mosque', 'bi-brightness-high', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 'School', 'bi-mortarboard', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 'Hospital', 'bi-hospital', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, 'Shopping Mall', 'bi-shop', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(12, 'Wide Roads', 'bi-signpost-split', CURDATE(), CURTIME(), CURTIME(), CURTIME());

INSERT INTO payment_methods (id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Cash', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Bank Transfer', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Cheque', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Online Payment', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Credit / Debit Card', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO banks (id, name, account_title, account_no, iban, branch, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'HBL', 'Prime Estate Pvt Ltd', '0012345678901', 'PK12HBLB000012345678901', 'Gulberg, Lahore', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'UBL', 'Prime Estate Pvt Ltd', '9876543210', 'PK12UNIL00009876543210', 'Main Boulevard, Lahore', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Meezan Bank', 'Prime Estate Pvt Ltd', '1122334455', 'PK12MEZN00001122334455', 'Gulberg, Lahore', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO expense_categories (id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Salaries', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Office Rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Utilities', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Marketing', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Transport', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'Legal Fees', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'Maintenance', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 'Miscellaneous', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO income_categories (id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Property Sales', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Rental Income', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Booking Fees', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Transfer Charges', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Service Charges', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'Other Income', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO document_types (id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 'CNIC', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Passport', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Booking Form', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Agreement', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Receipt', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'Transfer Letter', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'NOC', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 'Map / Layout', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO chart_of_accounts (id, code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES
(1, '1000', 'Cash', 'asset', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, '1001', 'Bank Accounts', 'asset', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, '1100', 'Accounts Receivable', 'asset', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, '2000', 'Accounts Payable', 'liability', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, '3000', 'Capital / Owner Equity', 'equity', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, '4000', 'Sales Income', 'income', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, '4100', 'Rental Income', 'income', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, '5000', 'Salaries Expense', 'expense', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, '5100', 'Office Rent Expense', 'expense', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, '5200', 'Utilities Expense', 'expense', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, '5300', 'Marketing Expense', 'expense', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(12, '5400', 'Transport Expense', 'expense', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(13, '5500', 'Miscellaneous Expense', 'expense', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(14, '2050', 'Employee Payable', 'liability', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(15, '2060', 'Contractor Payable', 'liability', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(16, '5600', 'Construction Expense', 'expense', NULL, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES
('4000-01', 'Commission Income', 'income', 6, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
('4000-02', 'Documentation Charges', 'income', 6, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
('4100-01', 'Plot / Property Rent', 'income', 7, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
('4100-02', 'Shop / Commercial Rent', 'income', 7, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME());
