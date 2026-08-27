import React from "react";
import { money, number } from "../../../utils/superAdminUtils.js";
import { StatCard } from "../common/AdminCommon.jsx";

function Reports({ data, handleExportReport }) {
  const metrics = data.metrics || {};
  return (
    <>
      <div className="section-head"><div><h2>Reports & Analytics</h2><p>Current-month values calculated from fulfilled orders.</p></div><button className="btn-primary" type="button" onClick={handleExportReport}>Export Report</button></div>
      <div className="stat-grid stat-grid-3">
        <StatCard title="Monthly Revenue" value={money(metrics.monthly_revenue)} icon="money" accent="green" />
        <StatCard title="Orders This Month" value={number(metrics.monthly_orders)} icon="cart" accent="blue" />
        <StatCard title="Products Sold" value={number(metrics.products_sold)} icon="package" accent="purple" />
      </div>
    </>
  );
}


export default Reports;
