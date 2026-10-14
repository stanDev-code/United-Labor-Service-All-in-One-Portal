-- =============================================
-- Migration: Add quantity column to equipment table
-- Run this SQL to add quantity field to equipment
-- =============================================

USE ulsc_inventory;

-- Add quantity column to equipment table with default value of 1
ALTER TABLE equipment 
ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1;

-- =============================================
-- Alternative: Run this PHP script to apply migration
-- Save as: migrate_add_quantity.php and run once
-- =============================================
-- <?php
-- require_once __DIR__ . '/config/database.php';
-- $pdo = getDBConnection();
-- $pdo->exec("ALTER TABLE equipment ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1");
-- echo "Migration completed: quantity column added to equipment table.";
-- ?>
