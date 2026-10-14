-- add price and price_breakdown to existing maintenance_records table
-- run once if you already have the database set up (after setup.php)

ALTER TABLE maintenance_records ADD COLUMN total_price DECIMAL(12,2) DEFAULT NULL AFTER performed_by;
ALTER TABLE maintenance_records ADD COLUMN price_breakdown TEXT AFTER total_price;
