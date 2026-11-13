BEGIN;

TRUNCATE harvest, planting, farm, crop, farmer RESTART IDENTITY;

TRUNCATE audit_log RESTART IDENTITY;

INSERT INTO farmer (first_name, last_name, contact_number, address, date_registered) VALUES
  ('Juan', 'Dela Cruz', '0917-555-0101', 'Brgy. Subangdaku, Mandaue City', '2025-01-15'),
  ('Maria', 'Reyes', '0917-555-0102', 'Brgy. Casuntingan, Mandaue City', '2025-02-03'),
  ('Pedro', 'Santos', '0917-555-0103', 'Brgy. Poblacion, Consolacion', '2025-02-20'),
  ('Ana', 'Garcia', '0917-555-0104', 'Brgy. Cansaga, Consolacion', '2025-03-11'),
  ('Jose', 'Flores', '0917-555-0105', 'Brgy. Yati, Liloan', '2025-04-02'),
  ('Rosa', 'Mendoza', '0917-555-0106', 'Brgy. Poblacion, Compostela', '2025-05-19'),
  ('Carlos', 'Villanueva', '0917-555-0107', 'Brgy. Guinsay, Danao City', '2025-06-08'),
  ('Elena', 'Navarro', '0917-555-0108', 'Brgy. Tabunok, Talisay City', '2025-07-25'),
  ('Ramon', 'Aquino', '0917-555-0109', 'Brgy. Poblacion, Carcar City', '2025-09-14'),
  ('Luz', 'Ramos', '0917-555-0110', 'Brgy. Mantalongon, Dalaguete', '2025-11-06'),
  ('Miguel', 'Torres', '0917-555-0111', 'Brgy. Poblacion, Naga City', '2026-02-17'),
  ('Teresa', 'Castillo', '0917-555-0112', 'Brgy. Bulacao, Cebu City', '2026-04-09');

INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id)
SELECT fr.farmer_id, v.farm_name, v.location, v.area, st.soil_type_id, it.irrigation_type_id
FROM (VALUES
  ('0917-555-0101', 'Dela Cruz Farm', 'Sitio Mabolo, Subangdaku, Mandaue City', 3.50, 'Loam', 'Irrigated'),
  ('0917-555-0102', 'Reyes Agricultural Farm', 'Sitio Riverside, Casuntingan, Mandaue City', 2.50, 'Clay Loam', 'Rainfed'),
  ('0917-555-0103', 'Santos Vegetable Farm', 'Sitio Upper, Poblacion, Consolacion', 1.80, 'Sandy Loam', 'Drip Irrigation'),
  ('0917-555-0104', 'Garcia Peanut Field', 'Sitio Lower, Cansaga, Consolacion', 2.00, 'Sandy Loam', 'Rainfed'),
  ('0917-555-0105', 'Flores Melon Farm', 'Sitio Coastal, Yati, Liloan', 1.50, 'Loam', 'Drip Irrigation'),
  ('0917-555-0106', 'Mendoza Farm', 'Sitio Centro, Poblacion, Compostela', 1.20, 'Clay Loam', 'Irrigated'),
  ('0917-555-0107', 'Villanueva Root Farm', 'Sitio Hillside, Guinsay, Danao City', 3.00, 'Loam', 'Rainfed'),
  ('0917-555-0108', 'Navarro Rice Field', 'Sitio Paddy, Tabunok, Talisay City', 4.00, 'Clay', 'Irrigated'),
  ('0917-555-0109', 'Aquino Farm', 'Sitio Maharlika, Poblacion, Carcar City', 2.20, 'Sandy Loam', 'Rainfed'),
  ('0917-555-0110', 'Ramos Corn Farm', 'Sitio Highland, Mantalongon, Dalaguete', 2.80, 'Clay Loam', 'Rainfed'),
  ('0917-555-0111', 'Torres Farm', 'Sitio Greenfield, Poblacion, Naga City', 1.60, 'Loam', 'Drip Irrigation'),
  ('0917-555-0112', 'Castillo Agricultural Farm', 'Sitio Lowland, Bulacao, Cebu City', 5.00, 'Clay', 'Irrigated')
) AS v(contact, farm_name, location, area, soil, irrigation)
JOIN farmer fr          ON fr.contact_number = v.contact
JOIN soil_type st       ON st.soil_type_name = v.soil
JOIN irrigation_type it ON it.irrigation_type_name = v.irrigation;

INSERT INTO crop (crop_name, variety, crop_category_id, growing_period_days, season_id)
SELECT v.crop_name, v.variety, cc.crop_category_id, v.days, s.season_id
FROM (VALUES
  ('Rice', 'NSIC Rc 222', 'Cereal', 115, 'Wet Season'),
  ('Corn', 'Yellow Corn', 'Cereal', 100, 'Dry Season'),
  ('Tomato', 'Diamante Max', 'Vegetable', 90, 'Dry Season'),
  ('Eggplant', 'Morena', 'Vegetable', 100, 'Year Round'),
  ('Banana', 'Lakatan', 'Fruit', 300, 'Year Round'),
  ('Watermelon', 'Sweet 16', 'Fruit', 85, 'Dry Season'),
  ('Mango', 'Carabao', 'Fruit', 150, 'Year Round'),
  ('Peanut', 'BPI Pn 9', 'Legume', 110, 'Year Round'),
  ('Mung Bean', 'NSIC Mb 22', 'Legume', 65, 'Dry Season'),
  ('Sweet Potato', 'NSIC Sp 30', 'Root Crop', 120, 'Year Round'),
  ('Cassava', 'Golden Yellow', 'Root Crop', 240, 'Year Round')
) AS v(crop_name, variety, category, days, season)
JOIN crop_category cc ON cc.crop_category_name = v.category
JOIN season s         ON s.season_name = v.season;

INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
SELECT f.farm_id, c.crop_id, v.planted::date, v.area, v.seed, v.planted::date + c.growing_period_days, ps.status_id
FROM (VALUES
  ('Dela Cruz Farm', 'Rice', '2026-01-10', 2.00, 80.00, 'Harvested'),
  ('Reyes Agricultural Farm', 'Corn', '2026-02-05', 1.50, 30.00, 'Harvested'),
  ('Santos Vegetable Farm', 'Tomato', '2026-02-20', 0.60, 2.00, 'Harvested'),
  ('Garcia Peanut Field', 'Peanut', '2026-03-01', 1.20, 96.00, 'Harvested'),
  ('Flores Melon Farm', 'Watermelon', '2026-03-15', 0.90, 5.00, 'Harvested'),
  ('Mendoza Farm', 'Eggplant', '2026-03-28', 0.50, 1.00, 'Harvested'),
  ('Villanueva Root Farm', 'Sweet Potato', '2026-04-10', 1.50, 120.00, 'Harvested'),
  ('Navarro Rice Field', 'Rice', '2026-04-22', 3.00, 120.00, 'Harvested'),
  ('Aquino Farm', 'Mung Bean', '2026-06-01', 1.00, 20.00, 'Harvested'),
  ('Ramos Corn Farm', 'Corn', '2026-06-18', 2.00, 40.00, 'Harvested'),
  ('Torres Farm', 'Tomato', '2026-07-12', 0.70, 2.00, 'Harvested'),
  ('Dela Cruz Farm', 'Cassava', '2026-06-05', 1.00, 100.00, 'Growing'),
  ('Castillo Agricultural Farm', 'Rice', '2026-09-10', 2.50, 100.00, 'Growing'),
  ('Reyes Agricultural Farm', 'Banana', '2026-11-03', 0.80, 160.00, 'Planted'),
  ('Santos Vegetable Farm', 'Corn', '2026-11-08', 1.00, 20.00, 'Planted')
) AS v(farm_name, crop_name, planted, area, seed, status)
JOIN farm f             ON f.farm_name = v.farm_name
JOIN crop c             ON c.crop_name = v.crop_name
JOIN planting_status ps ON ps.status_name = v.status;

INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remark_id)
SELECT p.planting_id, v.harvested::date, v.kg, v.price, qg.quality_grade_id, hr.remark_id
FROM (VALUES
  ('Dela Cruz Farm', 'Rice', '2026-01-10', '2026-05-08', 8600.00, 22.00, 'Grade A', 'Good Harvest'),
  ('Reyes Agricultural Farm', 'Corn', '2026-02-05', '2026-05-20', 3900.00, 20.50, 'Grade B', 'Low Yield'),
  ('Santos Vegetable Farm', 'Tomato', '2026-02-20', '2026-05-25', 2400.00, 38.00, 'Grade A', 'Good Harvest'),
  ('Garcia Peanut Field', 'Peanut', '2026-03-01', '2026-06-22', 2100.00, 80.00, 'Grade A', 'Good Harvest'),
  ('Flores Melon Farm', 'Watermelon', '2026-03-15', '2026-06-12', 9200.00, 24.00, 'Grade B', 'Acceptable Quality'),
  ('Mendoza Farm', 'Eggplant', '2026-03-28', '2026-07-10', 1850.00, 45.00, 'Grade B', 'Acceptable Quality'),
  ('Villanueva Root Farm', 'Sweet Potato', '2026-04-10', '2026-08-12', 12500.00, 29.00, 'Grade A', 'Good Harvest'),
  ('Navarro Rice Field', 'Rice', '2026-04-22', '2026-08-19', 14200.00, 21.00, 'Grade C', 'Poor Quality'),
  ('Aquino Farm', 'Mung Bean', '2026-06-01', '2026-08-07', 1100.00, 95.00, 'Grade B', 'Low Yield'),
  ('Ramos Corn Farm', 'Corn', '2026-06-18', '2026-09-29', 6300.00, 19.00, 'Grade C', 'Damaged Harvest'),
  ('Torres Farm', 'Tomato', '2026-07-12', '2026-10-14', 2000.00, 36.00, 'Grade A', 'Good Harvest')
) AS v(farm_name, crop_name, planted, harvested, kg, price, grade, remark)
JOIN farm f            ON f.farm_name = v.farm_name
JOIN crop c            ON c.crop_name = v.crop_name
JOIN planting p        ON p.farm_id = f.farm_id AND p.crop_id = c.crop_id AND p.planting_date = v.planted::date
JOIN quality_grade qg  ON qg.quality_grade_name = v.grade
JOIN harvest_remark hr ON hr.remark_description = v.remark;

DO $$
BEGIN
  IF (SELECT count(*) FROM farmer)   <> 12  OR (SELECT count(*) FROM farm)    <> 12 OR
     (SELECT count(*) FROM crop)     <> 11  OR (SELECT count(*) FROM planting) <> 15  OR
     (SELECT count(*) FROM harvest)  <> 11 THEN
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
