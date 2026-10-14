-- Add assigned_to column to tools table
-- This changes from assigned_to_id (foreign key) to assigned_to (text field)

-- First, drop the foreign key constraint
ALTER TABLE tools DROP FOREIGN KEY IF EXISTS tools_ibfk_1;

-- Then drop the old foreign key column
ALTER TABLE tools DROP COLUMN IF EXISTS assigned_to_id;

-- Add the new text column for manual entry
ALTER TABLE tools ADD COLUMN assigned_to VARCHAR(255) DEFAULT NULL AFTER condition_status;
