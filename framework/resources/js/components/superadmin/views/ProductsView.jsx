import React from "react";
import { money, number } from "../../../utils/superAdminUtils.js";
import { EmptyTable, ProductName } from "../common/AdminCommon.jsx";

function Products({ data, openModal }) {
  const products = data.products || [];
  return (
    <>
      <div className="section-head"><div><h2>Products</h2><p>Live catalog data from WBO_Products, WBO_ProductImages, and WBO_Batches.</p></div><button className="btn-primary" type="button" onClick={() => openModal("addProduct")}>+ Add Product</button></div>
      <div className="ops-panel"><div className="table-wrap"><table className="ops-table">
        <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>ABC</th><th>Cost</th><th>Price</th><th>Stock</th><th>Visible</th><th>Featured</th></tr></thead>
        <tbody>{products.length === 0 ? <EmptyTable colSpan={9} text="No products found." /> : products.map((product) => <tr key={product.product_id}>
          <td><ProductName product={product} /></td><td>{product.sku}</td><td>{product.category}</td><td>{product.abc_class}</td><td>{money(product.unit_cost)}</td><td>{money(product.unit_price)}</td><td>{number(product.available_stock)}</td><td>{product.is_visible ? "Yes" : "No"}</td><td>{product.is_featured ? "Yes" : "No"}</td>
        </tr>)}</tbody>
      </table></div></div>
    </>
  );
}


export default Products;
