# WalangBrownout Demo and Defense Guide

## 1. Project Overview

WalangBrownout is a web-based inventory, purchasing, warehouse, sales, and customer ordering system designed for a single-company, single-warehouse operation.

The system uses one shared inventory pool and supports multiple user roles with different responsibilities.

## 2. Technology Stack

### Backend
- Laravel
- PHP
- MySQL

### Frontend
- React
- Vite
- CSS

### Deployment
- Render
- Railway MySQL

## 3. Main Business Flow

The general system flow is:

Customer Order
→ Sales Processing
→ Inventory Checking
→ Warehouse Fulfillment
→ Stock Transaction Recording
→ Order Completion

For purchasing:

Low Stock / Replenishment Need
→ Purchasing Staff
→ Purchase Order
→ Purchasing Manager Approval
→ Supplier
→ Warehouse Receiving
→ Inventory Update

## 4. Recommended Demo Order

### Step 1 — Public Website
Show:
- Landing page
- FAQ
- Product information
- Login and registration

Explain that visitors can access public information before creating an account.

### Step 2 — System User / Customer
Show:
- Product catalog
- Product details
- Cart
- Checkout
- Order history
- Notifications
- Product reviews
- Profile

Explain that System_User represents the customer role.

### Step 3 — Purchasing Staff
Show:
- Supplier information
- Purchase order preparation
- Product replenishment information

Explain that Purchasing Staff prepares procurement transactions.

### Step 4 — Purchasing Manager
Show:
- Purchase order monitoring
- Approval workflow
- Supplier and purchasing information

Explain that the manager reviews and approves procurement activities.

### Step 5 — Warehouse Admin
Show:
- Incoming stock
- Receiving
- Stock release
- Inventory-related warehouse information

Explain that the Warehouse Admin manages physical receiving and releasing activities.

### Step 6 — Inventory Controller
Show:
- Product stock
- Batches
- Expiry information
- Stock adjustments
- Inventory movement records
- Low-stock alerts

Explain that the Inventory Controller focuses on inventory accuracy and monitoring.

### Step 7 — Sales Staff
Show:
- Customer orders
- Order processing
- Fulfillment-related activities

Explain that Sales Staff handles day-to-day customer order processing.

### Step 8 — Sales Manager
Show:
- Sales dashboard
- Order monitoring
- Sales reports
- Sales performance information

Explain that the Sales Manager monitors sales operations and fulfillment.

### Step 9 — User Admin
Show:
- Customer account administration
- User status information
- Account-related management

Explain that User Admin manages customer accounts rather than system-wide administrative settings.

### Step 10 — Operations Manager
Show:
- Operational dashboard
- Purchasing information
- Warehouse information
- Inventory information
- Sales information

Explain that Operations Manager monitors the overall operational flow.

### Step 11 — Super Admin
Show:
- System dashboard
- User management
- Role management
- Products
- Categories
- Suppliers
- Purchase orders
- Inventory
- Sales orders
- Product reviews
- Audit logs
- Sessions
- System Health
- Website content
- Settings
- Backup tools

Explain that Super Admin manages system-wide administration and oversight.

## 5. Important Database Concept

WalangBrownout follows a single-warehouse design.

There is:
- One company
- One warehouse
- One shared inventory pool

The project does not use a multi-warehouse architecture.

Inventory quantities are represented through product batches and inventory transactions.

## 6. Important Security Features

The system includes:
- Authentication
- Email OTP verification
- Login OTP
- Forgot password
- Password history
- Session management
- Trusted devices
- Idle session enforcement
- Role-based authorization
- Audit logging

## 7. Important System Features

The system includes:
- Product catalog
- Customer ordering
- Shopping cart
- Checkout
- Purchase orders
- Supplier management
- Inventory batches
- Inventory transactions
- Stock monitoring
- Notifications
- Product reviews
- User management
- Role dashboards
- Audit logs
- Session monitoring
- System Health
- Website content management

## 8. Database Tables

The baseline application contains 23 WBO tables.

Additional Phase 3 tables:
- WBO_PasswordHistory
- WBO_ProductReviews

Returns and refunds are planned future functionality and are not currently implemented.

## 9. Common Defense Questions

### Why use different user roles?
Different employees have different responsibilities. Role-based access prevents users from accessing functions outside their assigned job.

### Why is there only one warehouse?
The current business requirement represents one company operating one warehouse and one shared inventory pool.

### Why use batches?
Batches allow the system to track received quantities, remaining stock, receiving dates, and expiry dates.

### Why use audit logs?
Audit logs provide accountability by recording important system actions.

### Why separate React and Laravel responsibilities?
React handles the user interface while Laravel handles business rules, authorization, database access, and server-side processing.

### Does React directly connect to MySQL?
No. React communicates with Laravel, and Laravel accesses the database.

### Are returns and refunds implemented?
Not yet. They are planned future functionality.

## 10. Demo Advice

During the defense:

1. Explain the business problem first.
2. Demonstrate one complete business flow.
3. Avoid opening every page randomly.
4. Explain why each role exists.
5. Show how data moves from one role to another.
6. Use the database and audit logs as supporting evidence.
7. Mention planned features separately from implemented features.
