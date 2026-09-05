# WalangBrownout Roles

The system currently supports:

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

WalangBrownout is a **single-warehouse** system. Do not introduce multi-warehouse architecture unless the business requirements explicitly change.

Authorization must be enforced by Laravel. React role pages are presentation only and must never be treated as the security boundary.
