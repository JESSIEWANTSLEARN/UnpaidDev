import { useEffect, useState } from "react";
import { Link } from "react-router-dom";

// ================================
// CSS
// ================================
import "../css/LandingPage.css";

// ================================
// IMAGES
// Put these images inside:
// resources/js/assets/
// ================================
import Logo from "./assets/Logo.png";
import mainpic from "./assets/mainpic.jpg";
import PortableACUnits from "./assets/PortableACUnits.jpg";
import AirPurifier from "./assets/AirPurifier.jpg";
import ReplacementFilter from "./assets/ReplacementFilter.webp";
import SmartThermostat from "./assets/SmartThermostat.jpg";


// ==========================================
// PRODUCT CATEGORIES
// ==========================================

const services = [
  {
    imgSrc: PortableACUnits,
    title: "Portable AC Units",
  },

  {
    imgSrc: AirPurifier,
    title: "Air Purifiers",
  },

  {
    imgSrc: ReplacementFilter,
    title: "Replacement Filters",
  },

  {
    imgSrc: SmartThermostat,
    title: "Smart Thermostats",
  },
];


// ==========================================
// FEATURED PRODUCTS
// ==========================================

const products = [
  {
    imgSrc: PortableACUnits,
    name: "Portable AC Pro",
    description: "Cool rooms fast and quietly",
    price: "₱18,500",
  },

  {
    imgSrc: AirPurifier,
    name: "Air Purifier Plus",
    description: "Cleaner indoor air for your home",
    price: "₱12,900",
  },

  {
    imgSrc: ReplacementFilter,
    name: "Carbon Filter Pack",
    description: "High-efficiency replacement filter",
    price: "₱2,400",
  },

  {
    imgSrc: SmartThermostat,
    name: "Smart Thermostat",
    description: "Smart and energy-saving temperature control",
    price: "₱8,750",
  },
];


// ==========================================
// LANDING PAGE / HOMEPAGE
// ==========================================

function LandingPage() {

  // ========================================
  // CART
  // ========================================

  const [cartCount, setCartCount] = useState(0);


  // ========================================
  // COLLAPSIBLE SECTIONS
  // ========================================

  const [collapsed, setCollapsed] = useState({
    about: false,
    services: false,
    products: false,
  });


  // ========================================
  // SCROLL REVEAL ANIMATION
  // ========================================

  useEffect(() => {

    const revealItems =
      document.querySelectorAll(".reveal-up");


    const revealObserver =
      new IntersectionObserver(

        (entries) => {

          entries.forEach((entry) => {

            if (entry.isIntersecting) {

              const item = entry.target;

              item.classList.remove("is-visible");

              // Restart animation
              void item.offsetWidth;

              item.classList.add("is-visible");

            } else {

              entry.target.classList.remove(
                "is-visible"
              );
            }

          });

        },

        {
          threshold: 0.18,
          rootMargin: "0px 0px -8% 0px",
        }

      );


    revealItems.forEach((item) => {

      revealObserver.observe(item);

    });


    return () => {

      revealObserver.disconnect();

    };

  }, []);


  // ========================================
  // MOBILE COLLAPSIBLE SECTIONS
  // ========================================

  useEffect(() => {

    const handleResize = () => {

      if (window.innerWidth <= 640) {

        setCollapsed({
          about: true,
          services: true,
          products: true,
        });

      } else {

        setCollapsed({
          about: false,
          services: false,
          products: false,
        });

      }

    };


    handleResize();


    window.addEventListener(
      "resize",
      handleResize
    );


    return () => {

      window.removeEventListener(
        "resize",
        handleResize
      );

    };

  }, []);


  // ========================================
  // TOGGLE SECTION
  // ========================================

  const toggleSection = (section) => {

    setCollapsed((previous) => ({

      ...previous,

      [section]:
        !previous[section],

    }));

  };


  // ========================================
  // ADD TO CART
  // Temporary frontend cart counter.
  // Real cart database logic comes later.
  // ========================================

  const addToCart = () => {

    setCartCount(
      (count) => count + 1
    );

  };


  // ========================================
  // NEWSLETTER
  // Temporary frontend function.
  // ========================================

  const handleNewsletter = (event) => {

    event.preventDefault();

    alert(
      "Thank you for joining the WalangBrownout newsletter!"
    );

  };


  return (

    <div className="page-shell">


      {/* =====================================
          HEADER
      ===================================== */}

      <header className="site-header">

        <div className="max-width-container header-top">


          {/* BRAND */}

          <Link
            to="/"
            className="header-brand"
          >

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


          {/* USER ACTIONS */}

          <div className="nav-actions">


            {/* LOGIN */}

            <Link
              to="/login"
              className="nav-button"
            >
              Login
            </Link>


            {/* SIGN UP */}

            <Link
              to="/signup"
              className="nav-button primary-nav-button"
            >
              Sign Up
            </Link>


            {/* CART */}

            <button
              type="button"
              className="cart-button"
              aria-label={`Shopping cart with ${cartCount} items`}
            >
              Cart ({cartCount})
            </button>


          </div>

        </div>


        {/* =================================
            MAIN NAVIGATION
        ================================= */}

        <nav
          className="main-nav"
          aria-label="Main navigation"
        >

          <a href="#home">
            Home
          </a>

          <a href="#products">
            Products
          </a>

          <a href="#categories">
            Categories
          </a>

          <a href="#about">
            About
          </a>

        </nav>

      </header>


      {/* =====================================
          MAIN CONTENT
      ===================================== */}

      <main className="storefront">


        {/* =================================
            HERO
        ================================= */}

        <section
          id="home"
          className="promo-banner reveal-up"
        >


          <div className="promo-copy">

            <span className="promo-tag">
              Home Comfort Solutions
            </span>


            <h1>
              Comfort at home,
              all year round.
            </h1>


            <p>

              Walang BrownOut Appliances helps
              homes and businesses stay cool,
              clean, comfortable, and efficient
              with reliable portable AC units,
              air purifiers, replacement filters,
              and smart thermostats.

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
              alt="Walang BrownOut home comfort appliances"
            />

          </div>

        </section>


        {/* =================================
            ABOUT
        ================================= */}

        <section

          id="about"

          className={
            `categories-section ${
              collapsed.about
                ? "collapsed"
                : ""
            }`
          }

        >


          <div className="section-heading">


            <h2>
              About Walang BrownOut
            </h2>


            <button

              type="button"

              className="section-toggle"

              aria-expanded={
                !collapsed.about
              }

              aria-label="Toggle about section"

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
              regional distributor of home
              comfort products dedicated to
              providing dependable cooling,
              cleaner air, and smarter energy
              management solutions.

              We serve households, offices,
              businesses, and retail customers
              through a modern online ordering
              system connected to real-time
              inventory management.

            </p>

          </div>


        </section>


        {/* =================================
            PRODUCT CATEGORIES
        ================================= */}

        <section

          id="categories"

          className={
            `categories-section ${
              collapsed.services
                ? "collapsed"
                : ""
            }`
          }

        >


          <div className="section-heading">


            <h2>
              Shop by Category
            </h2>


            <a href="#products">
              View products
            </a>


            <button

              type="button"

              className="section-toggle"

              aria-expanded={
                !collapsed.services
              }

              aria-label="Toggle categories section"

              onClick={() =>
                toggleSection("services")
              }

            >

              <span className="chev">
                ▾
              </span>

            </button>


          </div>


          <div className="category-grid">


            {services.map(
              (service, index) => (

                <article

                  className={
                    `category-card reveal-up stagger-${
                      index + 1
                    }`
                  }

                  key={service.title}

                >


                  <div className="category-thumb">

                    <img
                      src={service.imgSrc}
                      alt={service.title}
                    />

                  </div>


                  <h3>
                    {service.title}
                  </h3>


                </article>

              )
            )}


          </div>

        </section>


        {/* =================================
            FEATURED PRODUCTS
        ================================= */}

        <section

          id="products"

          className={
            `products-section ${
              collapsed.products
                ? "collapsed"
                : ""
            }`
          }

        >


          <div className="section-heading">


            <h2>
              Featured Home Comfort Essentials
            </h2>


            <a href="#products">
              View all
            </a>


            <button

              type="button"

              className="section-toggle"

              aria-expanded={
                !collapsed.products
              }

              aria-label="Toggle products section"

              onClick={() =>
                toggleSection("products")
              }

            >

              <span className="chev">
                ▾
              </span>

            </button>


          </div>


          <div className="product-grid">


            {products.map(
              (product, index) => (

                <article

                  className={
                    `product-card reveal-up stagger-${
                      index + 1
                    }`
                  }

                  key={product.name}

                >


                  {/* PRODUCT IMAGE */}

                  <div className="product-image">

                    <img
                      src={product.imgSrc}
                      alt={product.name}
                    />

                  </div>


                  {/* PRODUCT INFORMATION */}

                  <div className="product-info">


                    <h3>
                      {product.name}
                    </h3>


                    <p>
                      {product.description}
                    </p>


                    <div className="product-meta">


                      <span className="price">
                        {product.price}
                      </span>


                      <button

                        type="button"

                        onClick={addToCart}

                      >
                        Add to Cart
                      </button>


                    </div>


                  </div>


                </article>

              )
            )}


          </div>

        </section>


        {/* =================================
            CUSTOMER BENEFITS
        ================================= */}

        <section className="promo-strip reveal-up">


          <div>

            <span className="promo-badge">
              Convenient ordering
            </span>

            <h3>
              Shop comfort products online
            </h3>

          </div>


          <div>

            <span className="promo-badge">
              Inventory connected
            </span>

            <h3>
              Product availability you can trust
            </h3>

          </div>


          <div>

            <span className="promo-badge">
              Order tracking
            </span>

            <h3>
              Monitor your order status
            </h3>

          </div>


        </section>


        {/* =================================
            NEWSLETTER
        ================================= */}

        <section className="newsletter-box reveal-up">


          <div>

            <span className="newsletter-tag">
              Newsletter
            </span>

            <h2>
              Stay updated with WalangBrownout.
            </h2>

          </div>


          <form

            className="newsletter-form"

            onSubmit={handleNewsletter}

          >


            <input

              type="email"

              placeholder="Enter your email"

              aria-label="Email address"

              required

            />


            <button type="submit">
              Join now
            </button>


          </form>


        </section>


      </main>


      {/* =====================================
          FOOTER
      ===================================== */}

      <footer className="site-footer reveal-up">


        <div className="footer-grid">


          {/* COMPANY DESCRIPTION */}

          <div className="footer-brand">


            <div className="brand">
              Walang BrownOut
            </div>


            <p>

              Home comfort appliances with
              online ordering connected to
              real-time inventory, helping
              customers find the products they
              need while reducing stockouts and
              unavailable orders.

            </p>


          </div>


          {/* SHOP */}

          <div className="footer-column">

            <h4>
              Shop
            </h4>

            <a href="#products">
              Featured Products
            </a>

            <a href="#categories">
              Categories
            </a>

            <a href="#products">
              Portable AC Units
            </a>

            <a href="#products">
              Air Purifiers
            </a>

          </div>


          {/* COMPANY */}

          <div className="footer-column">

            <h4>
              Company
            </h4>

            <a href="#about">
              About Us
            </a>

            <a href="#home">
              Home
            </a>

            <a href="#products">
              Products
            </a>

          </div>


          {/* ACCOUNT */}

          <div className="footer-column">

            <h4>
              Account
            </h4>

            <Link to="/login">
              Login
            </Link>

            <Link to="/signup">
              Create Account
            </Link>

          </div>


        </div>


        {/* FOOTER BOTTOM */}

        <div className="footer-bottom">


          <span>

            <strong>
              © 2026 WalangBrownOut.
            </strong>

            {" "}
            All rights reserved.

          </span>


          <div className="social-links">

            <a href="#">
              Facebook
            </a>

            <a href="#">
              Instagram
            </a>

            <a href="#">
              Contact
            </a>

          </div>


        </div>


      </footer>


    </div>

  );

}


export default LandingPage;