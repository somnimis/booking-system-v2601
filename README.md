# Booking System (Facilities & Equipment)

A web-based booking and requisition management system for facilities and equipment built with Laravel using an MVC architecture. It replaces manual reservation workflows with a centralized digital system for users and administrators.

## Purpose
Designed to modernize institutional booking systems by automating reservations, approvals, notifications, inventory tracking, and reporting into a single unified platform.

## Features

### Booking Catalog
- Browse available facilities and equipment
- View real-time availability before submitting requests

### Availability Calendar
- Interactive calendar for monitoring ongoing events
- Displays booked schedules and available time slots
- Helps prevent scheduling conflicts and improves planning visibility

### Digital Requisition System
- Fully digitalized requisition forms for booking facilities and equipment
- Structured submission workflow with validation and tracking
- Form status tracking for users (Pending, Approved, Rejected, etc.)

### Admin Dashboard
- Centralized reservation management
- Protected with Laravel Sanctum authentication
- Internal admin remarks system for collaboration on pending forms
- Fee management (add, waive, discounted fees)

### Inventory & Availability Tracking
- Real-time equipment tracking using equipment and equipment_item
- Automatic quantity computation based on active usage and reservations
- Prevents overbooking through validation logic
- Barcode scanning support for equipment check-in and check-out

### Approval Workflow System
- Multi-level signatory routing and approval process
- Automated status transitions based on approvals
- Supports structured institutional approval chains

### Notifications System
- Email notifications for users and administrators:
  - New booking requests
  - Status updates (approved/rejected)
  - Invoice generation
  - System alerts

### Document Automation
- Automated generation of use permits
- Automated invoice generation for approved bookings

### Feedback & Reporting
- User feedback for system and booking experience
- Export completed/archived transactions to Excel or CSV for auditing

## Tech Stack
- PHP (Laravel)
- MySQL
- Laravel Sanctum
- Cloudinary (media management and file uploads)
- Milo Barcode (barcode generation and scanning support)
- Mail system
- Excel/CSV export utilities

## Architecture
- Built using Laravel MVC
- Separation of concerns:
  - Models: data structure
  - Controllers: handle request flow and coordination
  - Services: contain core business logic and rules (keeps controllers thin and maintainable)
  - Routes/API: request handling layer
- RESTful API design for frontend integration

## Local Testing

To simulate and run the system locally, create a `.env` file based on the configuration below:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:your_app_key_here
APP_DEBUG=false
APP_URL=http://localhost:8000

FRONTEND_URL=http://localhost:8000

DEBUGBAR_ENABLED=false

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
CLOUDINARY_URL=your_cloudinary_url

CORS_ALLOWED_ORIGINS=http://localhost:8000,http://127.0.0.1:8000
CORS_ALLOWED_METHODS=POST,GET,OPTIONS,PUT,DELETE
CORS_ALLOWED_HEADERS=Content-Type,Accept,Authorization,X-Requested-With

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=false
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=localhost

SANCTUM_STATEFUL_DOMAINS=localhost:8000,localhost,127.0.0.1:8000,127.0.0.1

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=file
TRUSTED_PROXIES=*

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@example.com
MAIL_FROM_NAME="Booking System"

