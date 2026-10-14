-- add equipment breakdown report form fields to breakdowns table
-- run once if database already exists

ALTER TABLE breakdowns ADD COLUMN location VARCHAR(150) DEFAULT NULL AFTER date_reported;
ALTER TABLE breakdowns ADD COLUMN category_other VARCHAR(100) DEFAULT NULL AFTER category;
ALTER TABLE breakdowns ADD COLUMN detailed_findings TEXT AFTER description;
ALTER TABLE breakdowns ADD COLUMN parts_cost DECIMAL(12,2) DEFAULT NULL AFTER detailed_findings;
ALTER TABLE breakdowns ADD COLUMN labor_cost DECIMAL(12,2) DEFAULT NULL AFTER parts_cost;
ALTER TABLE breakdowns ADD COLUMN other_costs DECIMAL(12,2) DEFAULT NULL AFTER labor_cost;
ALTER TABLE breakdowns ADD COLUMN total_repair_cost DECIMAL(12,2) DEFAULT NULL AFTER other_costs;
ALTER TABLE breakdowns ADD COLUMN replacement_recommendation VARCHAR(50) DEFAULT NULL AFTER total_repair_cost;
ALTER TABLE breakdowns ADD COLUMN replacement_remarks TEXT AFTER replacement_recommendation;
ALTER TABLE breakdowns ADD COLUMN usage_reminders TEXT AFTER replacement_remarks;
ALTER TABLE breakdowns ADD COLUMN misuse_recommendations TEXT AFTER usage_reminders;
ALTER TABLE breakdowns ADD COLUMN misuse_remarks TEXT AFTER misuse_recommendations;
ALTER TABLE breakdowns ADD COLUMN report_by_name VARCHAR(100) DEFAULT NULL AFTER status;
ALTER TABLE breakdowns ADD COLUMN checked_by VARCHAR(100) DEFAULT NULL AFTER report_by_name;
ALTER TABLE breakdowns ADD COLUMN prepared_by VARCHAR(100) DEFAULT NULL AFTER checked_by;
ALTER TABLE breakdowns ADD COLUMN prepared_position VARCHAR(100) DEFAULT NULL AFTER prepared_by;
ALTER TABLE breakdowns ADD COLUMN approved_by VARCHAR(100) DEFAULT NULL AFTER prepared_position;
