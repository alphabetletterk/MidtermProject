CREATE TABLE soil_type (
  soil_type_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  soil_type_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE irrigation_type (
  irrigation_type_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  irrigation_type_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE crop_category (
  crop_category_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  crop_category_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE season (
  season_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  season_name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE planting_status (
  status_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  status_name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE quality_grade (
  quality_grade_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  quality_grade_name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE harvest_remark (
  remark_id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  remark_description VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE role (
  role_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  role_name VARCHAR(30) NOT NULL UNIQUE
);

INSERT INTO soil_type (soil_type_name) VALUES
  ('Loam'), ('Clay Loam'), ('Sandy Loam'), ('Clay');

INSERT INTO irrigation_type (irrigation_type_name) VALUES
  ('Rainfed'), ('Irrigated'), ('Drip Irrigation');

INSERT INTO crop_category (crop_category_name) VALUES
  ('Cereal'), ('Vegetable'), ('Fruit'), ('Legume'), ('Root Crop');

INSERT INTO season (season_name) VALUES
  ('Wet Season'), ('Dry Season'), ('Year Round');

INSERT INTO planting_status (status_name) VALUES
  ('Planted'), ('Growing'), ('Harvested');

INSERT INTO quality_grade (quality_grade_name) VALUES
  ('Grade A'), ('Grade B'), ('Grade C');

INSERT INTO harvest_remark (remark_description) VALUES
  ('Good Harvest'), ('Acceptable Quality'), ('Low Yield'),
  ('Poor Quality'), ('Damaged Harvest');

INSERT INTO role (role_name) VALUES
  ('Administrator'), ('Staff');

CREATE TABLE farmer (
  farmer_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  first_name      VARCHAR(50)  NOT NULL,
  last_name       VARCHAR(50)  NOT NULL,
  contact_number  VARCHAR(20)  NOT NULL UNIQUE,
  address         VARCHAR(150) NOT NULL,
  date_registered DATE         NOT NULL DEFAULT CURRENT_DATE,
  created_at      TIMESTAMPTZ  NOT NULL DEFAULT now(),
  updated_at      TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE farm (
  farm_id            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  farmer_id          INTEGER      NOT NULL REFERENCES farmer(farmer_id) ON DELETE RESTRICT,
  farm_name          VARCHAR(100) NOT NULL,
  location           VARCHAR(150) NOT NULL,
  area_hectares      NUMERIC(8,2) NOT NULL CHECK (area_hectares > 0),
  soil_type_id       INTEGER      NOT NULL REFERENCES soil_type(soil_type_id) ON DELETE RESTRICT,
  irrigation_type_id INTEGER      NOT NULL REFERENCES irrigation_type(irrigation_type_id) ON DELETE RESTRICT,
  created_at         TIMESTAMPTZ  NOT NULL DEFAULT now(),
  updated_at         TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE crop (
  crop_id             INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  crop_name           VARCHAR(80) NOT NULL,
  variety             VARCHAR(80) NOT NULL,
  crop_category_id    INTEGER     NOT NULL REFERENCES crop_category(crop_category_id) ON DELETE RESTRICT,
  growing_period_days INTEGER     NOT NULL CHECK (growing_period_days > 0),
  season_id           INTEGER     NOT NULL REFERENCES season(season_id) ON DELETE RESTRICT,
  created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (crop_name, variety)
);

CREATE TABLE planting (
  planting_id           INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  farm_id               INTEGER      NOT NULL REFERENCES farm(farm_id) ON DELETE RESTRICT,
  crop_id               INTEGER      NOT NULL REFERENCES crop(crop_id) ON DELETE RESTRICT,
  planting_date         DATE         NOT NULL,
  area_planted          NUMERIC(8,2) NOT NULL CHECK (area_planted > 0),
  seed_quantity         NUMERIC(8,2) NOT NULL CHECK (seed_quantity > 0),
  expected_harvest_date DATE         NOT NULL,
  status_id             INTEGER      NOT NULL REFERENCES planting_status(status_id) ON DELETE RESTRICT,
  created_at            TIMESTAMPTZ  NOT NULL DEFAULT now(),
  updated_at            TIMESTAMPTZ  NOT NULL DEFAULT now(),
  CHECK (expected_harvest_date >= planting_date)
);

CREATE TABLE harvest (
  harvest_id           INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  planting_id          INTEGER       NOT NULL REFERENCES planting(planting_id) ON DELETE RESTRICT,
  harvest_date         DATE          NOT NULL,
  quantity_kg          NUMERIC(10,2) NOT NULL CHECK (quantity_kg > 0),
  selling_price_per_kg NUMERIC(10,2) NOT NULL CHECK (selling_price_per_kg >= 0),
  quality_grade_id     INTEGER       NOT NULL REFERENCES quality_grade(quality_grade_id) ON DELETE RESTRICT,
  remark_id            INTEGER       NOT NULL REFERENCES harvest_remark(remark_id) ON DELETE RESTRICT,
  created_at           TIMESTAMPTZ   NOT NULL DEFAULT now(),
  updated_at           TIMESTAMPTZ   NOT NULL DEFAULT now()
);

CREATE TABLE users (
  user_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id       INTEGER      NOT NULL REFERENCES role(role_id) ON DELETE RESTRICT,
  created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
  updated_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX idx_farm_farmer       ON farm(farmer_id);
CREATE INDEX idx_planting_farm     ON planting(farm_id);
CREATE INDEX idx_planting_crop     ON planting(crop_id);
CREATE INDEX idx_planting_status   ON planting(status_id);
CREATE INDEX idx_harvest_planting  ON harvest(planting_id);

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = ''
AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$;

CREATE TRIGGER trg_farmer_updated   BEFORE UPDATE ON farmer   FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_farm_updated     BEFORE UPDATE ON farm     FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_crop_updated     BEFORE UPDATE ON crop     FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_planting_updated BEFORE UPDATE ON planting FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_harvest_updated  BEFORE UPDATE ON harvest  FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_users_updated    BEFORE UPDATE ON users    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

ALTER TABLE soil_type       ENABLE ROW LEVEL SECURITY;
ALTER TABLE irrigation_type ENABLE ROW LEVEL SECURITY;
ALTER TABLE crop_category   ENABLE ROW LEVEL SECURITY;
ALTER TABLE season          ENABLE ROW LEVEL SECURITY;
ALTER TABLE planting_status ENABLE ROW LEVEL SECURITY;
ALTER TABLE quality_grade   ENABLE ROW LEVEL SECURITY;
ALTER TABLE harvest_remark  ENABLE ROW LEVEL SECURITY;
ALTER TABLE role            ENABLE ROW LEVEL SECURITY;
ALTER TABLE farmer          ENABLE ROW LEVEL SECURITY;
ALTER TABLE farm            ENABLE ROW LEVEL SECURITY;
ALTER TABLE crop            ENABLE ROW LEVEL SECURITY;
ALTER TABLE planting        ENABLE ROW LEVEL SECURITY;
ALTER TABLE harvest         ENABLE ROW LEVEL SECURITY;
ALTER TABLE users           ENABLE ROW LEVEL SECURITY;

CREATE TABLE audit_log (
  audit_id     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id      INTEGER REFERENCES users(user_id) ON DELETE SET NULL,
  username     VARCHAR(50)  NOT NULL,
  action       VARCHAR(20)  NOT NULL
               CHECK (action IN ('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'LOGIN_FAILED')),
  table_name   VARCHAR(50)  NOT NULL,
  record_id    INTEGER,
  record_label VARCHAR(200),
  details      TEXT,
  created_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX idx_audit_created  ON audit_log(created_at DESC);
CREATE INDEX idx_audit_username ON audit_log(username);
CREATE INDEX idx_audit_table    ON audit_log(table_name);

ALTER TABLE audit_log ENABLE ROW LEVEL SECURITY;