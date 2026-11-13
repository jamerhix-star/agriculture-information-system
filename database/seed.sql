-- Agriculture Information System - seed data. Run AFTER schema.sql.

INSERT INTO roles VALUES (1,'Administrator'),(2,'Staff');
INSERT INTO soil_types VALUES (1,'Loam'),(2,'Clay Loam'),(3,'Sandy Loam'),(4,'Clay');
INSERT INTO irrigation_types VALUES (1,'Rainfed'),(2,'Irrigated'),(3,'Drip Irrigation');
INSERT INTO crop_categories VALUES (1,'Cereal'),(2,'Vegetable'),(3,'Fruit'),(4,'Legume'),(5,'Root Crop');
INSERT INTO seasons VALUES (1,'Wet Season'),(2,'Dry Season'),(3,'Year Round');
INSERT INTO planting_statuses VALUES (1,'Planted'),(2,'Growing'),(3,'Harvested'),(4,'Failed');
INSERT INTO quality_grades VALUES (1,'Grade A'),(2,'Grade B'),(3,'Grade C');
INSERT INTO harvest_remarks VALUES
 (1,'Good Harvest'),(2,'Acceptable Quality'),(3,'Low Yield'),(4,'Poor Quality'),(5,'Damaged Harvest');

INSERT INTO farmer (farmer_id, first_name, last_name, contact_number, address, date_registered) VALUES
 (1,'Juan','Dela Cruz','09171234567','Cebu City','2026-10-26'),
 (2,'Maria','Santos','09181234567','Carcar City, Cebu','2026-10-26'),
 (3,'Pedro','Reyes','09191234567','Danao City, Cebu','2026-10-26'),
 (4,'Ana','Garcia','09201234567','Argao, Cebu','2026-10-26'),
 (5,'Jose','Mendoza','09211234567','Bogo City, Cebu','2026-10-26'),
 (6,'Ramon','Flores','09221234567','Toledo City, Cebu','2026-10-26'),
 (7,'Elena','Cruz','09231234567','Naga City, Cebu','2026-10-26'),
 (8,'Antonio','Lim','09241234567','Minglanilla, Cebu','2026-10-26'),
 (9,'Rosa','Fernandez','09251234567','Liloan, Cebu','2026-10-26'),
 (10,'Mario','Villanueva','09261234567','Compostela, Cebu','2026-10-26'),
 (11,'Teresa','Navarro','09271234567','Talisay City, Cebu','2026-10-26'),
 (12,'Carlos','Ramos','09281234567','Consolacion, Cebu','2026-10-26'),
 (13,'Liza','Torres','09291234567','Dalaguete, Cebu','2026-10-26'),
 (14,'Roberto','Castillo','09301234567','Barili, Cebu','2026-10-26'),
 (15,'Susan','Aquino','09311234567','Sibonga, Cebu','2026-10-26');

-- soil: 1 Loam, 2 Clay Loam, 3 Sandy Loam, 4 Clay | irrigation: 1 Rainfed, 2 Irrigated, 3 Drip
INSERT INTO farm (farm_id, farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id) VALUES
 (1,1,'Dela Cruz Farm','Cebu City',2.50,1,1),
 (2,2,'Santos Rice Farm','Carcar City, Cebu',4.20,2,2),
 (3,3,'Reyes Agricultural Farm','Danao City, Cebu',3.80,3,1),
 (4,4,'Garcia Vegetable Farm','Argao, Cebu',1.75,1,3),
 (5,5,'Mendoza Farm','Bogo City, Cebu',5.00,4,2),
 (6,6,'Flores Corn Farm','Toledo City, Cebu',3.40,3,1),
 (7,7,'Cruz Family Farm','Naga City, Cebu',2.80,1,2),
 (8,8,'Lim Agricultural Farm','Minglanilla, Cebu',2.25,2,3),
 (9,9,'Fernandez Farm','Liloan, Cebu',3.10,3,1),
 (10,10,'Villanueva Green Farm','Compostela, Cebu',4.50,1,2),
 (11,11,'Navarro Rice Field','Talisay City, Cebu',3.75,4,2),
 (12,12,'Ramos Farm','Consolacion, Cebu',2.60,1,1),
 (13,13,'Torres Vegetable Farm','Dalaguete, Cebu',1.80,3,3),
 (14,14,'Castillo Agricultural Farm','Barili, Cebu',5.50,2,2),
 (15,15,'Aquino Farm','Sibonga, Cebu',3.20,1,1);

-- category: 1 Cereal, 2 Vegetable, 3 Fruit, 4 Legume, 5 Root Crop | season: 1 Wet, 2 Dry, 3 Year Round
INSERT INTO crop (crop_id, crop_name, variety, crop_category_id, growing_period_days, season_id) VALUES
 (1,'Rice','NSIC Rc 222',1,115,1),
 (2,'Corn','Yellow Corn',1,100,2),
 (3,'Tomato','Diamante Max',2,90,2),
 (4,'Eggplant','Morena',2,100,3),
 (5,'Banana','Lakatan',3,300,3),
 (6,'Mango','Carabao',3,150,3),
 (7,'Peanut','BPI Pn 9',4,110,3),
 (8,'Cassava','Golden Yellow',5,240,3),
 (9,'Sweet Potato','NSIC Sp 30',5,120,3),
 (10,'Watermelon','Sweet 16',3,85,2);

-- Plantings 1-30 follow the pattern visible in the project document (rows 11-28).
-- Rows 1-10 and 29-30 are generated with that same pattern.
INSERT INTO planting (planting_id, farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
SELECT g,
       ((g - 1) % 15) + 1,
       ((g - 1) % 10) + 1,
       DATE '2026-02-09' + ((g - 11) * 3),
       0.5 + 0.1 * ((g - 1) % 10),
       10 + 5 * ((((g - 9) % 8) + 8) % 8),
       DATE '2026-02-09' + ((g - 11) * 3) + c.growing_period_days,
       CASE WHEN g <= 20 THEN 3 WHEN g <= 28 THEN 2 ELSE 1 END
FROM generate_series(1, 30) AS g
JOIN crop c ON c.crop_id = ((g - 1) % 10) + 1;

-- grade: 1 A, 2 B, 3 C | remark: 1 Good Harvest, 2 Acceptable Quality
INSERT INTO harvest (harvest_id, planting_id, harvest_date, quantity_kg, selling_price_per_kg, grade_id, remark_id) VALUES
 (1,1,'2026-05-07',900,22.00,1,1),
 (2,2,'2026-04-25',1025,25.50,2,1),
 (3,3,'2026-04-18',1150,29.00,3,2),
 (4,4,'2026-05-01',1275,32.50,1,1),
 (5,5,'2026-11-20',1400,36.00,2,1),
 (6,6,'2026-06-26',1525,39.50,3,2),
 (7,7,'2026-05-20',1650,43.00,1,1),
 (8,8,'2026-09-30',1775,46.50,2,1),
 (9,9,'2026-06-05',1900,22.00,3,2),
 (10,10,'2026-05-04',2025,25.50,1,1),
 (11,11,'2026-06-06',2150,29.00,2,1),
 (12,12,'2026-05-25',2275,32.50,3,2),
 (13,13,'2026-05-18',2400,36.00,1,1),
 (14,14,'2026-05-31',2525,39.50,2,1),
 (15,15,'2026-12-20',2650,43.00,3,2),
 (16,16,'2026-07-26',2775,46.50,1,1),
 (17,17,'2026-06-19',2900,22.00,2,1),
 (18,18,'2026-10-30',3025,25.50,3,2),
 (19,19,'2026-07-05',3150,29.00,1,1),
 (20,20,'2026-06-03',3275,32.50,2,1);

-- Move identity counters past the manually inserted ids.
SELECT setval(pg_get_serial_sequence('farmer','farmer_id'),     (SELECT MAX(farmer_id)   FROM farmer));
SELECT setval(pg_get_serial_sequence('farm','farm_id'),         (SELECT MAX(farm_id)     FROM farm));
SELECT setval(pg_get_serial_sequence('crop','crop_id'),         (SELECT MAX(crop_id)     FROM crop));
SELECT setval(pg_get_serial_sequence('planting','planting_id'), (SELECT MAX(planting_id) FROM planting));
SELECT setval(pg_get_serial_sequence('harvest','harvest_id'),   (SELECT MAX(harvest_id)  FROM harvest));
