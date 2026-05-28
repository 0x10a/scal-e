-- =============================================================
--  Scal-e CDP — Database Schema
--  Engine  : InnoDB
--  Charset : utf8mb4 / utf8mb4_unicode_ci
--
--  MySQL user setup (run as root before applying this schema):
--
--    CREATE DATABASE scal_e_db
--      CHARACTER SET utf8mb4
--      COLLATE utf8mb4_unicode_ci;
--
--    CREATE USER 'scal_e_user'@'localhost'
--      IDENTIFIED BY '<strong_password>';
--
--    GRANT SELECT, INSERT, UPDATE, DELETE
--      ON scal_e_db.*
--      TO 'scal_e_user'@'localhost';
--
--    FLUSH PRIVILEGES;
--
--  Apply schema:
--    mysql -u root -p scal_e_db < database/schema.sql
-- =============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -------------------------------------------------------------
--  customers
--  Unique customer profiles, identified and de-duplicated by email.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255)  NOT NULL COMMENT 'Unique customer identifier',
    `name`       VARCHAR(255)  NOT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Fast upsert and lookup by email
    UNIQUE KEY `uk_customers_email` (`email`),

    -- Time-range listing (newest first)
    KEY `idx_customers_created_at` (`created_at`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Unique customer profiles';


-- -------------------------------------------------------------
--  events
--  Behavioural events linked to customers.
--
--  Index strategy:
--   - idx_events_customer_id       : all events per customer (profile endpoint)
--   - idx_events_customer_type     : compound for segmentation (customer + event type)
--   - idx_events_event_type        : analytics per event type
--   - idx_events_occurred_at       : time-range queries
--   - uk_events_hash               : deduplication (INSERT IGNORE on duplicate hash)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT UNSIGNED    NOT NULL,
    `event_type`  VARCHAR(100)    NOT NULL  COMMENT 'e.g. "purchase", "page_view"',
    `event_hash`  CHAR(40)            NULL  COMMENT 'SHA1 for deduplication; NULL = no dedup',
    `occurred_at` DATETIME        NOT NULL  COMMENT 'Business timestamp (UTC)',
    `created_at`  DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Ingestion timestamp',

    PRIMARY KEY (`id`),

    UNIQUE KEY `uk_events_hash`           (`event_hash`),
    KEY        `idx_events_customer_id`   (`customer_id`),
    KEY        `idx_events_customer_type` (`customer_id`, `event_type`),
    KEY        `idx_events_event_type`    (`event_type`),
    KEY        `idx_events_occurred_at`   (`occurred_at`),

    CONSTRAINT `fk_events_customer_id`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer behavioural events';


-- -------------------------------------------------------------
--  event_properties
--  Key/value properties attached to each event.
--
--  Dual-column design (property_value + property_numeric):
--   - property_value   VARCHAR  : raw string for all values; equality/contains queries.
--   - property_numeric DECIMAL  : populated for numeric values only; enables
--                                  B-tree range comparisons (>, <, >=, <=) via
--                                  idx_ep_key_numeric without CAST or JSON_EXTRACT.
--
--  Index strategy:
--   - idx_ep_event_id    : properties per event (JOIN from events)
--   - idx_ep_key_numeric : composite for segmentation numeric range queries
--   - idx_ep_key_value   : composite for segmentation string equality / prefix queries
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_properties` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_id`         BIGINT UNSIGNED NOT NULL,
    `property_key`     VARCHAR(100)    NOT NULL,
    `property_value`   VARCHAR(1000)       NULL COMMENT 'Raw string value',
    `property_numeric` DECIMAL(15, 4)      NULL COMMENT 'Numeric value for indexed range queries',

    PRIMARY KEY (`id`),

    KEY `idx_ep_event_id`    (`event_id`),
    KEY `idx_ep_key_numeric` (`property_key`, `property_numeric`),
    KEY `idx_ep_key_value`   (`property_key`, `property_value`(100)),

    CONSTRAINT `fk_ep_event_id`
        FOREIGN KEY (`event_id`)
        REFERENCES `events` (`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Key/value properties per event; dual-column for indexed numeric range queries';


SET foreign_key_checks = 1;
