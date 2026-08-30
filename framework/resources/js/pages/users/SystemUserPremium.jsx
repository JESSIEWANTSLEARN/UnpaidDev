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
import "../../../css/SystemUser.css";

const Logo = "/storage/site/Logo.png";
const HeroImage = "/storage/site/mainpic.jpg";
const THEME_KEY = "wbo_customer_theme_v1";

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

const getInitials = (name) => {
  const parts = String(name || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) return "U";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();

  return `${parts[0][0] ?? ""}${parts[parts.length - 1][0] ?? ""}`.toUpperCase();
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

function Icon({ name, size = 20 }) {
  const common = {
    width: size,
    height: size,
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: 1.9,
    strokeLinecap: "round",
    strokeLinejoin: "round",
    "aria-hidden": true,
  };

  const paths = {
    home: (
      <>
        <path d="M3 11.5 12 4l9 7.5" />
        <path d="M5.5 10.5V20h13v-9.5" />
        <path d="M9.5 20v-6h5v6" />
      </>
    ),
    products: (
      <>
        <path d="M4 7.5 12 3l8 4.5-8 4.5-8-4.5Z" />
        <path d="M4 7.5V16.5L12 21l8-4.5v-9" />
        <path d="M12 12v9" />
      </>
    ),
    orders: (
      <>
        <path d="M6 3h12v18H6z" />
        <path d="M9 7h6M9 11h6M9 15h4" />
      </>
    ),
    user: (
      <>
        <circle cx="12" cy="8" r="3.5" />
        <path d="M5 20c.8-4 3.1-6 7-6s6.2 2 7 6" />
      </>
    ),
    search: (
      <>
        <circle cx="11" cy="11" r="6.5" />
        <path d="m16 16 4 4" />
      </>
    ),
    cart: (
      <>
        <path d="M3 4h2l2.2 10.2h10.6L20 7H6" />
        <circle cx="9" cy="19" r="1.3" />
        <circle cx="17" cy="19" r="1.3" />
      </>
    ),
    bell: (
      <>
        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
        <path d="M10 21h4" />
      </>
    ),
    moon: (
      <path d="M20 15.2A8.5 8.5 0 0 1 8.8 4 8.6 8.6 0 1 0 20 15.2Z" />
    ),
    sun: (
      <>
        <circle cx="12" cy="12" r="3.5" />
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
      </>
    ),
    menu: (
      <>
        <path d="M4 7h16M4 12h16M4 17h16" />
      </>
    ),
    close: (
      <>
        <path d="m6 6 12 12M18 6 6 18" />
      </>
    ),
    arrow: (
      <>
        <path d="M5 12h14" />
        <path d="m14 7 5 5-5 5" />
      </>
    ),
    check: (
      <path d="m5 12 4 4L19 6" />
    ),
    minus: (
      <path d="M6 12h12" />
    ),
    plus: (
      <>
        <path d="M6 12h12M12 6v12" />
      </>
    ),
    logout: (
      <>
        <path d="M10 5H5v14h5" />
        <path d="M13 8l4 4-4 4M17 12H9" />
      </>
    ),
  };

  return <svg {...common}>{paths[name] ?? paths.products}</svg>;
}

function StatusBadge({ status }) {
  const key = String(status || "").toLowerCase();
  return <span className={`customer-status customer-status-${key}`}>{status || "UNKNOWN"}</span>;
}

function EmptyState({ title, text }) {
  return (
    <div className="customer-empty">
      <div className="customer-empty-icon"><Icon name="products" size={24} /></div>
      <h3>{title}</h3>
      <p>{text}</p>
    </div>
  );
}

function ProfileAvatar({ user, className = "" }) {
  const photoUrl = user?.has_profile_photo
    ? `/api/user/profile-photo?v=${encodeURIComponent(user.profile_photo_version ?? "1")}`
    : null;
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    setFailed(false);
  }, [photoUrl]);

  return (
    <div className={`customer-avatar ${className}`.trim()} aria-label={`${user?.name ?? "User"} profile photo`}>
      {photoUrl && !failed ? (
        <img
          src={photoUrl}
          alt={`${user?.name ?? "User"} profile`}
          onError={() => setFailed(true)}
        />
      ) : (
        <span>{getInitials(user?.name)}</span>
      )}
    </div>
  );
}

const ORDER_STATUS_FILTERS = [
  { key: "ALL", label: "All" },
  { key: "PENDING", label: "Pending" },
  { key: "PROCESSING", label: "Processing" },
  { key: "FULFILLED", label: "Completed" },
  { key: "CANCELLED", label: "Cancelled" },
];

function orderMatchesFilter(status, filter) {
  const normalized = String(status || "").toUpperCase();

  if (filter === "ALL") return true;

  if (filter === "PROCESSING") {
    return normalized === "PROCESSING" || normalized === "CONFIRMED";
  }

  return normalized === filter;
}

export default function SystemUser({ previewMode = false }) {
  const navigate = useNavigate();
  const [tab, setTab] = useState("dashboard");
  const [user, setUser] = useState(null);
  const [stats, setStats] = useState({});
  const [products, setProducts] = useState([]);
  const [orders, setOrders] = useState([]);
  const [orderFilter, setOrderFilter] = useState("ALL");
  const [cart, setCart] = useState({});
  const [cartOpen, setCartOpen] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("All");
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [photoBusy, setPhotoBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [cartPulse, setCartPulse] = useState(false);
  const [theme, setTheme] = useState(() => {
    const saved = localStorage.getItem(THEME_KEY);
    if (saved === "light" || saved === "dark") return saved;
    return window.matchMedia?.("(prefers-color-scheme: dark)").matches ? "dark" : "light";
  });
  const [profile, setProfile] = useState({ name: "", contact_number: "" });
  const [deliveryProfile, setDeliveryProfile] = useState({
    street_address: "",
    barangay: "",
    city_municipality: "",
    province: "",
    postal_code: "",
  });
  const [password, setPassword] = useState({
    current_password: "",
    password: "",
    password_confirmation: "",
  });
  const [checkoutOpen, setCheckoutOpen] = useState(false);
  const [checkoutStep, setCheckoutStep] = useState("details");
  const [paymentMethod, setPaymentMethod] = useState("CASH_ON_DELIVERY");
  const [placedOrder, setPlacedOrder] = useState(null);
  const [notifications, setNotifications] = useState([]);
  const [notificationOpen, setNotificationOpen] = useState(false);
  const [notificationBusy, setNotificationBusy] = useState(false);
  const [cartFeedback, setCartFeedback] = useState(null);
  const [cartAddedId, setCartAddedId] = useState(null);
  const [cartShakeId, setCartShakeId] = useState(null);
  const [checkoutForm, setCheckoutForm] = useState({
    full_name: "",
    email: "",
    contact_number: "",
    street_address: "",
    barangay: "",
    city_municipality: "",
    province: "",
    postal_code: "",
    delivery_notes: "",
  });

  const api = async (url, options = {}) => {
    const isFormData = options.body instanceof FormData;

    const response = await fetch(url, {
      credentials: "same-origin",
      ...options,
      headers: {
        Accept: "application/json",
        ...(options.body && !isFormData ? { "Content-Type": "application/json" } : {}),
        ...(options.method && options.method !== "GET" ? { "X-CSRF-TOKEN": getCsrf() } : {}),
        ...(options.headers ?? {}),
      },
    });

    const text = await response.text();
    let data = {};
    try {
      data = text ? JSON.parse(text) : {};
    } catch {
      data = { message: text };
    }

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

      if (previewMode) {
        const productData = await api("/api/store/products");

        const list = Array.isArray(productData)
          ? productData
          : productData.products ?? productData.data ?? [];

        const normalizedProducts = list.map(normalizeProduct);

        const previewUser = {
          user_id: 0,
          name: "Customer Preview",
          email: "Private customer data hidden",
          contact_number: "",
          has_profile_photo: false,
          profile_photo_version: null,
        };

        setUser(previewUser);
        setProfile({
          name: previewUser.name,
          contact_number: "",
        });
        setCheckoutForm((current) => ({
          ...current,
          full_name: previewUser.name,
          email: previewUser.email,
          contact_number: "",
        }));
        setStats({
          total_orders: 0,
          fulfilled_orders: 0,
        });
        setProducts(normalizedProducts);
        setOrders([]);
        setCart({});
        return;
      }

      const [
        me,
        productData,
        orderData,
        notificationData,
      ] = await Promise.all([
        api("/api/user/me"),
        api("/api/store/products"),
        api("/api/user/orders"),
        api("/api/user/notifications"),
      ]);

      setUser(me.user);
      setStats(me.stats ?? {});
      setProfile({
        name: me.user?.name ?? "",
        contact_number: me.user?.contact_number ?? "",
      });

      const savedDelivery = me.delivery_address ?? {
        street_address: "",
        barangay: "",
        city_municipality: "",
        province: "",
        postal_code: "",
      };

      setDeliveryProfile(savedDelivery);
      setCheckoutForm((current) => ({
        ...current,
        full_name: me.user?.name ?? "",
        email: me.user?.email ?? "",
        contact_number: me.user?.contact_number ?? "",
        ...savedDelivery,
      }));

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
      setNotifications(
        notificationData.notifications ?? []
      );

      if (Number.isInteger(userId) && userId > 0) {
        writeUserCart(userId, mergedCart);
      }

      if (importedGuestCount > 0) {
        clearGuestCart();
        setNotice(`${importedGuestCount} guest cart item(s) moved into your account.`);
      }
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  useEffect(() => {
    if (previewMode) return undefined;

    let cancelled = false;

    const refreshCustomerNotifications =
      async () => {
        try {
          const data = await api(
            "/api/user/notifications"
          );

          if (!cancelled) {
            setNotifications(
              data.notifications ?? []
            );
          }
        } catch {
          // Main session handling already manages auth failures.
        }
      };

    const timer = window.setInterval(
      refreshCustomerNotifications,
      30000
    );

    return () => {
      cancelled = true;
      window.clearInterval(timer);
    };
  }, [previewMode]);

  useEffect(() => {
    const userId = Number(user?.user_id);
    if (!Number.isInteger(userId) || userId <= 0) return;
    writeUserCart(userId, cart);
  }, [cart, user?.user_id]);

  useEffect(() => {
    localStorage.setItem(THEME_KEY, theme);
  }, [theme]);

  useEffect(() => {
    if (!notice) return;
    const timer = setTimeout(() => setNotice(""), 2800);
    return () => clearTimeout(timer);
  }, [notice]);

  useEffect(() => {
    if (!cartPulse) return;
    const timer = setTimeout(() => setCartPulse(false), 450);
    return () => clearTimeout(timer);
  }, [cartPulse]);

  const categories = useMemo(
    () => ["All", ...new Set(products.map((p) => p.category).filter(Boolean))],
    [products]
  );

  const categoryCards = useMemo(
    () =>
      categories
        .filter((item) => item !== "All")
        .slice(0, 4)
        .map((item) => ({
          name: item,
          product: products.find((product) => product.category === item),
          count: products.filter((product) => product.category === item).length,
        })),
    [categories, products]
  );

  const filteredProducts = useMemo(() => {
    const q = search.toLowerCase().trim();

    return products.filter(
      (p) =>
        (category === "All" || p.category === category) &&
        (!q ||
          p.name.toLowerCase().includes(q) ||
          p.sku.toLowerCase().includes(q) ||
          p.description.toLowerCase().includes(q))
    );
  }, [products, search, category]);

  const orderStatusCounts = useMemo(
    () =>
      Object.fromEntries(
        ORDER_STATUS_FILTERS.map((filter) => [
          filter.key,
          orders.filter((order) =>
            orderMatchesFilter(order.status, filter.key)
          ).length,
        ])
      ),
    [orders]
  );

  const filteredOrders = useMemo(
    () =>
      orders.filter((order) =>
        orderMatchesFilter(order.status, orderFilter)
      ),
    [orders, orderFilter]
  );

  const cartItems = useMemo(
    () =>
      Object.values(cart)
        .map((entry) => {
          const product = products.find((p) => p.product_id === entry.product_id);
          return product
            ? {
                ...product,
                quantity: entry.quantity,
                line_total: product.unit_price * entry.quantity,
              }
            : null;
        })
        .filter(Boolean),
    [cart, products]
  );

  const cartCount = cartItemCount(cart);
  const cartTotal = cartItems.reduce(
    (sum, item) => sum + item.line_total,
    0
  );

  const unreadNotificationCount =
    notifications.filter(
      (item) => item.status === "UNREAD"
    ).length;

  const readNotification = async (
    notificationId
  ) => {
    if (previewMode) return;

    setNotifications((current) =>
      current.map((item) =>
        item.notification_id === notificationId
          ? {
              ...item,
              status: "ACKNOWLEDGED",
            }
          : item
      )
    );

    try {
      await api(
        `/api/user/notifications/${notificationId}/read`,
        {
          method: "PUT",
          body: "{}",
        }
      );
    } catch (e) {
      setError(e.message);
    }
  };

  const readAllNotifications = async () => {
    if (
      previewMode ||
      unreadNotificationCount === 0
    ) {
      return;
    }

    try {
      setNotificationBusy(true);

      await api(
        "/api/user/notifications/read-all",
        {
          method: "PUT",
          body: "{}",
        }
      );

      setNotifications((current) =>
        current.map((item) =>
          item.status === "UNREAD"
            ? {
                ...item,
                status: "ACKNOWLEDGED",
              }
            : item
        )
      );
    } catch (e) {
      setError(e.message);
    } finally {
      setNotificationBusy(false);
    }
  };

  const changeTab = (nextTab) => {
    setTab(nextTab);
    setMobileOpen(false);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const chooseCategory = (nextCategory) => {
    setCategory(nextCategory);
    setTab("shop");
    setMobileOpen(false);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const handleSearch = (event) => {
    event.preventDefault();
    setTab("shop");
    setMobileOpen(false);
  };

  const showCartFeedback = (type, message, productId = null) => {
    const feedback = { type, message, key: Date.now() };
    setCartFeedback(feedback);

    window.setTimeout(() => {
      setCartFeedback((current) =>
        current?.key === feedback.key ? null : current
      );
    }, 2800);

    if (type === "success" && productId) {
      setCartAddedId(productId);
      window.setTimeout(() => {
        setCartAddedId((current) => current === productId ? null : current);
      }, 700);
    }

    if (type === "warning" && productId) {
      setCartShakeId(productId);
      window.setTimeout(() => {
        setCartShakeId((current) => current === productId ? null : current);
      }, 650);
    }
  };

  const addToCart = (product) => {
    const stock = Math.max(0, Number(product.available_stock || 0));
    const currentQty = Number(cart[product.product_id]?.quantity ?? 0);

    if (stock <= 0) {
      showCartFeedback(
        "warning",
        `${product.name} is currently out of stock.`,
        product.product_id,
      );
      return;
    }

    if (currentQty >= stock) {
      showCartFeedback(
        "warning",
        `You reached the ${stock}-unit stock limit for ${product.name}.`,
        product.product_id,
      );
      return;
    }

    setCart((current) => {
      const latestQty = Number(current[product.product_id]?.quantity ?? 0);

      return {
        ...current,
        [product.product_id]: {
          product_id: product.product_id,
          quantity: Math.min(latestQty + 1, stock),
        },
      };
    });

    showCartFeedback(
      "success",
      `${product.name} successfully added to your cart.`,
      product.product_id,
    );

    setCartPulse(false);
    requestAnimationFrame(() => setCartPulse(true));
  };

  const setQty = (id, qty) => {
    const product = products.find((p) => p.product_id === id);

    if (product && qty > product.available_stock) {
      showCartFeedback(
        "warning",
        `Maximum available quantity for ${product.name} is ${product.available_stock}.`,
        id,
      );
      return;
    }

    setCart((current) => {
      const copy = { ...current };

      if (qty <= 0) {
        delete copy[id];
      } else {
        copy[id] = { product_id: id, quantity: qty };
      }

      return copy;
    });
  };

  const startCheckout = () => {
    if (!cartItems.length) return;

    setCheckoutForm((current) => ({
      ...current,
      full_name: user?.name ?? current.full_name,
      email: user?.email ?? current.email,
      contact_number: user?.contact_number ?? current.contact_number,
      street_address:
        deliveryProfile.street_address || current.street_address,
      barangay:
        deliveryProfile.barangay || current.barangay,
      city_municipality:
        deliveryProfile.city_municipality || current.city_municipality,
      province:
        deliveryProfile.province || current.province,
      postal_code:
        deliveryProfile.postal_code || current.postal_code,
    }));

    setPaymentMethod("CASH_ON_DELIVERY");
    setPlacedOrder(null);
    setCheckoutStep("details");
    setError("");
    setCartOpen(false);
    setCheckoutOpen(true);
  };

  const closeCheckout = () => {
    if (busy) return;
    setCheckoutOpen(false);
    setCheckoutStep("details");
    setPlacedOrder(null);
    setError("");
  };

  const reviewCheckout = (event) => {
    event.preventDefault();
    setError("");
    setCheckoutStep("payment");
  };

  const reviewPayment = () => {
    setError("");
    setCheckoutStep("summary");
  };

  const checkout = async () => {
    if (!cartItems.length) return;

    if (previewMode) {
      setPlacedOrder({
        order_id: "PREVIEW",
        total_amount: cartTotal,
        payment_method: paymentMethod,
        payment_status: "PREVIEW ONLY",
      });
      setCheckoutStep("success");
      return;
    }

    try {
      setBusy(true);
      setError("");

      const data = await api("/api/user/orders", {
        method: "POST",
        body: JSON.stringify({
          items: cartItems.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
          })),
          delivery: {
            full_name: checkoutForm.full_name,
            contact_number: checkoutForm.contact_number,
            street_address: checkoutForm.street_address,
            barangay: checkoutForm.barangay,
            city_municipality: checkoutForm.city_municipality,
            province: checkoutForm.province,
            postal_code: checkoutForm.postal_code,
            delivery_notes: checkoutForm.delivery_notes,
          },
          payment_method: paymentMethod,
        }),
      });

      setCart({});

      if (user?.user_id) {
        writeUserCart(user.user_id, {});
      }

      setCartOpen(false);
      setPlacedOrder({
        order_id: data.order_id,
        total_amount: data.total_amount ?? cartTotal,
        payment_method:
          data.payment?.payment_method ?? paymentMethod,
        payment_status:
          data.payment?.payment_status ?? "PENDING",
      });
      setCheckoutStep("success");
      await load();
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  };

  const saveProfile = async (event) => {
    event.preventDefault();

    if (previewMode) {
      setNotice("Preview mode: profile changes are disabled.");
      return;
    }

    try {
      setBusy(true);
      setError("");

      const data = await api("/api/user/profile", {
        method: "PUT",
        body: JSON.stringify(profile),
      });

      setUser((current) => ({ ...current, ...data.user }));
      setNotice(data.message);
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  };

  const saveDeliveryAddress = async (event) => {
    event.preventDefault();

    if (previewMode) {
      setNotice("Preview mode: delivery information changes are disabled.");
      return;
    }

    try {
      setBusy(true);
      setError("");

      const data = await api("/api/user/delivery-address", {
        method: "PUT",
        body: JSON.stringify(deliveryProfile),
      });

      setDeliveryProfile(data.delivery_address ?? deliveryProfile);
      setCheckoutForm((current) => ({
        ...current,
        ...(data.delivery_address ?? deliveryProfile),
      }));
      setNotice(data.message || "Delivery information saved.");
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  };

  const changeProfilePhoto = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = "";

    if (!file) return;

    if (previewMode) {
      setNotice("Preview mode: profile photo changes are disabled.");
      return;
    }

    const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
    if (!allowedTypes.includes(file.type)) {
      setError("Profile photo must be a JPG, PNG, or WebP image.");
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      setError("Profile photo must be 2 MB or smaller.");
      return;
    }

    try {
      setPhotoBusy(true);
      setError("");

      const formData = new FormData();
      formData.append("photo", file);

      const data = await api("/api/user/profile-photo", {
        method: "POST",
        body: formData,
      });

      setUser((current) => ({
        ...current,
        has_profile_photo: true,
        profile_photo_version: data.profile_photo_version ?? Date.now(),
      }));
      setNotice(data.message || "Profile photo updated.");
    } catch (e) {
      setError(e.message);
    } finally {
      setPhotoBusy(false);
    }
  };

  const removeProfilePhoto = async () => {
    if (previewMode) {
      setNotice("Preview mode: profile photo removal is disabled.");
      return;
    }

    if (!user?.has_profile_photo) return;

    try {
      setPhotoBusy(true);
      setError("");

      const data = await api("/api/user/profile-photo", {
        method: "DELETE",
      });

      setUser((current) => ({
        ...current,
        has_profile_photo: false,
        profile_photo_version: null,
      }));
      setNotice(data.message || "Profile photo removed.");
    } catch (e) {
      setError(e.message);
    } finally {
      setPhotoBusy(false);
    }
  };

  const savePassword = async (event) => {
    event.preventDefault();

    if (previewMode) {
      setNotice("Preview mode: password changes are disabled.");
      return;
    }

    try {
      setBusy(true);
      setError("");

      const data = await api("/api/user/password", {
        method: "PUT",
        body: JSON.stringify(password),
      });

      setPassword({
        current_password: "",
        password: "",
        password_confirmation: "",
      });
      setNotice(data.message);
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  };

  const logout = async () => {
    if (previewMode) {
      window.location.href = "/super-admin";
      return;
    }

    try {
      setBusy(true);
      const data = await api("/logout", { method: "POST", body: "{}" });
      window.location.href = data.redirect || "/login";
    } catch (e) {
      setError(e.message);
      setBusy(false);
    }
  };

  const nav = [
    ["dashboard", "Home", "home"],
    ["shop", "Products", "products"],
    ["orders", "Orders", "orders"],
    ["account", "Account", "user"],
  ];

  if (loading) {
    return (
      <div className="customer-loading" data-theme={theme}>
        <div className="customer-spinner" />
        <p>Loading your store...</p>
      </div>
    );
  }

  return (
    <div className="customer-shell" data-theme={theme}>
      {previewMode && (
        <div className="customer-admin-preview-bar">
          <div>
            <strong>SUPER ADMIN - FULL CUSTOMER VIEW</strong>
            <span>
              You are still logged in as Super Admin. Private customer records and account-changing actions are protected.
            </span>
          </div>
          <div className="customer-admin-preview-actions">
            <a href="/">Landing Page</a>
            <a href="/super-admin">Back to Super Admin</a>
          </div>
        </div>
      )}

      <header className="customer-header">
        <div className="customer-header-inner">
          <button
            type="button"
            className="customer-mobile-menu"
            onClick={() => setMobileOpen((open) => !open)}
            aria-label={mobileOpen ? "Close navigation" : "Open navigation"}
            aria-expanded={mobileOpen}
          >
            <Icon name={mobileOpen ? "close" : "menu"} />
          </button>

          <button
            type="button"
            className="customer-brand"
            onClick={() => changeTab("dashboard")}
            aria-label="Walang Brownout home"
          >
            <img src={Logo} alt="" />
            <span>
              <small>Home Comfort Store</small>
              <strong>Walang Brownout</strong>
            </span>
          </button>

          <nav className="customer-desktop-nav" aria-label="Customer navigation">
            {nav.map(([id, label, icon]) => (
              <button
                type="button"
                key={id}
                className={tab === id ? "is-active" : ""}
                onClick={() => changeTab(id)}
              >
                <Icon name={icon} size={18} />
                <span>{label}</span>
              </button>
            ))}
          </nav>

          <form className="customer-header-search" onSubmit={handleSearch}>
            <Icon name="search" size={18} />
            <input
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search products..."
              aria-label="Search products"
            />
          </form>

          <div className="customer-header-actions">
            {!previewMode && (
              <div className="customer-notification-wrap">
                <button
                  type="button"
                  className={`customer-icon-button customer-notification-button ${
                    unreadNotificationCount > 0
                      ? "has-unread"
                      : ""
                  }`}
                  onClick={() =>
                    setNotificationOpen(
                      (open) => !open
                    )
                  }
                  aria-label={`Notifications${
                    unreadNotificationCount
                      ? `, ${unreadNotificationCount} unread`
                      : ""
                  }`}
                  aria-expanded={notificationOpen}
                >
                  <Icon name="bell" />
                  {unreadNotificationCount > 0 && (
                    <span className="customer-notification-count">
                      {unreadNotificationCount > 9
                        ? "9+"
                        : unreadNotificationCount}
                    </span>
                  )}
                </button>

                {notificationOpen && (
                  <div className="customer-notification-panel">
                    <div className="customer-notification-head">
                      <div>
                        <span>UPDATES</span>
                        <h3>Notifications</h3>
                      </div>

                      <button
                        type="button"
                        onClick={readAllNotifications}
                        disabled={
                          notificationBusy ||
                          unreadNotificationCount === 0
                        }
                      >
                        Mark all read
                      </button>
                    </div>

                    <div className="customer-notification-list">
                      {notifications.length ? (
                        notifications.map((item) => (
                          <button
                            type="button"
                            key={item.notification_id}
                            className={`customer-notification-item ${
                              item.status === "UNREAD"
                                ? "is-unread"
                                : ""
                            }`}
                            onClick={() =>
                              readNotification(
                                item.notification_id
                              )
                            }
                          >
                            <span
                              className={`customer-notification-dot tier-${String(
                                item.alert_tier || "Yellow"
                              ).toLowerCase()}`}
                            />

                            <span className="customer-notification-copy">
                              <strong>
                                {item.title ||
                                  "System update"}
                              </strong>
                              <small>
                                {item.message ||
                                  "You have a new update."}
                              </small>
                              <time>
                                {new Date(
                                  item.triggered_at
                                ).toLocaleString()}
                              </time>
                            </span>

                            {item.status === "UNREAD" && (
                              <span className="customer-notification-new">
                                NEW
                              </span>
                            )}
                          </button>
                        ))
                      ) : (
                        <div className="customer-notification-empty">
                          <Icon name="bell" size={23} />
                          <strong>You're all caught up</strong>
                          <span>No notifications yet.</span>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            )}

            <button
              type="button"
              className="customer-icon-button"
              onClick={() => setTheme((current) => (current === "dark" ? "light" : "dark"))}
              aria-label={`Switch to ${theme === "dark" ? "light" : "dark"} mode`}
            >
              <Icon name={theme === "dark" ? "sun" : "moon"} />
            </button>

            <button
              type="button"
              className={`customer-cart-button ${cartPulse ? "is-pulsing" : ""}`}
              onClick={() => setCartOpen(true)}
            >
              <Icon name="cart" size={20} />
              <span className="customer-cart-text">Cart</span>
              <span className="customer-cart-count" aria-live="polite">{cartCount}</span>
            </button>
          </div>
        </div>

        <form className="customer-mobile-search" onSubmit={handleSearch}>
          <Icon name="search" size={18} />
          <input
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Search products..."
            aria-label="Search products"
          />
        </form>

        {mobileOpen && (
          <nav className="customer-mobile-nav" aria-label="Mobile customer navigation">
            {nav.map(([id, label, icon]) => (
              <button
                type="button"
                key={id}
                className={tab === id ? "is-active" : ""}
                onClick={() => changeTab(id)}
              >
                <Icon name={icon} size={19} />
                <span>{label}</span>
              </button>
            ))}
            <button type="button" onClick={logout} disabled={busy}>
              <Icon name="logout" size={19} />
              <span>{previewMode ? "Back to Super Admin" : "Sign out"}</span>
            </button>
          </nav>
        )}
      </header>

      {error && (
        <div className="customer-error" role="alert">
          <span>{error}</span>
          <button type="button" onClick={() => setError("")} aria-label="Dismiss error">
            <Icon name="close" size={17} />
          </button>
        </div>
      )}

      <main className="customer-main">
        {tab === "dashboard" && (
          <>
            <section className="customer-hero">
              <div className="customer-hero-copy">
                <span className="customer-eyebrow">SMARTER HOME COMFORT</span>
                <h1>Smarter cooling. Cleaner air. Better living.</h1>
                <p>
                  Shop real Walang Brownout inventory, check live availability,
                  and manage every order from one secure account.
                </p>

                <div className="customer-hero-actions">
                  <button type="button" className="customer-primary" onClick={() => changeTab("shop")}>
                    Shop products <Icon name="arrow" size={18} />
                  </button>
                  <button type="button" className="customer-secondary" onClick={() => changeTab("orders")}>
                    View my orders
                  </button>
                </div>

                <div className="customer-hero-stats">
                  <div>
                    <strong>{products.length}</strong>
                    <span>Available products</span>
                  </div>
                  <div>
                    <strong>{stats.total_orders ?? 0}</strong>
                    <span>Your orders</span>
                  </div>
                  <div>
                    <strong>{stats.fulfilled_orders ?? 0}</strong>
                    <span>Fulfilled</span>
                  </div>
                </div>
              </div>

              <div className="customer-hero-visual">
                <img src={HeroImage} alt="Walang Brownout home comfort appliances" />
                <div className="customer-hero-card">
                  <span>Live inventory</span>
                  <strong>{products.reduce((sum, product) => sum + Math.max(0, product.available_stock), 0)} units</strong>
                  <small>Current warehouse availability</small>
                </div>
              </div>
            </section>

            <section className="customer-section">
              <div className="customer-section-head">
                <div>
                  <span className="customer-kicker">SHOP BY CATEGORY</span>
                  <h2>Find the right comfort solution</h2>
                </div>
                <button type="button" className="customer-text-link" onClick={() => chooseCategory("All")}>
                  View all products <Icon name="arrow" size={16} />
                </button>
              </div>

              {categoryCards.length ? (
                <div className="customer-category-grid">
                  {categoryCards.map((item) => (
                    <button
                      type="button"
                      key={item.name}
                      className="customer-category-card"
                      onClick={() => chooseCategory(item.name)}
                    >
                      <div className="customer-category-media">
                        {item.product?.image_url ? (
                          <img src={item.product.image_url} alt="" loading="lazy" />
                        ) : (
                          <Icon name="products" size={34} />
                        )}
                      </div>
                      <div>
                        <strong>{item.name}</strong>
                        <span>{item.count} product{item.count === 1 ? "" : "s"}</span>
                      </div>
                      <Icon name="arrow" size={18} />
                    </button>
                  ))}
                </div>
              ) : (
                <EmptyState
                  title="Categories will appear here"
                  text="Add categorized products to build the customer catalog."
                />
              )}
            </section>

            <section className="customer-section">
              <div className="customer-section-head">
                <div>
                  <span className="customer-kicker">FEATURED PRODUCTS</span>
                  <h2>Available now</h2>
                </div>
                <button type="button" className="customer-text-link" onClick={() => changeTab("shop")}>
                  Browse catalog <Icon name="arrow" size={16} />
                </button>
              </div>

              {products.length ? (
                <div className="customer-product-grid customer-featured-grid">
                  {products.slice(0, 4).map((product) => (
                    <article className="customer-product-card" key={product.product_id}>
                      <div className="customer-product-media">
                        {product.image_url ? (
                          <img
                            src={product.image_url}
                            alt={product.name}
                            loading="lazy"
                            className="wbo-glow-image"
                          />
                        ) : (
                          <div className="customer-product-placeholder"><Icon name="products" size={32} /></div>
                        )}
                        <span className={`customer-stock ${product.available_stock > 0 ? "is-in" : "is-out"}`}>
                          {product.available_stock > 0
                            ? `${product.available_stock} in stock`
                            : "Out of stock"}
                        </span>
                      </div>
                      <div className="customer-product-body">
                        <span className="customer-product-category">{product.category}</span>
                        <h3>{product.name}</h3>
                        <p>{product.description || "Home comfort product from Walang Brownout."}</p>
                        <div className="customer-product-footer">
                          <strong>{money(product.unit_price)}</strong>
                          <button
                            type="button"
                            className={`customer-add-cart-button ${
                              cartAddedId === product.product_id
                                ? "is-added"
                                : ""
                            } ${
                              cartShakeId === product.product_id
                                ? "is-limit"
                                : ""
                            }`}
                            onClick={() => addToCart(product)}
                            disabled={product.available_stock <= 0}
                          >
                            <Icon
                              name={
                                cartAddedId === product.product_id
                                  ? "check"
                                  : "cart"
                              }
                              size={17}
                            />
                            {cartAddedId === product.product_id
                              ? "Added!"
                              : "Add to cart"}
                          </button>
                        </div>
                      </div>
                    </article>
                  ))}
                </div>
              ) : (
                <EmptyState title="No products yet" text="Available products will appear here." />
              )}
            </section>

            <section className="customer-section customer-order-preview">
              <div className="customer-section-head">
                <div>
                  <span className="customer-kicker">ORDER UPDATES</span>
                  <h2>Recent activity</h2>
                </div>
                <button type="button" className="customer-text-link" onClick={() => changeTab("orders")}>
                  See all orders <Icon name="arrow" size={16} />
                </button>
              </div>

              {orders.length ? (
                <div className="customer-recent-orders">
                  {orders.slice(0, 3).map((order) => (
                    <button
                      type="button"
                      key={order.order_id}
                      className="customer-recent-order"
                      onClick={() => changeTab("orders")}
                    >
                      <div>
                        <span>Order #{order.order_id}</span>
                        <small>{new Date(order.order_date).toLocaleString()}</small>
                      </div>
                      <strong>{money(order.total)}</strong>
                      <StatusBadge status={order.status} />
                    </button>
                  ))}
                </div>
              ) : (
                <EmptyState title="No orders yet" text="Your latest order updates will appear here." />
              )}
            </section>
          </>
        )}

        {tab === "shop" && (
          <section className="customer-page-section">
            <div className="customer-page-title">
              <div>
                <span className="customer-kicker">PRODUCT CATALOG</span>
                <h1>Shop available products</h1>
                <p>Availability is calculated from current warehouse batch quantities.</p>
              </div>
              <span className="customer-result-count">{filteredProducts.length} result(s)</span>
            </div>

            <div className="customer-catalog-toolbar">
              <form className="customer-catalog-search" onSubmit={handleSearch}>
                <Icon name="search" size={18} />
                <input
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  placeholder="Search name, SKU, or description..."
                />
              </form>

              <div className="customer-category-pills" aria-label="Filter by category">
                {categories.map((item) => (
                  <button
                    type="button"
                    key={item}
                    className={category === item ? "is-active" : ""}
                    onClick={() => setCategory(item)}
                  >
                    {item}
                  </button>
                ))}
              </div>
            </div>

            {filteredProducts.length ? (
              <div className="customer-product-grid">
                {filteredProducts.map((product) => (
                  <article className="customer-product-card" key={product.product_id}>
                    <div className="customer-product-media">
                      {product.image_url ? (
                        <img
                          src={product.image_url}
                          alt={product.name}
                          loading="lazy"
                          className="wbo-glow-image"
                        />
                      ) : (
                        <div className="customer-product-placeholder"><Icon name="products" size={32} /></div>
                      )}
                      <span className={`customer-stock ${product.available_stock > 0 ? "is-in" : "is-out"}`}>
                        {product.available_stock > 0
                          ? `${product.available_stock} in stock`
                          : "Out of stock"}
                      </span>
                    </div>

                    <div className="customer-product-body">
                      <span className="customer-product-category">{product.category}</span>
                      <h3>{product.name}</h3>
                      <p>{product.description || "Home comfort product from Walang Brownout."}</p>
                      <small className="customer-sku">{product.sku}</small>

                      <div className="customer-product-footer">
                        <strong>{money(product.unit_price)}</strong>
                        <button
                          type="button"
                          className={`customer-add-cart-button ${
                            cartAddedId === product.product_id
                              ? "is-added"
                              : ""
                          } ${
                            cartShakeId === product.product_id
                              ? "is-limit"
                              : ""
                          }`}
                          onClick={() => addToCart(product)}
                          disabled={product.available_stock <= 0}
                        >
                          <Icon
                            name={
                              cartAddedId === product.product_id
                                ? "check"
                                : "cart"
                            }
                            size={17}
                          />
                          {product.available_stock <= 0
                            ? "Unavailable"
                            : cartAddedId === product.product_id
                              ? "Added!"
                              : "Add to cart"}
                        </button>
                      </div>
                    </div>
                  </article>
                ))}
              </div>
            ) : (
              <EmptyState title="No products found" text="Try another search or category." />
            )}
          </section>
        )}

        {tab === "orders" && (
          <section className="customer-page-section">
            <div className="customer-page-title">
              <div>
                <span className="customer-kicker">ORDER HISTORY</span>
                <h1>My orders</h1>
                <p>Track your submitted orders and their current processing status.</p>
              </div>
            </div>

            {!previewMode && orders.length > 0 && (
              <div
                className="customer-order-filters"
                aria-label="Filter orders by status"
              >
                {ORDER_STATUS_FILTERS.map((filter) => (
                  <button
                    key={filter.key}
                    type="button"
                    className={
                      orderFilter === filter.key
                        ? "customer-order-filter is-active"
                        : "customer-order-filter"
                    }
                    onClick={() => setOrderFilter(filter.key)}
                    aria-pressed={orderFilter === filter.key}
                  >
                    <strong>
                      {orderStatusCounts[filter.key] ?? 0}
                    </strong>
                    <span>{filter.label}</span>
                  </button>
                ))}
              </div>
            )}

            {filteredOrders.length ? (
              <div className="customer-orders-list">
                {filteredOrders.map((order) => (
                  <article className="customer-order-card" key={order.order_id}>
                    <div className="customer-order-head">
                      <div>
                        <span>ORDER</span>
                        <h2>#{order.order_id}</h2>
                        <small>{new Date(order.order_date).toLocaleString()}</small>
                      </div>
                      <div className="customer-order-summary">
                        <strong>{money(order.total)}</strong>
                        <StatusBadge status={order.status} />
                      </div>
                    </div>

                    {order.delivery && (
                      <div className="customer-order-delivery">
                        <div>
                          <span>DELIVERY TO</span>
                          <strong>{order.delivery.full_name}</strong>
                          <small>
                            {order.delivery.contact_number}
                            {" Â· "}
                            {order.delivery.email}
                          </small>
                        </div>
                        <p>
                          {order.delivery.street_address}, {order.delivery.barangay},{" "}
                          {order.delivery.city_municipality}, {order.delivery.province}{" "}
                          {order.delivery.postal_code}
                        </p>
                        {order.delivery.delivery_notes && (
                          <small className="customer-delivery-note">
                            Note: {order.delivery.delivery_notes}
                          </small>
                        )}
                      </div>
                    )}

                    <div className="customer-order-items">
                      {(order.items ?? []).map((item) => (
                        <div className="customer-order-item" key={item.order_detail_id}>
                          <div>
                            <strong>{item.product_name}</strong>
                            <small>{item.sku}</small>
                          </div>
                          <span>{item.quantity} x {money(item.unit_price)}</span>
                          <strong>{money(item.line_total)}</strong>
                        </div>
                      ))}
                    </div>
                  </article>
                ))}
              </div>
            ) : orders.length && !previewMode ? (
              <EmptyState
                title={`No ${ORDER_STATUS_FILTERS.find(
                  (item) => item.key === orderFilter
                )?.label.toLowerCase() ?? ""} orders`}
                text="Choose another order status to view your orders."
              />
            ) : (
              <EmptyState
                title={
                  previewMode
                    ? "Private order history protected"
                    : "No orders yet"
                }
                text={
                  previewMode
                    ? "This is the full customer interface, but Super Admin preview does not expose a specific customer's private order history."
                    : "Add products to your cart and place your first order."
                }
              />
            )}
          </section>
        )}
        {tab === "account" && (
          <section className="customer-page-section">
            <div className="customer-page-title">
              <div>
                <span className="customer-kicker">MY ACCOUNT</span>
                <h1>Profile and security</h1>
                <p>{previewMode ? "Preview the customer account interface without changing or exposing a real customer account." : "Keep your customer information and password up to date."}</p>
              </div>
            </div>

            <div className="customer-account-grid">
              <form className="customer-account-card" onSubmit={saveProfile}>
                <div className="customer-card-heading">
                  <span><Icon name="user" size={19} /></span>
                  <div>
                    <h2>Account information</h2>
                    <p>Used for your customer profile and orders.</p>
                  </div>
                </div>

                <div className="customer-profile-photo-editor">
                  <ProfileAvatar user={user} className="customer-avatar-large" />
                  <div className="customer-profile-photo-copy">
                    <strong>Profile photo</strong>
                    <p>JPG, PNG, or WebP. Maximum 2 MB.</p>
                    <div className="customer-profile-photo-actions">
                      <label className={`customer-photo-button ${photoBusy ? "is-disabled" : ""}`}>
                        <input
                          type="file"
                          accept="image/jpeg,image/png,image/webp"
                          onChange={changeProfilePhoto}
                          disabled={photoBusy || previewMode}
                        />
                        {photoBusy ? "Saving..." : user?.has_profile_photo ? "Change photo" : "Upload photo"}
                      </label>
                      {user?.has_profile_photo && (
                        <button
                          type="button"
                          className="customer-photo-remove"
                          onClick={removeProfilePhoto}
                          disabled={photoBusy || previewMode}
                        >
                          Remove
                        </button>
                      )}
                    </div>
                  </div>
                </div>

                <label>
                  <span>Full name</span>
                  <input
                    required
                    value={profile.name}
                    onChange={(event) => setProfile({ ...profile, name: event.target.value })}
                  />
                </label>

                <label>
                  <span>Email address</span>
                  <input readOnly value={user?.email ?? ""} className="is-readonly" />
                  <small>Your verified email cannot be changed from this page.</small>
                </label>

                <label>
                  <span>Contact number</span>
                  <input
                    value={profile.contact_number}
                    onChange={(event) =>
                      setProfile({ ...profile, contact_number: event.target.value })
                    }
                  />
                </label>

                <button type="submit" className="customer-primary customer-full-button" disabled={busy}>
                  {busy ? "Saving..." : "Save profile"}
                </button>
              </form>

              <form className="customer-account-card" onSubmit={savePassword}>
                <div className="customer-card-heading">
                  <span><Icon name="check" size={19} /></span>
                  <div>
                    <h2>Change password</h2>
                    <p>Use a strong password you do not reuse elsewhere.</p>
                  </div>
                </div>

                <label>
                  <span>Current password</span>
                  <input
                    type="password"
                    required
                    value={password.current_password}
                    onChange={(event) =>
                      setPassword({ ...password, current_password: event.target.value })
                    }
                  />
                </label>

                <label>
                  <span>New password</span>
                  <input
                    type="password"
                    minLength={8}
                    required
                    value={password.password}
                    onChange={(event) =>
                      setPassword({ ...password, password: event.target.value })
                    }
                  />
                </label>

                <label>
                  <span>Confirm new password</span>
                  <input
                    type="password"
                    minLength={8}
                    required
                    value={password.password_confirmation}
                    onChange={(event) =>
                      setPassword({
                        ...password,
                        password_confirmation: event.target.value,
                      })
                    }
                  />
                </label>

                <button type="submit" className="customer-primary customer-full-button" disabled={busy}>
                  {busy ? "Updating..." : "Update password"}
                </button>
              </form>

              <form
                className="customer-account-card customer-delivery-profile-card"
                onSubmit={saveDeliveryAddress}
              >
                <div className="customer-card-heading">
                  <span><Icon name="home" size={19} /></span>
                  <div>
                    <h2>Delivery information</h2>
                    <p>Saved as your default address and automatically filled during checkout.</p>
                  </div>
                </div>

                <div className="customer-delivery-profile-grid">
                  <label className="customer-delivery-wide">
                    <span>Street address</span>
                    <input
                      required
                      maxLength={255}
                      value={deliveryProfile.street_address}
                      onChange={(event) =>
                        setDeliveryProfile({ ...deliveryProfile, street_address: event.target.value })
                      }
                      placeholder="House / unit number, street, subdivision"
                      disabled={previewMode}
                    />
                  </label>

                  <label>
                    <span>Barangay</span>
                    <input
                      required
                      maxLength={100}
                      value={deliveryProfile.barangay}
                      onChange={(event) =>
                        setDeliveryProfile({ ...deliveryProfile, barangay: event.target.value })
                      }
                      disabled={previewMode}
                    />
                  </label>

                  <label>
                    <span>City / Municipality</span>
                    <input
                      required
                      maxLength={100}
                      value={deliveryProfile.city_municipality}
                      onChange={(event) =>
                        setDeliveryProfile({ ...deliveryProfile, city_municipality: event.target.value })
                      }
                      disabled={previewMode}
                    />
                  </label>

                  <label>
                    <span>Province</span>
                    <input
                      required
                      maxLength={100}
                      value={deliveryProfile.province}
                      onChange={(event) =>
                        setDeliveryProfile({ ...deliveryProfile, province: event.target.value })
                      }
                      disabled={previewMode}
                    />
                  </label>

                  <label>
                    <span>Postal code</span>
                    <input
                      required
                      maxLength={20}
                      value={deliveryProfile.postal_code}
                      onChange={(event) =>
                        setDeliveryProfile({ ...deliveryProfile, postal_code: event.target.value })
                      }
                      disabled={previewMode}
                    />
                  </label>
                </div>

                <button
                  type="submit"
                  className="customer-primary customer-full-button"
                  disabled={busy || previewMode}
                >
                  {busy ? "Saving..." : "Save delivery information"}
                </button>
              </form>

              <div className="customer-account-card customer-session-card">
                <div className="customer-card-heading">
                  <span><Icon name="logout" size={19} /></span>
                  <div>
                    <h2>Session</h2>
                    <p>Sign out securely from this browser session.</p>
                  </div>
                </div>

                <div className="customer-session-user">
                  <ProfileAvatar user={user} className="customer-avatar-session" />
                  <div>
                    <strong>{user?.name}</strong>
                    <span>{user?.email}</span>
                  </div>
                </div>

                <button
                  type="button"
                  className="customer-danger-button"
                  onClick={logout}
                  disabled={busy}
                >
                  <Icon name="logout" size={18} />
                  Sign out
                </button>
              </div>
            </div>
          </section>
        )}
      </main>

      {notice && (
        <div className="customer-toast" role="status" aria-live="polite">
          <span><Icon name="check" size={17} /></span>
          <p>{notice}</p>
        </div>
      )}

      {cartFeedback && (
        <div
          key={cartFeedback.key}
          className={`customer-cart-feedback is-${cartFeedback.type}`}
          role="status"
          aria-live="polite"
        >
          <span>{cartFeedback.type === "success" ? "âœ“" : "!"}</span>
          <p>{cartFeedback.message}</p>
        </div>
      )}

      {checkoutOpen && (
        <div className="customer-checkout-layer">
          <button
            type="button"
            className="customer-checkout-backdrop"
            onClick={closeCheckout}
            aria-label="Close checkout"
          />

          <section
            className="customer-checkout-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="customer-checkout-title"
          >
            <div className="customer-checkout-head">
              <div>
                <span>SECURE CHECKOUT</span>
                <h2 id="customer-checkout-title">
                  {checkoutStep === "details"
                    ? "Is this your delivery information?"
                    : checkoutStep === "payment"
                      ? "Choose your payment method"
                      : checkoutStep === "summary"
                        ? "Review and confirm"
                        : "Order placed successfully"}
                </h2>
                <p>
                  {checkoutStep === "details"
                    ? "Your saved account delivery information is filled in automatically. You can edit it for this order."
                    : checkoutStep === "payment"
                      ? "Select how you want to pay for this order."
                      : checkoutStep === "summary"
                        ? "Confirm the delivery information, payment method, products, and total."
                        : "Your order has been recorded and is waiting for processing."}
                </p>
              </div>

              <button
                type="button"
                className="customer-icon-button"
                onClick={closeCheckout}
                disabled={busy}
                aria-label="Close checkout"
              >
                <Icon name="close" />
              </button>
            </div>

            {error && checkoutStep !== "success" && (
              <div className="customer-checkout-error" role="alert">
                <span>!</span>
                <p>{error}</p>
                <button
                  type="button"
                  onClick={() => setError("")}
                  aria-label="Dismiss checkout error"
                >
                  <Icon name="close" size={15} />
                </button>
              </div>
            )}

            {checkoutStep !== "success" && (
              <div className="customer-checkout-steps" aria-label="Checkout progress">
                <span className={checkoutStep === "details" ? "is-active" : "is-done"}>
                  <strong>1</strong>
                  Delivery
                </span>
                <span
                  className={
                    checkoutStep === "payment"
                      ? "is-active"
                      : checkoutStep === "summary"
                        ? "is-done"
                        : ""
                  }
                >
                  <strong>2</strong>
                  Payment
                </span>
                <span className={checkoutStep === "summary" ? "is-active" : ""}>
                  <strong>3</strong>
                  Review
                </span>
              </div>
            )}

            {checkoutStep === "details" && (
              <form className="customer-checkout-form" onSubmit={reviewCheckout}>
                <div className="customer-checkout-grid">
                  <label>
                    <span>Full name</span>
                    <input
                      required
                      maxLength={100}
                      value={checkoutForm.full_name}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, full_name: event.target.value })
                      }
                    />
                  </label>

                  <label>
                    <span>Email address</span>
                    <input readOnly value={checkoutForm.email} className="is-readonly" />
                    <small>Uses your verified account email.</small>
                  </label>

                  <label>
                    <span>Contact number</span>
                    <input
                      required
                      maxLength={20}
                      value={checkoutForm.contact_number}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, contact_number: event.target.value })
                      }
                      placeholder="09XXXXXXXXX"
                    />
                  </label>

                  <label className="customer-checkout-wide">
                    <span>Street address</span>
                    <input
                      required
                      maxLength={255}
                      value={checkoutForm.street_address}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, street_address: event.target.value })
                      }
                      placeholder="House / unit number, street, subdivision"
                    />
                  </label>

                  <label>
                    <span>Barangay</span>
                    <input
                      required
                      maxLength={100}
                      value={checkoutForm.barangay}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, barangay: event.target.value })
                      }
                    />
                  </label>

                  <label>
                    <span>City / Municipality</span>
                    <input
                      required
                      maxLength={100}
                      value={checkoutForm.city_municipality}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, city_municipality: event.target.value })
                      }
                    />
                  </label>

                  <label>
                    <span>Province</span>
                    <input
                      required
                      maxLength={100}
                      value={checkoutForm.province}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, province: event.target.value })
                      }
                    />
                  </label>

                  <label>
                    <span>Postal code</span>
                    <input
                      required
                      maxLength={20}
                      value={checkoutForm.postal_code}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, postal_code: event.target.value })
                      }
                    />
                  </label>

                  <label className="customer-checkout-wide">
                    <span>Delivery notes <small>(optional)</small></span>
                    <textarea
                      maxLength={500}
                      rows={3}
                      value={checkoutForm.delivery_notes}
                      onChange={(event) =>
                        setCheckoutForm({ ...checkoutForm, delivery_notes: event.target.value })
                      }
                      placeholder="Landmark, gate instructions, preferred receiving note..."
                    />
                  </label>
                </div>

                <div className="customer-checkout-answer">
                  <span>Is this the correct delivery information?</span>
                  <strong>Review the fields above, then continue.</strong>
                </div>

                <div className="customer-checkout-actions">
                  <button
                    type="button"
                    className="customer-secondary"
                    onClick={() => {
                      setCheckoutOpen(false);
                      setCartOpen(true);
                    }}
                  >
                    Back to cart
                  </button>

                  <button type="submit" className="customer-primary">
                    Yes, continue <Icon name="arrow" size={18} />
                  </button>
                </div>
              </form>
            )}

            {checkoutStep === "payment" && (
              <div className="customer-checkout-review">
                <div className="customer-payment-options">
                  <button
                    type="button"
                    className={`customer-payment-option ${
                      paymentMethod === "CASH_ON_DELIVERY" ? "is-selected" : ""
                    }`}
                    onClick={() => setPaymentMethod("CASH_ON_DELIVERY")}
                  >
                    <span className="customer-payment-radio" />
                    <div>
                      <strong>Cash on Delivery</strong>
                      <small>Pay when your order is delivered.</small>
                    </div>
                    <span className="customer-payment-available">AVAILABLE</span>
                  </button>

                  <button type="button" className="customer-payment-option" disabled>
                    <span className="customer-payment-radio" />
                    <div>
                      <strong>GCash</strong>
                      <small>Online payment integration will be added later.</small>
                    </div>
                    <span>COMING SOON</span>
                  </button>

                  <button type="button" className="customer-payment-option" disabled>
                    <span className="customer-payment-radio" />
                    <div>
                      <strong>Bank Transfer</strong>
                      <small>Payment verification will be added later.</small>
                    </div>
                    <span>COMING SOON</span>
                  </button>
                </div>

                <div className="customer-checkout-actions">
                  <button type="button" className="customer-secondary" onClick={() => setCheckoutStep("details")}>
                    Back
                  </button>
                  <button type="button" className="customer-primary" onClick={reviewPayment}>
                    Review order <Icon name="arrow" size={18} />
                  </button>
                </div>
              </div>
            )}

            {checkoutStep === "summary" && (
              <div className="customer-checkout-review">
                <div className="customer-checkout-review-grid">
                  <section className="customer-checkout-review-card">
                    <span className="customer-kicker">DELIVERY</span>
                    <h3>{checkoutForm.full_name}</h3>
                    <p>{checkoutForm.email}</p>
                    <p>{checkoutForm.contact_number}</p>
                    <p className="customer-checkout-address">
                      {checkoutForm.street_address}, {checkoutForm.barangay},{" "}
                      {checkoutForm.city_municipality}, {checkoutForm.province}{" "}
                      {checkoutForm.postal_code}
                    </p>
                    {checkoutForm.delivery_notes && (
                      <small>Note: {checkoutForm.delivery_notes}</small>
                    )}
                    <button type="button" className="customer-text-link" onClick={() => setCheckoutStep("details")}>
                      Edit delivery details
                    </button>
                  </section>

                  <section className="customer-checkout-review-card">
                    <span className="customer-kicker">PAYMENT</span>
                    <h3>Cash on Delivery</h3>
                    <p>Payment status will remain pending until the order is completed.</p>
                    <button type="button" className="customer-text-link" onClick={() => setCheckoutStep("payment")}>
                      Change payment method
                    </button>
                  </section>

                  <section className="customer-checkout-review-card customer-checkout-order-summary-card">
                    <span className="customer-kicker">ORDER SUMMARY</span>
                    <div className="customer-checkout-items">
                      {cartItems.map((item) => (
                        <div key={item.product_id}>
                          <span>{item.quantity} Ã— {item.name}</span>
                          <strong>{money(item.line_total)}</strong>
                        </div>
                      ))}
                    </div>
                    <div className="customer-checkout-total">
                      <span>Total</span>
                      <strong>{money(cartTotal)}</strong>
                    </div>
                  </section>
                </div>

                <div className="customer-checkout-confirm-note">
                  Final stock availability will be checked again when you confirm.
                </div>

                <div className="customer-checkout-actions">
                  <button type="button" className="customer-secondary" onClick={() => setCheckoutStep("payment")} disabled={busy}>
                    Back
                  </button>
                  <button type="button" className="customer-primary" onClick={checkout} disabled={busy}>
                    {previewMode ? "Preview confirmation" : busy ? "Placing order..." : "Confirm order"}
                    {!busy && <Icon name="check" size={18} />}
                  </button>
                </div>
              </div>
            )}

            {checkoutStep === "success" && (
              <div className="customer-checkout-success">
                <div className="customer-checkout-success-icon">
                  <Icon name="check" size={34} />
                </div>
                <span className="customer-kicker">
                  {previewMode ? "PREVIEW COMPLETE" : "ORDER CONFIRMED"}
                </span>
                <h3>
                  {previewMode
                    ? "Customer checkout preview completed"
                    : `Order #${placedOrder?.order_id} was placed successfully`}
                </h3>
                <p>
                  {previewMode
                    ? "No real order or payment was created while using Super Admin Customer View."
                    : "Your order is now pending and can be tracked from My Orders."}
                </p>

                <div className="customer-checkout-success-summary">
                  <div>
                    <span>Payment method</span>
                    <strong>Cash on Delivery</strong>
                  </div>
                  <div>
                    <span>Payment status</span>
                    <strong>{placedOrder?.payment_status ?? "PENDING"}</strong>
                  </div>
                  <div>
                    <span>Total</span>
                    <strong>{money(placedOrder?.total_amount ?? 0)}</strong>
                  </div>
                </div>

                <div className="customer-checkout-actions">
                  {!previewMode && (
                    <button
                      type="button"
                      className="customer-secondary"
                      onClick={() => {
                        closeCheckout();
                        setTab("shop");
                      }}
                    >
                      Continue shopping
                    </button>
                  )}
                  <button
                    type="button"
                    className="customer-primary"
                    onClick={() => {
                      closeCheckout();
                      if (previewMode) {
                        setNotice("Preview complete. No real order was created.");
                      } else {
                        setTab("orders");
                      }
                    }}
                  >
                    {previewMode ? "Close preview" : "View my orders"}
                  </button>
                </div>
              </div>
            )}
          </section>
        </div>
      )}
      {cartOpen && (
        <div className="customer-cart-layer">
          <button
            type="button"
            className="customer-cart-backdrop"
            onClick={() => setCartOpen(false)}
            aria-label="Close cart"
          />

          <aside className="customer-cart-drawer" aria-label="Shopping cart">
            <div className="customer-cart-head">
              <div>
                <span>YOUR CART</span>
                <h2>{cartCount} item{cartCount === 1 ? "" : "s"}</h2>
              </div>

              <button
                type="button"
                className="customer-icon-button"
                onClick={() => setCartOpen(false)}
                aria-label="Close cart"
              >
                <Icon name="close" />
              </button>
            </div>

            <div className="customer-cart-items">
              {cartItems.length ? (
                cartItems.map((item) => (
                  <article className="customer-cart-item" key={item.product_id}>
                    <div className="customer-cart-item-image">
                      {item.image_url ? (
                        <img src={item.image_url} alt={item.name} />
                      ) : (
                        <Icon name="products" size={26} />
                      )}
                    </div>

                    <div className="customer-cart-item-copy">
                      <strong>{item.name}</strong>
                      <span>{money(item.unit_price)}</span>
                      <small>{item.available_stock} available</small>

                      <div className="customer-qty">
                        <button
                          type="button"
                          onClick={() => setQty(item.product_id, item.quantity - 1)}
                          aria-label={`Decrease ${item.name} quantity`}
                        >
                          <Icon name="minus" size={15} />
                        </button>
                        <span>{item.quantity}</span>
                        <button
                          type="button"
                          className={
                            item.quantity >= item.available_stock
                              ? "is-at-limit"
                              : ""
                          }
                          onClick={() =>
                            setQty(item.product_id, item.quantity + 1)
                          }
                          aria-disabled={
                            item.quantity >= item.available_stock
                          }
                          aria-label={`Increase ${item.name} quantity`}
                        >
                          <Icon name="plus" size={15} />
                        </button>
                      </div>
                    </div>

                    <strong className="customer-cart-line-total">{money(item.line_total)}</strong>
                  </article>
                ))
              ) : (
                <EmptyState title="Your cart is empty" text="Add an available product to get started." />
              )}
            </div>

            <div className="customer-cart-footer">
              <div>
                <span>Subtotal</span>
                <strong>{money(cartTotal)}</strong>
              </div>
              <p>Final availability is rechecked when your order is submitted.</p>
              <button
                type="button"
                className="customer-primary customer-checkout-button"
                disabled={!cartItems.length || busy}
                onClick={startCheckout}
              >
                {previewMode
                  ? "Preview checkout"
                  : "Continue to checkout"}
                <Icon name="arrow" size={18} />
              </button>
            </div>
          </aside>
        </div>
      )}
    </div>
  );
}
