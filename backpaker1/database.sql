/CREATE DATABASE IF NOT EXISTS backpacker;
USE backpacker;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    mobile VARCHAR(15) UNIQUE,
    phone VARCHAR(20) UNIQUE,
    usertype ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    city VARCHAR(100) DEFAULT NULL,
    resisterdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say') NOT NULL DEFAULT 'Prefer not to say',
    profile_photo VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS user_id INT AFTER id,
    ADD COLUMN IF NOT EXISTS password VARCHAR(255) AFTER email,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER mobile,
    ADD COLUMN IF NOT EXISTS usertype ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER phone,
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER usertype,
    ADD COLUMN IF NOT EXISTS resisterdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER city,
    ADD COLUMN IF NOT EXISTS gender ENUM('Male', 'Female', 'Other', 'Prefer not to say') NOT NULL DEFAULT 'Prefer not to say' AFTER resisterdate;

UPDATE users
SET password = COALESCE(password, password_hash),
    usertype = COALESCE(usertype, role),
    role = COALESCE(role, usertype),
    user_id = COALESCE(user_id, id)
WHERE user_id IS NULL OR password IS NULL OR password = '';

CREATE UNIQUE INDEX IF NOT EXISTS idx_users_user_id ON users(user_id);

INSERT INTO users (name, email, mobile, phone, password, password_hash, usertype, role, city, gender, user_id)
VALUES (
    'Admin User',
    'admin@backpacker.com',
    '9999999999',
    '9999999999',
    '$2y$10$fnjUG38pKGDrYJZ0xkzv4ucFFJ0OKAeZ1H/cYcYspnNyLEO/4ZBVu',
    '$2y$10$fnjUG38pKGDrYJZ0xkzv4ucFFJ0OKAeZ1H/cYcYspnNyLEO/4ZBVu',
    'admin',
    'admin',
    'Delhi',
    'Male',
    NULL
) ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    password_hash = VALUES(password_hash),
    usertype = VALUES(usertype),
    role = VALUES(role),
    city = VALUES(city),
    gender = VALUES(gender),
    user_id = COALESCE(user_id, id);

UPDATE users SET user_id = id WHERE user_id IS NULL;

CREATE TABLE IF NOT EXISTS destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    duration VARCHAR(100),
    highlights VARCHAR(255),
    category VARCHAR(100) DEFAULT 'General',
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    location VARCHAR(255),
    description TEXT,
    overview TEXT,
    itinerary TEXT,
    inclusions TEXT,
    best_time VARCHAR(255),
    difficulty VARCHAR(100),
    hero_image VARCHAR(255)
);

ALTER TABLE destinations ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT 'General';
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS location VARCHAR(255);
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS description TEXT;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS overview TEXT;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS itinerary TEXT;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS inclusions TEXT;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS best_time VARCHAR(255);
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS difficulty VARCHAR(100);
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS hero_image VARCHAR(255);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(50) UNIQUE,
    user_id INT,
    destination_id INT,
    travel_date DATE NOT NULL,
    guests INT DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Paid', 'Failed', 'Cancelled') DEFAULT 'Pending',
    payment_method ENUM('Card', 'UPI', 'Net Banking', 'Wallet') NOT NULL DEFAULT 'Card',
    payment_reference VARCHAR(40) UNIQUE,
    payment_detail VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id)
);

ALTER TABLE bookings
    MODIFY payment_status ENUM('Pending', 'Paid', 'Failed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS payment_method ENUM('Card', 'UPI', 'Net Banking', 'Wallet') NOT NULL DEFAULT 'Card',
    ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(40) UNIQUE,
    ADD COLUMN IF NOT EXISTS payment_detail VARCHAR(100) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS trips (
    trip_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT NOT NULL,
    trip_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    travelers INT NOT NULL DEFAULT 1,
    trip_type VARCHAR(50) NOT NULL,
    status ENUM('Planned', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id)
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    payment_method ENUM('Card', 'UPI', 'Net Banking', 'Wallet'),
    transaction_id VARCHAR(100) UNIQUE,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    destination_id INT,
    rating INT CHECK(rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id)
);

-- Seed destination data (run once)
INSERT IGNORE INTO destinations (title, duration, highlights, price, image_url, hero_image, category) VALUES
  ('Leh Ladakh',   '8 Days / 7 Nights', 'Mountains, Pangong Lake, Monasteries', 24999.00, 'images/ladakh.jpg', 'images/ladakh.jpg', 'Mountain'),
  ('Spiti Valley', '7 Days / 6 Nights', 'Key Monastery, Chandratal Lake, Star Gazing', 19999.00, 'images/spiti.jpg', 'images/spiti.jpg', 'Mountain'),
  ('Goa',          '4 Days / 3 Nights', 'Beaches, Nightlife, Water Sports', 9999.00, 'images/goa.jpg', 'images/goa.jpg', 'Beach'),
  ('Girnar',       '2 Days / 1 Nights', 'Trekking, Jain & Hindu Temples', 2999.00, 'images/girnar.jpg', 'images/girnar.jpg', 'Mountain'),
  ('Swaziland',    '5 Days / 4 Nights', 'Wildlife Safari, Swazi Culture', 35999.00, 'images/swaziland.jpg', 'images/swaziland.jpg', 'Wildlife');

UPDATE destinations
SET image_url = CASE title
  WHEN 'Leh Ladakh' THEN 'images/ladakh.jpg'
  WHEN 'Spiti Valley' THEN 'images/spiti.jpg'
  WHEN 'Goa' THEN 'images/goa.jpg'
  WHEN 'Girnar' THEN 'images/girnar.jpg'
  WHEN 'Swaziland' THEN 'images/swaziland.jpg'
  WHEN 'Mussoorie' THEN 'images/Dehradun.jpg'
  WHEN 'kanyakumari temple' THEN 'images/photo-1513786704796-b35842f0dca6.jpg'
  ELSE image_url
END,
hero_image = CASE title
  WHEN 'Leh Ladakh' THEN 'images/ladakh.jpg'
  WHEN 'Spiti Valley' THEN 'images/spiti.jpg'
  WHEN 'Goa' THEN 'images/goa.jpg'
  WHEN 'Girnar' THEN 'images/girnar.jpg'
  WHEN 'Swaziland' THEN 'images/swaziland.jpg'
  WHEN 'Mussoorie' THEN 'images/Dehradun.jpg'
  WHEN 'kanyakumari temple' THEN 'images/photo-1513786704796-b35842f0dca6.jpg'
  ELSE hero_image
END
WHERE title IN ('Leh Ladakh', 'Spiti Valley', 'Goa', 'Girnar', 'Swaziland', 'Mussoorie', 'kanyakumari temple');
