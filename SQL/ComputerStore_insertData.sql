
USE ComputerStore;
GO

-- Insert Suppliers
INSERT INTO Supplier (Name, Email, Phone, Address) VALUES
('TechSupply Lebanon', 'contact@techsupply.lb', '+961 1 234567', 'Beirut, Lebanon'),
('Global Components', 'info@globalcomp.com', '+961 1 345678', 'Jounieh, Lebanon'),
('Elite Hardware', 'sales@elitehw.com', '+961 1 456789', 'Tripoli, Lebanon');
GO

-- Insert Customers
INSERT INTO Customer (Name, Email, Phone, Address) VALUES
('Ahmad Hassan', 'ahmad.h@email.com', '+961 70 123456', 'Hamra, Beirut'),
('Sara Khalil', 'sara.k@email.com', '+961 71 234567', 'Ashrafieh, Beirut'),
('Mohamed Ali', 'mohamed.a@email.com', '+961 76 345678', 'Saida, Lebanon'),
('Layla Fares', 'layla.f@email.com', '+961 78 456789', 'Tripoli, Lebanon');
GO

-- Insert Products
INSERT INTO Product (Name, Category, Price, Stock, SupplierID, Description, ImageURL) VALUES
('Dell Inspiron 15 3520', 'Laptops', 850, 20, 1, 'Intel i5, 8GB RAM, 512GB SSD', 'laptop01.webp'),
('Dell Latitude 5530', 'Laptops', 1200, 15, 1, 'Business laptop, Intel i7, 16GB RAM', 'laptop02.jpg'),
('HP Pavilion 15', 'Laptops', 900, 18, 2, 'Everyday laptop, Intel i5, SSD', 'laptop03.jpg'),
('HP Victus 15 Gaming', 'Laptops', 1400, 10, 2, 'Gaming laptop with RTX GPU', 'laptop04.jpg'),
('Lenovo IdeaPad 5', 'Laptops', 780, 22, 3, 'Slim laptop, Ryzen 5', 'laptop05.avif'),
('Lenovo ThinkPad E14', 'Laptops', 1100, 12, 3, 'Professional business laptop', 'laptop06.jpg'),
('ASUS VivoBook 15', 'Laptops', 830, 20, 1, 'Lightweight laptop for students', 'laptop07.jpg'),
('ASUS ZenBook 14', 'Laptops', 1300, 10, 1, 'Premium ultrabook', 'laptop08.jpg'),
('ASUS TUF Gaming F15', 'Laptops', 1500, 8, 1, 'Gaming laptop, high performance', 'laptop09.jpg'),
('Acer Aspire 5', 'Laptops', 760, 25, 2, 'Affordable daily laptop', 'laptop10.jpg'),
('Acer Nitro 5', 'Laptops', 1450, 9, 2, 'Gaming laptop with cooling system', 'laptop11.jpeg'),
('MacBook Air M2', 'Laptops', 1800, 7, 1, 'Apple M2 chip, ultrathin design', 'laptop12.jpg'),


('Dell UltraSharp 27"', 'Monitors', 520, 14, 1, '27 inch QHD professional monitor', 'monitor01.jpg'),
('HP 24mh IPS Monitor', 'Monitors', 230, 20, 2, '24 inch Full HD IPS', 'monitor02.jpg'),
('LG UltraWide 29"', 'Monitors', 380, 12, 2, 'UltraWide productivity monitor', 'monitor03.jpg'),
('Samsung Odyssey G5', 'Monitors', 420, 10, 1, 'Gaming curved monitor', 'monitor04.jpg'),
('ASUS TUF Gaming 24"', 'Monitors', 310, 16, 3, '144Hz gaming monitor', 'monitor05.png'),
('Acer Nitro VG240Y', 'Monitors', 260, 18, 3, 'Gaming IPS monitor', 'monitor06.jpg'),

('Logitech G Pro Mouse', 'Accessories', 120, 30, 2, 'Professional gaming mouse', 'accessory01.webp'),
('Razer DeathAdder Essential', 'Accessories', 70, 40, 2, 'Ergonomic gaming mouse', 'accessory02.jpg'),
('Logitech MX Master 3', 'Accessories', 140, 25, 1, 'Advanced wireless mouse', 'accessory03.jpg'),
('Redragon K552 Keyboard', 'Accessories', 85, 35, 3, 'Mechanical RGB keyboard', 'accessory04.jpg'),
('Corsair K70 RGB Keyboard', 'Accessories', 170, 15, 1, 'Premium mechanical keyboard', 'accessory05.jpg'),
('SteelSeries Arctis 5', 'Accessories', 150, 18, 3, 'Gaming headset with surround sound', 'accessory06.jpg'),

('Samsung 970 EVO Plus 1TB', 'Storage', 160, 30, 1, 'High speed NVMe SSD', 'storage01.jpg'),
('Kingston NV2 500GB', 'Storage', 70, 40, 2, 'Affordable NVMe SSD', 'storage02.jpg'),
('WD Blue 2TB HDD', 'Storage', 85, 35, 2, 'Internal hard drive', 'storage03.png'),
('Seagate Barracuda 1TB', 'Storage', 60, 45, 3, 'Reliable HDD storage', 'storage04.jpg'),

('Intel Core i7-13700K', 'Components', 420, 20, 1, '13th Gen Intel Processor', 'component01.jpg'),
('AMD Ryzen 5 5600X', 'Components', 260, 25, 2, 'High performance CPU', 'component02.jpg'),
('Corsair Vengeance 16GB RAM', 'Components', 95, 40, 3, 'DDR4 Gaming RAM', 'component03.jpg'),
('NVIDIA RTX 4060', 'Components', 480, 10, 1, 'Next-gen graphics card', 'component04.jpg'),

('TP-Link Archer AX55', 'Networking', 150, 25, 2, 'WiFi 6 Router', 'network01.jpg'),
('ASUS RT-AX58U', 'Networking', 180, 20, 1, 'High speed WiFi router', 'network02.jpg'),
('NETGEAR 8-Port Switch', 'Networking', 90, 30, 3, 'Gigabit Ethernet switch', 'network03.jpg'),

('Windows 11 Pro', 'Software', 190, 100, 1, 'Microsoft OS License', 'software01.jpg'),
('Microsoft Office 365', 'Software', 99, 120, 2, '1 Year subscription', 'software02.png'),
('Adobe Photoshop', 'Software', 120, 80, 3, 'Photo editing software', 'software03.png');
 

GO

-- Insert sample orders
DECLARE @OrderID1 INT, @OrderID2 INT;

INSERT INTO Orders (CustomerID, Status) VALUES (1, 'Completed');
SET @OrderID1 = SCOPE_IDENTITY();

INSERT INTO Orders (CustomerID, Status) VALUES (2, 'Processing');
SET @OrderID2 = SCOPE_IDENTITY();

-- Insert order details (triggers will update stock and totals)
INSERT INTO OrderDetail (OrderID, ProductID, Quantity, UnitPrice) VALUES
(@OrderID1, 1, 1, 1200.00),
(@OrderID1, 3, 2, 25.00);

INSERT INTO OrderDetail (OrderID, ProductID, Quantity, UnitPrice) VALUES
(@OrderID2, 2, 1, 350.00),
(@OrderID2, 4, 2, 80.00);
GO

PRINT 'Sample data inserted successfully';
GO
