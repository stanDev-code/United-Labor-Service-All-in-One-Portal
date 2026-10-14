-- separate vehicles from equipment
-- create vehicles table, vehicle_oil_changes, add vehicle_id to maintenance/breakdowns
-- run via setup.php which handles the data migration from equipment to vehicles

CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    plate_number VARCHAR(20) NOT NULL,
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

CREATE TABLE IF NOT EXISTS vehicle_oil_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    change_date DATE NOT NULL,
    next_due_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
