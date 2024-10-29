-- CREATE USER 'newuser'@'localhost' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS gatechUser@localhost IDENTIFIED BY 'gatech123';

DROP DATABASE IF EXISTS `cs6400_fa21_team054`; 
SET default_storage_engine=InnoDB;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS cs6400_fa21_team054 
    DEFAULT CHARACTER SET utf8mb4 
    DEFAULT COLLATE utf8mb4_unicode_ci;
USE cs6400_fa21_team054;

GRANT SELECT, INSERT, UPDATE, DELETE, FILE ON *.* TO 'gatechUser'@'localhost';
GRANT ALL PRIVILEGES ON `gatechuser`.* TO 'gatechUser'@'localhost';
GRANT ALL PRIVILEGES ON `cs6400_fa21_team054`.* TO 'gatechUser'@'localhost';
FLUSH PRIVILEGES;

-- Tables 
CREATE TABLE LoginUser (
	username varchar(250) NOT NULL,
	`password` varchar(50) NOT NULL,
	first_name varchar(50) NOT NULL,
	last_name varchar(50) NOT NULL,
	`role` varchar(250) NOT NULL,
	PRIMARY KEY (username)
);

CREATE TABLE VehicleColor (
	VIN varchar(250) NOT NULL,
	color varchar(50) NOT NULL,
	PRIMARY KEY (VIN, color)
);

CREATE TABLE Manufacturer (
	mID int(16) NOT NULL AUTO_INCREMENT,
	`name` varchar(50) NOT NULL,
	PRIMARY KEY (mID)
);

CREATE TABLE Car (
	VIN varchar(250) NOT NULL,
	`type` varchar(50) NOT NULL,
	door_num int(16) NOT NULL,
	PRIMARY KEY (VIN)
);

CREATE TABLE Convertible (
	VIN varchar(250) NOT NULL,
	`type` varchar(50) NOT NULL,
	roof_type varchar(50) NOT NULL,
	backseat_num int(16) NOT NULL,
	PRIMARY KEY (VIN)
);

CREATE TABLE Truck (
	VIN varchar(250) NOT NULL,
	`type` varchar(50) NOT NULL,
	cargo_capacity decimal(10,2) NOT NULL,
	cargocover_type varchar(50) DEFAULT NULL,
	rear_axle_num int(16) NOT NULL,
	PRIMARY KEY (VIN)
);

CREATE TABLE Van (
	VIN varchar(250) NOT NULL,
	`type` varchar(50) NOT NULL,
	driverside_backdoor boolean NOT NULL,
	PRIMARY KEY (VIN)
);

CREATE TABLE SUV (
	VIN varchar(250) NOT NULL,
	`type` varchar(50) NOT NULL,
	drivetrain_type varchar(50) NOT NULL,
	cupholder_num int(16) NOT NULL,
	PRIMARY KEY (VIN)
);

CREATE TABLE Vehicle (
    VIN varchar(250) NOT NULL,
    model_name varchar(50) NOT NULL,
    model_year int(16) NOT NULL,
    invoice_price decimal(10,2) NOT NULL,
    is_sold boolean NOT NULL,
    `description` varchar(500) DEFAULT NULL,
    add_date date NOT NULL,
    mID int(16) NOT NULL,
    username varchar(250) NOT NULL, 
    PRIMARY KEY (VIN)
);

CREATE TABLE Customer (
    customerID int(16) NOT NULL AUTO_INCREMENT,
    email varchar(250) DEFAULT NULL,
    phone varchar(10) NOT NULL,
    street_address varchar(250) NOT NULL,
    city varchar(50) NOT NULL,
    state varchar(50) NOT NULL,
    postal_code varchar(50) NOT NULL,
    PRIMARY KEY (customerID)
);

CREATE TABLE Individual (
    driver_license varchar (50) NOT NULL, 
    first_name varchar(50) NOT NULL,
    last_name varchar(50) NOT NULL,
    customerID int(16) NOT NULL,
    PRIMARY KEY (driver_license)
);

CREATE TABLE Business (
	taxID varchar(50) NOT NULL,
	business_name varchar(50) NOT NULL,
	title varchar(50) NOT NULL,
	contact_first_name varchar(50) NOT NULL,
    contact_last_name varchar(50) NOT NULL,
	customerID int(16) NOT NULL,
	PRIMARY KEY (taxID)
);


CREATE TABLE Sale (
	username varchar(250) NOT NULL,
	customerID int(16) NOT NULL,
	VIN varchar(250) NOT NULL,
	sold_date date NOT NULL,
	sold_price decimal(10, 2) NOT NULL,
	PRIMARY KEY (username, customerID, VIN)
);

CREATE TABLE `Repair` (
	repairID int(16) NOT NULL AUTO_INCREMENT,
	VIN varchar(250) NOT NULL,
	customerID int(16) NOT NULL,
	start_date date NOT NULL,
	complete_date date DEFAULT NULL,
	labor_charge decimal(10, 2) DEFAULT NULL, 
	odometer int(32) NOT NULL,
	`description` varchar(500) DEFAULT NULL,
	username varchar(250) NOT NULL,
	PRIMARY KEY (repairID),
	UNIQUE KEY (VIN, customerID, start_date)
);
    
CREATE TABLE Part (
	repairID int(16) NOT NULL,
	partID varchar(250) NOT NULL,
	part_quantity int(32) NOT NULL,
	part_price decimal(10,2) NOT NULL,
	vendor varchar(250) NOT NULL,
	PRIMARY KEY (repairID, partID)
); 

CREATE TABLE Color (
	color varchar(50) NOT NULL,
	PRIMARY KEY (color)
);
  

-- Constraints    Foreign Keys: FK_ChildTable_childColumn_ParentTable_parentColumn

ALTER TABLE Vehicle
  ADD CONSTRAINT fk_Vehicle_mID_Manufacturer_mID FOREIGN KEY (mID) REFERENCES Manufacturer (mID),
  ADD CONSTRAINT fk_Vehicle_username_LoginUser_username FOREIGN KEY (username) REFERENCES LoginUser (username);

ALTER TABLE Individual
  ADD CONSTRAINT fk_Individual_customerID_Customer_customerID FOREIGN KEY (customerID) REFERENCES Customer (customerID);

ALTER TABLE Business
  ADD CONSTRAINT fk_Business_customerID_Customer_customerID FOREIGN KEY (customerID) REFERENCES Customer (customerID);

ALTER TABLE VehicleColor
  ADD CONSTRAINT fk_VehicleColor_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN);

ALTER TABLE Car
  ADD CONSTRAINT fk_Car_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN);

ALTER TABLE Convertible
  ADD CONSTRAINT fk_Convertible_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN);

ALTER TABLE Truck
  ADD CONSTRAINT fk_Truck_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN);

ALTER TABLE Van
  ADD CONSTRAINT fk_Van_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN);

ALTER TABLE SUV
  ADD CONSTRAINT fk_SUV_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN);
  
ALTER TABLE Sale
  ADD CONSTRAINT fk_Sale_username_LoginUser_username FOREIGN KEY (username) REFERENCES LoginUser (username),
  ADD CONSTRAINT fk_Sale_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN),
  ADD CONSTRAINT fk_Sale_customerID_Vehicle_customerID FOREIGN KEY (customerID) REFERENCES Customer (customerID);

ALTER TABLE `Repair`
  ADD CONSTRAINT fk_Repair_VIN_Vehicle_VIN FOREIGN KEY (VIN) REFERENCES Vehicle (VIN),
  ADD CONSTRAINT fk_Repair_customerID_Vehicle_customerID FOREIGN KEY (customerID) REFERENCES Customer (customerID),
  ADD CONSTRAINT fk_Part_userName_LoginUser FOREIGN KEY (username) REFERENCES LoginUser (username);
  
ALTER TABLE Part
  ADD CONSTRAINT fk_Part_repairID_Repair_repairID FOREIGN KEY (repairID) REFERENCES `Repair` (repairID);
