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
