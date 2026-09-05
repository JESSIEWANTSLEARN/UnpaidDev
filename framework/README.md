# WalangBrownout

WalangBrownout is a full-stack inventory, purchasing, warehouse, sales, and customer-ordering system for a single-warehouse appliance distributor. It replaces spreadsheet/manual monitoring with centralized stock, purchasing, sales, customer, audit, and security workflows.

## Stack

- Laravel 12 / PHP 8.2+
- React 19 + Vite
- MySQL
- Railway database
- Render deployment
- Brevo OTP/email

## Local setup

1. Clone the repository.
2. Run `composer install`.
3. Run `npm install`.
4. Copy `.env.example` to `.env`.
5. Fill in your own MySQL and Brevo development credentials.
6. Run `php artisan key:generate`.
7. Run `php artisan migrate`.
8. Run `php artisan serve`.
9. Run `npm run dev` in another terminal.

Do not commit the real `.env`.

## Main roles

`super_admin`, `Operations_Manager`, `Purchasing_Manager`, `Purchasing_Staff`, `Warehouse_Admin`, `Inventory_Controller`, `Sales_Manager`, `Sales_Staff`, `User_Admin`, `System_User`.

## Source organization

Role/feature-specific code belongs to its domain folder. Reusable code belongs under a clearly named shared folder. See `docs/ARCHITECTURE.md`.

## Future repository split

- `walangbrownout-api` - Laravel backend (planned from this repository)
- `walangbrownout-web` - React frontend (planned new repository)

## Documentation

- `docs/ARCHITECTURE.md`
- `docs/ROLES.md`
- `docs/DATABASE.md`
- `docs/DEPLOYMENT.md`
- `docs/PHASE4_REFACTOR.md`
