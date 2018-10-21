-- Last modification date: 2018-10-21 11:29:30.22

-- tables
-- Table: answers
CREATE TABLE answers (
	id int unsigned NOT NULL AUTO_INCREMENT,
	hunt_id int unsigned NOT NULL,
	team_id int unsigned NOT NULL,
	question_id int unsigned NOT NULL,
	action tinyint(1) unsigned NOT NULL COMMENT 'Answered
Answered with hint
Skipped',
	answer text NULL,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	funfact_viewed timestamp NULL,
	UNIQUE INDEX teamquestion (team_id,hunt_id,question_id),
	CONSTRAINT answers_pk PRIMARY KEY (id)
);

CREATE INDEX team_action ON answers (team_id,action);

-- Table: blocked
CREATE TABLE blocked (
	email varchar(120) NOT NULL,
	CONSTRAINT blocked_pk PRIMARY KEY (email)
);

-- Table: bonus_questions
CREATE TABLE bonus_questions (
	id int unsigned NOT NULL AUTO_INCREMENT,
	type tinyint unsigned NOT NULL COMMENT 'Team
Private',
	order_hunt_id int unsigned NOT NULL,
	winner_id int unsigned NULL,
	question text NOT NULL,
	answers text NOT NULL,
	score smallint unsigned NULL,
	CONSTRAINT bonus_questions_pk PRIMARY KEY (id)
);

CREATE INDEX orderhuntbonus ON bonus_questions (order_hunt_id,type);

-- Table: catquestion_types
CREATE TABLE catquestion_types (
	id tinyint unsigned NOT NULL AUTO_INCREMENT,
	name varchar(30) NOT NULL,
	UNIQUE INDEX catqtype (name),
	CONSTRAINT catquestion_types_pk PRIMARY KEY (id)
);

-- Table: catquestions
CREATE TABLE catquestions (
	id int unsigned NOT NULL AUTO_INCREMENT,
	type_id tinyint unsigned NOT NULL,
	question text NOT NULL,
	hint text NOT NULL,
	response_correct text NOT NULL,
	response_incorrect text NOT NULL,
	response_skip text NOT NULL,
	answers text NOT NULL,
	CONSTRAINT catquestions_pk PRIMARY KEY (id)
);

-- Table: catquestions_orders
CREATE TABLE catquestions_orders (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	hunt_point_id int unsigned NOT NULL,
	catquestion_id int unsigned NOT NULL,
	CONSTRAINT catquestions_orders_pk PRIMARY KEY (id)
);

-- Table: cities
CREATE TABLE cities (
	id smallint unsigned NOT NULL AUTO_INCREMENT,
	country_id smallint unsigned NOT NULL,
	name varchar(100) NOT NULL,
	timezone varchar(150) NOT NULL DEFAULT America/New_York,
	status tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Active
New
Coming Soon
Contact Only
B2c',
	UNIQUE INDEX countrycity (country_id,name),
	CONSTRAINT cities_pk PRIMARY KEY (id)
);

-- Table: clients
CREATE TABLE clients (
	id int unsigned NOT NULL AUTO_INCREMENT,
	email varchar(120) NOT NULL,
	company varchar(150) NOT NULL,
	password varchar(32) NOT NULL,
	first_name varchar(20) NOT NULL,
	last_name varchar(20) NOT NULL,
	phone varchar(20) NULL DEFAULT NULL,
	notes text NULL DEFAULT NULL,
	active tinyint(1) unsigned NOT NULL DEFAULT 1,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE INDEX email (email),
	CONSTRAINT clients_pk PRIMARY KEY (id)
);

CREATE INDEX activeclient ON clients (active);

-- Table: countries
CREATE TABLE countries (
	id smallint unsigned NOT NULL AUTO_INCREMENT,
	name varchar(100) NOT NULL,
	UNIQUE INDEX countryname (name),
	CONSTRAINT countries_pk PRIMARY KEY (id)
);

-- Table: custom_answers
CREATE TABLE custom_answers (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	custom_question_id int unsigned NOT NULL,
	team_id int unsigned NOT NULL,
	answer text NULL,
	action tinyint(1) unsigned NOT NULL,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	funfact_viewed timestamp NULL,
	UNIQUE INDEX teamcustom (team_id,custom_question_id),
	CONSTRAINT custom_answers_pk PRIMARY KEY (id)
);

CREATE INDEX orderaction ON custom_answers (order_hunt_id,action);

-- Table: custom_events
CREATE TABLE custom_events (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	team_id int unsigned NULL,
	title varchar(100) NOT NULL,
	score smallint unsigned NOT NULL,
	CONSTRAINT custom_events_pk PRIMARY KEY (id)
);

-- Table: custom_questions
CREATE TABLE custom_questions (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	type_id tinyint unsigned NOT NULL,
	name varchar(100) NOT NULL,
	score smallint unsigned NOT NULL,
	question text NOT NULL,
	qattachment text NULL,
	hint text NOT NULL,
	funfact int NOT NULL,
	response_correct text NOT NULL,
	answers text NOT NULL,
	attachment text NULL,
	timeout mediumint unsigned NULL,
	idx tinyint unsigned NOT NULL,
	UNIQUE INDEX customidx (order_hunt_id,idx),
	CONSTRAINT custom_questions_pk PRIMARY KEY (id)
);

CREATE INDEX orderhuntcustom ON custom_questions (order_hunt_id);

-- Table: hunt_points
CREATE TABLE hunt_points (
	id int unsigned NOT NULL AUTO_INCREMENT,
	hunt_id int unsigned NOT NULL,
	point_id mediumint unsigned NULL,
	question_id int unsigned NOT NULL,
	is_start tinyint(1) unsigned NOT NULL DEFAULT 0,
	idx tinyint unsigned NOT NULL DEFAULT 0,
	UNIQUE INDEX huntpoint (hunt_id,point_id),
	CONSTRAINT hunt_points_pk PRIMARY KEY (id)
);

-- Table: hunt_types
CREATE TABLE hunt_types (
	id tinyint unsigned NOT NULL AUTO_INCREMENT,
	name varchar(100) NOT NULL,
	UNIQUE INDEX htname (name),
	CONSTRAINT hunt_types_pk PRIMARY KEY (id)
);

-- Table: hunts
CREATE TABLE hunts (
	id int unsigned NOT NULL AUTO_INCREMENT,
	city_id smallint unsigned NOT NULL,
	type_id tinyint unsigned NOT NULL,
	name varchar(100) NOT NULL,
	slug varchar(120) NOT NULL,
	time char(5) NOT NULL,
	approved tinyint(1) unsigned NOT NULL DEFAULT 0,
	last_edit timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	breakpoints varchar(250) NULL,
	multilang tinyint unsigned(1) NOT NULL DEFAULT 0,
	flags tinyint unsigned NOT NULL DEFAULT 0,
	UNIQUE INDEX huntslug (slug),
	CONSTRAINT hunts_pk PRIMARY KEY (id)
);

CREATE INDEX approvedhunts ON hunts (approved);

-- Table: login_pages
CREATE TABLE login_pages (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	slug varchar(200) COLLATE utf8_unicode_ci  NOT NULL,
	title varchar(200) NOT NULL,
	welcome_title varchar(200) NOT NULL,
	UNIQUE INDEX slug (slug),
	CONSTRAINT login_pages_pk PRIMARY KEY (id)
);

-- Table: order_hunts
CREATE TABLE order_hunts (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_id int unsigned NOT NULL,
	hunt_id int unsigned NOT NULL,
	max_players smallint unsigned NOT NULL,
	max_teams smallint unsigned NOT NULL,
	start timestamp NOT NULL DEFAULT 0000-00-00 00:00:00,
	finish timestamp NOT NULL DEFAULT 0000-00-00 00:00:00,
	expire timestamp NULL,
	start_msg text NULL,
	end_msg text NULL,
	timeout_msg text NULL,
	pdf_start text NULL,
	pdf_finish text NULL,
	redirect varchar(200) NULL,
	video varchar(60) NULL,
	flags smallint unsigned NOT NULL DEFAULT 0,
	CONSTRAINT order_hunts_pk PRIMARY KEY (id)
);

CREATE INDEX start ON order_hunts (start);

CREATE INDEX finish ON order_hunts (finish);

CREATE INDEX orderhunt ON order_hunts (order_id,hunt_id);

-- Table: order_hunts_post
CREATE TABLE order_hunts_post (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	identifier tinyint unsigned NOT NULL DEFAULT 0,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE INDEX order_hunts_post (order_hunt_id,identifier),
	CONSTRAINT order_hunts_post_pk PRIMARY KEY (id)
);

-- Table: orders
CREATE TABLE orders (
	id int unsigned NOT NULL AUTO_INCREMENT,
	name varchar(120) NOT NULL,
	client_id int unsigned NOT NULL,
	customize text NULL,
	code_prefix varchar(20) NOT NULL,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT orders_pk PRIMARY KEY (id)
);

-- Table: players
CREATE TABLE players (
	id int unsigned NOT NULL AUTO_INCREMENT,
	team_id int unsigned NOT NULL,
	email varchar(120) NULL,
	first_name varchar(20) NULL,
	last_name varchar(20) NULL,
	CONSTRAINT players_pk PRIMARY KEY (id)
);

CREATE INDEX team_email ON players (team_id,email);

-- Table: point_types
CREATE TABLE point_types (
	id tinyint unsigned NOT NULL AUTO_INCREMENT,
	name varchar(20) NOT NULL,
	UNIQUE INDEX pointtype (name),
	CONSTRAINT point_types_pk PRIMARY KEY (id)
);

-- Table: points
CREATE TABLE points (
	id mediumint unsigned NOT NULL AUTO_INCREMENT,
	city_id smallint unsigned NOT NULL,
	type_id tinyint unsigned NOT NULL,
	internal_name varchar(100) NOT NULL,
	name varchar(100) NOT NULL,
	subtitle varchar(100) NOT NULL,
	latitude decimal(10,8) NOT NULL,
	longitude decimal(11,8) NOT NULL,
	address varchar(200) NULL,
	phone varchar(20) NULL,
	hours char(11) NULL,
	notes text NULL,
	CONSTRAINT points_pk PRIMARY KEY (id)
);

CREATE INDEX points_internal ON points (internal_name);

-- Table: question_tags
CREATE TABLE question_tags (
	id int unsigned NOT NULL AUTO_INCREMENT,
	question_id int unsigned NOT NULL,
	tag_id int unsigned NOT NULL,
	UNIQUE INDEX qtag (question_id,tag_id),
	CONSTRAINT question_tags_pk PRIMARY KEY (id)
);

-- Table: question_types
CREATE TABLE question_types (
	id tinyint unsigned NOT NULL AUTO_INCREMENT,
	name varchar(100) NOT NULL,
	type tinyint(1) unsigned NOT NULL COMMENT 'Text
Photo
Completion
Other',
	score smallint unsigned NOT NULL,
	custom tinyint(1) unsigned NOT NULL DEFAULT 0,
	limitAnswers tinyint(1) unsigned NOT NULL DEFAULT 0,
	UNIQUE INDEX qtname (name),
	CONSTRAINT question_types_pk PRIMARY KEY (id)
);

-- Table: questions
CREATE TABLE questions (
	id int unsigned NOT NULL AUTO_INCREMENT,
	point_id mediumint unsigned NULL,
	type_id tinyint unsigned NOT NULL,
	name varchar(100) NOT NULL,
	score smallint unsigned NULL,
	question text NOT NULL,
	qattachment text NULL,
	hint text NOT NULL,
	funfact text NOT NULL,
	correct_response text NOT NULL,
	answers text NOT NULL,
	attachment text NULL,
	timeout mediumint unsigned NULL,
	CONSTRAINT questions_pk PRIMARY KEY (id)
);

-- Table: route_points
CREATE TABLE route_points (
	id int unsigned NOT NULL AUTO_INCREMENT,
	route_id int unsigned NOT NULL,
	hunt_point_id int unsigned NOT NULL,
	idx tinyint unsigned NOT NULL DEFAULT 0,
	UNIQUE INDEX route_point (route_id,hunt_point_id),
	CONSTRAINT route_points_pk PRIMARY KEY (id)
);

-- Table: routes
CREATE TABLE routes (
	id int unsigned NOT NULL AUTO_INCREMENT,
	hunt_id int unsigned NOT NULL,
	active tinyint(1) unsigned NOT NULL DEFAULT 1,
	CONSTRAINT routes_pk PRIMARY KEY (id)
);

-- Table: social_players
CREATE TABLE social_players (
	player_id int unsigned NOT NULL,
	network tinyint unsigned NOT NULL,
	network_id bigint unsigned NOT NULL,
	first_name varchar(25) NOT NULL,
	last_name varchar(25) NOT NULL,
	thumbnail varchar(250) NULL,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT social_players_pk PRIMARY KEY (player_id)
);

CREATE INDEX network ON social_players (network);

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

-- Table: tags
CREATE TABLE tags (
	id int unsigned NOT NULL AUTO_INCREMENT,
	tag varchar(100) NOT NULL,
	UNIQUE INDEX tag (tag),
	CONSTRAINT tags_pk PRIMARY KEY (id)
);

-- Table: teams
CREATE TABLE teams (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	route_id int unsigned NOT NULL,
	activation_player varchar(32) NOT NULL,
	activation_leader varchar(32) NOT NULL,
	leader int unsigned NULL,
	name varchar(30) NULL,
	activation timestamp NULL,
	first_activation timestamp NULL,
	UNIQUE INDEX activation (activation_player),
	UNIQUE INDEX activation_leader (activation_leader),
	CONSTRAINT teams_pk PRIMARY KEY (id)
);

CREATE INDEX orderactive ON teams (order_hunt_id,activation);

CREATE INDEX orderfirstactive ON teams (order_hunt_id,first_activation);

-- Table: users
CREATE TABLE users (
	id int unsigned NOT NULL AUTO_INCREMENT,
	email varchar(120) NOT NULL,
	password varchar(80) NOT NULL,
	first_name varchar(20) NOT NULL,
	last_name varchar(20) NOT NULL,
	phone varchar(20) NULL DEFAULT NULL,
	active tinyint(1) unsigned NOT NULL DEFAULT 1,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE INDEX email (email),
	CONSTRAINT users_pk PRIMARY KEY (id)
);

-- Table: wrong_answers
CREATE TABLE wrong_answers (
	id int unsigned NOT NULL AUTO_INCREMENT,
	order_hunt_id int unsigned NOT NULL,
	question_id int unsigned NOT NULL,
	player_id int unsigned NOT NULL,
	hint tinyint(1) unsigned NOT NULL,
	answer text NOT NULL,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT wrong_answers_pk PRIMARY KEY (id)
);

CREATE INDEX question ON wrong_answers (question_id);

-- foreign keys
-- Reference: Table_48_teams (table: custom_events)
ALTER TABLE custom_events ADD CONSTRAINT Table_48_teams FOREIGN KEY Table_48_teams (team_id)
	REFERENCES teams (id)
	ON DELETE SET NULL
	ON UPDATE CASCADE;

-- Reference: answers_hunts (table: answers)
ALTER TABLE answers ADD CONSTRAINT answers_hunts FOREIGN KEY answers_hunts (hunt_id)
	REFERENCES hunts (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: answers_questions (table: answers)
ALTER TABLE answers ADD CONSTRAINT answers_questions FOREIGN KEY answers_questions (question_id)
	REFERENCES questions (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: answers_teams (table: answers)
ALTER TABLE answers ADD CONSTRAINT answers_teams FOREIGN KEY answers_teams (team_id)
	REFERENCES teams (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: bonus_questions_order_hunts (table: bonus_questions)
ALTER TABLE bonus_questions ADD CONSTRAINT bonus_questions_order_hunts FOREIGN KEY bonus_questions_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: bonus_questions_players (table: bonus_questions)
ALTER TABLE bonus_questions ADD CONSTRAINT bonus_questions_players FOREIGN KEY bonus_questions_players (winner_id)
	REFERENCES players (id)
	ON DELETE SET NULL
	ON UPDATE CASCADE;

-- Reference: catquestions_catquestion_types (table: catquestions)
ALTER TABLE catquestions ADD CONSTRAINT catquestions_catquestion_types FOREIGN KEY catquestions_catquestion_types (type_id)
	REFERENCES catquestion_types (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: catquestions_orders_catquestions (table: catquestions_orders)
ALTER TABLE catquestions_orders ADD CONSTRAINT catquestions_orders_catquestions FOREIGN KEY catquestions_orders_catquestions (catquestion_id)
	REFERENCES catquestions (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: catquestions_orders_hunt_points (table: catquestions_orders)
ALTER TABLE catquestions_orders ADD CONSTRAINT catquestions_orders_hunt_points FOREIGN KEY catquestions_orders_hunt_points (hunt_point_id)
	REFERENCES hunt_points (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: catquestions_orders_order_hunts (table: catquestions_orders)
ALTER TABLE catquestions_orders ADD CONSTRAINT catquestions_orders_order_hunts FOREIGN KEY catquestions_orders_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: cities_countries (table: cities)
ALTER TABLE cities ADD CONSTRAINT cities_countries FOREIGN KEY cities_countries (country_id)
	REFERENCES countries (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: custom_answers_custom_questions (table: custom_answers)
ALTER TABLE custom_answers ADD CONSTRAINT custom_answers_custom_questions FOREIGN KEY custom_answers_custom_questions (custom_question_id)
	REFERENCES custom_questions (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: custom_answers_order_hunts (table: custom_answers)
ALTER TABLE custom_answers ADD CONSTRAINT custom_answers_order_hunts FOREIGN KEY custom_answers_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: custom_answers_teams (table: custom_answers)
ALTER TABLE custom_answers ADD CONSTRAINT custom_answers_teams FOREIGN KEY custom_answers_teams (team_id)
	REFERENCES teams (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: custom_events_order_hunts (table: custom_events)
ALTER TABLE custom_events ADD CONSTRAINT custom_events_order_hunts FOREIGN KEY custom_events_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: custom_questions_order_hunts (table: custom_questions)
ALTER TABLE custom_questions ADD CONSTRAINT custom_questions_order_hunts FOREIGN KEY custom_questions_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: custom_questions_question_types (table: custom_questions)
ALTER TABLE custom_questions ADD CONSTRAINT custom_questions_question_types FOREIGN KEY custom_questions_question_types (type_id)
	REFERENCES question_types (id);

-- Reference: hunt_points_hunts (table: hunt_points)
ALTER TABLE hunt_points ADD CONSTRAINT hunt_points_hunts FOREIGN KEY hunt_points_hunts (hunt_id)
	REFERENCES hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: hunt_points_points (table: hunt_points)
ALTER TABLE hunt_points ADD CONSTRAINT hunt_points_points FOREIGN KEY hunt_points_points (point_id)
	REFERENCES points (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: hunt_points_questions (table: hunt_points)
ALTER TABLE hunt_points ADD CONSTRAINT hunt_points_questions FOREIGN KEY hunt_points_questions (question_id)
	REFERENCES questions (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: hunts_cities (table: hunts)
ALTER TABLE hunts ADD CONSTRAINT hunts_cities FOREIGN KEY hunts_cities (city_id)
	REFERENCES cities (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: hunts_hunt_types (table: hunts)
ALTER TABLE hunts ADD CONSTRAINT hunts_hunt_types FOREIGN KEY hunts_hunt_types (type_id)
	REFERENCES hunt_types (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: login_pages_order_hunts (table: login_pages)
ALTER TABLE login_pages ADD CONSTRAINT login_pages_order_hunts FOREIGN KEY login_pages_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: order_hunts_hunts (table: order_hunts)
ALTER TABLE order_hunts ADD CONSTRAINT order_hunts_hunts FOREIGN KEY order_hunts_hunts (hunt_id)
	REFERENCES hunts (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: order_hunts_orders (table: order_hunts)
ALTER TABLE order_hunts ADD CONSTRAINT order_hunts_orders FOREIGN KEY order_hunts_orders (order_id)
	REFERENCES orders (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: order_hunts_post_order_hunts (table: order_hunts_post)
ALTER TABLE order_hunts_post ADD CONSTRAINT order_hunts_post_order_hunts FOREIGN KEY order_hunts_post_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: orders_clients (table: orders)
ALTER TABLE orders ADD CONSTRAINT orders_clients FOREIGN KEY orders_clients (client_id)
	REFERENCES clients (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: players_teams (table: players)
ALTER TABLE players ADD CONSTRAINT players_teams FOREIGN KEY players_teams (team_id)
	REFERENCES teams (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: points_cities (table: points)
ALTER TABLE points ADD CONSTRAINT points_cities FOREIGN KEY points_cities (city_id)
	REFERENCES cities (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: points_point_types (table: points)
ALTER TABLE points ADD CONSTRAINT points_point_types FOREIGN KEY points_point_types (type_id)
	REFERENCES point_types (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: question_tags_questions (table: question_tags)
ALTER TABLE question_tags ADD CONSTRAINT question_tags_questions FOREIGN KEY question_tags_questions (question_id)
	REFERENCES questions (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: question_tags_tags (table: question_tags)
ALTER TABLE question_tags ADD CONSTRAINT question_tags_tags FOREIGN KEY question_tags_tags (tag_id)
	REFERENCES tags (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: questions_points (table: questions)
ALTER TABLE questions ADD CONSTRAINT questions_points FOREIGN KEY questions_points (point_id)
	REFERENCES points (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: questions_question_types (table: questions)
ALTER TABLE questions ADD CONSTRAINT questions_question_types FOREIGN KEY questions_question_types (type_id)
	REFERENCES question_types (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: route_points_hunt_points (table: route_points)
ALTER TABLE route_points ADD CONSTRAINT route_points_hunt_points FOREIGN KEY route_points_hunt_points (hunt_point_id)
	REFERENCES hunt_points (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: route_points_routes (table: route_points)
ALTER TABLE route_points ADD CONSTRAINT route_points_routes FOREIGN KEY route_points_routes (route_id)
	REFERENCES routes (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: routes_hunts (table: routes)
ALTER TABLE routes ADD CONSTRAINT routes_hunts FOREIGN KEY routes_hunts (hunt_id)
	REFERENCES hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: social_players_players (table: social_players)
ALTER TABLE social_players ADD CONSTRAINT social_players_players FOREIGN KEY social_players_players (player_id)
	REFERENCES players (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

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

-- Reference: teams_order_hunts (table: teams)
ALTER TABLE teams ADD CONSTRAINT teams_order_hunts FOREIGN KEY teams_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: teams_players (table: teams)
ALTER TABLE teams ADD CONSTRAINT teams_players FOREIGN KEY teams_players (leader)
	REFERENCES players (id)
	ON DELETE SET NULL
	ON UPDATE CASCADE;

-- Reference: teams_routes (table: teams)
ALTER TABLE teams ADD CONSTRAINT teams_routes FOREIGN KEY teams_routes (route_id)
	REFERENCES routes (id)
	ON DELETE RESTRICT
	ON UPDATE CASCADE;

-- Reference: wrong_answers_order_hunts (table: wrong_answers)
ALTER TABLE wrong_answers ADD CONSTRAINT wrong_answers_order_hunts FOREIGN KEY wrong_answers_order_hunts (order_hunt_id)
	REFERENCES order_hunts (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: wrong_answers_players (table: wrong_answers)
ALTER TABLE wrong_answers ADD CONSTRAINT wrong_answers_players FOREIGN KEY wrong_answers_players (player_id)
	REFERENCES players (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- Reference: wrong_answers_questions (table: wrong_answers)
ALTER TABLE wrong_answers ADD CONSTRAINT wrong_answers_questions FOREIGN KEY wrong_answers_questions (question_id)
	REFERENCES questions (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

-- End of file.

