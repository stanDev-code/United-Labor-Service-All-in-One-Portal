-- add vehicle type field to vehicles table
-- run once if you already have the database set up (after setup.php)

ALTER TABLE vehicles ADD COLUMN vehicle_type ENUM('Transport', 'Service') NOT NULL DEFAULT 'Transport' AFTER name;
