-- =====================================================================
-- DUMMY / SAMPLE DATA
-- Real Estate ERP (Prime Estate Pvt Ltd)
-- ---------------------------------------------------------------------
-- Import this AFTER a fresh install of database.sql, for example:
--   mysql -u root property_erp < dummy_data.sql
-- Do NOT run twice on the same database (primary keys will conflict).
-- All created/updated timestamps use CURDATE()/CURTIME() like the seed.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ===================== USERS =====================

INSERT INTO users (id, role_id, branch_id, username, password, full_name, email, phone, status, created_date, created_time, updated_date, updated_time) VALUES
(2, 4, 1, 'ahmed', 'ahmed123', 'Ahmed Raza', 'ahmed@example.com', '03011112222', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 6, 1, 'sara', 'sara123', 'Sara Khan', 'sara@example.com', '03011113333', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 8, 1, 'bilal', 'bilal123', 'Bilal Hussain', 'bilal@example.com', '03011114444', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== PROJECTS / LAYOUT =====================

INSERT INTO projects (id, name, developer, location, country_id, city_id, society_id, noc, description, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Al-Noor Residency', 'Al-Noor Developers', 'DHA Phase 5, Lahore', 1, 2, 1, 'NOC-2024-001', 'Residential housing scheme with plots and houses', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Green Valley Apartments', 'Green Valley Builders', 'Bahria Town, Lahore', 1, 2, 2, 'NOC-2024-014', 'Premium apartment towers', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Skyline Commercial Plaza', 'Skyline Group', 'Main Boulevard, Gulberg III, Lahore', 1, 2, 2, 'NOC-2025-006', 'Commercial shops on main boulevard', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO blocks (id, project_id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 'Block A', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, 'Block B', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, 'Block C', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 1, 'Block D', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 2, 'Tower 1', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 2, 'Tower 2', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 3, 'Commercial Avenue', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO roads (id, project_id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 'Main Boulevard Road', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, '12th Avenue Road', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 2, 'Jinnah Avenue', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 3, 'Commercial Avenue Road', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO streets (id, project_id, block_id, name, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 1, 'Street 1', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, 1, 'Street 2', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, 2, 'Street 5', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 1, 4, 'Street 10', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 3, 7, 'Main Walkway', CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== CUSTOMERS =====================

INSERT INTO customers (id, customer_no, full_name, cnic, phone, whatsapp, email, address, city_id, nominee_name, nominee_cnic, nominee_relation, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'CUST-0001', 'Muhammad Ali', '35202-1234567-1', '03001234001', '03001234001', 'muhammad.ali@gmail.com', 'House 12, Block G, Model Town, Lahore', 2, 'Sana Ali', '35202-2234567-1', 'Wife', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'CUST-0002', 'Fatima Noor', '35202-7654321-3', '03001234002', '03001234002', 'fatima.noor@gmail.com', 'Flat 4B, Gulberg III, Lahore', 2, 'Omar Noor', '35202-8654321-3', 'Brother', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'CUST-0003', 'Ahmed Hassan', '35201-1112223-5', '03001234003', NULL, 'ahmed.hassan@gmail.com', 'DHA Phase 6, Karachi', 1, 'Zoya Hassan', '35201-2112223-5', 'Wife', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'CUST-0004', 'Sana Tariq', '35202-3334445-7', '03001234004', '03001234004', 'sana.tariq@gmail.com', 'House 5, Faisal Town, Lahore', 2, 'Tariq Mehmood', '35202-4334445-7', 'Father', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'CUST-0005', 'Usman Ghani', '35202-5556667-9', '03001234005', '03001234005', 'usman.ghani@gmail.com', 'G-10/4, Islamabad', 3, 'Ghani Bakhsh', '35202-6556667-9', 'Father', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'CUST-0006', 'Hira Shahid', '35202-7778889-1', '03001234006', NULL, 'hira.shahid@gmail.com', 'Westridge 2, Rawalpindi', 4, 'Shahid Malik', '35202-8778889-1', 'Father', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== OWNERS =====================

INSERT INTO owners (id, owner_no, full_name, cnic, phone, whatsapp, email, address, bank_id, bank_account_title, bank_account_no, commission_rate, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'OWN-0001', 'Khalid Mahmood', '35201-9990001-3', '03002001001', '03002001001', 'khalid.mahmood@gmail.com', 'House 3, Shadman, Lahore', 1, 'Khalid Mahmood', '0102030405', 0.00, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'OWN-0002', 'Naeem Akhtar', '35201-2220003-5', '03002002002', '03002002002', 'naeem.akhtar@gmail.com', 'Street 4, Iqbal Town, Lahore', 2, 'Naeem Akhtar', '0203040506', 0.00, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'OWN-0003', 'Zahid Mehmood', '35201-4440005-7', '03002003003', NULL, 'zahid.mehmood@gmail.com', 'Gulshan-e-Ravi, Lahore', 3, 'Zahid Mehmood', '0304050607', 0.00, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== DEALERS =====================

INSERT INTO dealers (id, dealer_no, full_name, cnic, phone, whatsapp, email, address, dealer_type, commission_rate, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'DLR-0001', 'Property Link Associates', '35201-1239876-5', '03003001001', '03003001001', 'info@propertylink.pk', 'Office 2, Liberty Market, Lahore', 'agent', 2.50, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'DLR-0002', 'Rashid & Co Real Estate', '35201-4563218-9', '03003002002', '03003002002', 'rashidco@gmail.com', 'Shop 8, Johar Town, Lahore', 'dealer', 2.00, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== PROPERTIES =====================

INSERT INTO properties (id, property_no, file_no, plot_no, house_no, apartment_no, shop_no, project_id, block_id, road_id, street_id, property_type_id, property_category_id, owner_id, customer_id, size_value, size_unit, status, corner, main_boulevard, park_facing, sale_price, rent_amount, possession_status, possession_date, description, created_date, created_time, updated_date, updated_time) VALUES
(1, 'PRP-0001', 'F-1001', 'Plot 5-A', NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 5, 5.00, 'marla', 'sold', 0, 0, 0, 6500000.00, 0.00, 'completed', '2026-04-10', 'Corner plot near park in Block A', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'PRP-0002', 'F-1002', 'Plot 10-B', NULL, NULL, NULL, 1, 1, 1, 2, 1, 1, 1, NULL, 10.00, 'marla', 'available', 0, 0, 0, 11500000.00, 0.00, 'pending', NULL, 'Facing 40 ft road', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'PRP-0003', 'F-1003', NULL, 'House 1', NULL, NULL, 1, 2, 2, 3, 2, 1, 2, NULL, 1.00, 'kanal', 'booked', 0, 0, 1, 15000000.00, 0.00, 'pending', NULL, 'Double storey house, park facing', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'PRP-0004', 'F-1004', 'Plot 5-C', NULL, NULL, NULL, 1, 2, 2, 3, 1, 1, 3, NULL, 5.00, 'marla', 'available', 0, 0, 0, 7200000.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'PRP-0005', 'F-2001', NULL, NULL, 'Apt 3-B', NULL, 2, 5, 3, NULL, 3, 1, 3, NULL, 3.00, 'marla', 'rental', 0, 0, 0, 9500000.00, 60000.00, 'in_progress', NULL, '2 bed apartment in Tower 1', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'PRP-0006', 'F-1005', 'Plot 10-D', NULL, NULL, NULL, 1, 3, 2, NULL, 1, 1, 1, NULL, 10.00, 'marla', 'available', 0, 0, 1, 12000000.00, 0.00, 'pending', NULL, 'Park facing corner in Block C', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'PRP-0007', 'F-3001', NULL, NULL, NULL, 'Shop 12', 3, 7, 4, 5, 4, 2, 2, NULL, 2.00, 'marla', 'available', 0, 1, 0, 18000000.00, 120000.00, 'pending', NULL, 'Main boulevard commercial shop', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 'PRP-0008', 'F-2002', NULL, 'House 8', NULL, NULL, 2, 6, 3, NULL, 2, 1, 2, NULL, 5.00, 'marla', 'rental', 0, 0, 0, 11000000.00, 85000.00, 'completed', '2026-01-15', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 'PRP-0009', 'F-1006', 'Plot 5-E', NULL, NULL, NULL, 1, 3, 2, NULL, 1, 1, 1, 4, 5.00, 'marla', 'sold', 0, 0, 0, 7000000.00, 0.00, 'completed', '2026-01-20', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 'PRP-0010', 'F-1007', 'Plot 8-F', NULL, NULL, NULL, 1, 1, 1, 2, 1, 1, 3, NULL, 8.00, 'marla', 'booked', 0, 0, 0, 8500000.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, 'PRP-0011', 'F-2003', NULL, 'House 15', NULL, NULL, 2, 6, 3, NULL, 2, 1, 1, NULL, 5.00, 'marla', 'rental', 0, 0, 0, 12500000.00, 95000.00, 'in_progress', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO property_amenities (id, property_id, amenity_id, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, 3, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, 6, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 2, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 2, 5, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 3, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 3, 2, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 3, 7, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 5, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 5, 7, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, 5, 9, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(12, 7, 11, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(13, 7, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(14, 8, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(15, 8, 2, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(16, 11, 3, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== SALES =====================

INSERT INTO quotations (id, quotation_no, customer_id, property_id, dealer_id, quotation_date, amount, status, remarks, created_date, created_time, updated_date, updated_time) VALUES
(1, 'QUO-0001', 1, 2, 1, '2026-06-10', 11500000.00, 'sent', 'Price slightly negotiable', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'QUO-0002', 3, 4, NULL, '2026-06-25', 7200000.00, 'accepted', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'QUO-0003', 6, 7, 2, '2026-07-01', 17500000.00, 'draft', 'Awaiting final approval', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO bookings (id, booking_no, quotation_id, property_id, customer_id, dealer_id, booking_date, total_price, discount, token_amount, booking_amount, possession_charges, transfer_charges, installment_plan, installment_years, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'BK-0001', 2, 3, 2, 1, '2025-06-15', 15000000.00, 150000.00, 500000.00, 500000.00, 100000.00, 50000.00, 'quarterly', 2, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'BK-0002', NULL, 1, 5, NULL, '2025-03-10', 6500000.00, 0.00, 300000.00, 300000.00, 0.00, 0.00, 'lump_sum', 1, 'completed', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'BK-0003', 3, 10, 6, 2, '2026-07-20', 8500000.00, 50000.00, 250000.00, 250000.00, 50000.00, 25000.00, 'monthly', 1, 'booking', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO sale_agreements (id, agreement_no, booking_id, agreement_date, file_path, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'AGR-0001', 1, '2025-12-01', NULL, 'signed', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'AGR-0002', 2, '2026-03-15', NULL, 'registered', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO installments (id, booking_id, installment_no, installment_type, due_date, amount, penalty, paid_amount, status, paid_date, received_by, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 1, 'booking', '2025-06-15', 1000000.00, 0.00, 1000000.00, 'paid', '2025-06-15', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, 2, 'possession', '2027-06-15', 100000.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, 3, 'installment', '2025-09-15', 1712500.00, 0.00, 1712500.00, 'paid', '2025-09-16', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 1, 4, 'installment', '2025-12-15', 1712500.00, 0.00, 1712500.00, 'paid', '2025-12-16', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 1, 5, 'installment', '2026-03-15', 1712500.00, 0.00, 1000000.00, 'partial', '2026-03-20', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 1, 6, 'installment', '2026-06-15', 1712500.00, 0.00, 0.00, 'overdue', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 1, 7, 'installment', '2026-09-15', 1712500.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 1, 8, 'installment', '2026-12-15', 1712500.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 1, 9, 'installment', '2027-03-15', 1712500.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 1, 10, 'installment', '2027-06-15', 1712500.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, 2, 1, 'booking', '2025-03-10', 600000.00, 0.00, 600000.00, 'paid', '2025-03-10', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(12, 2, 2, 'possession', '2026-03-10', 0.00, 0.00, 0.00, 'paid', '2026-03-10', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(13, 2, 3, 'installment', '2026-03-10', 5900000.00, 0.00, 5900000.00, 'paid', '2026-03-10', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(14, 3, 1, 'booking', '2026-07-20', 500000.00, 0.00, 500000.00, 'paid', '2026-07-20', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(15, 3, 2, 'possession', '2027-07-20', 50000.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(16, 3, 3, 'installment', '2026-08-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(17, 3, 4, 'installment', '2026-09-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(18, 3, 5, 'installment', '2026-10-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(19, 3, 6, 'installment', '2026-11-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(20, 3, 7, 'installment', '2026-12-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(21, 3, 8, 'installment', '2027-01-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(22, 3, 9, 'installment', '2027-02-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(23, 3, 10, 'installment', '2027-03-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(24, 3, 11, 'installment', '2027-04-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(25, 3, 12, 'installment', '2027-05-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(26, 3, 13, 'installment', '2027-06-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(27, 3, 14, 'installment', '2027-07-20', 656250.00, 0.00, 0.00, 'pending', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO receipts (id, receipt_no, receipt_date, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, created_date, created_time, updated_date, updated_time) VALUES
(1, 'RCT-0001', '2025-06-15', 2, 1, 1, 1000000.00, 1, NULL, 'Bank draft 1001', 'Token + booking amount', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'RCT-0002', '2025-09-16', 2, 1, 3, 1712500.00, 2, 1, 'TRX-9981', '1st quarterly installment', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'RCT-0003', '2025-12-16', 2, 1, 4, 1712500.00, 2, 1, 'TRX-10102', '2nd quarterly installment', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'RCT-0004', '2026-03-20', 2, 1, 5, 1000000.00, 3, 2, 'Cheque 0092', 'Partial 3rd installment', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'RCT-0005', '2025-03-10', 5, 2, 11, 600000.00, 1, NULL, NULL, 'Token + booking amount', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 'RCT-0006', '2026-03-10', 5, 2, 13, 5900000.00, 2, 3, 'TRX-12050', 'Full and final payment', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 'RCT-0007', '2026-07-20', 6, 3, 14, 500000.00, 1, NULL, NULL, 'Token + booking amount', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== TENANTS / RENTALS =====================

INSERT INTO tenants (id, tenant_no, full_name, cnic, police_verification, emergency_contact, emergency_name, occupation, company, address, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'TEN-0001', 'Adnan Yousaf', '35201-3332221-5', 'cleared', '03004444001', 'Bilal Yousaf', 'Software Engineer', 'TechCorp (Pvt) Ltd', 'Street 4, Gulberg II, Lahore', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'TEN-0002', 'Shahzad Riaz', '35202-6665554-7', 'cleared', '03004444002', 'Riaz Ahmed', 'Businessman', NULL, 'Shop 9, Anarkali Bazaar, Lahore', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'TEN-0003', 'Mariam Farooq', '35202-9998887-9', 'cleared', '03004444003', 'Farooq Khan', 'Doctor', 'City General Hospital', 'Civic Centre, Bahria Town, Lahore', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO rental_agreements (id, agreement_no, property_id, tenant_id, owner_id, dealer_id, start_date, end_date, monthly_rent, security_deposit, advance_rent, parking_charges, maintenance_charges, utility_included, rent_increase_percent, notice_period_days, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'RA-0001', 5, 1, 3, NULL, '2026-01-01', '2026-12-31', 60000.00, 120000.00, 60000.00, 0.00, 0.00, 0, 5.00, 60, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'RA-0002', 8, 2, 2, NULL, '2026-02-01', '2027-01-31', 85000.00, 170000.00, 85000.00, 0.00, 0.00, 0, 5.00, 30, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'RA-0003', 11, 3, 1, NULL, '2026-04-01', '2027-03-31', 95000.00, 190000.00, 0.00, 0.00, 0.00, 0, 0.00, 30, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO rent_schedule (id, agreement_id, period, due_date, rent_amount, late_charges, paid_amount, status, paid_date, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, '2026-01', '2026-01-01', 60000.00, 0.00, 60000.00, 'paid', '2026-01-05', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, '2026-02', '2026-02-01', 60000.00, 0.00, 60000.00, 'paid', '2026-02-05', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, '2026-03', '2026-03-01', 60000.00, 0.00, 60000.00, 'paid', '2026-03-05', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 1, '2026-04', '2026-04-01', 60000.00, 0.00, 60000.00, 'paid', '2026-04-05', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 1, '2026-05', '2026-05-01', 60000.00, 0.00, 60000.00, 'paid', '2026-05-05', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 1, '2026-06', '2026-06-01', 60000.00, 0.00, 60000.00, 'paid', '2026-06-05', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 1, '2026-07', '2026-07-01', 60000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 1, '2026-08', '2026-08-01', 60000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 1, '2026-09', '2026-09-01', 60000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 1, '2026-10', '2026-10-01', 60000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, 1, '2026-11', '2026-11-01', 60000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(12, 1, '2026-12', '2026-12-01', 60000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(13, 2, '2026-02', '2026-02-01', 85000.00, 0.00, 85000.00, 'paid', '2026-02-06', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(14, 2, '2026-03', '2026-03-01', 85000.00, 0.00, 85000.00, 'paid', '2026-03-06', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(15, 2, '2026-04', '2026-04-01', 85000.00, 0.00, 85000.00, 'paid', '2026-04-06', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(16, 2, '2026-05', '2026-05-01', 85000.00, 0.00, 85000.00, 'paid', '2026-05-06', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(17, 2, '2026-06', '2026-06-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(18, 2, '2026-07', '2026-07-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(19, 2, '2026-08', '2026-08-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(20, 2, '2026-09', '2026-09-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(21, 2, '2026-10', '2026-10-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(22, 2, '2026-11', '2026-11-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(23, 2, '2026-12', '2026-12-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(24, 2, '2027-01', '2027-01-01', 85000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(25, 3, '2026-04', '2026-04-01', 95000.00, 0.00, 95000.00, 'paid', '2026-04-07', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(26, 3, '2026-05', '2026-05-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(27, 3, '2026-06', '2026-06-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(28, 3, '2026-07', '2026-07-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(29, 3, '2026-08', '2026-08-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(30, 3, '2026-09', '2026-09-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(31, 3, '2026-10', '2026-10-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(32, 3, '2026-11', '2026-11-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(33, 3, '2026-12', '2026-12-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(34, 3, '2027-01', '2027-01-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(35, 3, '2027-02', '2027-02-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(36, 3, '2027-03', '2027-03-01', 95000.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO rent_collections (id, schedule_id, agreement_id, collection_date, amount, payment_method_id, bank_id, reference, remarks, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 1, '2026-01-05', 60000.00, 1, NULL, NULL, 'January 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, 1, '2026-02-05', 60000.00, 1, NULL, NULL, 'February 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 3, 1, '2026-03-05', 60000.00, 2, 1, 'TRX-8801', 'March 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 4, 1, '2026-04-05', 60000.00, 1, NULL, NULL, 'April 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 5, 1, '2026-05-05', 60000.00, 2, 1, 'TRX-8877', 'May 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 6, 1, '2026-06-05', 60000.00, 1, NULL, NULL, 'June 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 13, 2, '2026-02-06', 85000.00, 1, NULL, NULL, 'February 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 14, 2, '2026-03-06', 85000.00, 2, 2, 'TRX-8912', 'March 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 15, 2, '2026-04-06', 85000.00, 1, NULL, NULL, 'April 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 16, 2, '2026-05-06', 85000.00, 1, NULL, NULL, 'May 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(11, 25, 3, '2026-04-07', 95000.00, 2, 3, 'TRX-9020', 'April 2026 rent', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO tenant_ledger (id, tenant_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, '2026-01-01', 'Security deposit RA-0001', 0.00, 120000.00, 120000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, '2026-01-01', 'Advance rent RA-0001', 0.00, 60000.00, 180000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 1, '2026-01-01', 'Rent due January 2026', 60000.00, 0.00, 120000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 1, '2026-02-01', 'Rent due February 2026', 60000.00, 0.00, 60000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 1, '2026-02-05', 'Rent received February 2026', 0.00, 60000.00, 120000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 1, '2026-03-01', 'Rent due March 2026', 60000.00, 0.00, 60000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(7, 1, '2026-03-05', 'Rent received March 2026', 0.00, 60000.00, 120000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(8, 2, '2026-02-01', 'Security deposit RA-0002', 0.00, 170000.00, 170000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(9, 2, '2026-02-01', 'Rent due February 2026', 85000.00, 0.00, 85000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(10, 3, '2026-04-01', 'Security deposit RA-0003', 0.00, 190000.00, 190000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO owner_settlements (id, owner_id, agreement_id, settlement_date, rent_income, deductions, settlement_amount, status, payment_method_id, bank_id, remarks, created_date, created_time, updated_date, updated_time) VALUES
(1, 3, 1, '2026-06-30', 360000.00, 0.00, 360000.00, 'paid', 1, NULL, 'June 2026 settlement RA-0001', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, 2, '2026-06-30', 340000.00, 5000.00, 335000.00, 'pending', NULL, NULL, 'June 2026 settlement RA-0002 (5% management fee)', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO owner_ledger (id, owner_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES
(1, 3, '2026-07-05', 'Rent settlement RA-0001 June 2026', 0.00, 360000.00, 360000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, '2026-06-30', 'Rent settlement RA-0002 June 2026 (pending)', 0.00, 335000.00, 335000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== UTILITIES =====================

INSERT INTO utilities (id, property_id, tenant_id, utility_type, meter_no, connection_no, consumer_no, rate, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 5, 1, 'electricity', 'MTR-E-1101', 'CON-1101', 'CSR-1101', 28.00, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 5, 1, 'gas', 'MTR-G-1102', 'CON-1102', 'CSR-1102', 80.00, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 8, 2, 'electricity', 'MTR-E-2203', 'CON-2203', 'CSR-2203', 30.00, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 11, 3, 'water', NULL, 'CON-3301', 'CSR-3301', 25.00, 'active', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO meter_readings (id, utility_id, reading_date, previous_reading, current_reading, units, rate, amount, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, '2026-06-01', 2400.00, 2650.00, 250.00, 28.00, 7000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, '2026-07-01', 2650.00, 2910.00, 260.00, 28.00, 7280.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 3, '2026-06-01', 1100.00, 1320.00, 220.00, 30.00, 6600.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 3, '2026-07-01', 1320.00, 1550.00, 230.00, 30.00, 6900.00, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO utility_bills (id, utility_id, property_id, tenant_id, billing_month, bill_date, due_date, amount, penalty, paid_amount, status, paid_date, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 5, 1, '2026-06', '2026-06-05', '2026-06-20', 7000.00, 0.00, 7000.00, 'paid', '2026-06-18', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, 5, 1, '2026-07', '2026-07-05', '2026-07-20', 7280.00, 0.00, 0.00, 'pending', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 3, 8, 2, '2026-06', '2026-06-05', '2026-06-20', 6600.00, 100.00, 4000.00, 'partial', '2026-06-22', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 4, 11, 3, '2026-06', '2026-06-05', '2026-06-20', 2500.00, 0.00, 2500.00, 'paid', '2026-06-10', CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== MAINTENANCE =====================

INSERT INTO technicians (id, name, phone, speciality, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Iqbal Electrician', '03005555001', 'Electric', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Rashid Plumber', '03005555002', 'Plumbing', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO maintenance_complaints (id, complaint_no, property_id, tenant_id, category, description, priority, status, reported_by, reported_date, created_date, created_time, updated_date, updated_time) VALUES
(1, 'MC-0001', 5, 1, 'electric', 'Power keeps tripping in apartment 3-B', 'urgent', 'in_progress', 'Adnan Yousaf', '2026-07-15', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'MC-0002', 8, 2, 'plumbing', 'Leaking pipe in bathroom', 'medium', 'completed', 'Shahzad Riaz', '2026-06-20', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'MC-0003', 11, 3, 'painting', 'Wall paint peeling in bedroom', 'low', 'open', 'Mariam Farooq', '2026-07-25', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO maintenance_tasks (id, complaint_id, technician_id, task_description, cost, completion_date, photos, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 1, 'Check main wiring and replace circuit breaker', 5000.00, '2026-07-18', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, 2, 'Replace bathroom pipe joints', 3500.00, '2026-06-25', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== ACCOUNTING =====================

INSERT INTO vouchers (id, voucher_no, voucher_date, voucher_type, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES
(1, 'CR-0001', '2026-07-05', 'cash_receipt', 'Rent collected RA-0001 June 2026', 'posted', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'CP-0001', '2026-07-10', 'cash_payment', 'Staff salaries - July 2026', 'posted', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'BP-0001', '2026-07-15', 'bank_payment', 'Marketing campaign - Gulberg hoarding', 'posted', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO voucher_items (id, voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, 1, 'Cash received', 60000.00, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 1, 7, 'Rental income RA-0001', 0.00, 60000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 2, 8, 'Salaries July 2026', 150000.00, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 2, 1, 'Cash paid', 0.00, 150000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 3, 11, 'Marketing hoarding Gulberg', 25000.00, 0.00, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(6, 3, 2, 'Bank transfer', 0.00, 25000.00, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== CRM =====================

INSERT INTO leads (id, lead_no, name, phone, whatsapp, email, source, property_type_id, project_id, budget, status, assigned_to, next_follow_up, remarks, created_date, created_time, updated_date, updated_time) VALUES
(1, 'LD-0001', 'Taimoor Sheikh', '03006666001', '03006666001', 'taimoor.sheikh@gmail.com', 'facebook', 1, 1, 12000000.00, 'follow_up', 2, '2026-08-05', 'Interested in Block A plots', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'LD-0002', 'Rabia Saleem', '03006666002', '03006666002', 'rabia.saleem@gmail.com', 'whatsapp', 2, 2, 10000000.00, 'qualified', 4, '2026-08-02', 'Site visit scheduled for Tower 1', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'LD-0003', 'Faisal Mirza', '03006666003', NULL, 'faisal.mirza@gmail.com', 'website', 4, 3, 20000000.00, 'new', 2, '2026-08-01', 'Wants a main boulevard shop', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'LD-0004', 'Asma Iqbal', '03006666004', '03006666004', 'asma.iqbal@gmail.com', 'referral', 1, 1, 7000000.00, 'converted', 4, NULL, 'Converted - purchased PRP-0009', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'LD-0005', 'Kamran Javed', '03006666005', NULL, NULL, 'walk_in', 3, 2, 9500000.00, 'lost', 2, NULL, 'Budget too low, deal lost', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO lead_followups (id, lead_id, followup_date, note, next_follow_up, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, '2026-07-20', 'Shared project brochure, customer interested in Block A', '2026-08-05', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, '2026-07-22', 'Site visit scheduled for Tower 1 apartment', '2026-08-02', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 4, '2026-05-10', 'Customer confirmed booking for PRP-0009', NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO call_logs (id, lead_id, call_date, duration, direction, note, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, '2026-07-20 11:30:00', 12, 'outbound', 'Discussed payment plan options', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, '2026-07-21 15:00:00', 8, 'inbound', 'Customer asked about maintenance charges', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 3, '2026-07-22 10:15:00', 5, 'outbound', 'Line busy, call again', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 5, '2026-06-18 14:45:00', 20, 'outbound', 'Budget too low, suggested smaller property', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO meetings (id, lead_id, customer_id, meeting_date, location, note, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 1, NULL, '2026-07-25 11:00:00', 'DHA Phase 5 office', 'Site visit Block A plots', 'completed', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 2, NULL, '2026-07-28 16:00:00', 'Green Valley site office', 'Apartment tour Tower 1', 'scheduled', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, NULL, 1, '2026-06-15 12:00:00', 'Head Office', 'Finalized quotation for Plot 10-B', 'completed', CURDATE(), CURTIME(), CURDATE(), CURTIME());

INSERT INTO tasks (id, title, description, assigned_to, due_date, priority, status, related_type, related_id, created_date, created_time, updated_date, updated_time) VALUES
(1, 'Follow up on BK-0001 overdue installment', 'Call customer for June 2026 installment', 5, '2026-07-05', 'high', 'in_progress', 'booking', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'Prepare June rent settlement report', 'Compile rent collections for owners', 3, '2026-07-02', 'medium', 'completed', NULL, NULL, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'Schedule maintenance for MC-0001', 'Assign technician for electrical work', 4, '2026-07-16', 'medium', 'completed', 'maintenance', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'Site visit - LD-0002', 'Accompany prospect to Tower 1', 4, '2026-07-28', 'low', 'pending', 'lead', 2, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'Renew RA-0001 agreement', 'Prepare renewal for January 2027', 4, '2026-12-15', 'medium', 'pending', 'agreement', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== NOTIFICATIONS =====================

INSERT INTO notifications (id, notification_type, channel, title, message, recipient_type, recipient_id, scheduled_date, status, created_date, created_time, updated_date, updated_time) VALUES
(1, 'installment', 'sms', 'BK-0001 June installment overdue', 'Installment no. 6 of Rs. 1,712,500 for BK-0001 is overdue since 2026-06-15', 'customer', 2, '2026-07-01', 'sent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'rent', 'whatsapp', 'RA-0002 June rent due', 'June 2026 rent of Rs. 85,000 is due', 'tenant', 2, '2026-06-25', 'sent', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'lead', 'system', 'Follow-up due for LD-0001', 'Next follow-up scheduled for 2026-08-05', 'user', 2, '2026-08-01', 'pending', CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'general', 'system', 'Rent settlement paid to owner', 'Rs. 360,000 settled for RA-0001', 'owner', 3, '2026-07-05', 'sent', CURDATE(), CURTIME(), CURDATE(), CURTIME());

-- ===================== DOCUMENTS =====================

INSERT INTO documents (id, related_type, related_id, document_type_id, title, file_path, remarks, uploaded_by, created_date, created_time, updated_date, updated_time) VALUES
(1, 'customer', 2, 1, 'Fatima Noor CNIC', 'assets/uploads/documents/cust-cnic-0002.pdf', NULL, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(2, 'booking', 1, 3, 'BK-0001 Booking Form', 'assets/uploads/documents/bk-0001-form.pdf', 'Signed copy', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(3, 'property', 1, 8, 'PRP-0001 Layout Map', 'assets/uploads/documents/prp-0001-map.pdf', NULL, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(4, 'agreement', 2, 4, 'AGR-0002 Sale Agreement', 'assets/uploads/documents/agr-0002.pdf', 'Registered', 1, CURDATE(), CURTIME(), CURDATE(), CURTIME()),
(5, 'tenant', 1, 1, 'Adnan Yousaf CNIC', 'assets/uploads/documents/tenant-cnic-0001.pdf', NULL, 1, CURDATE(), CURTIME(), CURDATE(), CURTIME());

SET FOREIGN_KEY_CHECKS = 1;
