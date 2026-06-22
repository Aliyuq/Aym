-- ALMAF BAKERY Management System Database Schema
-- MySQL DDL for InfinityFree Hosting
-- Character Set: utf8mb4 (Unicode support)

-- Drop existing tables if they exist (for clean setup)
DROP TABLE IF EXISTS `distributor_ledgers`;
DROP TABLE IF EXISTS `sales_logs`;
DROP TABLE IF EXISTS `daily_production`;
DROP TABLE IF EXISTS `attendance_payroll`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `ingredients`;
DROP TABLE IF EXISTS `products`;

-- =====================================================
-- TABLE: Products
-- Description: Bakery products with wholesale/retail pricing
-- =====================================================
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uid` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique alphanumeric identifier',
    `name` VARCHAR(100) NOT NULL COMMENT 'Product name (e.g., White Loaf, Brown Bread)',
    `wholesale_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Wholesale unit price',
    `retail_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Retail unit price',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_uid (uid),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Ingredients
-- Description: Raw materials and inventory tracking
-- =====================================================
CREATE TABLE `ingredients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uid` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique alphanumeric identifier',
    `name` VARCHAR(100) NOT NULL COMMENT 'Ingredient name (e.g., All-Purpose Flour)',
    `current_stock` DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Current quantity in stock',
    `minimum_stock_threshold` DECIMAL(10, 2) NOT NULL DEFAULT 10 COMMENT 'Alert threshold',
    `unit` VARCHAR(20) NOT NULL DEFAULT 'kg' COMMENT 'Unit of measurement (kg, liters, bags, etc.)',
    `unit_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Cost per unit',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_uid (uid),
    INDEX idx_stock_alert (current_stock, minimum_stock_threshold)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Staff
-- Description: Employee records with payroll details
-- =====================================================
CREATE TABLE `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uid` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique staff identifier',
    `name` VARCHAR(100) NOT NULL COMMENT 'Full name of staff member',
    `email` VARCHAR(100) UNIQUE COMMENT 'Email address for authentication',
    `password_hash` VARCHAR(255) COMMENT 'Hashed password for non-admin users',
    `role` ENUM('Baker', 'Driver', 'Cashier', 'Manager', 'Admin') NOT NULL DEFAULT 'Baker' COMMENT 'Staff role/position',
    `contact_details` VARCHAR(20) COMMENT 'Phone number or contact info',
    `hire_date` DATE NOT NULL COMMENT 'Date staff was hired',
    `bank_name` VARCHAR(100) COMMENT 'Bank name for salary payment',
    `account_number` VARCHAR(50) COMMENT 'Bank account number',
    `account_name` VARCHAR(100) COMMENT 'Account holder name',
    `daily_rate` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Daily wage in currency',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Staff active status',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_email (email),
    INDEX idx_uid (uid),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Attendance & Payroll
-- Description: Daily work logs and salary calculations
-- =====================================================
CREATE TABLE `attendance_payroll` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uid` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique transaction identifier',
    `staff_id` INT NOT NULL COMMENT 'Foreign key to staff table',
    `date` DATE NOT NULL COMMENT 'Work date',
    `days_worked` DECIMAL(5, 2) NOT NULL DEFAULT 1.0 COMMENT 'Days worked (supports partial days)',
    `total_calculated_pay` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Calculated salary for period',
    `status` ENUM('Paid', 'Unpaid', 'Pending') DEFAULT 'Unpaid' COMMENT 'Payment status',
    `paid_date` DATE COMMENT 'Date payment was made',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_staff_date (staff_id, date),
    INDEX idx_status (status),
    INDEX idx_uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Daily Production
-- Description: Daily production metrics and yield tracking
-- =====================================================
CREATE TABLE `daily_production` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uid` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique production record ID',
    `date` DATE NOT NULL UNIQUE COMMENT 'Production date',
    `flour_bags_used` INT NOT NULL DEFAULT 0 COMMENT 'Number of flour bags (standard: 1 bag = 100 loaves)',
    `expected_yield` INT NOT NULL DEFAULT 0 COMMENT 'Expected loaves based on standard calculation',
    `actual_yield` INT NOT NULL DEFAULT 0 COMMENT 'Actual loaves produced',
    `damaged_loaves` INT NOT NULL DEFAULT 0 COMMENT 'Number of damaged/unsellable loaves',
    `net_yield` INT GENERATED ALWAYS AS (actual_yield - damaged_loaves) STORED COMMENT 'Net sellable loaves',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Sales Logs
-- Description: Daily sales transactions and revenue tracking
-- =====================================================
CREATE TABLE `sales_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uid` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique sales transaction ID',
    `date` DATE NOT NULL COMMENT 'Sale date',
    `product_id` INT NOT NULL COMMENT 'Foreign key to products table',
    `quantity_sold` INT NOT NULL DEFAULT 0 COMMENT 'Number of units sold',
    `sale_type` ENUM('Wholesale', 'Retail') NOT NULL DEFAULT 'Retail' COMMENT 'Type of sale',
    `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Price per unit at time of sale',
    `total_revenue` DECIMAL(10, 2) GENERATED ALWAYS AS (quantity_sold * unit_price) STORED COMMENT 'Total revenue from this transaction',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_date_product (date, product_id),
    INDEX idx_sale_type (sale_type),
    INDEX idx_uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Distributor Ledgers
-- Description: Client/distributor balance and transaction tracking
-- =====================================================
CREATE TABLE `distributor_ledgers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uid` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique ledger entry ID',
    `distributor_name` VARCHAR(100) NOT NULL COMMENT 'Name of distributor/client',
    `loaves_issued` INT NOT NULL DEFAULT 0 COMMENT 'Number of loaves given to distributor',
    `wholesale_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Wholesale price per loaf',
    `amount_due` DECIMAL(10, 2) GENERATED ALWAYS AS (loaves_issued * wholesale_price) STORED COMMENT 'Total amount due from distributor',
    `amount_paid` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Amount already paid',
    `outstanding_balance` DECIMAL(10, 2) GENERATED ALWAYS AS (amount_due - amount_paid) STORED COMMENT 'Outstanding balance (Amount Due - Amount Paid)',
    `date` DATE NOT NULL COMMENT 'Transaction date',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_distributor (distributor_name),
    INDEX idx_date (date),
    INDEX idx_balance (outstanding_balance),
    INDEX idx_uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Sessions (for login tracking)
-- Description: User login sessions for RBAC
-- =====================================================
CREATE TABLE `sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(255) UNIQUE NOT NULL,
    `staff_id` INT COMMENT 'Foreign key to staff table (NULL for guest sessions)',
    `role` VARCHAR(50) NOT NULL COMMENT 'User role at login time',
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_active` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_staff_id (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Data Insertion (Optional)
-- =====================================================

-- Insert sample products
INSERT INTO `products` (`uid`, `name`, `wholesale_price`, `retail_price`) VALUES
('PROD_WL001', 'White Loaf', 2.50, 4.00),
('PROD_BL001', 'Brown Bread', 3.00, 5.00),
('PROD_WHL001', 'Whole Wheat', 3.50, 5.50);

-- Insert sample ingredients
INSERT INTO `ingredients` (`uid`, `name`, `current_stock`, `minimum_stock_threshold`, `unit`, `unit_cost`) VALUES
('ING_FLR001', 'All-Purpose Flour', 150.00, 50.00, 'kg', 1.50),
('ING_SAL001', 'Salt', 10.00, 5.00, 'kg', 0.50),
('ING_YST001', 'Yeast', 5.00, 2.00, 'kg', 25.00);

-- Insert sample staff member (Admin)
INSERT INTO `staff` (`uid`, `name`, `email`, `role`, `contact_details`, `hire_date`, `bank_name`, `account_number`, `account_name`, `daily_rate`) VALUES
('STF_ADM001', 'Aliyu Manager', 'aliyu2k22@gmail.com', 'Admin', '+234-XXX-XXXX', '2025-01-01', 'GTBank', '0123456789', 'Aliyu Y.', 500.00);
