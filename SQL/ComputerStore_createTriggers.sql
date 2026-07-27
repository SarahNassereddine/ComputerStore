
USE ComputerStore;
GO

-- Trigger: Check stock before inserting order detail
CREATE TRIGGER trg_CheckStock
ON OrderDetail
INSTEAD OF INSERT
AS
BEGIN
    DECLARE @ProductID INT;
    DECLARE @Quantity INT;
    DECLARE @AvailableStock INT;
    DECLARE @OrderID INT;
    
    -- Get data from inserted table using Select
    SELECT @ProductID = ProductID, @Quantity = Quantity, @OrderID = OrderID FROM inserted;
    
    -- Get available stock
    SELECT @AvailableStock = Stock FROM Product WHERE ProductID = @ProductID;
    
    IF @AvailableStock < @Quantity
    BEGIN
        RAISERROR ('Insufficient stock for this product', 16, 1);
        ROLLBACK TRANSACTION;
    END
    ELSE
    BEGIN
        -- Insert into OrderDetail
        INSERT INTO OrderDetail (OrderID, ProductID, Quantity, UnitPrice)
        SELECT OrderID, ProductID, Quantity, UnitPrice FROM inserted;
        
        -- Update stock
        UPDATE Product
        SET Stock = Stock - @Quantity
        WHERE ProductID = @ProductID;
        
        -- Update order total using Subquery
        UPDATE Orders
        SET TotalAmount = (
            SELECT SUM(Quantity * UnitPrice)
            FROM OrderDetail
            WHERE OrderID = @OrderID
        )
        WHERE OrderID = @OrderID;
    END
END;
GO

-- Trigger: Restore stock when order detail is deleted
CREATE TRIGGER trg_RestoreStock
ON OrderDetail
AFTER DELETE
AS
BEGIN
    DECLARE @ProductID INT;
    DECLARE @Quantity INT;
    DECLARE @OrderID INT;
    
    -- Get data from deleted table using Select
    SELECT @ProductID = ProductID, @Quantity = Quantity, @OrderID = OrderID FROM deleted;
    
    -- Restore stock
    UPDATE Product
    SET Stock = Stock + @Quantity
    WHERE ProductID = @ProductID;
    
    -- Update order total using Subquery
    UPDATE Orders
    SET TotalAmount = ISNULL((
        SELECT SUM(Quantity * UnitPrice)
        FROM OrderDetail
        WHERE OrderID = @OrderID
    ), 0)
    WHERE OrderID = @OrderID;
END;
GO

-- Trigger: Audit log for product changes (Replaced INNER JOIN with Subqueries)
CREATE TRIGGER trg_ProductAudit
ON Product
AFTER UPDATE
AS
BEGIN
    INSERT INTO AuditLog (TableName, Operation, RecordID, Details)
    SELECT 
        'Product',
        'UPDATE',
        i.ProductID,
        'Price: ' + 
        CAST((SELECT Price FROM deleted WHERE ProductID = i.ProductID) AS NVARCHAR) + 
        ' -> ' + 
        CAST(i.Price AS NVARCHAR) +
        ', Stock: ' + 
        CAST((SELECT Stock FROM deleted WHERE ProductID = i.ProductID) AS NVARCHAR) + 
        ' -> ' + 
        CAST(i.Stock AS NVARCHAR)
    FROM inserted i;
END;
GO

PRINT 'All triggers created successfully';
GO