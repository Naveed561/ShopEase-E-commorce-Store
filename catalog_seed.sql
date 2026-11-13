INSERT INTO categories (name)
SELECT additions.name
FROM (
    SELECT 'Accessories' AS name
    UNION ALL SELECT 'Home & Living'
    UNION ALL SELECT 'Sports & Outdoors'
) AS additions
WHERE NOT EXISTS (
    SELECT 1 FROM categories existing WHERE existing.name = additions.name
);

INSERT INTO products (name, price, image, category_id)
SELECT additions.name, additions.price, additions.image, categories.id
FROM (
    SELECT 'Denim Jacket' AS name, 6200.00 AS price,
           'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=900&q=85' AS image,
           'Fashion' AS category_name
    UNION ALL SELECT 'Everyday Dress', 4800.00,
           'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=900&q=85',
           'Fashion'
    UNION ALL SELECT 'Classic Running Sneakers', 5200.00,
           'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85',
           'Shoes'
    UNION ALL SELECT 'Trail Running Shoes', 6800.00,
           'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?auto=format&fit=crop&w=900&q=85',
           'Shoes'
    UNION ALL SELECT 'Wireless Earbuds', 3900.00,
           'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=900&q=85',
           'Electronics'
    UNION ALL SELECT 'Bluetooth Headphones', 4600.00,
           'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=85',
           'Electronics'
    UNION ALL SELECT 'Everyday Handbag', 3500.00,
           'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=900&q=85',
           'Accessories'
    UNION ALL SELECT 'Classic Sunglasses', 1800.00,
           'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=900&q=85',
           'Accessories'
    UNION ALL SELECT 'Table Lamp', 3200.00,
           'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=85',
           'Home & Living'
    UNION ALL SELECT 'Reusable Water Bottle', 1200.00,
           'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=900&q=85',
           'Sports & Outdoors'
) AS additions
INNER JOIN categories ON categories.name = additions.category_name
WHERE NOT EXISTS (
    SELECT 1 FROM products existing WHERE existing.name = additions.name
);
