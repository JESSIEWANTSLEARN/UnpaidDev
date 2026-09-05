# WalangBrownout

WalangBrownout is a full-stack inventory, purchasing, warehouse, sales, and customer-ordering system designed for a single-warehouse appliance distributor.

The project replaces spreadsheet and manual monitoring with centralized workflows for inventory, purchasing, sales, customer orders, audit logs, notifications, and account security.

## Business Problem

The business previously relied heavily on spreadsheet-based inventory monitoring.

This can lead to problems such as:

- Stockouts during high-demand periods
- Overstocking after seasonal demand
- Differences between recorded and actual inventory
- Online orders exceeding available stock
- Difficulty tracking purchasing and warehouse activities
- Limited accountability for important system actions

WalangBrownout centralizes these activities into one system.

## Project Scope

The current system follows this architecture:

**One Company -> One Warehouse -> One Shared Inventory Pool**

The project does not currently support multiple warehouses.

Returns and refunds are planned future functionality and are not currently implemented.

## Technology Stack

### Backend

- Laravel 12
- PHP 8.2+
- MySQL

### Frontend

- React 19
- Vite
- CSS

### Services and Deployment

- Railway MySQL
- Render
- Brevo for OTP and email delivery

## Main Features

### Customer

- Product catalog
- Product details
- Shopping cart
- Checkout
- Customer orders
- Order history
- Notifications
- Product reviews
- Profile management

### Purchasing

- Supplier management
- Purchase order preparation
- Purchase order approval
- Purchasing monitoring
- Incoming stock coordination

### Warehouse and Inventory

- Product batches
- Receiving
- Stock releasing
- Inventory monitoring
- Stock adjustments
- Inventory transactions
- Expiry monitoring
- Low-stock alerts
- FEFO-related inventory handling

### Sales

- Customer order processing
- Order status management
- Fulfillment monitoring
- Sales dashboards
- Sales reports

### Administration

- User management
- Role-based access
- Customer account administration
- Audit logs
- Session monitoring
- Product and category management
- Supplier management
- Product review administration
- Website content management
- System Health
- System settings
- Backup tools

## Security Features

The project includes:

- Authentication
- Signup email verification
- Login OTP
- Forgot password
- Password history
- Role-based authorization
- Trusted devices
- Session management
- Idle session enforcement
- Audit logging

## Main Roles

The system currently uses these roles:

- `super_admin`
- `Operations_Manager`
- `Purchasing_Manager`
- `Purchasing_Staff`
- `Warehouse_Admin`
- `Inventory_Controller`
- `Sales_Manager`
- `Sales_Staff`
- `User_Admin`
- `System_User`

See `docs/ROLES.md` and `docs/FEATURE_MATRIX.md` for role responsibilities.

## Business Flow

### Customer Order Flow

Customer Order
-> Sales Processing
-> Inventory Checking
-> Warehouse Fulfillment
-> Inventory Transaction Recording
-> Order Completion

### Purchasing Flow

Replenishment Need
-> Purchasing Staff
-> Purchase Order
-> Purchasing Manager Approval
-> Supplier
-> Warehouse Receiving
-> Inventory Update

## Architecture

React handles the user interface.

Laravel handles:

- Business rules
- Authentication
- Authorization
- Database access
- Server-side processing

React does not connect directly to MySQL.

Current architecture:

```text
React Frontend
      |
      v
Laravel Application
      |
      v
MySQL Database
```

The application is currently maintained in one repository while preparing for a possible future frontend/backend repository split.

See `docs/ARCHITECTURE.md` for more information.

## Database

The baseline WalangBrownout schema contains 23 WBO application tables.

Additional implemented tables include:

- `WBO_PasswordHistory`
- `WBO_ProductReviews`

The project also uses Laravel framework tables where required.

See `docs/DATABASE.md` for database documentation.

## Local Setup

1. Clone the repository.

2. Install PHP dependencies:

```bash
composer install
```

3. Install frontend dependencies:

```bash
npm install
```

4. Copy `.env.example` to `.env`.

5. Configure your own MySQL and Brevo development credentials.

6. Generate the Laravel application key:

```bash
php artisan key:generate
```

7. Run database migrations:

```bash
php artisan migrate
```

8. Start Laravel:

```bash
php artisan serve
```

9. In another terminal, start Vite:

```bash
npm run dev
```

Do not commit the real `.env` file.

## Production Build

To create the frontend production build:

```bash
npm run build
```

## Source Organization

Important application folders:

```text
app/
├── Http/Controllers/
└── Services/

resources/
├── css/
└── js/
    ├── components/
    ├── config/
    ├── hooks/
    ├── pages/
    ├── services/
    └── utils/

database/
├── migrations/
├── schema/
└── seeders/

docs/
```

Role-specific code belongs inside its corresponding domain folder.

Reusable functionality belongs inside clearly named shared folders.

## Documentation

- `docs/ARCHITECTURE.md` - Application structure and frontend/backend responsibilities
- `docs/DATABASE.md` - Database design and table information
- `docs/DEPLOYMENT.md` - Deployment notes
- `docs/ROLES.md` - User role responsibilities
- `docs/FEATURE_MATRIX.md` - Role and feature summary
- `docs/DEMO_GUIDE.md` - Recommended system demonstration and defense flow
- `docs/PHASE4_REFACTOR.md` - Phase 4 structural refactor record

## Recommended Defense Demonstration

For a project presentation or defense, demonstrate the system in this order:

1. Business problem
2. Public website
3. Customer ordering
4. Purchasing workflow
5. Warehouse receiving
6. Inventory monitoring
7. Sales processing
8. User administration
9. Operations monitoring
10. Super Admin
11. Audit logs and security

See `docs/DEMO_GUIDE.md` for the complete demonstration guide.

## Future Repository Split

A future deployment structure may separate the application into:

- `walangbrownout-api` - Laravel backend
- `walangbrownout-web` - React frontend

The React frontend will communicate with Laravel through HTTPS/JSON API requests.

Database credentials, Laravel `APP_KEY`, Brevo API keys, and other server secrets must remain backend-only.
