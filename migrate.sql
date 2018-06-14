
-- Table: supplier_product_cities
CREATE TABLE supplier_product_cities (
	id int unsigned NOT NULL AUTO_INCREMENT,
	supplier_product_id int unsigned NOT NULL,
	city_id smallint unsigned NOT NULL,
	UNIQUE INDEX supplier_product_citiy (supplier_product_id,city_id),
	CONSTRAINT supplier_product_cities_pk PRIMARY KEY (id)
);

-- Table: supplier_products
CREATE TABLE supplier_products (
	id int unsigned NOT NULL AUTO_INCREMENT,
	supplier_id int unsigned NOT NULL,
	name varchar(120) NOT NULL,
	description text NOT NULL,
	price decimal(6,2) NOT NULL,
	min_players int unsigned NOT NULL,
	max_players int unsigned NOT NULL,
	hours char(11) NULL,
	address varchar(200) NOT NULL,
	latitude decimal(10,8) NOT NULL,
	longitude decimal(11,8) NOT NULL,
    images mediumtext NULL,
	active tinyint(1) unsigned NOT NULL DEFAULT 1,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT supplier_products_pk PRIMARY KEY (id)
);

-- Table: suppliers
CREATE TABLE suppliers (
	id int unsigned NOT NULL AUTO_INCREMENT,
	email varchar(120) NOT NULL,
	company varchar(150) NOT NULL,
	password varchar(32) NOT NULL,
	first_name varchar(20) NOT NULL,
	last_name varchar(20) NOT NULL,
	phone varchar(20) NULL DEFAULT NULL,
	notes text NULL DEFAULT NULL,
	active tinyint(1) unsigned NOT NULL DEFAULT 0,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE INDEX email (email),
	CONSTRAINT suppliers_pk PRIMARY KEY (id)
);

CREATE INDEX activesupplier ON suppliers (active);

-- Reference: supplier_product_cities_cities (table: supplier_product_cities)
ALTER TABLE supplier_product_cities ADD CONSTRAINT supplier_product_cities_cities FOREIGN KEY supplier_product_cities_cities (city_id)
	REFERENCES cities (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: supplier_product_cities_supplier_products (table: supplier_product_cities)
ALTER TABLE supplier_product_cities ADD CONSTRAINT supplier_product_cities_supplier_products FOREIGN KEY supplier_product_cities_supplier_products (supplier_product_id)
	REFERENCES supplier_products (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: supplier_products_suppliers (table: supplier_products)
ALTER TABLE supplier_products ADD CONSTRAINT supplier_products_suppliers FOREIGN KEY supplier_products_suppliers (supplier_id)
	REFERENCES suppliers (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;
