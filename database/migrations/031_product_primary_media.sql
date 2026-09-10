ALTER TABLE products
 ADD COLUMN IF NOT EXISTS primary_media_type ENUM('image','video') NOT NULL DEFAULT 'image' AFTER video_url;
