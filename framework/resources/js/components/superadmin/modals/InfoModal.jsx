import React from "react";

const TEXT = {
  categoryInfo: (
    <>
      The current database stores a category directly in <strong>WBO_Products.category</strong> and has no separate category table. No fake category records are created. Enter a new category when adding a product to introduce it to the catalog.
    </>
  ),
  messages: (
    <>
      The Messages control is preserved. The current database has no messages table, so this page intentionally shows no invented message records.
    </>
  ),
};

export default function InfoModal({ type }) {
  return <div className="admin-modal-body"><p className="ops-subtext">{TEXT[type]}</p></div>;
}
