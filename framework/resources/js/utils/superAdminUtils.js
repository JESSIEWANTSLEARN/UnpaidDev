export const Logo = "/storage/site/Logo.png";

export const money = (value) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(value || 0));

export const number = (value) => new Intl.NumberFormat("en-PH").format(Number(value || 0));

export const formatDate = (value) => {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return new Intl.DateTimeFormat("en-PH", {
    year: "numeric", month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit",
  }).format(date);
};

export const initials = (name) =>
  !name ? "SA" : name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join("");

export const stockState = (stock, lowStockThreshold = 10) => {
  const value = Number(stock || 0);
  if (value <= 0) return { label: "Out of Stock", className: "status-bad", tone: "bad" };
  if (value <= lowStockThreshold) return { label: "Low Stock", className: "status-warn", tone: "warn" };
  return { label: "In Stock", className: "status-ok", tone: "ok" };
};

export const orderStatusClass = (status) =>
  status === "FULFILLED" ? "status-ok" : ["CANCELLED", "UNFULFILLED"].includes(status) ? "status-bad" : "status-warn";

export const poStatusClass = (status) =>
  status === "RECEIVED" ? "status-ok" : status === "CANCELLED" ? "status-bad" : "status-warn";
