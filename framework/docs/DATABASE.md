# WalangBrownout Database

WalangBrownout uses MySQL and a **single warehouse**. Business tables use the `WBO_` prefix.

## Reproducible setup

The repository now contains `database/schema/walangbrownout_baseline.sql` plus a baseline migration. The baseline creates the original 23 `WBO_*` business tables (and safely tolerates Laravel's existing `sessions` table). Later migrations add Phase 3 tables such as `WBO_PasswordHistory` and `WBO_ProductReviews`.

For a new development database:

```bash
php artisan migrate
```

The baseline uses `CREATE TABLE IF NOT EXISTS`, which also lets an existing WalangBrownout database register the migration without dropping/replacing current tables. Its rollback is intentionally non-destructive.

## Current WBO tables

Baseline business tables:

1. WBO_Users
2. WBO_Suppliers
3. WBO_Categories
4. WBO_Products
5. WBO_ProductImages
6. WBO_Batches
7. WBO_Orders
8. WBO_OrderDetails
9. WBO_PurchaseOrders
10. WBO_PurchaseOrderDetails
11. WBO_Transactions
12. WBO_Notifications
13. WBO_AuditLogs
14. WBO_SystemSettings
15. WBO_TrustedDevices
16. WBO_UserSessions
17. WBO_DataImports
18. WBO_DataImportErrors
19. WBO_Messages
20. WBO_UserProfilePhotos
21. WBO_NotificationState
22. WBO_FAQs
23. WBO_TeamMembers

Phase 3 additions:

24. WBO_PasswordHistory
25. WBO_ProductReviews

`WBO_DataImportErrors` and `WBO_Messages` are present in the schema but are not currently referenced by the uploaded application source. Keep them only while they remain part of the approved business scope.

## Design rule

Reuse an existing table when data belongs to that entity. Add a column when appropriate. Create a new table only for a genuine separate/repeating entity, workflow, or history.
