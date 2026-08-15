import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import "../css/LandingPage.css";

const Logo = "/storage/site/Logo.png";
const mainpic = "/storage/site/mainpic.jpg";

const PortableACUnits =
  "/storage/products/PortableAcUnits.jpg";

const AirPurifier =
  "/storage/products/AirPurifier.jpg";

const ReplacementFilter =
  "/storage/products/ReplacementFilter.webp";

const SmartThermostat =
  "/storage/products/SmartThermostat.jpg";

  // =====================================================
  // LOAD PRODUCTS FROM LARAVEL
  // =====================================================

  useEffect(() => {
    const loadProducts = async () => {
      try {
        setLoading(true);
        setProductError("");

        const response = await fetch("/api/store/products", {
          headers: {
            Accept: "application/json",
          },
        });

        if (!response.ok) {
          throw new Error("Unable to load products.");
        }

        const data = await response.json();

        if (!data.success) {
          throw new Error(
            data.message || "Unable to load products."
          );
        }

        setProducts(data.products || []);
      } catch (error) {
        console.error("Product loading error:", error);

        setProductError(
          "Products are temporarily unavailable."
        );
      } finally {
        setLoading(false);
      }
    };

    loadProducts();
  }, []);

  // =====================================================
  // REVEAL ANIMATION
  // =====================================================

  useEffect(() => {
    const revealItems =
      document.querySelectorAll(".reveal-up");

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
          }
        });
      },
      {
        threshold: 0.18,
      }
    );

    revealItems.forEach((item) => {
      observer.observe(item);
    });

    return () => observer.disconnect();
  }, [products]);

  // =====================================================
  // MOBILE SECTIONS
  // =====================================================

  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth <= 640) {
        setCollapsed({
          about: true,
          products: true,
        });
      } else {
        setCollapsed({
          about: false,
          products: false,
        });
      }
    };

    handleResize();

    window.addEventListener("resize", handleResize);

    return () => {
      window.removeEventListener(
        "resize",
        handleResize
      );
    };
  }, []);

  const toggleSection = (section) => {
    setCollapsed((previous) => ({
      ...previous,
      [section]: !previous[section],
    }));
  };

  // =====================================================
  // TEMPORARY CART COUNTER
  // Real order/cart logic comes later.
  // =====================================================

  const addToCart = (product) => {
    if (product.available_stock <= 0) {
      return;
    }

    setCartCount((count) => count + 1);
  };

  // =====================================================
  // PRICE FORMAT
  // =====================================================

  const formatPrice = (price) => {
    return new Intl.NumberFormat("en-PH", {
      style: "currency",
      currency: "PHP",
    }).format(price);
  };

  return (
    <div className="page-shell">

      {/* =================================================
          HEADER
      ================================================= */}

      <header className="site-header">
        <div className="max-width-container header-top">

          <Link to="/" className="header-brand">
            <img
              src={Logo}
              alt="Walang Brown Out Logo"
              width="45"
              height="45"
            />

            <div className="brand-text">
              <span className="republic-text">
                Republic of the Philippines
              </span>

              <div className="brand-title">
                WALANG BROWN OUT
              </div>
            </div>
          </Link>

          <div className="nav-actions">
            <Link
              to="/login"
              className="nav-button"
            >
              Login
            </Link>

            <Link
              to="/signup"
              className="nav-button primary-nav-button"
            >
              Sign Up
            </Link>

            <button
              type="button"
              className="cart-button"
            >
              Cart ({cartCount})
            </button>
          </div>
        </div>

        <nav
          className="main-nav"
          aria-label="Main navigation"
        >
          <a href="#home">Home</a>

          <a href="#products">
            Products
          </a>

          <a href="#about">
            About
          </a>
        </nav>
      </header>

      {/* =================================================
          MAIN
      ================================================= */}

      <main className="storefront">

        {/* =================================================
            HERO
        ================================================= */}

        <section
          id="home"
          className="promo-banner reveal-up"
        >
          <div className="promo-copy">
            <span className="promo-tag">
              Home Comfort Solutions
            </span>

            <h1>
              Comfort at home, all year round.
            </h1>

            <p>
              Walang BrownOut Appliances provides
              dependable cooling, cleaner air and
              smart home-comfort solutions for
              households and businesses.
            </p>

            <div className="promo-actions">
              <a
                href="#products"
                className="primary-link"
              >
                Shop Products
              </a>

              <Link
                to="/signup"
                className="secondary-link"
              >
                Create Account
              </Link>
            </div>
          </div>

          <div className="promo-visual">
            <img
              src={mainpic}
              alt="Walang BrownOut Appliances"
            />
          </div>
        </section>

        {/* =================================================
            ABOUT
        ================================================= */}

        <section
          id="about"
          className={`categories-section ${
            collapsed.about ? "collapsed" : ""
          }`}
        >
          <div className="section-heading">
            <h2>
              About Walang BrownOut
            </h2>

            <button
              type="button"
              className="section-toggle"
              aria-expanded={!collapsed.about}
              onClick={() =>
                toggleSection("about")
              }
            >
              <span className="chev">
                ▾
              </span>
            </button>
          </div>

          <div className="about-box reveal-up">
            <p>
              Walang BrownOut Appliances is a
              regional distributor of home comfort
              products including portable AC units,
              air purifiers, replacement filters and
              smart thermostats.
            </p>

            <p>
              Our online storefront is connected to
              the inventory system so product
              information and availability can be
              based on actual warehouse data.
            </p>
          </div>
        </section>

        {/* =================================================
            PRODUCTS
        ================================================= */}

        <section
          id="products"
          className={`products-section ${
            collapsed.products ? "collapsed" : ""
          }`}
        >
          <div className="section-heading">
            <h2>
              Featured Products
            </h2>

            <button
              type="button"
              className="section-toggle"
              aria-expanded={!collapsed.products}
              onClick={() =>
                toggleSection("products")
              }
            >
              <span className="chev">
                ▾
              </span>
            </button>
          </div>

          {/* LOADING */}

          {loading && (
            <div className="product-status">
              Loading products...
            </div>
          )}

          {/* ERROR */}

          {!loading && productError && (
            <div className="product-status product-error">
              {productError}
            </div>
          )}

          {/* NO PRODUCTS */}

          {!loading &&
            !productError &&
            products.length === 0 && (
              <div className="product-status">
                No products are currently available.
              </div>
            )}

          {/* PRODUCT GRID */}

          {!loading &&
            !productError &&
            products.length > 0 && (
              <div className="product-grid">

                {products.map(
                  (product, index) => {

                    const outOfStock =
                      product.available_stock <= 0;

                    return (
                      <article
                        key={product.product_id}
                        className={`product-card reveal-up stagger-${
                          index + 1
                        }`}
                      >

                        {/* IMAGE */}

                        <div className="product-image">
                          {product.image_url ? (
                            <img
                              src={product.image_url}
                              alt={product.name}
                            />
                          ) : (
                            <div className="no-product-image">
                              No Image
                            </div>
                          )}
                        </div>

                        {/* INFORMATION */}

                        <div className="product-info">

                          <span className="product-category">
                            {product.category}
                          </span>

                          <h3>
                            {product.name}
                          </h3>

                          <p>
                            {product.description}
                          </p>

                          {/* STOCK */}

                          <div className="stock-information">
                            {outOfStock ? (
                              <span className="out-of-stock">
                                Out of Stock
                              </span>
                            ) : (
                              <span className="in-stock">
                                {product.available_stock}{" "}
                                available
                              </span>
                            )}
                          </div>

                          {/* PRICE + CART */}

                          <div className="product-meta">

                            <span className="price">
                              {formatPrice(
                                product.price
                              )}
                            </span>

                            <button
                              type="button"
                              disabled={outOfStock}
                              onClick={() =>
                                addToCart(product)
                              }
                            >
                              {outOfStock
                                ? "Unavailable"
                                : "Add to Cart"}
                            </button>

                          </div>
                        </div>
                      </article>
                    );
                  }
                )}

              </div>
            )}
        </section>

        {/* =================================================
            BENEFITS
        ================================================= */}

        <section className="promo-strip reveal-up">

          <div>
            <span className="promo-badge">
              Real Inventory
            </span>

            <h3>
              Availability connected to stock
            </h3>
          </div>

          <div>
            <span className="promo-badge">
              Easy Ordering
            </span>

            <h3>
              Order home-comfort products online
            </h3>
          </div>

          <div>
            <span className="promo-badge">
              Reliable Supply
            </span>

            <h3>
              Inventory replenishment monitoring
            </h3>
          </div>

        </section>

      </main>

      {/* =================================================
          FOOTER
      ================================================= */}

      <footer className="site-footer reveal-up">

        <div className="footer-grid">

          <div className="footer-brand">
            <div className="brand">
              Walang BrownOut
            </div>

            <p>
              Home comfort products backed by
              integrated warehouse and inventory
              management.
            </p>
          </div>

          <div className="footer-column">
            <h4>Shop</h4>

            <a href="#products">
              Products
            </a>
          </div>

          <div className="footer-column">
            <h4>Company</h4>

            <a href="#about">
              About Us
            </a>

            <a href="#home">
              Home
            </a>
          </div>

          <div className="footer-column">
            <h4>Account</h4>

            <Link to="/login">
              Login
            </Link>

            <Link to="/signup">
              Create Account
            </Link>
          </div>

        </div>

        <div className="footer-bottom">
          <span>
            <strong>
              © 2026 WalangBrownOut.
            </strong>{" "}
            All rights reserved.
          </span>
        </div>

      </footer>

    </div>
  );
}

export default LandingPage;