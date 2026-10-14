<?php
require_once __DIR__ . '/config/database.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    $schema = preg_replace('/--.*$/m', '', $schema);
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $stmt) {
        if (!empty($stmt) && stripos($stmt, 'CREATE DATABASE') === false) {
            try { $pdo->exec($stmt); } catch (PDOException $e) {
                $code = $pdo->errorInfo()[1] ?? 0;
                if (!in_array($code, [1061, 1060, 1072])) throw $e; // 1061=duplicate key, 1060=duplicate column, 1072=key column doesn't exist
            }
        }
    }
    $migrations = [
        "ALTER TABLE maintenance_records ADD COLUMN total_price DECIMAL(12,2) DEFAULT NULL AFTER performed_by",
        "ALTER TABLE maintenance_records ADD COLUMN price_breakdown TEXT AFTER total_price",
        "ALTER TABLE breakdowns ADD COLUMN location VARCHAR(150) DEFAULT NULL AFTER date_reported",
        "ALTER TABLE breakdowns ADD COLUMN category_other VARCHAR(100) DEFAULT NULL AFTER category",
        "ALTER TABLE breakdowns ADD COLUMN detailed_findings TEXT AFTER description",
        "ALTER TABLE breakdowns ADD COLUMN parts_cost DECIMAL(12,2) DEFAULT NULL AFTER detailed_findings",
        "ALTER TABLE breakdowns ADD COLUMN labor_cost DECIMAL(12,2) DEFAULT NULL AFTER parts_cost",
        "ALTER TABLE breakdowns ADD COLUMN other_costs DECIMAL(12,2) DEFAULT NULL AFTER labor_cost",
        "ALTER TABLE breakdowns ADD COLUMN total_repair_cost DECIMAL(12,2) DEFAULT NULL AFTER other_costs",
        "ALTER TABLE breakdowns ADD COLUMN replacement_recommendation VARCHAR(50) DEFAULT NULL AFTER total_repair_cost",
        "ALTER TABLE breakdowns ADD COLUMN replacement_remarks TEXT AFTER replacement_recommendation",
        "ALTER TABLE breakdowns ADD COLUMN usage_reminders TEXT AFTER replacement_remarks",
        "ALTER TABLE breakdowns ADD COLUMN misuse_recommendations TEXT AFTER usage_reminders",
        "ALTER TABLE breakdowns ADD COLUMN misuse_remarks TEXT AFTER misuse_recommendations",
        "ALTER TABLE breakdowns ADD COLUMN report_by_name VARCHAR(100) DEFAULT NULL AFTER status",
        "ALTER TABLE breakdowns ADD COLUMN checked_by VARCHAR(100) DEFAULT NULL AFTER report_by_name",
        "ALTER TABLE breakdowns ADD COLUMN prepared_by VARCHAR(100) DEFAULT NULL AFTER checked_by",
        "ALTER TABLE breakdowns ADD COLUMN prepared_position VARCHAR(100) DEFAULT NULL AFTER prepared_by",
        "ALTER TABLE breakdowns ADD COLUMN approved_by VARCHAR(100) DEFAULT NULL AFTER prepared_position",
    ];
    foreach ($migrations as $sql) { try { $pdo->exec($sql); } catch (PDOException $e) { /* column may exist */ } }

    // Vehicles migration: create tables and add vehicle_id columns
    $vehicleMigrations = [
        "CREATE TABLE IF NOT EXISTS vehicles (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, plate_number VARCHAR(20) NOT NULL, location VARCHAR(150) DEFAULT NULL, last_oil_change_date DATE DEFAULT NULL, next_oil_due_date DATE DEFAULT NULL, status ENUM('Operational', 'Under Repair', 'Out of Service') NOT NULL DEFAULT 'Operational', facilitated_by VARCHAR(100) DEFAULT NULL, purchase_date DATE DEFAULT NULL, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS vehicle_oil_changes (id INT AUTO_INCREMENT PRIMARY KEY, vehicle_id INT NOT NULL, change_date DATE NOT NULL, next_due_date DATE NOT NULL, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "ALTER TABLE maintenance_records ADD COLUMN vehicle_id INT NULL",
        "ALTER TABLE breakdowns ADD COLUMN vehicle_id INT NULL",
    ];
    foreach ($vehicleMigrations as $sql) { try { $pdo->exec($sql); } catch (PDOException $e) { /* may exist */ } }
    try { $pdo->exec("ALTER TABLE maintenance_records ADD CONSTRAINT fk_mr_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE"); } catch (PDOException $e) { }
    try { $pdo->exec("ALTER TABLE breakdowns ADD CONSTRAINT fk_bd_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE"); } catch (PDOException $e) { }
    try { $pdo->exec("ALTER TABLE maintenance_records MODIFY equipment_id INT NULL"); } catch (PDOException $e) { }
    try { $pdo->exec("ALTER TABLE breakdowns MODIFY equipment_id INT NULL"); } catch (PDOException $e) { }
    try { $pdo->exec("CREATE INDEX idx_maintenance_vehicle ON maintenance_records(vehicle_id)"); } catch (PDOException $e) { }
    try { $pdo->exec("CREATE INDEX idx_breakdowns_vehicle ON breakdowns(vehicle_id)"); } catch (PDOException $e) { }

    // Migrate existing Vehicle records from equipment to vehicles
    $check = $pdo->query("SHOW COLUMNS FROM equipment LIKE 'category'")->fetch();
    if ($check && strpos($check['Type'] ?? '', 'Vehicle') !== false) {
        $oldVehicles = $pdo->query("SELECT id, name, plate_number, item_id, status, purchase_date, notes FROM equipment WHERE category = 'Vehicle'")->fetchAll(PDO::FETCH_ASSOC);
        $idMap = [];
        foreach ($oldVehicles as $ev) {
            $plate = !empty($ev['plate_number']) ? $ev['plate_number'] : ($ev['item_id'] ?? 'V' . $ev['id']);
            $pdo->prepare("INSERT INTO vehicles (name, plate_number, status, purchase_date, notes) VALUES (?,?,?,?,?)")->execute([$ev['name'], $plate, $ev['status'] ?? 'Operational', $ev['purchase_date'] ?: null, $ev['notes'] ?: null]);
            $idMap[(int)$ev['id']] = (int)$pdo->lastInsertId();
        }
        foreach ($idMap as $oldId => $newId) {
            $pdo->prepare("UPDATE maintenance_records SET vehicle_id=?, equipment_id=NULL WHERE equipment_id=?")->execute([$newId, $oldId]);
            $pdo->prepare("UPDATE breakdowns SET vehicle_id=?, equipment_id=NULL WHERE equipment_id=?")->execute([$newId, $oldId]);
        }
        if (!empty($idMap)) {
            $phs = implode(',', array_fill(0, count($idMap), '?'));
            $pdo->prepare("DELETE FROM equipment WHERE id IN ($phs)")->execute(array_keys($idMap));
        }
    }

    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'")->execute([$hash]);
    echo "<h2>Setup complete!</h2><p>Database and tables created. Default login: <strong>admin</strong> / <strong>admin123</strong></p>";
    echo "<p><a href='login.php' class='btn btn-primary'>Go to Login</a></p>";
    echo "<p style='color:red'><strong>Delete setup.php for security!</strong></p>";
} catch (PDOException $e) {
    echo "<h2>Setup Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
