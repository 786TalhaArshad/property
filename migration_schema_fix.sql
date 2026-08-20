-- =====================================================
-- Migration: Add missing columns to match PHP code
-- Run on existing DBs installed from property_erp_full.sql
-- Safe to run multiple times (IF NOT EXISTS guards)
-- =====================================================

-- 1. banks: add opening_balance
ALTER TABLE `banks`
  ADD COLUMN IF NOT EXISTS `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00 AFTER `status`;

-- 2. customers: add opening_balance + balance_type
ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00 AFTER `photo`,
  ADD COLUMN IF NOT EXISTS `balance_type` enum('receivable','payable') NOT NULL DEFAULT 'receivable' AFTER `opening_balance`;

-- 3. vouchers: add party tracking columns + amount + remarks + reference_no
ALTER TABLE `vouchers`
  ADD COLUMN IF NOT EXISTS `reference_no` varchar(80) DEFAULT NULL AFTER `narration`,
  ADD COLUMN IF NOT EXISTS `credit_party_type` varchar(20) DEFAULT NULL AFTER `reference_no`,
  ADD COLUMN IF NOT EXISTS `credit_party_id` int(10) unsigned DEFAULT NULL AFTER `credit_party_type`,
  ADD COLUMN IF NOT EXISTS `debit_party_type` varchar(20) DEFAULT NULL AFTER `credit_party_id`,
  ADD COLUMN IF NOT EXISTS `debit_party_id` int(10) unsigned DEFAULT NULL AFTER `debit_party_type`,
  ADD COLUMN IF NOT EXISTS `amount` decimal(14,2) NOT NULL DEFAULT 0.00 AFTER `debit_party_id`,
  ADD COLUMN IF NOT EXISTS `remarks` varchar(255) DEFAULT NULL AFTER `amount`;

-- 4. owner_settlements: add voucher_id
ALTER TABLE `owner_settlements`
  ADD COLUMN IF NOT EXISTS `voucher_id` int(10) unsigned DEFAULT NULL AFTER `remarks`;

-- 5. owner_ledger: add voucher_id
ALTER TABLE `owner_ledger`
  ADD COLUMN IF NOT EXISTS `voucher_id` int(10) unsigned DEFAULT NULL AFTER `balance`;

-- 6. investor_ledger: add voucher_id
ALTER TABLE `investor_ledger`
  ADD COLUMN IF NOT EXISTS `voucher_id` int(10) unsigned DEFAULT NULL AFTER `balance`;

-- 7. rent_collections: add voucher_id
ALTER TABLE `rent_collections`
  ADD COLUMN IF NOT EXISTS `voucher_id` int(10) unsigned DEFAULT NULL AFTER `remarks`;

-- 8. dealer_payments: add voucher_id
ALTER TABLE `dealer_payments`
  ADD COLUMN IF NOT EXISTS `voucher_id` int(10) unsigned DEFAULT NULL AFTER `remarks`;

-- 9. purchase_items: add product_id (PHP uses it; schema only had expense_account_id)
ALTER TABLE `purchase_items`
  ADD COLUMN IF NOT EXISTS `product_id` int(10) unsigned DEFAULT NULL AFTER `purchase_id`;
