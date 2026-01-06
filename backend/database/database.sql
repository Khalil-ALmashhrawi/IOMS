CREATE DATABASE IF NOT EXISTS QuickMartIOMS;
USE QuickMartIOMS;

-- Staff Table
CREATE TABLE Staff (
    Staff_ID INT PRIMARY KEY AUTO_INCREMENT,
    Full_Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Phone_Number VARCHAR(20),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Product Table
CREATE TABLE Product (
    Product_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Category VARCHAR(50),
    Quantity INT DEFAULT 0,
    Price DECIMAL(10, 2) NOT NULL,
    Status VARCHAR(20) DEFAULT 'Normal' -- Normal, Low Stock
);

-- Order Header Table (Renamed from Order)
CREATE TABLE OrderHeader (
    Order_ID INT PRIMARY KEY AUTO_INCREMENT,
    Order_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Order_Type VARCHAR(20) NOT NULL, -- Sell, Purchase
    Party_Name VARCHAR(100), -- Customer Name or Supplier Name
    Staff_ID INT,
    FOREIGN KEY (Staff_ID) REFERENCES Staff(Staff_ID)
);

-- OrderDetail Table
CREATE TABLE OrderDetail (
    Detail_ID INT PRIMARY KEY AUTO_INCREMENT,
    Order_ID INT,
    Product_ID INT,
    Ordered_Qty INT NOT NULL,
    Sold_Price DECIMAL(10, 2) NOT NULL, -- Price at the time of order
    FOREIGN KEY (Order_ID) REFERENCES OrderHeader(Order_ID),
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID)
);
