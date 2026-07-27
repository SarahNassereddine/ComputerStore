
USE ComputerStore;
GO

-- Note: In development, we typically use Windows Authentication or sa account
-- For production, create specific users with limited privileges

-- Create a read-only role for reports
IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = 'ReportUser')
BEGIN
    CREATE USER ReportUser WITHOUT LOGIN;
END
GO

-- Grant read permissions to views
GRANT SELECT ON vw_ProductInventory TO ReportUser;
GRANT SELECT ON vw_OrderSummary TO ReportUser;
GRANT SELECT ON vw_LowStockProducts TO ReportUser;
GRANT SELECT ON vw_SalesByCategory TO ReportUser;
GO

PRINT 'Users and roles configured successfully';
GO
