-- Add Status column to OrderHeader table
ALTER TABLE OrderHeader ADD COLUMN Status VARCHAR(20) DEFAULT 'Pending';
-- Possible values: Pending, Confirmed, Cancelled
