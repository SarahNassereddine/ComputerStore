
USE ComputerStore;
GO

-- Procedure: Create new order
-- Procedure: Create new order
CREATE PROCEDURE sp_CreateOrder
    @CustomerID INT,
    @OrderID INT OUTPUT
AS
BEGIN
    INSERT INTO Orders (CustomerID, Status)
    VALUES (@CustomerID, 'Pending');
    
    SET @OrderID = SCOPE_IDENTITY();
END;
GO

-- Procedure: Add item to order (with transaction)
CREATE PROCEDURE sp_AddOrderItem
    @OrderID INT,
    @ProductID INT,
    @Quantity INT
AS
BEGIN
    BEGIN TRANSACTION;
    BEGIN TRY
        DECLARE @Price DECIMAL(10,2);
        
        SELECT @Price = Price FROM Product WHERE ProductID = @ProductID;
        
        INSERT INTO OrderDetail (OrderID, ProductID, Quantity, UnitPrice)
        VALUES (@OrderID, @ProductID, @Quantity, @Price);
        
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- Procedure: Get customer orders using cursor
CREATE PROCEDURE sp_GetCustomerOrdersWithCursor
    @CustomerID INT
AS
BEGIN
    DECLARE @OrderID INT;
    DECLARE @OrderDate DATETIME;
    DECLARE @TotalAmount DECIMAL(10,2);
    DECLARE @Status NVARCHAR(20);
    
    -- Create temp table for results
    CREATE TABLE #CustomerOrders (
        OrderID INT,
        OrderDate DATETIME,
        TotalAmount DECIMAL(10,2),
        Status NVARCHAR(20)
    );
    
    -- Declare cursor
    DECLARE order_cursor CURSOR FOR
    SELECT OrderID, OrderDate, TotalAmount, Status
    FROM Orders
    WHERE CustomerID = @CustomerID
    ORDER BY OrderDate DESC;
    
    OPEN order_cursor;
    
    FETCH NEXT FROM order_cursor INTO @OrderID, @OrderDate, @TotalAmount, @Status;
    
    WHILE @@FETCH_STATUS = 0
    BEGIN
        INSERT INTO #CustomerOrders VALUES (@OrderID, @OrderDate, @TotalAmount, @Status);
        FETCH NEXT FROM order_cursor INTO @OrderID, @OrderDate, @TotalAmount, @Status;
    END;
    
    CLOSE order_cursor;
    DEALLOCATE order_cursor;
    
    SELECT * FROM #CustomerOrders;
    DROP TABLE #CustomerOrders;
END;
GO

-- Function: Calculate total sales
CREATE FUNCTION fn_TotalSales(@StartDate DATE, @EndDate DATE)
RETURNS DECIMAL(10,2)
AS
BEGIN
    DECLARE @Total DECIMAL(10,2);
    
    SELECT @Total = ISNULL(SUM(TotalAmount), 0)
    FROM Orders
    WHERE OrderDate BETWEEN @StartDate AND @EndDate
    AND Status = 'Completed';
    
    RETURN @Total;
END;
GO

-- Procedure: Update order status
CREATE PROCEDURE sp_UpdateOrderStatus
    @OrderID INT,
    @NewStatus NVARCHAR(20)
AS
BEGIN
    UPDATE Orders
    SET Status = @NewStatus
    WHERE OrderID = @OrderID;
END;
GO

PRINT 'All procedures and functions created successfully';
GO