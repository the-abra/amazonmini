-- ==============================================================================
-- Modular PHP E-Commerce & Cargo Tracking Platform
-- Rich Initial Database Seeds (Demo Accounts, Catalog, Orders & Shipments)
-- Password for all demo accounts: Password123!
-- Bcrypt Hash: $2y$10$JULhX4pEUkRx7veDEcle3OiW5h0csIgbxvDqAqk8WxTk3mT9EV62u
-- ==============================================================================

-- 1. Insert Demo Users
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`) VALUES
(1, 'Alice Freeman', 'customer@example.com', '$2y$10$JULhX4pEUkRx7veDEcle3OiW5h0csIgbxvDqAqk8WxTk3mT9EV62u', 'customer', '+1 206-555-0142'),
(2, 'Marcus Store Manager', 'admin@example.com', '$2y$10$JULhX4pEUkRx7veDEcle3OiW5h0csIgbxvDqAqk8WxTk3mT9EV62u', 'admin', '+1 206-555-0199'),
(3, 'David Logistics Officer', 'courier@example.com', '$2y$10$JULhX4pEUkRx7veDEcle3OiW5h0csIgbxvDqAqk8WxTk3mT9EV62u', 'courier', '+1 206-555-0188');

-- 2. Insert Default Addresses for Demo Customer
INSERT INTO `addresses` (`id`, `user_id`, `title`, `recipient_name`, `phone`, `address_line`, `city`, `postal_code`, `country`, `is_default`) VALUES
(1, 1, 'Home (Seattle)', 'Alice Freeman', '+1 206-555-0142', '412 Pike Street, Apt 8B', 'Seattle', '98101', 'United States', 1),
(2, 1, 'Office (Bellevue Tech Park)', 'Alice Freeman', '+1 206-555-0142', '108th Ave NE, Suite 500', 'Bellevue', '98004', 'United States', 0);

-- 3. Insert Product Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon_name`) VALUES
(1, 'Electronics & Gadgets', 'electronics-gadgets', 'High-performance mobile devices, smart gadgets, and wearables.', 'cpu'),
(2, 'Computers & Accessories', 'computers-accessories', 'Laptops, mechanical keyboards, precision mice, and desktop peripherals.', 'monitor'),
(3, 'Audio & Acoustics', 'audio-acoustics', 'Noise-canceling headphones, studio monitors, and wireless earbuds.', 'headphones'),
(4, 'Books & Computer Science', 'books-computer-science', 'Authoritative software architecture, programming algorithms, and AI texts.', 'book-open'),
(5, 'Smart Home & Office', 'smart-home-office', 'Ergonomic desk accessories, ambient lighting, and productivity tools.', 'home');

-- 4. Insert Products with Specifications JSON
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `slug`, `description`, `price`, `stock`, `image_url`, `specs_json`, `rating_avg`, `review_count`, `is_active`) VALUES
(1, 3, 'AUD-NC-700', 'SonicPro Wireless ANC Headphones', 'sonicpro-wireless-anc-headphones', 'Engineered with hybrid active noise cancellation, custom 40mm beryllium drivers, and up to 40 hours of playback time on a single USB-C charge.', 199.99, 45, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', '{"Driver Size": "40mm Beryllium", "Battery Life": "40 Hours", "Connectivity": "Bluetooth 5.3 + 3.5mm", "Weight": "245g"}', 4.80, 2, 1),
(2, 2, 'KEY-MECH-TKL', 'Apex Tenkeyless Mechanical Keyboard', 'apex-tenkeyless-mechanical-keyboard', 'Compact aluminum 80% chassis featuring hot-swappable linear switches, double-shot PBT keycaps, per-key RGB backlighting, and detachable coiled cable.', 129.50, 30, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&auto=format&fit=crop&q=80', '{"Layout": "Tenkeyless (87 keys)", "Switch Type": "Gateron Red Linear", "Keycaps": "Double-shot PBT", "Connection": "USB-C Braided"}', 4.90, 2, 1),
(3, 2, 'MOU-PREC-WL', 'Vanguard Wireless Precision Mouse', 'vanguard-wireless-precision-mouse', 'Ergonomic palm grip with 26,000 DPI optical tracking sensor, dual wireless modes (2.4GHz dongle + Bluetooth), and hyperscroll mechanical wheel.', 79.99, 55, 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600&auto=format&fit=crop&q=80', '{"DPI Sensor": "26,000 DPI", "Polling Rate": "1,000 Hz", "Battery Life": "70 Hours", "Weight": "68g"}', 4.70, 1, 1),
(4, 1, 'GAD-HUB-10IN1', 'OmniPort 10-in-1 USB-C Thunderbolt Hub', 'omniport-10-in-1-usb-c-thunderbolt-hub', 'Universal aluminum hub with dual 4K@60Hz HDMI outputs, Gigabit Ethernet, 100W Power Delivery pass-through, UHS-II SD reader, and 3x USB 3.2 Gen 2 ports.', 89.00, 40, 'https://images.unsplash.com/photo-1544652478-6653e09f18a2?w=600&auto=format&fit=crop&q=80', '{"Ports": "2x HDMI, 1x GbE, 3x USB-A, 1x USB-C, SD/microSD", "Max Power": "100W PD Pass-through", "Material": "Anodized Space Gray Aluminum"}', 4.60, 1, 1),
(5, 4, 'BOK-ARCH-PHP', 'Modern PHP & Software Architecture Guide', 'modern-php-software-architecture-guide', 'Master clean architecture, domain-driven design, transactional integrity, and containerized deployment patterns using modern PHP 8.x.', 44.95, 120, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&auto=format&fit=crop&q=80', '{"Pages": "480 Pages", "Format": "Hardcover / High-grade paper", "Language": "English", "ISBN": "978-0134494166"}', 5.00, 1, 1),
(6, 4, 'BOK-SYS-DESIGN', 'Designing High-Scale Distributed Systems', 'designing-high-scale-distributed-systems', 'The definitive handbook for engineering fault-tolerant microservices, event streams, caching strategies, and global replication.', 54.00, 85, 'https://images.unsplash.com/photo-1532012164546-f432f2e3777f?w=600&auto=format&fit=crop&q=80', '{"Pages": "620 Pages", "Format": "Paperback", "Edition": "3rd Revised", "ISBN": "978-1491950357"}', 4.90, 1, 1),
(7, 5, 'LGT-MON-BAR', 'Lumina Eye-Care Monitor Screen Bar', 'lumina-eye-care-monitor-screen-bar', 'Zero screen-glare asymmetric optical design with auto-dimming ambient light sensor, touch controls, and adjustable color temperature (2700K - 6500K).', 59.99, 60, 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=600&auto=format&fit=crop&q=80', '{"Color Temp": "2700K - 6500K", "Mounting": "Counterweight clamp (1-3cm monitor thickness)", "Power": "5V 1A USB-A", "CRI": ">95"}', 4.75, 1, 1),
(8, 5, 'DSK-PAD-LEATH', 'Artisan Vegan Leather Desk Mat (90x40cm)', 'artisan-vegan-leather-desk-mat-90x40cm', 'Waterproof, dual-sided smooth texture desk protector with anti-slip suede backing and reinforced stitched borders.', 29.99, 90, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=600&auto=format&fit=crop&q=80', '{"Dimensions": "900 x 400 x 2.2 mm", "Surface": "Splash-proof PU Vegan Leather", "Edge": "Precision perimeter stitching"}', 4.50, 0, 1),
(9, 3, 'SPK-STUDIO-BT', 'AuraBooks Hi-Res Bluetooth Bookshelf Monitors', 'aurabooks-hi-res-bluetooth-bookshelf-monitors', 'Active nearfield monitors with 4-inch Kevlar woofers, 1-inch silk dome tweeters, optical/coaxial inputs, and Sub-Out for home recording & entertainment.', 159.00, 25, 'https://images.unsplash.com/photo-1545454675-3531b543be5d?w=600&auto=format&fit=crop&q=80', '{"Total RMS Power": "42 Watts RMS", "Inputs": "Optical, Coaxial, Dual RCA, Bluetooth 5.1", "Frequency Response": "52Hz - 20kHz"}', 4.85, 1, 1),
(10, 1, 'WAT-PRO-GPS', 'Titan Smartwatch Pro with Multi-Band GPS', 'titan-smartwatch-pro-with-multi-band-gps', 'Rugged titanium bezel, 1.4-inch AMOLED sapphire crystal display, 10-day battery life, and complete biometric heart-rate & SpO2 tracking.', 249.99, 35, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80', '{"Case Material": "Grade 5 Titanium", "Water Resistance": "10 ATM (100 meters)", "Battery": "Up to 14 Days", "Sensors": "Multi-band GPS, PPG HR, Pulse Ox"}', 4.80, 0, 1);

-- 5. Insert Verified Customer Reviews
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `comment`) VALUES
(1, 1, 1, 5, 'Superb Sound Clarity & Quiet Cabin ANC', 'The noise cancellation completely blocks out train and cafe chatter. High frequencies are crisp and the bass is tight without overpowering vocals.'),
(2, 1, 3, 5, 'Comfortable for 8-hour shifts', 'Extremely plush ear cups, zero clamp fatigue even after an entire working day in logistics delivery.'),
(3, 2, 1, 5, 'Tactile bliss for programming', 'The linear switches are butter smooth and the PBT keycaps feel fantastic. Great build quality with aluminum plate.'),
(4, 2, 3, 4, 'Great keyboard, very solid', 'Heavy chassis that does not slide on the desk. Lighting is subtle and fully customizable.'),
(5, 3, 1, 5, 'Flawless tracking sensor', 'Zero latency on the 2.4GHz connection and comfortable grip for full workday typing and coding.'),
(6, 4, 1, 5, 'Fixed all my laptop port limitations', 'Handles my dual monitors and ethernet connection simultaneously without running hot.'),
(7, 5, 1, 5, 'Essential reading for any software engineer', 'Clear architectural breakdown of DDD, transactions, and service containers. Highly recommended.'),
(8, 6, 1, 5, 'A masterpiece on system scalability', 'Every chapter provides practical tradeoffs for real-world distributed architectures.'),
(9, 7, 1, 5, 'Saves eye strain during late night coding', 'No glare on my curved display and the auto-dimmer is surprisingly accurate.'),
(10, 9, 1, 5, 'Rich studio sound on a desk setup', 'Warm acoustic soundstage and great connectivity options with my laptop.');

-- 6. Insert Pre-seeded Demo Orders & Cargo Shipments (Illustrating Different States)

-- Order 1: Delivered Order (Completed Timeline)
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `address_snapshot_json`, `subtotal`, `shipping_cost`, `total_amount`, `payment_status`, `stripe_session_id`, `stripe_payment_intent`, `order_status`, `created_at`) VALUES
(1, 'ORD-2026-10001', 1, '{"recipient_name":"Alice Freeman","phone":"+1 206-555-0142","address_line":"412 Pike Street, Apt 8B","city":"Seattle","postal_code":"98101","country":"United States"}', 329.49, 0.00, 329.49, 'paid', 'cs_test_mock_delivered_session_01', 'pi_test_mock_intent_01', 'delivered', DATE_SUB(NOW(), INTERVAL 4 DAY));

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `sku`, `unit_price`, `quantity`, `line_total`) VALUES
(1, 1, 'SonicPro Wireless ANC Headphones', 'AUD-NC-700', 199.99, 1, 199.99),
(1, 2, 'Apex Tenkeyless Mechanical Keyboard', 'KEY-MECH-TKL', 129.50, 1, 129.50);

INSERT INTO `shipments` (`id`, `order_id`, `tracking_number`, `carrier_name`, `assigned_courier_id`, `current_status`, `estimated_delivery`, `updated_at`) VALUES
(1, 1, 'TRK-2026-88001', 'Amazon Logistics Express', 3, 'delivered', DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO `shipment_events` (`shipment_id`, `status_step`, `location`, `notes`, `updated_by_user_id`, `event_time`) VALUES
(1, 'delivered', 'Front Porch, Seattle WA 98101', 'Package securely delivered to resident front door.', 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'out_for_delivery', 'Amazon Delivery Station #DSE4, Seattle WA', 'Courier loaded shipment onto delivery vehicle.', 3, DATE_SUB(NOW(), INTERVAL 27 HOUR)),
(1, 'in_transit', 'Regional Sorting Center, Kent WA', 'Package processed through sorting facility conveyor.', 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'picked_up', 'Amazon Fulfillment Warehouse BFI4, Sumner WA', 'Carrier scanned and received package from fulfillment bays.', 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'awaiting_pickup', 'Amazon Fulfillment Warehouse BFI4, Sumner WA', 'Shipping label generated and package packed.', 2, DATE_SUB(NOW(), INTERVAL 4 DAY));


-- Order 2: In-Transit Order (Active in Logistics Hub)
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `address_snapshot_json`, `subtotal`, `shipping_cost`, `total_amount`, `payment_status`, `stripe_session_id`, `stripe_payment_intent`, `order_status`, `created_at`) VALUES
(2, 'ORD-2026-10002', 1, '{"recipient_name":"Alice Freeman","phone":"+1 206-555-0142","address_line":"412 Pike Street, Apt 8B","city":"Seattle","postal_code":"98101","country":"United States"}', 148.99, 0.00, 148.99, 'paid', 'cs_test_mock_transit_session_02', 'pi_test_mock_intent_02', 'shipped', DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `sku`, `unit_price`, `quantity`, `line_total`) VALUES
(2, 7, 'Lumina Eye-Care Monitor Screen Bar', 'LGT-MON-BAR', 59.99, 1, 59.99),
(2, 4, 'OmniPort 10-in-1 USB-C Thunderbolt Hub', 'GAD-HUB-10IN1', 89.00, 1, 89.00);

INSERT INTO `shipments` (`id`, `order_id`, `tracking_number`, `carrier_name`, `assigned_courier_id`, `current_status`, `estimated_delivery`, `updated_at`) VALUES
(2, 2, 'TRK-2026-88002', 'Amazon Logistics Express', 3, 'in_transit', DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 6 HOUR));

INSERT INTO `shipment_events` (`shipment_id`, `status_step`, `location`, `notes`, `updated_by_user_id`, `event_time`) VALUES
(2, 'in_transit', 'Regional Sorting Center, Kent WA Hub', 'Package departed sorting hub; transferred to local delivery depot.', 3, DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(2, 'picked_up', 'Amazon Fulfillment Center #12, Seattle WA', 'Courier officer accepted custody of packaged parcel.', 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'awaiting_pickup', 'Amazon Fulfillment Center #12, Seattle WA', 'Packed, weighed, and awaiting courier dispatch.', 2, DATE_SUB(NOW(), INTERVAL 2 DAY));


-- Order 3: Packed & Awaiting Courier Pickup
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `address_snapshot_json`, `subtotal`, `shipping_cost`, `total_amount`, `payment_status`, `stripe_session_id`, `stripe_payment_intent`, `order_status`, `created_at`) VALUES
(3, 'ORD-2026-10003', 1, '{"recipient_name":"Alice Freeman","phone":"+1 206-555-0142","address_line":"412 Pike Street, Apt 8B","city":"Seattle","postal_code":"98101","country":"United States"}', 44.95, 4.99, 49.94, 'paid', 'cs_test_mock_packed_session_03', 'pi_test_mock_intent_03', 'packed', DATE_SUB(NOW(), INTERVAL 3 HOUR));

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `sku`, `unit_price`, `quantity`, `line_total`) VALUES
(3, 5, 'Modern PHP & Software Architecture Guide', 'BOK-ARCH-PHP', 44.95, 1, 44.95);

INSERT INTO `shipments` (`id`, `order_id`, `tracking_number`, `carrier_name`, `assigned_courier_id`, `current_status`, `estimated_delivery`, `updated_at`) VALUES
(3, 3, 'TRK-2026-88003', 'Amazon Logistics Express', 3, 'awaiting_pickup', DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), NOW());

INSERT INTO `shipment_events` (`shipment_id`, `status_step`, `location`, `notes`, `updated_by_user_id`, `event_time`) VALUES
(3, 'awaiting_pickup', 'Amazon Fulfillment Center #12, Seattle WA', 'Order packed and assigned to courier route.', 2, DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Pre-seed an item into Alice's Cart and Wishlist
INSERT INTO `cart_items` (`user_id`, `product_id`, `quantity`) VALUES
(1, 3, 1);

INSERT INTO `wishlist_items` (`user_id`, `product_id`) VALUES
(1, 10);


-- 7. Extended Catalog (demonstrates pagination: 30 products => 3 pages of 12)
INSERT INTO `products` (`category_id`, `sku`, `name`, `slug`, `description`, `price`, `stock`, `image_url`, `specs_json`, `rating_avg`, `review_count`, `is_active`) VALUES
(1, 'GAD-PBK-20K', 'Voltix 20,000mAh Fast-Charge Power Bank', 'voltix-20-000mah-fast-charge-power-bank', '20,000mAh, 65W USB-C PD, dual output. Ships with Amazon Logistics Express.', 49.0, 80, 'https://picsum.photos/seed/gad-pbk-20k/600/450', '{"SKU": "GAD-PBK-20K"}', 4.1, 3, 1),
(1, 'GAD-CAM-ACT', 'Trailblazer 4K Action Camera', 'trailblazer-4k-action-camera', '4K/60fps, waterproof to 10m, electronic stabilisation. Ships with Amazon Logistics Express.', 139.0, 22, 'https://picsum.photos/seed/gad-cam-act/600/450', '{"SKU": "GAD-CAM-ACT"}', 4.8, 4, 1),
(1, 'GAD-TRK-TAG', 'Locator Bluetooth Item Tracker (4-pack)', 'locator-bluetooth-item-tracker-4-pack', 'Precision finding, 2-year battery. Ships with Amazon Logistics Express.', 69.0, 150, 'https://picsum.photos/seed/gad-trk-tag/600/450', '{"SKU": "GAD-TRK-TAG"}', 4.6, 5, 1),
(1, 'GAD-TAB-10', 'Slate 10.5" Android Tablet', 'slate-10-5-android-tablet', '10.5" 2K display, 8GB RAM, 128GB storage. Ships with Amazon Logistics Express.', 229.0, 18, 'https://picsum.photos/seed/gad-tab-10/600/450', '{"SKU": "GAD-TAB-10"}', 4.4, 6, 1),
(2, 'COM-LAP-14', 'Zenith 14" Developer Laptop', 'zenith-14-developer-laptop', 'Ryzen 7, 32GB RAM, 1TB NVMe, 14" 2.8K OLED. Ships with Amazon Logistics Express.', 1099.0, 9, 'https://picsum.photos/seed/com-lap-14/600/450', '{"SKU": "COM-LAP-14"}', 4.2, 7, 1),
(2, 'COM-MON-27', 'ClearView 27" 4K IPS Monitor', 'clearview-27-4k-ips-monitor', '27" 4K, 99% sRGB, USB-C 65W. Ships with Amazon Logistics Express.', 329.0, 14, 'https://picsum.photos/seed/com-mon-27/600/450', '{"SKU": "COM-MON-27"}', 4.9, 8, 1),
(2, 'COM-SSD-1T', 'Rapid 1TB Portable NVMe SSD', 'rapid-1tb-portable-nvme-ssd', '1,050 MB/s, shock resistant, USB 3.2 Gen 2. Ships with Amazon Logistics Express.', 99.0, 60, 'https://picsum.photos/seed/com-ssd-1t/600/450', '{"SKU": "COM-SSD-1T"}', 4.7, 9, 1),
(2, 'COM-WEB-HD', 'StreamLine 1080p Webcam', 'streamline-1080p-webcam', '1080p/60fps, autofocus, dual noise-reducing mics. Ships with Amazon Logistics Express.', 59.0, 40, 'https://picsum.photos/seed/com-web-hd/600/450', '{"SKU": "COM-WEB-HD"}', 4.5, 3, 1),
(3, 'AUD-EAR-TWS', 'Pebble True Wireless Earbuds', 'pebble-true-wireless-earbuds', 'IPX5, 28h with case, low-latency gaming mode. Ships with Amazon Logistics Express.', 79.0, 70, 'https://picsum.photos/seed/aud-ear-tws/600/450', '{"SKU": "AUD-EAR-TWS"}', 4.3, 4, 1),
(3, 'AUD-MIC-USB', 'Podcaster USB Condenser Microphone', 'podcaster-usb-condenser-microphone', 'Cardioid pattern, zero-latency monitoring, mute button. Ships with Amazon Logistics Express.', 89.0, 33, 'https://picsum.photos/seed/aud-mic-usb/600/450', '{"SKU": "AUD-MIC-USB"}', 4.1, 5, 1),
(3, 'AUD-SPK-PRT', 'Boulder Portable Waterproof Speaker', 'boulder-portable-waterproof-speaker', 'IP67, 24h playtime, stereo pairing. Ships with Amazon Logistics Express.', 59.0, 95, 'https://picsum.photos/seed/aud-spk-prt/600/450', '{"SKU": "AUD-SPK-PRT"}', 4.8, 6, 1),
(4, 'BOK-ALGO-CLR', 'Algorithms Unlocked: A Student Companion', 'algorithms-unlocked-a-student-companion', 'Sorting, graphs, dynamic programming explained visually. Ships with Amazon Logistics Express.', 39.9, 140, 'https://picsum.photos/seed/bok-algo-clr/600/450', '{"SKU": "BOK-ALGO-CLR"}', 4.6, 7, 1),
(4, 'BOK-DB-INT', 'Database Internals & Transactions', 'database-internals-transactions', 'B-trees, MVCC, WAL and distributed consensus. Ships with Amazon Logistics Express.', 59.0, 66, 'https://picsum.photos/seed/bok-db-int/600/450', '{"SKU": "BOK-DB-INT"}', 4.4, 8, 1),
(4, 'BOK-NET-TCP', 'Computer Networks: Top-Down Edition', 'computer-networks-top-down-edition', 'Protocols from the application layer down. Ships with Amazon Logistics Express.', 64.0, 48, 'https://picsum.photos/seed/bok-net-tcp/600/450', '{"SKU": "BOK-NET-TCP"}', 4.2, 9, 1),
(4, 'BOK-DEVOPS', 'The DevOps Handbook for Small Teams', 'the-devops-handbook-for-small-teams', 'CI/CD, containers and monitoring on a budget. Ships with Amazon Logistics Express.', 34.9, 100, 'https://picsum.photos/seed/bok-devops/600/450', '{"SKU": "BOK-DEVOPS"}', 4.9, 3, 1),
(5, 'HOM-DSK-STD', 'Ergo Electric Standing Desk Frame', 'ergo-electric-standing-desk-frame', 'Dual motor, memory presets, 120kg capacity. Ships with Amazon Logistics Express.', 299.0, 12, 'https://picsum.photos/seed/hom-dsk-std/600/450', '{"SKU": "HOM-DSK-STD"}', 4.7, 4, 1),
(5, 'HOM-CHR-ERG', 'Posture Mesh Ergonomic Office Chair', 'posture-mesh-ergonomic-office-chair', 'Lumbar support, 4D armrests, breathable mesh. Ships with Amazon Logistics Express.', 189.0, 20, 'https://picsum.photos/seed/hom-chr-erg/600/450', '{"SKU": "HOM-CHR-ERG"}', 4.5, 5, 1),
(5, 'HOM-PLG-SMT', 'Wattwise Smart Plug (4-pack)', 'wattwise-smart-plug-4-pack', 'Energy monitoring, voice assistant compatible. Ships with Amazon Logistics Express.', 27.0, 200, 'https://picsum.photos/seed/hom-plg-smt/600/450', '{"SKU": "HOM-PLG-SMT"}', 4.3, 6, 1),
(5, 'HOM-LMP-LED', 'Aurora Adjustable LED Desk Lamp', 'aurora-adjustable-led-desk-lamp', 'Wireless charging base, 5 colour modes. Ships with Amazon Logistics Express.', 34.0, 3, 'https://picsum.photos/seed/hom-lmp-led/600/450', '{"SKU": "HOM-LMP-LED"}', 4.1, 7, 1),
(1, 'GAD-VRG-LTE', 'Horizon Limited Edition Smart Ring', 'horizon-limited-edition-smart-ring', 'Sleep and recovery tracking, 7-day battery. Ships with Amazon Logistics Express.', 299.0, 0, 'https://picsum.photos/seed/gad-vrg-lte/600/450', '{"SKU": "GAD-VRG-LTE"}', 4.8, 8, 1);

-- 8. Exceptional Logistics Demo States

-- Order 4: first delivery attempt failed (recipient not at address)
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `address_snapshot_json`, `subtotal`, `shipping_cost`, `total_amount`, `payment_status`, `stripe_session_id`, `stripe_payment_intent`, `order_status`, `created_at`) VALUES
(4, 'ORD-2026-10004', 1, '{"recipient_name":"Alice Freeman","phone":"+1 206-555-0142","address_line":"108th Ave NE, Suite 500","city":"Bellevue","postal_code":"98004","country":"United States"}', 79.99, 0.00, 79.99, 'paid', 'cs_test_mock_failed_session_04', 'pi_test_mock_intent_04', 'shipped', DATE_SUB(NOW(), INTERVAL 3 DAY));
INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `sku`, `unit_price`, `quantity`, `line_total`) VALUES
(4, 3, 'Vanguard Wireless Precision Mouse', 'MOU-PREC-WL', 79.99, 1, 79.99);
INSERT INTO `shipments` (`id`, `order_id`, `tracking_number`, `carrier_name`, `assigned_courier_id`, `current_status`, `estimated_delivery`, `delivery_attempts`, `updated_at`) VALUES
(4, 4, 'TRK-2026-88004', 'Amazon Logistics Express', 3, 'delivery_failed', DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), 1, DATE_SUB(NOW(), INTERVAL 2 HOUR));
INSERT INTO `shipment_events` (`shipment_id`, `status_step`, `location`, `reason_code`, `notes`, `updated_by_user_id`, `event_time`) VALUES
(4, 'delivery_failed', '108th Ave NE, Bellevue WA 98004', 'RECIPIENT_ABSENT', 'Delivery attempt 1 of 3: nobody answered the door. Notice card left at reception.', 3, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(4, 'out_for_delivery', 'Amazon Delivery Station #DSE4, Seattle WA', NULL, 'Courier loaded shipment onto delivery vehicle.', 3, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(4, 'in_transit', 'Regional Sorting Center, Kent WA', NULL, 'Package processed through sorting facility.', 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'picked_up', 'Amazon Fulfillment Warehouse BFI4, Sumner WA', NULL, 'Carrier received package.', 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'awaiting_pickup', 'Amazon Fulfillment Center #12, Seattle WA', NULL, 'Shipping label created and package packed.', 2, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Order 5: customer cancelled before shipping, stock restored and payment refunded
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `address_snapshot_json`, `subtotal`, `shipping_cost`, `total_amount`, `payment_status`, `stripe_session_id`, `stripe_payment_intent`, `order_status`, `cancel_reason`, `cancelled_at`, `refund_id`, `created_at`) VALUES
(5, 'ORD-2026-10005', 1, '{"recipient_name":"Alice Freeman","phone":"+1 206-555-0142","address_line":"412 Pike Street, Apt 8B","city":"Seattle","postal_code":"98101","country":"United States"}', 29.99, 4.99, 34.98, 'refunded', 'cs_test_mock_cancel_session_05', 'pi_test_mock_intent_05', 'cancelled', 'Cancelled by customer', DATE_SUB(NOW(), INTERVAL 1 DAY), 're_test_mock_05', DATE_SUB(NOW(), INTERVAL 30 HOUR));
INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `sku`, `unit_price`, `quantity`, `line_total`) VALUES
(5, 8, 'Artisan Vegan Leather Desk Mat (90x40cm)', 'DSK-PAD-LEATH', 29.99, 1, 29.99);

-- Sample webhook audit rows for the admin console
INSERT INTO `stripe_events` (`event_id`, `event_type`, `order_id`, `source`, `outcome`, `payload`, `created_at`) VALUES
('evt_seed_0001', 'checkout.session.completed', 3, 'stripe', 'order paid', '{}', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('evt_seed_0002', 'charge.refunded', 5, 'stripe', 'order refunded', '{}', DATE_SUB(NOW(), INTERVAL 1 DAY));
