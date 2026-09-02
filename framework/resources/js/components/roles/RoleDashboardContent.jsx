import React, {
  useState,
} from "react";
import {
  AdjustmentForm,
  StockInForm,
} from "./RoleInventoryActions.jsx";
import {
  PurchaseOrderForm,
  SupplierForm,
} from "./RolePurchasingActions.jsx";
import "../../../css/RoleDashboardData.css";
import RoleSalesContent from "./RoleSalesContent.jsx";
import RoleUserAdminContent from "./RoleUserAdminContent.jsx";

/* WBO_ROLE_DASHBOARD_CONTENT_V2 */

const number = (value) =>
  Number(value || 0).toLocaleString();

const money = (value) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(Number(value || 0));

const date = (value) => {
  if (!value) return "â€”";

  const parsed = new Date(value);

  return Number.isNaN(parsed.getTime())
    ? String(value)
    : parsed.toLocaleString();
};

function Empty({ text }) {
  return (
    <div className="role-live-empty">
      {text}
    </div>
  );
}

function Metrics({
  metrics = {},
  roleKey,
}) {
  const base = [
    ["Products", metrics.total_products],
    ["Available Stock", metrics.total_stock],
    ["Low Stock", metrics.low_stock_items],
    ["Out of Stock", metrics.out_of_stock],
    ["Open POs", metrics.open_purchase_orders],
  ];

  const purchasing = [
    [
      "Awaiting Approval",
      metrics.pending_approval_pos,
    ],
    ["Ordered POs", metrics.ordered_pos],
    [
      "Active Suppliers",
      metrics.active_suppliers,
    ],
    [
      "Reorder Needs",
      metrics.reorder_needs,
    ],
  ];

  const operations = [
    ["Pending Orders", metrics.pending_orders],
    [
      "Movements Today",
      metrics.stock_movements_today,
    ],
    ["Receipts Today", metrics.receipts_today],
    ["Expiry Watch", metrics.expiring_batches],
  ];

  const cards =
    roleKey === "Purchasing_Manager" ||
    roleKey === "Purchasing_Staff"
      ? [...base, ...purchasing]
      : [...base, ...operations];

  return (
    <div className="role-live-metrics">
      {cards.map(([label, value]) => (
        <article
          className="role-live-metric"
          key={label}
        >
          <span>{label}</span>
          <strong>{number(value)}</strong>
        </article>
      ))}
    </div>
  );
}

function Alerts({ alerts = [] }) {
  if (!alerts.length) {
    return (
      <Empty text="No current operational alerts." />
    );
  }

  return (
    <div className="role-live-alerts">
      {alerts.map((alert, index) => (
        <article
          key={`${alert.title}-${index}`}
          className={`role-live-alert tone-${alert.tone || "info"}`}
        >
          <strong>{alert.title}</strong>
          <p>{alert.message}</p>
        </article>
      ))}
    </div>
  );
}

function ProductsTable({
  products = [],
}) {
  if (!products.length) {
    return <Empty text="No products found." />;
  }

  return (
    <div className="role-live-table-wrap">
      <table className="role-live-table">
        <thead>
          <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>Category</th>
            <th>Supplier</th>
            <th>Available</th>
          </tr>
        </thead>
        <tbody>
          {products.map((product) => (
            <tr key={product.product_id}>
              <td>{product.sku}</td>
              <td>{product.name}</td>
              <td>{product.category}</td>
              <td>
                {product.supplier_name || "â€”"}
              </td>
              <td>
                {number(product.available_stock)}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function SuppliersTable({
  suppliers = [],
  previewMode,
  onEdit,
}) {
  if (!suppliers.length) {
    return <Empty text="No suppliers found." />;
  }

  return (
    <div className="role-live-table-wrap">
      <table className="role-live-table">
        <thead>
          <tr>
            <th>Supplier</th>
            <th>Status</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Lead Time</th>
            <th>Products</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          {suppliers.map((supplier) => (
            <tr key={supplier.supplier_id}>
              <td>{supplier.name}</td>
              <td>
                {supplier.supplier_status}
              </td>
              <td>
                {supplier.contact_number ||
                  "â€”"}
              </td>
              <td>{supplier.email || "â€”"}</td>
              <td>
                {number(
                  supplier.lead_time_days,
                )}{" "}
                day(s)
              </td>
              <td>
                {number(
                  supplier.product_count,
                )}
              </td>
              <td>
                <button
                  type="button"
                  className="role-live-table-action"
                  disabled={previewMode}
                  onClick={() =>
                    onEdit(supplier)
                  }
                >
                  {previewMode
                    ? "Read only"
                    : "Edit"}
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function BatchesTable({ batches = [] }) {
  if (!batches.length) {
    return (
      <Empty text="No inventory batches found." />
    );
  }

  return (
    <div className="role-live-table-wrap">
      <table className="role-live-table">
        <thead>
          <tr>
            <th>Batch</th>
            <th>Product</th>
            <th>SKU</th>
            <th>Received</th>
            <th>Current</th>
            <th>Received Date</th>
            <th>Expiry</th>
          </tr>
        </thead>
        <tbody>
          {batches.map((batch) => (
            <tr key={batch.batch_id}>
              <td>{batch.batch_number}</td>
              <td>{batch.product_name}</td>
              <td>{batch.sku}</td>
              <td>
                {number(
                  batch.quantity_received,
                )}
              </td>
              <td>
                {number(
                  batch.current_quantity,
                )}
              </td>
              <td>
                {date(batch.received_date)}
              </td>
              <td>{date(batch.expiry_date)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function TransactionsTable({
  transactions = [],
}) {
  if (!transactions.length) {
    return (
      <Empty text="No stock movements found." />
    );
  }

  return (
    <div className="role-live-table-wrap">
      <table className="role-live-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Batch</th>
            <th>Product</th>
            <th>Change</th>
            <th>Performed By</th>
            <th>Reference</th>
          </tr>
        </thead>
        <tbody>
          {transactions.map(
            (transaction) => (
              <tr
                key={
                  transaction.transaction_id
                }
              >
                <td>
                  {date(
                    transaction.timestamp,
                  )}
                </td>
                <td>
                  {
                    transaction.transaction_type
                  }
                </td>
                <td>
                  {
                    transaction.batch_number
                  }
                </td>
                <td>
                  {
                    transaction.product_name
                  }
                </td>
                <td>
                  {transaction.quantity_change >
                  0
                    ? "+"
                    : ""}
                  {number(
                    transaction.quantity_change,
                  )}
                </td>
                <td>
                  {transaction.performed_by ||
                    "â€”"}
                </td>
                <td>
                  {transaction.reference_note ||
                    "â€”"}
                </td>
              </tr>
            ),
          )}
        </tbody>
      </table>
    </div>
  );
}

function PurchaseOrdersTable({
  purchaseOrders = [],
  roleKey,
  previewMode,
  currentUserId,
  busy,
  onStatus,
  approvalsOnly = false,
}) {
  const rows = approvalsOnly
    ? purchaseOrders.filter(
        (po) =>
          po.status ===
          "PENDING_APPROVAL",
      )
    : purchaseOrders;

  if (!rows.length) {
    return (
      <Empty
        text={
          approvalsOnly
            ? "No purchase orders are awaiting approval."
            : "No purchase orders found."
        }
      />
    );
  }

  const manager =
    roleKey === "Purchasing_Manager";

  const actionsFor = (po) => {
    if (previewMode) return [];

    const actions = [];

    if (po.status === "DRAFT") {
      if (
        manager ||
        Number(po.created_by_user_id) ===
          Number(currentUserId)
      ) {
        actions.push([
          "submit",
          "Submit",
        ]);
      }
    }

    if (
      manager &&
      po.status === "PENDING_APPROVAL"
    ) {
      actions.push([
        "approve",
        "Approve",
      ]);
    }

    if (
      manager &&
      po.status === "APPROVED"
    ) {
      actions.push([
        "order",
        "Mark Ordered",
      ]);
    }

    if (
      manager &&
      ![
        "RECEIVED",
        "PARTIALLY_RECEIVED",
        "CANCELLED",
      ].includes(po.status)
    ) {
      actions.push([
        "cancel",
        "Cancel",
      ]);
    }

    return actions;
  };

  return (
    <div className="role-live-table-wrap">
      <table className="role-live-table">
        <thead>
          <tr>
            <th>PO</th>
            <th>Supplier</th>
            <th>Product</th>
            <th>Ordered</th>
            <th>Received</th>
            <th>Cost</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Approved By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((po, index) => (
            <tr
              key={`${po.po_id}-${po.po_detail_id || index}`}
            >
              <td>{po.po_number}</td>
              <td>{po.supplier_name}</td>
              <td>
                {po.product_name || "â€”"}
              </td>
              <td>
                {number(
                  po.quantity_ordered,
                )}
              </td>
              <td>
                {number(
                  po.quantity_received,
                )}
              </td>
              <td>{money(po.unit_cost)}</td>
              <td>{po.status}</td>
              <td>{po.created_by || "â€”"}</td>
              <td>{po.approved_by || "â€”"}</td>
              <td>
                <div className="role-live-inline-actions">
                  {previewMode && (
                    <span className="role-live-muted">
                      Read only
                    </span>
                  )}

                  {actionsFor(po).map(
                    ([action, label]) => (
                      <button
                        key={action}
                        type="button"
                        className={
                          action === "cancel"
                            ? "role-live-danger-action"
                            : "role-live-table-action"
                        }
                        disabled={busy}
                        onClick={() =>
                          onStatus(
                            po.po_id,
                            action,
                          )
                        }
                      >
                        {label}
                      </button>
                    ),
                  )}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function OrdersTable({ orders = [] }) {
  if (!orders.length) {
    return (
      <Empty text="No sales orders found." />
    );
  }

  return (
    <div className="role-live-table-wrap">
      <table className="role-live-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Date</th>
            <th>Status</th>
            <th>Units</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          {orders.map((order) => (
            <tr key={order.order_id}>
              <td>#{order.order_id}</td>
              <td>{date(order.order_date)}</td>
              <td>{order.status}</td>
              <td>
                {number(
                  order.total_quantity,
                )}
              </td>
              <td>
                {money(order.total_amount)}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function Section({
  title,
  description,
  children,
}) {
  return (
    <section className="role-live-panel">
      <header className="role-live-section-head">
        <div>
          <h2>{title}</h2>
          {description && (
            <p>{description}</p>
          )}
        </div>
      </header>
      {children}
    </section>
  );
}

function Overview({
  roleKey,
  data,
}) {
  return (
    <>
      <Metrics
        metrics={data.metrics}
        roleKey={roleKey}
      />

      <Section
        title="Operational Alerts"
        description="Live conditions calculated from current inventory, purchase orders, sales orders, and expiry dates."
      >
        <Alerts alerts={data.alerts} />
      </Section>

      {roleKey === "Operations_Manager" && (
        <>
          <Section
            title="Recent Purchase Activity"
            description="Latest purchase-order activity."
          >
            <PurchaseOrdersTable
              purchaseOrders={
                data.purchase_orders
              }
              roleKey={roleKey}
              previewMode
              currentUserId={
                data.current_user_id
              }
              onStatus={() => {}}
            />
          </Section>

          <Section
            title="Recent Sales Activity"
            description="Latest customer-order activity without unnecessary customer profile data."
          >
            <OrdersTable
              orders={data.orders}
            />
          </Section>
        </>
      )}

      {roleKey === "Warehouse_Admin" && (
        <Section
          title="Recent Receiving & Batches"
          description="Newest batch-level inventory records."
        >
          <BatchesTable
            batches={(
              data.batches || []
            ).slice(0, 12)}
          />
        </Section>
      )}

      {roleKey ===
        "Inventory_Controller" && (
        <Section
          title="Inventory Health"
          description={`Products at or below ${data.low_stock_threshold} units require attention.`}
        >
          <ProductsTable
            products={
              data.low_stock_products
            }
          />
        </Section>
      )}

      {(roleKey === "Purchasing_Manager" ||
        roleKey === "Purchasing_Staff") && (
        <>
          <Section
            title="Reorder Needs"
            description={`Products at or below ${data.low_stock_threshold} available units.`}
          >
            <ProductsTable
              products={
                data.reorder_needs
              }
            />
          </Section>

          <Section
            title="Recent Purchase Orders"
            description="Latest procurement activity."
          >
            <PurchaseOrdersTable
              purchaseOrders={
                data.purchase_orders
              }
              roleKey={roleKey}
              previewMode
              currentUserId={
                data.current_user_id
              }
              onStatus={() => {}}
            />
          </Section>
        </>
      )}
    </>
  );
}

function FoundationOnly({
  activeModule,
}) {
  return (
    <section className="role-dashboard-module">
      <span>Role Foundation</span>
      <h2>{activeModule}</h2>
      <p>
        This role remains on the protected
        dashboard foundation. Its live business
        API is added in the next role batch.
      </p>
    </section>
  );
}

export default function RoleDashboardContent({
  roleKey,
  activeModule,
  data,
  previewMode,
  busy,
  onStockIn,
  onAdjustment,
  onCreateSupplier,
  onUpdateSupplier,
  onCreatePurchaseOrder,
  onPurchaseOrderStatus,
  onSalesOrderStatus,
  theme,
}) {
  const [
    selectedSupplier,
    setSelectedSupplier,
  ] = useState(null);

  if (roleKey === "User_Admin") {
    return (
      <RoleUserAdminContent
        activeModule={activeModule}
        previewMode={previewMode}
        theme={theme}
      />
    );
  }
  if (!data?.live) {
    return (
      <FoundationOnly
        activeModule={activeModule}
      />
    );
  }

  if (
    roleKey === "Sales_Manager" ||
    roleKey === "Sales_Staff"
  ) {
    return (
      <RoleSalesContent
        roleKey={roleKey}
        activeModule={activeModule}
        data={data}
        previewMode={previewMode}
        busy={busy}
        onOrderStatus={
          onSalesOrderStatus
        }
      />
    );
  }
  if (activeModule === "Overview") {
    return (
      <Overview
        roleKey={roleKey}
        data={data}
      />
    );
  }

  // Operations Manager
  if (roleKey === "Operations_Manager") {
    if (
      activeModule ===
      "Operational Overview"
    ) {
      return (
        <Metrics
          metrics={data.metrics}
          roleKey={roleKey}
        />
      );
    }

    if (
      activeModule ===
      "Inventory Health"
    ) {
      return (
        <Section
          title="Inventory Health"
          description="Current available stock calculated from live batch quantities."
        >
          <ProductsTable
            products={data.products}
          />
        </Section>
      );
    }

    if (
      activeModule ===
      "Purchase Activity"
    ) {
      return (
        <Section
          title="Purchase Activity"
          description="Recent purchase orders and receiving progress."
        >
          <PurchaseOrdersTable
            purchaseOrders={
              data.purchase_orders
            }
            roleKey={roleKey}
            previewMode
            currentUserId={
              data.current_user_id
            }
            onStatus={() => {}}
          />
        </Section>
      );
    }

    if (
      activeModule ===
      "Sales Activity"
    ) {
      return (
        <Section
          title="Sales Activity"
          description="Recent customer orders and totals."
        >
          <OrdersTable
            orders={data.orders}
          />
        </Section>
      );
    }

    if (
      activeModule ===
      "Operational Alerts"
    ) {
      return (
        <Section
          title="Operational Alerts"
          description="Current exceptions needing operational attention."
        >
          <Alerts alerts={data.alerts} />
        </Section>
      );
    }
  }

  // Warehouse Admin
  if (roleKey === "Warehouse_Admin") {
    if (
      activeModule ===
      "Warehouse Overview"
    ) {
      return (
        <Overview
          roleKey={roleKey}
          data={data}
        />
      );
    }

    if (activeModule === "Receiving") {
      return (
        <>
          <Section
            title="Receive Stock"
            description="Create a batch and preserve the receiving movement in WBO_Transactions."
          >
            <StockInForm
              products={data.products}
              previewMode={previewMode}
              busy={busy}
              onSubmit={onStockIn}
            />
          </Section>

          <Section
            title="Open Purchase Orders"
            description="Orders that may require receiving follow-up."
          >
            <PurchaseOrdersTable
              purchaseOrders={(
                data.purchase_orders || []
              ).filter((po) =>
                [
                  "APPROVED",
                  "ORDERED",
                  "PARTIALLY_RECEIVED",
                ].includes(po.status),
              )}
              roleKey={roleKey}
              previewMode
              currentUserId={
                data.current_user_id
              }
              onStatus={() => {}}
            />
          </Section>
        </>
      );
    }

    if (
      activeModule ===
      "Batch Tracking"
    ) {
      return (
        <Section
          title="Batch Tracking"
          description="Batch quantities, received dates, and expiry dates."
        >
          <BatchesTable
            batches={data.batches}
          />
        </Section>
      );
    }

    if (
      activeModule ===
      "Stock Movement"
    ) {
      return (
        <Section
          title="Stock Movement"
          description="Latest RECEIVE, SALE, RESERVE, ADJUSTMENT, and WRITE_OFF records."
        >
          <TransactionsTable
            transactions={
              data.transactions
            }
          />
        </Section>
      );
    }

    if (
      activeModule ===
      "Warehouse Issues"
    ) {
      return (
        <Section
          title="Warehouse Issues"
          description="Low stock, out-of-stock, expiry, and open-order conditions."
        >
          <Alerts alerts={data.alerts} />
        </Section>
      );
    }
  }

  // Inventory Controller
  if (
    roleKey === "Inventory_Controller"
  ) {
    if (
      activeModule ===
      "Inventory Overview"
    ) {
      return (
        <Overview
          roleKey={roleKey}
          data={data}
        />
      );
    }

    if (
      activeModule ===
      "Stock Movement"
    ) {
      return (
        <Section
          title="Stock Movement"
          description="Auditable inventory transaction history."
        >
          <TransactionsTable
            transactions={
              data.transactions
            }
          />
        </Section>
      );
    }

    if (activeModule === "Stock In") {
      return (
        <Section
          title="Stock In"
          description="Receive a new product batch. Preview mode cannot write data."
        >
          <StockInForm
            products={data.products}
            previewMode={previewMode}
            busy={busy}
            onSubmit={onStockIn}
          />
        </Section>
      );
    }

    if (
      activeModule === "Adjustments"
    ) {
      return (
        <>
          <Section
            title="Inventory Adjustment"
            description="Correct a batch quantity while preserving an ADJUSTMENT transaction and audit record."
          >
            <AdjustmentForm
              batches={data.batches}
              previewMode={previewMode}
              busy={busy}
              onSubmit={
                onAdjustment
              }
            />
          </Section>

          <Section
            title="Recent Adjustments"
            description="Latest recorded inventory corrections."
          >
            <TransactionsTable
              transactions={(
                data.transactions || []
              ).filter(
                (transaction) =>
                  transaction.transaction_type ===
                  "ADJUSTMENT",
              )}
            />
          </Section>
        </>
      );
    }

    if (activeModule === "Low Stock") {
      return (
        <Section
          title="Low Stock"
          description={`Products with 1â€“${data.low_stock_threshold} units available.`}
        >
          <ProductsTable
            products={
              data.low_stock_products
            }
          />
        </Section>
      );
    }
  }

  // Purchasing Manager + Purchasing Staff
  const purchasingRole =
    roleKey === "Purchasing_Manager" ||
    roleKey === "Purchasing_Staff";

  if (purchasingRole) {
    const overviewModule =
      roleKey === "Purchasing_Manager"
        ? "Purchasing Overview"
        : "Purchasing Tasks";

    if (activeModule === overviewModule) {
      return (
        <Overview
          roleKey={roleKey}
          data={data}
        />
      );
    }

    if (activeModule === "Suppliers") {
      return (
        <>
          <Section
            title={
              selectedSupplier
                ? `Edit ${selectedSupplier.name}`
                : "Add Supplier"
            }
            description={
              roleKey ===
              "Purchasing_Manager"
                ? "Maintain supplier contacts, lead times, and activation status."
                : "Maintain day-to-day supplier contact information. Supplier activation remains a manager decision."
            }
          >
            <SupplierForm
              roleKey={roleKey}
              previewMode={previewMode}
              busy={busy}
              selectedSupplier={
                selectedSupplier
              }
              onClearSelection={() =>
                setSelectedSupplier(
                  null,
                )
              }
              onCreate={
                onCreateSupplier
              }
              onUpdate={
                onUpdateSupplier
              }
            />
          </Section>

          <Section
            title="Suppliers"
            description="Live supplier records and linked-product counts."
          >
            <SuppliersTable
              suppliers={data.suppliers}
              previewMode={previewMode}
              onEdit={
                setSelectedSupplier
              }
            />
          </Section>
        </>
      );
    }

    if (
      activeModule ===
      "Purchase Orders"
    ) {
      return (
        <>
          <Section
            title="Prepare Purchase Order"
            description="Create a draft or submit directly for Purchasing Manager approval."
          >
            <PurchaseOrderForm
              suppliers={data.suppliers}
              products={data.products}
              previewMode={previewMode}
              busy={busy}
              onCreate={
                onCreatePurchaseOrder
              }
            />
          </Section>

          <Section
            title="Purchase Orders"
            description="Draft, approval, ordering, and receiving status."
          >
            <PurchaseOrdersTable
              purchaseOrders={
                data.purchase_orders
              }
              roleKey={roleKey}
              previewMode={previewMode}
              currentUserId={
                data.current_user_id
              }
              busy={busy}
              onStatus={
                onPurchaseOrderStatus
              }
            />
          </Section>
        </>
      );
    }

    if (
      activeModule === "Reorder Needs" ||
      activeModule ===
        "Reorder Requests"
    ) {
      return (
        <Section
          title={
            activeModule
          }
          description={`These are live stock conditions, not fake request records. Products at or below ${data.low_stock_threshold} units can be turned into a purchase order from the Purchase Orders page.`}
        >
          <ProductsTable
            products={
              data.reorder_needs
            }
          />
        </Section>
      );
    }

    if (
      roleKey ===
        "Purchasing_Manager" &&
      activeModule === "Approvals"
    ) {
      return (
        <Section
          title="Purchase Order Approvals"
          description="Only Purchasing Manager can approve pending purchase orders."
        >
          <PurchaseOrdersTable
            purchaseOrders={
              data.purchase_orders
            }
            roleKey={roleKey}
            previewMode={previewMode}
            currentUserId={
              data.current_user_id
            }
            busy={busy}
            onStatus={
              onPurchaseOrderStatus
            }
            approvalsOnly
          />
        </Section>
      );
    }
  }

  return (
    <FoundationOnly
      activeModule={activeModule}
    />
  );
}