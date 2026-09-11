UPDATE products p
JOIN (
    SELECT product_id, SUM(stock_quantity) AS total_stock
    FROM product_variants
    GROUP BY product_id
) variants ON variants.product_id = p.id
SET p.stock_quantity = variants.total_stock;
