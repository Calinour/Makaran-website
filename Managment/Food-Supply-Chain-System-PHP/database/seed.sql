USE `sahanfresh_fscms`;

-- Clean existing data just in case
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `purchase_orders`;
TRUNCATE TABLE `supplier_performance`;
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `orders`;
TRUNCATE TABLE `inventory_batches`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Insert Users
-- Roles: admin, supplier, customer, driver
-- Passwords: admin123, supplier123, customer123, driver123 respectively
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`) VALUES
(1, 'Ahmed Omar (Admin)', 'admin@sahanfresh.com', '$2y$10$llF9m4ju8w5WEWIT51eRR.rExpw7Jrcb9jlH2ynqXG/wkUOxQPsdq', 'admin', '+252615555551', 'Mogadishu Headquarters, Somalia'),
(2, 'Sahan Organic Farms (Supplier 1)', 'supplier1@sahanfresh.com', '$2y$10$RRj0TIKP3bv/rXnrolbrLuf9V2fIec1R3Az6sMAdNuQ8m9WsdDtBW', 'supplier', '+252615555552', 'Shabelle Valley Farms, Afgooye'),
(3, 'Nomad Dairy Cooperative (Supplier 2)', 'supplier2@sahanfresh.com', '$2y$10$RRj0TIKP3bv/rXnrolbrLuf9V2fIec1R3Az6sMAdNuQ8m9WsdDtBW', 'supplier', '+252615555553', 'Pastoral Dairy Hub, Galkayo'),
(4, 'Farah Ali (Customer)', 'customer@sahanfresh.com', '$2y$10$PDFW4CHBctb7/rxDeqOSh.deApg3myHNId8gnvk9/as1o.YhA2GMm', 'customer', '+252615555554', 'Hoddan District, Mogadishu'),
(5, 'Alice Johnson (Customer)', 'alice@gmail.com', '$2y$10$PDFW4CHBctb7/rxDeqOSh.deApg3myHNId8gnvk9/as1o.YhA2GMm', 'customer', '+252619999999', 'Waberi District, Mogadishu'),
(6, 'Jama Mohamed (Driver)', 'driver1@sahanfresh.com', '$2y$10$Xdb0eQePTlpGCuSbnjfb3u/FF1UepRyDQnrWQO1pCiL5DSbiaoW0m', 'driver', '+252615555555', 'Logistics Depot A, Mogadishu'),
(7, 'Hassan Yusuf (Driver)', 'driver2@sahanfresh.com', '$2y$10$Xdb0eQePTlpGCuSbnjfb3u/FF1UepRyDQnrWQO1pCiL5DSbiaoW0m', 'driver', '+252615555556', 'Logistics Depot B, Mogadishu');

-- Insert Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Fruits & Vegetables', 'Fresh farm harvest including tomatoes, bananas, and leafy greens'),
(2, 'Dairy & Eggs', 'Fresh camel milk, cow milk, cheese, and organic eggs'),
(3, 'Meat & Poultry', 'High-quality local beef, goat meat, and fresh chicken'),
(4, 'Bakery', 'Freshly baked traditional bread, buns, and Somali Muufo'),
(5, 'Grains & Pulses', 'Local rice, beans, maize, and sorghum grains');

-- Insert Products
INSERT INTO `products` (`id`, `category_id`, `supplier_id`, `name`, `description`, `price`, `sku`, `image_url`) VALUES
(1, 1, 2, 'Organic Red Tomatoes', 'Juicy organic red tomatoes, harvested daily from Shabelle Valley.', 2.50, 'VEG-TOM-001', 'assets/images/tomatoes.jpg'),
(2, 1, 2, 'Somali Sweet Bananas', 'Naturally sweet Somali bananas, famous for their rich taste.', 1.80, 'FRU-BAN-001', 'assets/images/bananas.jpg'),
(3, 2, 3, 'Fresh Camel Milk', 'Raw, nutritious camel milk from pastoralist herds in Galkayo.', 4.50, 'DAI-CAM-001', 'assets/images/camel_milk.jpg'),
(4, 2, 3, 'Local Cow Ghee', 'Pure, traditional cow milk ghee (Subag) for Somali cooking.', 8.00, 'DAI-GHE-001', 'assets/images/ghee.jpg'),
(5, 3, 2, 'Tender Goat Meat', 'Fresh, grass-fed local goat meat (cut into pieces), 1kg.', 12.00, 'MEA-GOA-001', 'assets/images/goat_meat.jpg'),
(6, 4, 2, 'Somali Muufo Bread', 'Traditional Somali clay-oven baked Muufo bread (Pack of 5).', 3.00, 'BAK-MUU-001', 'assets/images/muufo.jpg'),
(7, 5, 3, 'Premium Somali Sorghum', 'Locally grown red sorghum (Haro), highly nutritious grain, 1kg.', 2.20, 'GRA-SOR-001', 'assets/images/sorghum.jpg');

-- Insert Inventory Batches
-- Track quantities, expiry dates, and statuses
INSERT INTO `inventory_batches` (`id`, `product_id`, `batch_number`, `quantity`, `expiry_date`, `status`, `notes`) VALUES
-- Tomatoes: batch 1 active, batch 2 expired, batch 3 damaged
(1, 1, 'B-TOM-101', 80, '2026-07-15', 'active', 'Fresh harvest, stored in cooling crates.'),
(2, 1, 'B-TOM-102', 15, '2026-06-10', 'expired', 'Unsold batch, started rotting.'),
(3, 1, 'B-TOM-103', 10, '2026-07-20', 'damaged', 'Crushed during transit loading.'),
-- Bananas: batch 1 active (low stock warning)
(4, 2, 'B-BAN-201', 8, '2026-06-25', 'active', 'Ripening nicely.'),
-- Camel Milk: batch 1 active, batch 2 near expiry
(5, 3, 'B-MIL-301', 50, '2026-07-02', 'active', 'Pasteurized and bottled.'),
(6, 3, 'B-MIL-302', 20, '2026-06-18', 'active', 'Needs quick sale (expires in 2 days).'),
-- Cow Ghee: batch 1 active
(7, 4, 'B-GHE-401', 40, '2027-06-15', 'active', 'Aseptic packaging, long shelf life.'),
-- Goat Meat: batch 1 active
(8, 5, 'B-MEA-501', 25, '2026-06-20', 'active', 'Chilled at 2 degrees C.'),
-- Muufo: batch 1 active
(9, 6, 'B-BAK-601', 30, '2026-06-22', 'active', 'Freshly baked.'),
-- Sorghum: batch 1 active
(10, 7, 'B-GRA-701', 150, '2027-12-30', 'active', 'Dry grains, moisture content 12%.');

-- Insert Supplier Performance Metrics
INSERT INTO `supplier_performance` (`supplier_id`, `rating`, `review`, `ontime_delivery_rate`) VALUES
(2, 4, 'Reliable organic produce supplier, sometimes delays due to road blockades.', 88.50),
(3, 5, 'Excellent quality camel milk and ghee. Deliveries are always prompt.', 97.20);

-- Insert Purchase Orders
INSERT INTO `purchase_orders` (`supplier_id`, `product_id`, `quantity`, `status`) VALUES
(2, 1, 100, 'received'),
(2, 5, 20, 'received'),
(3, 3, 50, 'pending'),
(3, 4, 30, 'pending');

-- Insert Orders
-- Order 1: Delivered
-- Order 2: Assigned to driver (Out for delivery)
-- Order 3: Paid & Approved (Pending assignment)
-- Order 4: Pending payment
INSERT INTO `orders` (`id`, `customer_id`, `total_amount`, `status`, `payment_status`, `shipping_address`, `driver_id`, `delivery_notes`) VALUES
(1, 4, 25.00, 'delivered', 'paid', 'Apartment 4B, Wadajir District, Mogadishu', 6, 'Delivered directly to the customer doorstep. Prompt response.'),
(2, 4, 15.60, 'out_for_delivery', 'paid', 'Villa Somalia Area, Wardhiigley, Mogadishu', 6, 'Call when at the main security gate.'),
(3, 5, 17.50, 'approved', 'paid', 'Bulla Hubey, Wadajir District, Mogadishu', NULL, 'Deliver in the afternoon please.'),
(4, 5, 18.00, 'pending', 'pending', 'Waberi, Mogadishu', NULL, NULL);

-- Insert Order Items
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`) VALUES
-- Order 1 items
(1, 1, 4, 2.50), -- 10.00
(1, 3, 2, 4.50), -- 9.00
(1, 6, 2, 3.00), -- 6.00
-- Order 2 items
(2, 2, 2, 1.80), -- 3.60
(2, 5, 1, 12.00), -- 12.00
-- Order 3 items
(3, 1, 3, 2.50), -- 7.50
(3, 6, 2, 3.00), -- 6.00
(3, 2, 2, 1.80), -- 3.60
-- Order 4 items
(4, 4, 1, 8.00), -- 8.00
(4, 5, 1, 10.00); -- 10.00 (discount price at the time)

-- Insert Payments
INSERT INTO `payments` (`order_id`, `amount`, `payment_method`, `transaction_id`, `status`) VALUES
(1, 25.00, 'mobile_money', 'TXN-9021830129', 'completed'),
(2, 15.60, 'card', 'TXN-8819283120', 'completed'),
(3, 17.50, 'mobile_money', 'TXN-7728192318', 'completed');
