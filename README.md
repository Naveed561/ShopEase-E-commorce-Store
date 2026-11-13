# ShopEase – E-Commerce Store

A simple, modern PHP-based e-commerce website named **ShopEase**.  
It lets users browse products by category, search, manage a shopping cart, place orders (as guests or registered users), and receive order confirmation emails.

## Features

- **Product Catalog**
  - Categories: Fashion, Shoes, Electronics, Accessories, Home & Living, Sports & Outdoors
  - Search and filter by category
  - Product cards with images and prices
- **Shopping Cart**
  - Add / update / remove items
  - Session-based cart
  - Quantity limits and validation
- **User Accounts**
  - Registration & Login
  - Logout
  - View order history (`orders.php`)
  - Forgot Password + secure password reset (token-based, 1-hour expiry)
- **Checkout & Orders**
  - Guest checkout supported
  - Order confirmation with line items stored in the database
  - Order confirmation email sent to logged-in users (via PHPMailer)
- **Email System**
  - SMTP support (tested with Gmail + App Password)
  - Configurable via environment variables or `mail.local.php`
- **UI**
  - Bootstrap 5 + Bootstrap Icons
  - Responsive design
  - Hero carousel
  - Clean modern layout

## Tech Stack

| Layer       | Technology                  |
|-------------|-----------------------------|
| Backend     | PHP (procedural + mysqli)   |
| Database    | MySQL / MariaDB             |
| Frontend    | HTML, CSS, Bootstrap 5      |
| Email       | PHPMailer                   |
| Dependencies| Composer                    |

## Project Structure
ShopEase-E-commorce-Store/

├── index.php              # Homepage + product listing

├── productscard.php       # Product cards partial

├── cart.php               # Shopping cart

├── add_to_cart.php

├── update_cart.php

├── remove_from_cart.php

├── confirm_order.php      # Order placement + email

├── login.php / register.php / logout.php

├── forgot_password.php / reset_password.php

├── orders.php             # User order history

├── connection.php         # Database connection

├── mail_*.php             # Email configuration & helpers

├── order_email_template.php

├── catalog_seed.sql       # Sample categories & products

├── password_resets.sql

├── MAIL_SETUP.md          # Detailed email setup guide

├── composer.json

└── uploads/               # (for future image uploads)

## Requirements

- PHP 8.0+ (with OpenSSL extension)
- MySQL / MariaDB
- Composer
- Apache (or any web server that supports PHP)
- XAMPP / Laragon / WAMP recommended for local development

## Installation & Setup

### 1. Clone the repository

```bash
git clone https://github.com/Naveed561/ShopEase-E-commorce-Store.git
cd ShopEase-E-commorce-Store
