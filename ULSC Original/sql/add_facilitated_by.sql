-- =============================================
-- Migration: Add facilitated_by column to equipment table
-- =============================================

USE ulsc_inventory;

-- Add facilitated_by column to equipment table
ALTER TABLE equipment 
ADD COLUMN IF NOT EXISTS facilitated_by VARCHAR(100) NULL;
