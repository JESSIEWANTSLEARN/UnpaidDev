import React from "react";
import "../../css/SuperAdmin.css";
import useSuperAdmin from "../hooks/useSuperAdmin.js";
import SuperAdminSidebar from "../components/superadmin/SuperAdminSidebar.jsx";
import SuperAdminHeader from "../components/superadmin/SuperAdminHeader.jsx";
import SuperAdminModal from "../components/superadmin/SuperAdminModal.jsx";
import SuperAdminPageContent from "../components/superadmin/SuperAdminPageContent.jsx";

export default function SuperAdmin() {
  const admin = useSuperAdmin();

  return (
    <div className={`ops-portal ${admin.sidebarOpen ? "sidebar-open" : ""}`} data-theme={admin.theme}>
      <SuperAdminSidebar
        activeMenu={admin.activeMenu}
        setActiveMenu={admin.setActiveMenu}
        setSidebarOpen={admin.setSidebarOpen}
        brandName={admin.brandName}
        brandLogo={admin.brandLogo}
      />

      <div className="ops-content">
        <SuperAdminHeader {...admin} />

        <main className="ops-main">
          <SuperAdminPageContent
            activeMenu={admin.activeMenu}
            loading={admin.loading}
            error={admin.error}
            onRetry={admin.refresh}
            data={admin.data}
            setActiveMenu={admin.setActiveMenu}
            openModal={admin.openModal}
            openUserEditor={admin.openUserEditor}
            openUserSessions={admin.openUserSessions}
            openUserDelete={admin.openUserDelete}
            handleExportReport={admin.handleExportReport}
          />
        </main>

        {admin.activeModal && <SuperAdminModal {...admin.modalProps} />}
      </div>
    </div>
  );
}