-- Comprehensive schema fixes for Process Templates system
-- This script fixes all column mismatches between database and code

USE rocketprod;

-- Fix 1: Add missing Description column to processtemplates table
ALTER TABLE processtemplates ADD COLUMN IF NOT EXISTS Description TEXT AFTER TemplateName;

-- Fix 2: Add missing StepName column to processtemplatesteps table
ALTER TABLE processtemplatesteps ADD COLUMN IF NOT EXISTS StepName VARCHAR(100) AFTER ProcessName;

-- Fix 3: Add missing DefaultValue column to processtemplatesteps table  
ALTER TABLE processtemplatesteps ADD COLUMN IF NOT EXISTS DefaultValue VARCHAR(255) AFTER StepName;

-- Verify the table structures
DESCRIBE processtemplates;
DESCRIBE processtemplatesteps;
