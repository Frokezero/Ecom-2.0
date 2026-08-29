ALTER TABLE seller_profiles
    ADD COLUMN shop_logo VARCHAR(255) NULL AFTER shop_description,
    ADD COLUMN cover_image VARCHAR(255) NULL AFTER shop_logo,
    ADD COLUMN promo_image VARCHAR(255) NULL AFTER cover_image,
    ADD COLUMN promo_title VARCHAR(120) NULL AFTER promo_image,
    ADD COLUMN promo_text VARCHAR(250) NULL AFTER promo_title,
    ADD COLUMN promo_url VARCHAR(500) NULL AFTER promo_text;
