-- =============================================
-- inventory & maintenance management system
-- Database Schema
-- =============================================

-- create database
CREATE DATABASE IF NOT EXISTS ulsc_inventory;
USE ulsc_inventory;

-- -----------------------------------------------------
-- users table (staff and admin accounts)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- equipment table (non-vehicle items only)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    model VARCHAR(100),
    category ENUM('Equipment') NOT NULL DEFAULT 'Equipment',
    assigned_operator_id INT NULL,
    purchase_date DATE,
    status ENUM('Operational', 'Under Repair', 'Out of Service') NOT NULL DEFAULT 'Operational',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_operator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- vehicles table (transport vehicles - separate from equipment)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    location VARCHAR(150) DEFAULT NULL,
    last_oil_change_date DATE DEFAULT NULL,
    next_oil_due_date DATE DEFAULT NULL,
    status ENUM('Operational', 'Under Repair', 'Out of Service') NOT NULL DEFAULT 'Operational',
    facilitated_by VARCHAR(100) DEFAULT NULL,
    purchase_date DATE DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- vehicle oil change history
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicle_oil_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    change_date DATE NOT NULL,
    next_due_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- maintenance records (for equipment OR vehicles)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NULL,
    vehicle_id INT NULL,
    date_performed DATE NOT NULL,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    performed_by VARCHAR(100) NOT NULL,
    total_price DECIMAL(12,2) DEFAULT NULL,
    price_breakdown TEXT,
    next_maintenance_date DATE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- breakdown & repair records (for equipment OR vehicles)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS breakdowns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NULL,
    vehicle_id INT NULL,
    date_reported DATE NOT NULL,
    location VARCHAR(150) DEFAULT NULL,
    category ENUM(
        'Mechanical Failure',
        'Electrical Failure',
        'Wear and Tear',
        'Misuse / Improper Operation',
        'Lack of Maintenance',
        'Other'
    ) NOT NULL,
    category_other VARCHAR(100) DEFAULT NULL,
    description TEXT,
    detailed_findings TEXT,
    parts_cost DECIMAL(12,2) DEFAULT NULL,
    labor_cost DECIMAL(12,2) DEFAULT NULL,
    other_costs DECIMAL(12,2) DEFAULT NULL,
    total_repair_cost DECIMAL(12,2) DEFAULT NULL,
    replacement_recommendation ENUM('Not Required', 'Recommend for Replacement') DEFAULT NULL,
    replacement_remarks TEXT,
    usage_reminders TEXT,
    misuse_recommendations TEXT,
    misuse_remarks TEXT,
    repair_action TEXT,
    downtime_minutes INT DEFAULT 0,
    status ENUM('Ongoing', 'Resolved') NOT NULL DEFAULT 'Ongoing',
    report_by_name VARCHAR(100) DEFAULT NULL,
    checked_by VARCHAR(100) DEFAULT NULL,
    prepared_by VARCHAR(100) DEFAULT NULL,
    prepared_position VARCHAR(100) DEFAULT NULL,
    approved_by VARCHAR(100) DEFAULT NULL,
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- tools inventory
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    condition_status ENUM('Good', 'Fair', 'Poor', 'Needs Replacement') NOT NULL DEFAULT 'Good',
    assigned_to_id INT NULL,
    last_inspection_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- indexes for better query performance
-- -----------------------------------------------------
CREATE INDEX idx_equipment_category ON equipment(category);
CREATE INDEX idx_equipment_status ON equipment(status);
CREATE INDEX idx_vehicles_status ON vehicles(status);
CREATE INDEX idx_vehicle_oil_changes_vehicle ON vehicle_oil_changes(vehicle_id);
CREATE INDEX idx_maintenance_equipment ON maintenance_records(equipment_id);
CREATE INDEX idx_maintenance_vehicle ON maintenance_records(vehicle_id);
CREATE INDEX idx_breakdowns_vehicle ON breakdowns(vehicle_id);
CREATE INDEX idx_maintenance_date ON maintenance_records(date_performed);
CREATE INDEX idx_breakdowns_equipment ON breakdowns(equipment_id);
CREATE INDEX idx_breakdowns_status ON breakdowns(status);
CREATE INDEX idx_breakdowns_date ON breakdowns(date_reported);

-- -----------------------------------------------------
    -- default admin user (run setup.php to set password: admin123)
-- Change this password after first login!
-- -----------------------------------------------------
INSERT IGNORE INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');
