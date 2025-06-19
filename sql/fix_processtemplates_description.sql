-- Fix for ProcessTemplates table - Add missing Description column
-- This script fixes the "Column not found: 'Description'" error

USE rocketprod;

-- Add Description column if it doesn't exist
ALTER TABLE processtemplates ADD COLUMN IF NOT EXISTS Description TEXT AFTER TemplateName;

-- Verify the table structure
DESCRIBE processtemplates;
