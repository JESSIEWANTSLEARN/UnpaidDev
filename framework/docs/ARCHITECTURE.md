# WalangBrownout Architecture

WalangBrownout is currently a combined Laravel + React application. Laravel owns server-side authentication, authorization, business rules, database access, email/OTP integration, and JSON endpoints. React renders the browser UI and calls Laravel through HTTP requests.

## Current request flow

`React -> Laravel route -> Controller -> Service/Database -> JSON -> React`

Controllers should stay focused on HTTP concerns: authorization, validation, choosing the workflow, and returning responses. Large read/query aggregation belongs in a service when it is reused or makes a controller hard to maintain.

## Backend folder ownership

- `app/Http/Controllers/Auth` - authentication and session endpoints.
- `app/Http/Controllers/Customer` - customer/store endpoints.
- `app/Http/Controllers/Staff` - endpoints shared by several operational staff roles.
- `app/Http/Controllers/Sales` - Sales Manager and Sales Staff workflows.
- `app/Http/Controllers/UserAdmin` - customer-account administration.
- `app/Http/Controllers/SuperAdmin` - Super Admin-only endpoints.
- `app/Http/Controllers/Reviews` - review workflows shared by customer and moderation routes.
- `app/Http/Controllers/Website` - public/admin website-content endpoints.
- `app/Services/Auth` - OTP, password history, sessions, and trusted devices.
- `app/Services/Shared` - cross-domain services such as notifications.
- `app/Services/Staff` - operational dashboard/read aggregation shared by staff roles.
- `app/Services/Sales` - sales dashboard/read aggregation.
- `app/Services/SuperAdmin` - Super Admin reporting and domain services.
- `app/Services/Website` - website-content business/data access.

## Frontend folder ownership

- `resources/js/components/shared` - UI used by multiple roles/features.
- `resources/js/components/customer` - customer-only UI such as checkout and cart.
- `resources/js/components/super-admin` - Super Admin-only UI.
- `resources/js/pages/<domain>` - route/page composition for that domain.
- `resources/js/services/<domain>` - browser API/storage helpers owned by that domain.
- `resources/js/utils/<domain>` - pure helpers/formatters owned by that domain.
- `resources/css/shared` - styles truly shared across roles.
- `resources/css/<domain>` - styles owned by one domain/role.

Rule: if one role/feature owns a file, keep it in that domain. If multiple domains genuinely use it, move it to `shared` rather than duplicating it.

## Future split

The existing repository is planned to become `walangbrownout-api` (Laravel). A new `walangbrownout-web` repository will contain React. The browser frontend will communicate with Laravel using HTTPS/JSON APIs.
