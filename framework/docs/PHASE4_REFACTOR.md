# Phase 4 Refactor Status

## Completed

### Project and folder cleanup

- Removed development-only fix scripts, audit reports, SQL helpers, and backup trees from the runtime project root.
- Organized Laravel controllers by Auth, Customer, Shared/Staff, Sales, User Admin, Super Admin, Reviews, and Website responsibilities.
- Organized Laravel services by Auth, Shared, Staff, Sales, Super Admin, and Website responsibilities.
- Organized React pages into public, auth, customer, operations, purchasing, warehouse, inventory, sales, user-admin, and super-admin areas.
- Moved reusable React UI into shared components and kept role/feature-specific components with their owning domain.
- Standardized the Super Admin frontend folder name to `super-admin` for pages, components, hooks, config, services, utilities, and CSS.
- Organized CSS into auth, public, customer, shared, sales, user-admin, and super-admin folders.
- Removed confirmed stale/dead source from the uploaded snapshot.

### Deep decomposition completed

- Extracted operational dashboard aggregation from `RoleDashboardController` into `App\\Services\\Staff\\OperationalDashboardService`.
- Extracted sales dashboard aggregation from `SalesRoleController` into `App\\Services\\Sales\\SalesDashboardService`.
- Split role-dashboard presentation tables, metrics, alerts, and section primitives into `RoleDashboardPrimitives.jsx`.
- Split customer-only UI primitives and storefront formatting/normalization helpers out of `SystemUser.jsx`.
- Split the customer checkout dialog and shopping-cart drawer into dedicated customer components.
- Split customer CSS for preview mode, checkout/cart UX, cart feedback, notifications, and order filters out of the main customer stylesheet.
- Split Super Admin website-content FAQ and team-member editors into dedicated components.

### Handoff and database foundation

- Replaced the default Laravel README with WalangBrownout setup documentation.
- Corrected `.env.example` for WalangBrownout/MySQL and documented required non-secret settings.
- Added architecture, roles, database, deployment, and refactor documentation.
- Added a source-controlled baseline business schema and safe baseline migration for fresh developer setup.
- The baseline defines the pre-Phase-3 WBO business tables; later migrations add Password History and Product Reviews.

## Remaining behavior-preserving decomposition priorities

The files below are still sizeable, but they should only be split when a clear responsibility boundary exists. File size alone is not a reason to create more files.

Backend priorities:

1. `app/Http/Controllers/SuperAdmin/SuperAdminController.php` - user/session/account administration can move into focused services.
2. `app/Http/Controllers/SuperAdmin/SuperAdminDashboardController.php` - dashboard aggregation can move into a read service.
3. `app/Http/Controllers/UserAdmin/UserAdminController.php` - account/session administration can be separated from HTTP validation/authorization.
4. `app/Http/Controllers/Customer/SystemUserController.php` - order, profile, notification, and media concerns can be separated carefully.
5. `app/Services/SuperAdmin/SuperAdminReportService.php` - report builders may be split by report domain only if maintenance requires it.

Frontend priorities:

1. `resources/js/pages/customer/SystemUser.jsx` - dashboard/shop/orders/account views can become customer-owned tab components while state remains centralized.
2. `resources/js/components/user-admin/UserAdminDashboardContent.jsx` - split by dashboard module when stable boundaries are confirmed.
3. `resources/js/pages/super-admin/WebsiteContentAdmin.jsx` - API/state orchestration can later be separated from page composition.
4. `resources/js/components/sales/SalesDashboardContent.jsx` - split tables/actions only where reuse is real.

CSS priorities:

1. `resources/css/customer/system-user.css` - remaining base/store/account layout may be split after visual regression testing.
2. `resources/css/public/landing-page.css` - split only along stable landing-page sections.
3. `resources/css/super-admin/super-admin.css` - keep shared Super Admin shell rules here; feature rules belong in the existing imported modules.
4. Large auth stylesheets can be reduced after login/signup/OTP visual regression tests.

## Database coverage notes

The canonical baseline defines 23 `WBO_*` business tables. Phase 3 migrations add `WBO_PasswordHistory` and `WBO_ProductReviews`.

`WBO_DataImportErrors` and `WBO_Messages` are part of the approved baseline schema but had no direct literal references in the uploaded application source during the Phase 4 audit. Do not remove them solely on that basis; confirm whether those business features remain in scope.

Returns and refunds remain a planned feature, not a completed migration. If implemented, the current design direction is `WBO_Returns` plus `WBO_ReturnDetails` only when the workflow is finalized.

## Validation gate

After each structural batch run:

```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list
php artisan migrate:status
npm run build
```

Then regression-test login/OTP, customer checkout/orders, every staff dashboard, Super Admin, User Admin, sessions, reviews, system health, and website-content management before committing the batch.
