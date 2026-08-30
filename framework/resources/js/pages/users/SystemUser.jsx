import React, { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  cartItemCount,
  clearGuestCart,
  mergeCartsWithProducts,
  readGuestCart,
  readUserCart,
  writeUserCart,
} from "../../utils/cartStorage.js";


import "../../../css/ImageHoverEffects.css";
const Logo = "/storage/site/Logo.png";

const getCsrf = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

const money = (value) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(Number(value || 0));

const imagePath = (value) => {
  if (!value) return null;
  if (/^https?:\/\//i.test(value) || value.startsWith("/storage/")) return value;
  if (value.startsWith("storage/")) return `/${value}`;
  if (value.startsWith("/")) return value;
  return `/storage/${value}`;
};

const normalizeProduct = (p, i) => ({
  product_id: Number(p.product_id ?? p.id ?? i),
  sku: p.sku ?? "",
  name: p.name ?? "Product",
  description: p.description ?? "",
  category: p.category ?? "General",
  unit_price: Number(p.unit_price ?? p.price ?? 0),
  available_stock: Number(p.available_stock ?? p.total_stock ?? p.stock ?? 0),
  image_url: imagePath(p.image_url ?? p.image_path ?? p.primary_image ?? p.image),
});

function Badge({ status }) {
  const styles = {
    PENDING: "bg-amber-50 text-amber-700 ring-amber-600/20",
    FULFILLED: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
    UNFULFILLED: "bg-rose-50 text-rose-700 ring-rose-600/20",
    CANCELLED: "bg-slate-100 text-slate-600 ring-slate-500/20",
  };

  return (
    <span className={`rounded-full px-2.5 py-1 text-[11px] font-black ring-1 ring-inset ${styles[status] ?? styles.CANCELLED}`}>
      {status}
    </span>
  );
}

function Empty({ title, text }) {
  return (
    <div className="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
      <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl">◌</div>
      <h3 className="font-black text-slate-900">{title}</h3>
      <p className="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">{text}</p>
    </div>
  );
}

export default function SystemUser() {
  const navigate = useNavigate();
  const [tab, setTab] = useState("dashboard");
  const [user, setUser] = useState(null);
  const [stats, setStats] = useState({});
  const [products, setProducts] = useState([]);
  const [orders, setOrders] = useState([]);
  const [cart, setCart] = useState({});
  const [cartOpen, setCartOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("All");
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [profile, setProfile] = useState({ name: "", contact_number: "" });
  const [password, setPassword] = useState({
    current_password: "",
    password: "",
    password_confirmation: "",
  });

  const api = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: "same-origin",
      ...options,
      headers: {
        Accept: "application/json",
        ...(options.body ? { "Content-Type": "application/json" } : {}),
        ...(options.method && options.method !== "GET" ? { "X-CSRF-TOKEN": getCsrf() } : {}),
        ...(options.headers ?? {}),
      },
    });

    const text = await response.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch { data = { message: text }; }

    if (response.status === 401 || response.status === 403) {
      navigate("/login");
      throw new Error(data.message || "Please log in again.");
    }

    if (!response.ok || data.success === false) {
      const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
      throw new Error(validation || data.message || "Request failed.");
    }

    return data;
  };

  const load = async () => {
    try {
      setLoading(true);
      setError("");
      const [me, productData, orderData] = await Promise.all([
        api("/api/user/me"),
        api("/api/store/products"),
        api("/api/user/orders"),
      ]);

      setUser(me.user);
      setStats(me.stats ?? {});
      setProfile({
        name: me.user?.name ?? "",
        contact_number: me.user?.contact_number ?? "",
      });

      const list = Array.isArray(productData)
        ? productData
        : productData.products ?? productData.data ?? [];

      const normalizedProducts = list.map(normalizeProduct);
      const userId = Number(me.user?.user_id);
      const guestCart = readGuestCart();
      const savedUserCart = readUserCart(userId);
      const mergedCart = mergeCartsWithProducts(
        normalizedProducts,
        savedUserCart,
        guestCart
      );
      const importedGuestCount = cartItemCount(guestCart);

      setProducts(normalizedProducts);
      setCart(mergedCart);
      setOrders(orderData.orders ?? []);

      if (Number.isInteger(userId) && userId > 0) {
        writeUserCart(userId, mergedCart);
      }

      if (importedGuestCount > 0) {
        clearGuestCart();
        setNotice(
          `${importedGuestCount} guest cart item(s) moved into your account.`
        );
      }
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, []);

  useEffect(() => {
    const userId = Number(user?.user_id);

    if (!Number.isInteger(userId) || userId <= 0) return;

    writeUserCart(userId, cart);
  }, [cart, user?.user_id]);

  useEffect(() => {
    if (!notice) return;
    const timer = setTimeout(() => setNotice(""), 3500);
    return () => clearTimeout(timer);
  }, [notice]);

  const categories = useMemo(
    () => ["All", ...new Set(products.map((p) => p.category).filter(Boolean))],
    [products]
  );

  const filteredProducts = useMemo(() => {
    const q = search.toLowerCase().trim();
    return products.filter((p) =>
      (category === "All" || p.category === category) &&
      (!q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || p.description.toLowerCase().includes(q))
    );
  }, [products, search, category]);

  const cartItems = useMemo(() =>
    Object.values(cart).map((entry) => {
      const product = products.find((p) => p.product_id === entry.product_id);
      return product ? { ...product, quantity: entry.quantity, line_total: product.unit_price * entry.quantity } : null;
    }).filter(Boolean),
    [cart, products]
  );

  const cartCount = cartItems.reduce((n, item) => n + item.quantity, 0);
  const cartTotal = cartItems.reduce((n, item) => n + item.line_total, 0);

  const addToCart = (product) => {
    if (product.available_stock <= 0) return;
    setCart((current) => {
      const qty = current[product.product_id]?.quantity ?? 0;
      if (qty >= product.available_stock) {
        setError(`Only ${product.available_stock} unit(s) of ${product.name} are available.`);
        return current;
      }
      return { ...current, [product.product_id]: { product_id: product.product_id, quantity: qty + 1 } };
    });
    setNotice(`${product.name} added to cart.`);
  };

  const setQty = (id, qty) => {
    const product = products.find((p) => p.product_id === id);
    setCart((current) => {
      const copy = { ...current };
      if (qty <= 0) delete copy[id];
      else if (!product || qty <= product.available_stock) copy[id] = { product_id: id, quantity: qty };
      return copy;
    });
  };

  const checkout = async () => {
    if (!cartItems.length) return;
    try {
      setBusy(true);
      setError("");
      const data = await api("/api/user/orders", {
        method: "POST",
        body: JSON.stringify({ items: cartItems.map((i) => ({ product_id: i.product_id, quantity: i.quantity })) }),
      });
      setCart({});
      if (user?.user_id) {
        writeUserCart(user.user_id, {});
      }
      setCartOpen(false);
      setTab("orders");
      setNotice(data.message);
      await load();
    } catch (e) { setError(e.message); }
    finally { setBusy(false); }
  };

  const saveProfile = async (e) => {
    e.preventDefault();
    try {
      setBusy(true); setError("");
      const data = await api("/api/user/profile", { method: "PUT", body: JSON.stringify(profile) });
      setUser((u) => ({ ...u, ...data.user }));
      setNotice(data.message);
    } catch (e) { setError(e.message); }
    finally { setBusy(false); }
  };

  const savePassword = async (e) => {
    e.preventDefault();
    try {
      setBusy(true); setError("");
      const data = await api("/api/user/password", { method: "PUT", body: JSON.stringify(password) });
      setPassword({ current_password: "", password: "", password_confirmation: "" });
      setNotice(data.message);
    } catch (e) { setError(e.message); }
    finally { setBusy(false); }
  };

  const logout = async () => {
    try {
      setBusy(true);
      const data = await api("/logout", { method: "POST", body: "{}" });
      window.location.href = data.redirect || "/login";
    } catch (e) { setError(e.message); setBusy(false); }
  };

  const nav = [
    ["dashboard", "Overview", "⌂"],
    ["shop", "Products", "▦"],
    ["orders", "My Orders", "▤"],
    ["account", "My Account", "◎"],
  ];

  if (loading) {
    return <div className="flex min-h-screen items-center justify-center bg-slate-50"><div className="text-center"><div className="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-blue-700"/><p className="mt-4 text-sm font-bold text-slate-500">Loading customer portal...</p></div></div>;
  }

  return (
    <div className="min-h-screen bg-[#f5f6f8] text-slate-900">
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-slate-950 text-white lg:flex">
        <div className="flex h-20 items-center gap-3 border-b border-white/10 px-6">
          <img src={Logo} alt="WalangBrownout" className="h-10 w-10 rounded-xl bg-white object-contain p-1" />
          <div><p className="text-[10px] font-bold uppercase tracking-[.22em] text-slate-400">Customer Portal</p><p className="font-black">WalangBrownout</p></div>
        </div>
        <nav className="flex-1 space-y-1 p-4">
          {nav.map(([id, label, icon]) => <button key={id} onClick={() => setTab(id)} className={`flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold ${tab === id ? "bg-blue-600 text-white" : "text-slate-300 hover:bg-white/10"}`}><span className="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10">{icon}</span>{label}</button>)}
        </nav>
        <div className="border-t border-white/10 p-4"><div className="mb-3 rounded-2xl bg-white/5 p-4"><p className="truncate text-sm font-bold">{user?.name}</p><p className="mt-1 truncate text-xs text-slate-400">{user?.email}</p></div><button onClick={logout} className="w-full rounded-2xl bg-rose-500/10 px-4 py-3 text-sm font-bold text-rose-300 hover:bg-rose-500/20">Sign out</button></div>
      </aside>

      <div className="min-w-0 lg:pl-64">
        <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
          <div className="flex h-20 items-center justify-between px-4 sm:px-6 xl:px-10"><div><p className="text-xs font-bold uppercase tracking-[.18em] text-blue-600">System User</p><h1 className="text-xl font-black sm:text-2xl">Hello, {user?.name?.split(" ")[0]}</h1></div><button onClick={() => setCartOpen(true)} className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold shadow-sm">Cart <span className="ml-2 rounded-full bg-blue-600 px-2 py-.5 text-xs text-white">{cartCount}</span></button></div>
          <div className="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-3 lg:hidden">{nav.map(([id,label]) => <button key={id} onClick={() => setTab(id)} className={`whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold ${tab === id ? "bg-slate-950 text-white" : "bg-slate-100 text-slate-600"}`}>{label}</button>)}</div>
        </header>

        <main className="px-4 py-6 sm:px-6 xl:px-10 xl:py-8">
          {error && <div className="mb-5 flex justify-between rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700"><span>{error}</span><button onClick={() => setError("")}>✕</button></div>}
          {notice && <div className="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{notice}</div>}

          {tab === "dashboard" && <div className="space-y-6">
            <section className="rounded-[2rem] bg-slate-950 p-6 text-white shadow-xl sm:p-8"><div className="grid gap-6 lg:grid-cols-[1fr_360px] lg:items-center"><div><span className="rounded-full bg-blue-500/15 px-3 py-1 text-xs font-bold text-blue-300">CUSTOMER DASHBOARD</span><h2 className="mt-4 max-w-xl text-3xl font-black sm:text-4xl">Products, orders, and your account in one place.</h2><p className="mt-3 max-w-xl text-sm leading-6 text-slate-300">Browse real available products, submit orders, follow their status, and manage your account.</p><div className="mt-6 flex gap-3"><button onClick={() => setTab("shop")} className="rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold">Browse Products</button><button onClick={() => setTab("orders")} className="rounded-2xl bg-white/10 px-5 py-3 text-sm font-bold">My Orders</button></div></div><div className="grid grid-cols-2 gap-3">{[[stats.total_orders,"Total Orders"],[stats.pending_orders,"Pending"],[stats.fulfilled_orders,"Fulfilled"],[products.length,"Products"]].map(([n,l]) => <div key={l} className="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10"><p className="text-3xl font-black">{n ?? 0}</p><p className="mt-1 text-xs font-bold uppercase tracking-wider text-slate-400">{l}</p></div>)}</div></div></section>
            <div className="grid gap-6 xl:grid-cols-[1.4fr_1fr]"><section className="rounded-[2rem] border border-slate-200 bg-white p-6"><div className="mb-5 flex justify-between"><div><p className="text-xs font-bold uppercase text-blue-600">Featured</p><h3 className="text-xl font-black">Available products</h3></div><button onClick={() => setTab("shop")} className="text-sm font-bold text-blue-600">View all →</button></div><div className="grid gap-4 sm:grid-cols-2">{products.slice(0,4).map(p => <div key={p.product_id} className="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50"><div className="aspect-[16/10] bg-slate-100">{p.image_url && <img src={p.image_url} alt={p.name} className="h-full w-full object-cover wbo-glow-image"/>}</div><div className="p-4"><p className="text-xs font-bold uppercase text-slate-400">{p.category}</p><h4 className="font-black">{p.name}</h4><div className="mt-3 flex items-center justify-between"><span className="font-black text-blue-700">{money(p.unit_price)}</span><button onClick={() => addToCart(p)} disabled={p.available_stock<=0} className="rounded-xl bg-slate-950 px-3 py-2 text-xs font-bold text-white disabled:bg-slate-300">{p.available_stock>0?"Add":"Out"}</button></div></div></div>)}</div></section><section className="rounded-[2rem] border border-slate-200 bg-white p-6"><p className="text-xs font-bold uppercase text-blue-600">Order Updates</p><h3 className="mb-5 text-xl font-black">Recent activity</h3>{orders.length ? <div className="space-y-3">{orders.slice(0,4).map(o => <button key={o.order_id} onClick={() => setTab("orders")} className="w-full rounded-2xl border border-slate-200 p-4 text-left"><div className="flex justify-between"><p className="font-black">Order #{o.order_id}</p><Badge status={o.status}/></div><p className="mt-2 text-xs text-slate-500">{new Date(o.order_date).toLocaleString()}</p><p className="mt-2 font-bold">{money(o.total)}</p></button>)}</div> : <Empty title="No activity yet" text="Your order updates will appear here."/>}</section></div>
          </div>}

          {tab === "shop" && <div className="space-y-6"><section className="rounded-[2rem] border border-slate-200 bg-white p-6"><div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between"><div><p className="text-xs font-bold uppercase text-blue-600">Product Catalog</p><h2 className="text-3xl font-black">Browse available products</h2><p className="mt-2 text-sm text-slate-500">Stock comes from current warehouse batch quantities.</p></div><div className="grid gap-3 sm:grid-cols-2"><input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search products..." className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-blue-500"/><select value={category} onChange={e=>setCategory(e.target.value)} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">{categories.map(c=><option key={c}>{c}</option>)}</select></div></div></section>{filteredProducts.length ? <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">{filteredProducts.map(p => <article key={p.product_id} className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm"><div className="relative aspect-[4/3] bg-slate-100">{p.image_url && <img src={p.image_url} alt={p.name} className="h-full w-full object-cover wbo-glow-image"/>}<span className={`absolute right-3 top-3 rounded-full px-3 py-1 text-[11px] font-black text-white ${p.available_stock>0?"bg-emerald-500":"bg-rose-500"}`}>{p.available_stock>0?`${p.available_stock} in stock`:"Out of stock"}</span></div><div className="p-5"><p className="text-xs font-bold uppercase text-blue-600">{p.category}</p><h3 className="mt-1 text-lg font-black">{p.name}</h3><p className="mt-2 min-h-10 text-sm text-slate-500">{p.description}</p><div className="mt-5 flex items-center justify-between"><span className="text-lg font-black">{money(p.unit_price)}</span><button onClick={()=>addToCart(p)} disabled={p.available_stock<=0} className="rounded-2xl bg-blue-600 px-4 py-2.5 text-xs font-black text-white disabled:bg-slate-300">Add to cart</button></div></div></article>)}</div> : <Empty title="No products found" text="Try another search or category."/>}</div>}

          {tab === "orders" && <div className="space-y-6"><div><p className="text-xs font-bold uppercase text-blue-600">Order History</p><h2 className="text-3xl font-black">My orders</h2><p className="mt-2 text-sm text-slate-500">Sales Staff reviews PENDING orders before warehouse fulfillment.</p></div>{orders.length ? <div className="space-y-4">{orders.map(o => <article key={o.order_id} className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white"><div className="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between"><div><div className="flex items-center gap-3"><h3 className="text-lg font-black">Order #{o.order_id}</h3><Badge status={o.status}/></div><p className="mt-1 text-xs text-slate-500">{new Date(o.order_date).toLocaleString()}</p></div><p className="text-xl font-black text-blue-700">{money(o.total)}</p></div><div className="divide-y divide-slate-100">{(o.items??[]).map(i => <div key={i.order_detail_id} className="grid gap-2 p-5 sm:grid-cols-[1fr_auto_auto] sm:items-center sm:gap-6"><div><p className="font-black">{i.product_name}</p><p className="text-xs text-slate-500">{i.sku}</p></div><p className="text-sm font-semibold text-slate-600">{i.quantity} × {money(i.unit_price)}</p><p className="font-black">{money(i.line_total)}</p></div>)}</div></article>)}</div> : <Empty title="No orders yet" text="Add products to your cart and place your first order."/>}</div>}

          {tab === "account" && <div className="grid gap-6 xl:grid-cols-2"><section className="rounded-[2rem] border border-slate-200 bg-white p-6"><p className="text-xs font-bold uppercase text-blue-600">Profile</p><h2 className="text-2xl font-black">Account information</h2><form onSubmit={saveProfile} className="mt-6 space-y-5"><label className="block"><span className="mb-2 block text-xs font-black uppercase text-slate-500">Full name</span><input required value={profile.name} onChange={e=>setProfile({...profile,name:e.target.value})} className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-blue-500"/></label><label className="block"><span className="mb-2 block text-xs font-black uppercase text-slate-500">Email</span><input readOnly value={user?.email??""} className="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-500"/><small className="mt-2 block text-slate-400">Email stays read-only here so verified-email status cannot be bypassed.</small></label><label className="block"><span className="mb-2 block text-xs font-black uppercase text-slate-500">Contact number</span><input value={profile.contact_number} onChange={e=>setProfile({...profile,contact_number:e.target.value})} className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-blue-500"/></label><button disabled={busy} className="w-full rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white">{busy?"Saving...":"Save profile"}</button></form></section><section className="rounded-[2rem] border border-slate-200 bg-white p-6"><p className="text-xs font-bold uppercase text-blue-600">Security</p><h2 className="text-2xl font-black">Change password</h2><form onSubmit={savePassword} className="mt-6 space-y-5">{[["current_password","Current password"],["password","New password"],["password_confirmation","Confirm new password"]].map(([key,label]) => <label key={key} className="block"><span className="mb-2 block text-xs font-black uppercase text-slate-500">{label}</span><input type="password" minLength={key==="current_password"?undefined:8} required value={password[key]} onChange={e=>setPassword({...password,[key]:e.target.value})} className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-blue-500"/></label>)}<button disabled={busy} className="w-full rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">{busy?"Updating...":"Update password"}</button></form></section><section className="rounded-[2rem] border border-slate-200 bg-white p-6 xl:col-span-2"><div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-bold uppercase text-slate-400">Session</p><h3 className="text-xl font-black">Sign out securely</h3><p className="mt-1 text-sm text-slate-500">Logout invalidates the current Laravel session.</p></div><button onClick={logout} className="rounded-2xl bg-rose-600 px-6 py-3 text-sm font-black text-white">Logout</button></div></section></div>}
        </main>
      </div>

      {cartOpen && <div className="fixed inset-0 z-50"><button aria-label="Close cart" onClick={()=>setCartOpen(false)} className="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"/><aside className="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl"><div className="flex items-center justify-between border-b border-slate-200 p-5"><div><p className="text-xs font-bold uppercase text-blue-600">Shopping Cart</p><h2 className="text-xl font-black">{cartCount} item(s)</h2></div><button onClick={()=>setCartOpen(false)} className="h-10 w-10 rounded-full bg-slate-100 font-black">✕</button></div><div className="flex-1 overflow-y-auto p-5">{cartItems.length ? <div className="space-y-4">{cartItems.map(i => <div key={i.product_id} className="rounded-3xl border border-slate-200 p-4"><div className="flex gap-4"><div className="h-20 w-20 overflow-hidden rounded-2xl bg-slate-100">{i.image_url && <img src={i.image_url} alt={i.name} className="h-full w-full object-cover wbo-glow-image"/>}</div><div className="flex-1"><p className="font-black">{i.name}</p><p className="mt-1 font-bold text-blue-700">{money(i.unit_price)}</p><div className="mt-3 flex items-center gap-2"><button onClick={()=>setQty(i.product_id,i.quantity-1)} className="h-8 w-8 rounded-xl bg-slate-100 font-black">−</button><span className="min-w-8 text-center font-black">{i.quantity}</span><button onClick={()=>setQty(i.product_id,i.quantity+1)} className="h-8 w-8 rounded-xl bg-slate-100 font-black">+</button></div></div></div></div>)}</div> : <Empty title="Your cart is empty" text="Add an available product before ordering."/>}</div><div className="border-t border-slate-200 p-5"><div className="mb-4 flex justify-between"><span className="font-bold text-slate-500">Total</span><span className="text-2xl font-black">{money(cartTotal)}</span></div><button disabled={!cartItems.length||busy} onClick={checkout} className="w-full rounded-2xl bg-blue-600 px-5 py-4 text-sm font-black text-white disabled:bg-slate-300">{busy?"Submitting...":"Place order"}</button><p className="mt-3 text-center text-xs leading-5 text-slate-400">Creates a PENDING order for Sales Staff review.</p></div></aside></div>}
    </div>
  );
}