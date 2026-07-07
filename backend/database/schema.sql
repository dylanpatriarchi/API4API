-- API4API — database schema
-- MySQL / MariaDB. Import with: mysql -u <user> -p <database> < schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ESP32 controller boards
CREATE TABLE IF NOT EXISTS esp (
    esp_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dev_kit   VARCHAR(64)  NOT NULL,
    pin_count INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (esp_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Physical beehives, each wired to one ESP board
CREATE TABLE IF NOT EXISTS beehives (
    beehive_id INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(128)  NOT NULL,
    latitude   DECIMAL(10, 7) NOT NULL,
    longitude  DECIMAL(10, 7) NOT NULL,
    esp_id     INT UNSIGNED  NOT NULL,
    PRIMARY KEY (beehive_id),
    UNIQUE KEY uq_beehive_name (name),
    KEY idx_beehive_esp (esp_id),
    CONSTRAINT fk_beehive_esp FOREIGN KEY (esp_id) REFERENCES esp (esp_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Time-series sensor readings coming from the hives
CREATE TABLE IF NOT EXISTS measurements (
    measurement_id INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    weight         DOUBLE         NOT NULL,
    temperature    DOUBLE         NOT NULL,
    humidity       DOUBLE         NOT NULL,
    noise_level    DOUBLE         NOT NULL,
    beehive_id     INT UNSIGNED   NOT NULL,
    recorded_at    DATETIME       NOT NULL,
    PRIMARY KEY (measurement_id),
    KEY idx_measurement_beehive (beehive_id),
    KEY idx_measurement_recorded_at (recorded_at),
    CONSTRAINT fk_measurement_beehive FOREIGN KEY (beehive_id) REFERENCES beehives (beehive_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Alert thresholds, one row per monitored metric
CREATE TABLE IF NOT EXISTS thresholds (
    metric          VARCHAR(32) NOT NULL,
    threshold_value DOUBLE      NOT NULL,
    PRIMARY KEY (metric)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO thresholds (metric, threshold_value) VALUES
    ('weight', 100),
    ('temperature', 40),
    ('humidity', 80),
    ('noise_level', 600000)
ON DUPLICATE KEY UPDATE threshold_value = VALUES(threshold_value);

SET FOREIGN_KEY_CHECKS = 1;
