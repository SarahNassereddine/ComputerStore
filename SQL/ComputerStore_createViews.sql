
USE ComputerStore;
GO

-- View: Product Inventory
CREATE VIEW vw_ProductInventory AS
SELECT 
    p.ProductID,
    p.Name AS ProductName,
    p.Category,
    p.Price,
    p.Stock,
    p.ImageURL,
    -- Get Supplier Name 
    (SELECT Name FROM Supplier s WHERE s.SupplierID = p.SupplierID) AS SupplierName
FROM Product p;
GO

-- View: Order Summary
CREATE VIEW vw_OrderSummary AS
SELECT 
    o.OrderID,
    -- Get Customer Name and Email 
    (SELECT Name FROM Customer c WHERE c.CustomerID = o.CustomerID) AS CustomerName,
    (SELECT Email FROM Customer c WHERE c.CustomerID = o.CustomerID) AS CustomerEmail,
    o.OrderDate,
    o.TotalAmount,
    o.Status,
    (SELECT COUNT(*) FROM OrderDetail WHERE OrderID = o.OrderID) AS TotalItems
FROM Orders o;
GO

-- View: Low Stock Products
CREATE VIEW vw_LowStockProducts AS
SELECT 
    ProductID,
    Name,
    Category,
    Stock,
    Price
FROM Product
WHERE Stock < 10;
GO

-- View: Sales by Category 
CREATE VIEW vw_SalesByCategory AS
SELECT 
    DISTINCT p.Category,
    -- Total Orders for this category
    (SELECT COUNT(DISTINCT OrderID) 
     FROM OrderDetail 
     WHERE ProductID IN (SELECT ProductID FROM Product WHERE Category = p.Category)
     AND OrderID IN (SELECT OrderID FROM Orders WHERE Status = 'Completed')) AS TotalOrders,
    
    -- Total Quantity for this category
    (SELECT SUM(Quantity) 
     FROM OrderDetail 
     WHERE ProductID IN (SELECT ProductID FROM Product WHERE Category = p.Category)
     AND OrderID IN (SELECT OrderID FROM Orders WHERE Status = 'Completed')) AS TotalQuantitySold,
    
    -- Total Revenue for this category
    (SELECT SUM(Quantity * UnitPrice) 
     FROM OrderDetail 
     WHERE ProductID IN (SELECT ProductID FROM Product WHERE Category = p.Category)
     AND OrderID IN (SELECT OrderID FROM Orders WHERE Status = 'Completed')) AS TotalRevenue
FROM Product p;
GO

PRINT 'All views created successfully';
GO
