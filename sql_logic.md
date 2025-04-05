--- categories
id uuid
name string
description string
created_at datetime
updated_at datetime

--- books
id uuid
title string
author string
publisher string
publication_date date
price decimal
stock_quantity int
description string
short_description string
image_url string
created_at datetime
updated_at datetime
\*category_id uuid

--- users
id uuid
full_name string
email string
phone string
password string
created_at datetime
updated_at datetime

--- orders
id uuid
\*user_id uuid
total_price decimal
status Enum(Pending, Processing, Shipped, Delivered, Cancelled)
shipping_address string
created_at datetime
updated_at datetime

--- order_details
id uuid
*order_id uuid
*book_id uuid
quantity int
price decimal
created_at datetime
updated_at datetime

--- reviews
id uuid
*user_id uuid
*book_id uuid
rating int
comment string
created_at datetime
updated_at datetime

--- payments
id uuid
\*order_id uuid
payment_method Enum(VNPay, Momo, COD, Paypal, Stripe)
status Enum(Pending, Paid, Failed, Refunded)
created_at datetime
updated_at datetime

-- Bảng categories
CREATE TABLE categories (
id CHAR(36) PRIMARY KEY,
name VARCHAR(255) NOT NULL,
description TEXT,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng books
CREATE TABLE books (
id CHAR(36) PRIMARY KEY,
title VARCHAR(255) NOT NULL,
author VARCHAR(255),
publisher VARCHAR(255),
publication_date DATE,
price DECIMAL(10,2) NOT NULL,
stock_quantity INT NOT NULL DEFAULT 0,
description TEXT,
short_description TEXT,
image_url VARCHAR(2083),
category_id CHAR(36),
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Bảng users
CREATE TABLE users (
id CHAR(36) PRIMARY KEY,
full_name VARCHAR(255) NOT NULL,
email VARCHAR(255) NOT NULL UNIQUE,
phone VARCHAR(20),
refresh_token VARCHAR(255),
password VARCHAR(255) NOT NULL,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng orders
CREATE TABLE orders (
id CHAR(36) PRIMARY KEY,
user_id CHAR(36),
total_price DECIMAL(10,2) NOT NULL,
status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
shipping_address TEXT,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Bảng order_details
CREATE TABLE order_details (
id CHAR(36) PRIMARY KEY,
order_id CHAR(36),
book_id CHAR(36),
quantity INT NOT NULL,
price DECIMAL(10,2) NOT NULL,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (order_id) REFERENCES orders(id),
FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Bảng reviews
CREATE TABLE reviews (
id CHAR(36) PRIMARY KEY,
user_id CHAR(36),
book_id CHAR(36),
rating INT CHECK (rating >= 1 AND rating <= 5),
comment TEXT,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id),
FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Bảng payments
CREATE TABLE payments (
id CHAR(36) PRIMARY KEY,
order_id CHAR(36),
payment_method ENUM('VNPay', 'Momo', 'COD', 'Paypal', 'Stripe'),
status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (order_id) REFERENCES orders(id)
);
