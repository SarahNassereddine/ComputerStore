  
USE master;
GO

-- Drop database if exists
IF EXISTS (SELECT name FROM sys.databases WHERE name = 'ComputerStore')
BEGIN
    ALTER DATABASE ComputerStore SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE ComputerStore;
END
GO

-- Create database
CREATE DATABASE ComputerStore;
GO

-- Use the database
USE ComputerStore;
GO

PRINT 'Database ComputerStore created successfully';
GO