
USE ComputerStore;
GO

-- Foreign Key Indexes
CREATE INDEX idx_product_supplier ON Product(SupplierID);
CREATE INDEX idx_orders_customer ON Orders(CustomerID);
CREATE INDEX idx_orderdetail_order ON OrderDetail(OrderID);
CREATE INDEX idx_orderdetail_product ON OrderDetail(ProductID);

-- Search Optimization Indexes
CREATE INDEX idx_customer_email ON Customer(Email);
CREATE INDEX idx_product_category ON Product(Category);
CREATE INDEX idx_orders_date ON Orders(OrderDate);
CREATE INDEX idx_orders_status ON Orders(Status);

PRINT 'All indexes created successfully';
GO