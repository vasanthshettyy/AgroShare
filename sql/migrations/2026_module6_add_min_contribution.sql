ALTER TABLE pooling_campaigns ADD COLUMN min_contribution INT UNSIGNED NOT NULL DEFAULT 1 AFTER target_quantity;
