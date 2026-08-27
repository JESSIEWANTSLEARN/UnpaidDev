import React from "react";
import { formatDate } from "../../../utils/superAdminUtils.js";
import { EmptyTable } from "../common/AdminCommon.jsx";

function AuditLogs({ data }) {
  const logs = data.audit_logs || [];
  return (
    <>
      <div className="section-head"><div><h2>Audit Logs</h2><p>Live records from WBO_AuditLogs.</p></div></div>
      <div className="ops-panel"><div className="table-wrap"><table className="ops-table">
        <thead><tr><th>Log ID</th><th>User</th><th>Action</th><th>Description</th><th>IP Address</th><th>Date & Time</th></tr></thead>
        <tbody>{logs.length === 0 ? <EmptyTable colSpan={6} text="No audit logs found." /> : logs.map((log) => <tr key={log.log_id}>
          <td>{log.log_id}</td><td>{log.user_name || "System"}</td><td>{log.action}</td><td>{log.description || "—"}</td><td>{log.ip_address || "—"}</td><td>{formatDate(log.created_at)}</td>
        </tr>)}</tbody>
      </table></div></div>
    </>
  );
}


export default AuditLogs;
