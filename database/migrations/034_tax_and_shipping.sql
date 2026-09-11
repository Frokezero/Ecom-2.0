ALTER TABLE seller_profiles
    ADD COLUMN shipping_fee DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 50.00 AFTER response_minutes,
    ADD COLUMN free_shipping_min DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 1000.00 AFTER shipping_fee;

ALTER TABLE orders
    ADD COLUMN tax_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0 AFTER shipping_amount;
