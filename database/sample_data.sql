BEGIN;

TRUNCATE harvest, planting, farm, crop, farmer RESTART IDENTITY;
TRUNCATE audit_log RESTART IDENTITY;

INSERT INTO farmer (farmer_id, first_name, last_name, contact_number, address, date_registered)
OVERRIDING SYSTEM VALUE VALUES
  (1, 'Juan', 'Dela Cruz', '09171234567', 'Cebu City', '2026-10-26'),
  (2, 'Maria', 'Santos', '09181234567', 'Carcar City, Cebu', '2026-10-26'),
  (3, 'Pedro', 'Reyes', '09191234567', 'Danao City, Cebu', '2026-10-26'),
  (4, 'Ana', 'Garcia', '09201234567', 'Argao, Cebu', '2026-10-26'),
  (5, 'Jose', 'Mendoza', '09211234567', 'Bogo City, Cebu', '2026-10-26'),
  (6, 'Ramon', 'Flores', '09221234567', 'Toledo City, Cebu', '2026-10-26'),
  (7, 'Elena', 'Cruz', '09231234567', 'Naga City, Cebu', '2026-10-26'),
  (8, 'Antonio', 'Lim', '09241234567', 'Minglanilla, Cebu', '2026-10-26'),
  (9, 'Rosa', 'Fernandez', '09251234567', 'Liloan, Cebu', '2026-10-26'),
  (10, 'Mario', 'Villanueva', '09261234567', 'Compostela, Cebu', '2026-10-26'),
  (11, 'Teresa', 'Navarro', '09271234567', 'Talisay City, Cebu', '2026-10-26'),
  (12, 'Carlos', 'Ramos', '09281234567', 'Consolacion, Cebu', '2026-10-26'),
  (13, 'Liza', 'Torres', '09291234567', 'Dalaguete, Cebu', '2026-10-26'),
  (14, 'Roberto', 'Castillo', '09301234567', 'Barili, Cebu', '2026-10-26'),
  (15, 'Susan', 'Aquino', '09311234567', 'Sibonga, Cebu', '2026-10-26');

INSERT INTO farm (farm_id, farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id)
OVERRIDING SYSTEM VALUE
SELECT v.id, v.id, v.farm_name, v.location, v.area, st.soil_type_id, it.irrigation_type_id
FROM (VALUES
  (1, 'Dela Cruz Farm', 'Cebu City', 2.50, 'Loam', 'Rainfed'),
  (2, 'Santos Rice Farm', 'Carcar City, Cebu', 4.20, 'Clay Loam', 'Irrigated'),
  (3, 'Reyes Agricultural Farm', 'Danao City, Cebu', 3.80, 'Sandy Loam', 'Rainfed'),
  (4, 'Garcia Vegetable Farm', 'Argao, Cebu', 1.75, 'Loam', 'Drip Irrigation'),
  (5, 'Mendoza Farm', 'Bogo City, Cebu', 5.00, 'Clay', 'Irrigated'),
  (6, 'Flores Corn Farm', 'Toledo City, Cebu', 3.40, 'Sandy Loam', 'Rainfed'),
  (7, 'Cruz Family Farm', 'Naga City, Cebu', 2.80, 'Loam', 'Irrigated'),
  (8, 'Lim Agricultural Farm', 'Minglanilla, Cebu', 2.25, 'Clay Loam', 'Drip Irrigation'),
  (9, 'Fernandez Farm', 'Liloan, Cebu', 3.10, 'Sandy Loam', 'Rainfed'),
  (10, 'Villanueva Green Farm', 'Compostela, Cebu', 4.50, 'Loam', 'Irrigated'),
  (11, 'Navarro Rice Field', 'Talisay City, Cebu', 3.75, 'Clay', 'Irrigated'),
  (12, 'Ramos Farm', 'Consolacion, Cebu', 2.60, 'Loam', 'Rainfed'),
  (13, 'Torres Vegetable Farm', 'Dalaguete, Cebu', 1.80, 'Sandy Loam', 'Drip Irrigation'),
  (14, 'Castillo Agricultural Farm', 'Barili, Cebu', 5.50, 'Clay Loam', 'Irrigated'),
  (15, 'Aquino Farm', 'Sibonga, Cebu', 3.20, 'Loam', 'Rainfed')
) AS v(id, farm_name, location, area, soil, irrigation)
JOIN soil_type st       ON st.soil_type_name = v.soil
JOIN irrigation_type it ON it.irrigation_type_name = v.irrigation;

INSERT INTO crop (crop_id, crop_name, variety, crop_category_id, growing_period_days, season_id)
OVERRIDING SYSTEM VALUE
SELECT v.id, v.crop_name, v.variety, cc.crop_category_id, v.days, s.season_id
FROM (VALUES
  (1, 'Rice', 'NSIC Rc 222', 'Cereal', 115, 'Wet Season'),
  (2, 'Corn', 'Yellow Corn', 'Cereal', 100, 'Dry Season'),
  (3, 'Tomato', 'Diamante Max', 'Vegetable', 90, 'Dry Season'),
  (4, 'Eggplant', 'Morena', 'Vegetable', 100, 'Year Round'),
  (5, 'Banana', 'Lakatan', 'Fruit', 300, 'Year Round'),
  (6, 'Mango', 'Carabao', 'Fruit', 150, 'Year Round'),
  (7, 'Peanut', 'BPI Pn 9', 'Legume', 110, 'Year Round'),
  (8, 'Cassava', 'Golden Yellow', 'Root Crop', 240, 'Year Round'),
  (9, 'Sweet Potato', 'NSIC Sp 30', 'Root Crop', 120, 'Year Round'),
  (10, 'Watermelon', 'Sweet 16', 'Fruit', 85, 'Dry Season')
) AS v(id, crop_name, variety, category, days, season)
JOIN crop_category cc ON cc.crop_category_name = v.category
JOIN season s         ON s.season_name = v.season;

INSERT INTO planting (planting_id, farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
OVERRIDING SYSTEM VALUE
SELECT v.id, v.farm_id, v.crop_id, v.planted::date, v.area, v.seed, v.expected::date, ps.status_id
FROM (VALUES
  (1, 1, 1, '2026-01-10', 0.50, 10.00, '2026-05-05', 'Harvested'),
  (2, 2, 2, '2026-01-13', 0.60, 15.00, '2026-04-23', 'Harvested'),
  (3, 3, 3, '2026-01-16', 0.70, 20.00, '2026-04-16', 'Harvested'),
  (4, 4, 4, '2026-01-19', 0.80, 25.00, '2026-04-29', 'Harvested'),
  (5, 5, 5, '2026-01-12', 0.90, 30.00, '2026-11-08', 'Harvested'),
  (6, 6, 6, '2026-01-25', 1.00, 35.00, '2026-06-24', 'Harvested'),
  (7, 7, 7, '2026-01-28', 1.10, 40.00, '2026-05-18', 'Harvested'),
  (8, 8, 8, '2026-01-31', 1.20, 45.00, '2026-09-28', 'Harvested'),
  (9, 9, 9, '2026-02-03', 1.30, 10.00, '2026-06-03', 'Harvested'),
  (10, 10, 10, '2026-02-06', 1.40, 15.00, '2026-05-02', 'Harvested'),
  (11, 11, 1, '2026-02-09', 0.50, 20.00, '2026-06-04', 'Harvested'),
  (12, 12, 2, '2026-02-12', 0.60, 25.00, '2026-05-23', 'Harvested'),
  (13, 13, 3, '2026-02-15', 0.70, 30.00, '2026-05-16', 'Harvested'),
  (14, 14, 4, '2026-02-18', 0.80, 35.00, '2026-05-29', 'Harvested'),
  (15, 15, 5, '2026-01-14', 0.90, 40.00, '2026-11-10', 'Harvested'),
  (16, 1, 6, '2026-02-24', 1.00, 45.00, '2026-07-24', 'Harvested'),
  (17, 2, 7, '2026-02-27', 1.10, 10.00, '2026-06-17', 'Harvested'),
  (18, 3, 8, '2026-03-02', 1.20, 15.00, '2026-10-28', 'Harvested'),
  (19, 4, 9, '2026-03-05', 1.30, 20.00, '2026-07-03', 'Harvested'),
  (20, 5, 10, '2026-03-08', 1.40, 25.00, '2026-06-01', 'Harvested'),
  (21, 6, 1, '2026-03-11', 0.50, 30.00, '2026-07-04', 'Growing'),
  (22, 7, 2, '2026-03-14', 0.60, 35.00, '2026-06-22', 'Growing'),
  (23, 8, 3, '2026-03-17', 0.70, 40.00, '2026-06-15', 'Growing'),
  (24, 9, 4, '2026-03-20', 0.80, 45.00, '2026-06-28', 'Growing'),
  (25, 10, 5, '2026-03-23', 0.90, 10.00, '2027-01-17', 'Growing'),
  (26, 11, 6, '2026-03-26', 1.00, 15.00, '2026-08-23', 'Growing'),
  (27, 12, 7, '2026-03-29', 1.10, 20.00, '2026-07-17', 'Growing'),
  (28, 13, 8, '2026-04-01', 1.20, 25.00, '2026-11-27', 'Growing')
) AS v(id, farm_id, crop_id, planted, area, seed, expected, status)
JOIN planting_status ps ON ps.status_name = v.status;

INSERT INTO harvest (harvest_id, planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remark_id)
OVERRIDING SYSTEM VALUE
SELECT v.id, v.planting_id, v.harvested::date, v.kg, v.price, qg.quality_grade_id, hr.remark_id
FROM (VALUES
  (1, 1, '2026-05-07', 900.00, 22.00, 'Grade A', 'Good Harvest'),
  (2, 2, '2026-04-25', 1025.00, 25.50, 'Grade B', 'Good Harvest'),
  (3, 3, '2026-04-18', 1150.00, 29.00, 'Grade C', 'Acceptable Quality'),
  (4, 4, '2026-05-01', 1275.00, 32.50, 'Grade A', 'Good Harvest'),
  (5, 5, '2026-11-10', 1400.00, 36.00, 'Grade B', 'Good Harvest'),
  (6, 6, '2026-06-26', 1525.00, 39.50, 'Grade C', 'Acceptable Quality'),
  (7, 7, '2026-05-20', 1650.00, 43.00, 'Grade A', 'Good Harvest'),
  (8, 8, '2026-09-30', 1775.00, 46.50, 'Grade B', 'Good Harvest'),
  (9, 9, '2026-06-05', 1900.00, 22.00, 'Grade C', 'Acceptable Quality'),
  (10, 10, '2026-05-04', 2025.00, 25.50, 'Grade A', 'Good Harvest'),
  (11, 11, '2026-06-06', 2150.00, 29.00, 'Grade B', 'Good Harvest'),
  (12, 12, '2026-05-25', 2275.00, 32.50, 'Grade C', 'Acceptable Quality'),
  (13, 13, '2026-05-18', 2400.00, 36.00, 'Grade A', 'Good Harvest'),
  (14, 14, '2026-05-31', 2525.00, 39.50, 'Grade B', 'Good Harvest'),
  (15, 15, '2026-11-12', 2650.00, 43.00, 'Grade C', 'Acceptable Quality'),
  (16, 16, '2026-07-26', 2775.00, 46.50, 'Grade A', 'Good Harvest'),
  (17, 17, '2026-06-19', 2900.00, 22.00, 'Grade B', 'Good Harvest'),
  (18, 18, '2026-10-30', 3025.00, 25.50, 'Grade C', 'Acceptable Quality'),
  (19, 19, '2026-07-05', 3150.00, 29.00, 'Grade A', 'Good Harvest'),
  (20, 20, '2026-06-03', 3275.00, 32.50, 'Grade B', 'Good Harvest')
) AS v(id, planting_id, harvested, kg, price, grade, remark)
JOIN quality_grade qg  ON qg.quality_grade_name = v.grade
JOIN harvest_remark hr ON hr.remark_description = v.remark;

SELECT setval(pg_get_serial_sequence('farmer',   'farmer_id'),   (SELECT max(farmer_id)   FROM farmer)),
       setval(pg_get_serial_sequence('farm',     'farm_id'),     (SELECT max(farm_id)     FROM farm)),
       setval(pg_get_serial_sequence('crop',     'crop_id'),     (SELECT max(crop_id)     FROM crop)),
       setval(pg_get_serial_sequence('planting', 'planting_id'), (SELECT max(planting_id) FROM planting)),
       setval(pg_get_serial_sequence('harvest',  'harvest_id'),  (SELECT max(harvest_id)  FROM harvest));

DO $$
BEGIN
  IF (SELECT count(*) FROM farmer)  <> 15 OR (SELECT count(*) FROM farm)     <> 15 OR
     (SELECT count(*) FROM crop)    <> 10 OR (SELECT count(*) FROM planting) <> 28 OR
     (SELECT count(*) FROM harvest) <> 20 THEN
    RAISE EXCEPTION 'Row counts do not match the expected values. Nothing was changed.';
  END IF;
END $$;

COMMIT;

SELECT 'farmer' AS tbl, count(*) FROM farmer
UNION ALL SELECT 'farm', count(*) FROM farm
UNION ALL SELECT 'crop', count(*) FROM crop
UNION ALL SELECT 'planting', count(*) FROM planting
UNION ALL SELECT 'harvest', count(*) FROM harvest
UNION ALL SELECT 'audit_log', count(*) FROM audit_log
UNION ALL SELECT 'users (kept)', count(*) FROM users;

SELECT sum(quantity_kg) AS total_kg, sum(quantity_kg * selling_price_per_kg) AS estimated_value FROM harvest;