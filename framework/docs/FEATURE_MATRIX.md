# WalangBrownout Role and Feature Matrix

| Role | Main Responsibility | Main Features |
|---|---|---|
| Super Admin | System-wide administration | Users, roles, settings, audits, sessions, products, suppliers, inventory, reports, System Health |
| Operations Manager | Monitor overall operations | Purchasing, warehouse, inventory, sales, operational reports |
| Purchasing Manager | Manage procurement | Purchase order approval, suppliers, purchasing monitoring |
| Purchasing Staff | Prepare procurement activities | Purchase orders, supplier information, incoming stock coordination |
| Warehouse Admin | Manage receiving and releasing | Receiving, releasing, discrepancies, warehouse operations |
| Inventory Controller | Maintain inventory accuracy | Stock, batches, FEFO, transactions, adjustments, alerts |
| Sales Manager | Manage sales operations | Customer orders, sales staff, fulfillment, reports |
| Sales Staff | Process customer orders | Order processing, status updates, fulfillment, customer concerns |
| User Admin | Manage customer accounts | Customer account administration and status management |
| System User | Customer | Store, cart, checkout, orders, profile, notifications, reviews |

## Shared System Features

- Authentication
- OTP verification
- Password reset
- Session security
- Notifications
- Audit logging
- Role-based authorization

## Architecture Rule

WalangBrownout currently supports:

**One Company → One Warehouse → One Shared Inventory Pool**

Multi-warehouse functionality is outside the current project scope.
