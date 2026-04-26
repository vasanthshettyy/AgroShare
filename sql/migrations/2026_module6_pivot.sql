ALTER TABLE pooling_campaigns DROP CHECK chk_campaigns_bulk_cheaper;
ALTER TABLE pooling_campaigns DROP COLUMN price_per_unit_individual;
ALTER TABLE pooling_campaigns CHANGE price_per_unit_bulk offering_price DECIMAL(10,2) NOT NULL;
ALTER TABLE pooling_campaigns CHANGE minimum_quantity target_quantity INT UNSIGNED NOT NULL;
