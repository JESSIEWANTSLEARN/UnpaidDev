import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";

// If you have Signup.css inside resources/css:
import "../css/Signup.css";

// Change this path if your Logo.png is somewhere else
import Logo from "./assets/Logo.png";


function Signup() {

  const navigate = useNavigate();


  // ==========================================
  // FORM DATA
  // ==========================================

  const [formData, setFormData] = useState({

    name: "",

    email: "",

    contactNumber: "",

    password: "",

    confirmPassword: "",

  });


  // ==========================================
  // PAGE STATE
  // ==========================================

  const [error, setError] = useState("");

  const [loading, setLoading] = useState(false);


  // ==========================================
  // HANDLE INPUT CHANGE
  // ==========================================

  const handleChange = (event) => {

    const {
      name,
      value,
    } = event.target;


    setFormData((previous) => ({

      ...previous,

      [name]: value,

    }));

  };


  // ==========================================
  // HANDLE SIGNUP
  // ==========================================

  const handleSubmit = async (event) => {

    event.preventDefault();

    setError("");


    const {

      name,

      email,

      contactNumber,

      password,

      confirmPassword,

    } = formData;


    // ========================================
    // CLIENT-SIDE VALIDATION
    // ========================================

    if (
      !name ||
      !email ||
      !contactNumber ||
      !password ||
      !confirmPassword
    ) {

      setError(
        "Please complete all required fields."
      );

      return;

    }


    // EMAIL

    const emailRegex =
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


    if (!emailRegex.test(email)) {

      setError(
        "Please enter a valid email address."
      );

      return;

    }


    // CONTACT NUMBER

    const contactRegex =
      /^[0-9+\-\s]{7,20}$/;


    if (!contactRegex.test(contactNumber)) {

      setError(
        "Please enter a valid contact number."
      );

      return;

    }


    // PASSWORD

    if (password.length < 6) {

      setError(
        "Password must be at least 6 characters long."
      );

      return;

    }


    // CONFIRM PASSWORD

    if (password !== confirmPassword) {

      setError(
        "Passwords do not match."
      );

      return;

    }


    // ========================================
    // SEND TO LARAVEL
    // ========================================

    try {

      setLoading(true);


      // Get Laravel CSRF token from react.blade.php

      const csrfToken =
        document
          .querySelector(
            'meta[name="csrf-token"]'
          )
          ?.getAttribute("content");


      const response =
        await fetch(
          "/register",
          {

            method: "POST",

            headers: {

              "Content-Type":
                "application/json",

              Accept:
                "application/json",

              "X-CSRF-TOKEN":
                csrfToken ?? "",

            },

            credentials:
              "same-origin",


            body: JSON.stringify({

              name: name,

              email: email,

              contact_number:
                contactNumber,

              password:
                password,

              password_confirmation:
                confirmPassword,

            }),

          }
        );


      const result =
        await response.json();


      // ======================================
      // VALIDATION / SERVER ERROR
      // ======================================

      if (!response.ok) {

        if (result.errors) {

          const firstError =
            Object.values(
              result.errors
            )[0]?.[0];


          setError(
            firstError ??
              "Please check your information."
          );

        } else {

          setError(
            result.message ??
              "Unable to create your account."
          );

        }


        return;

      }


      // ======================================
      // SUCCESS
      // ======================================

      navigate(
        "/signup-verify"
      );


    } catch (error) {

      console.error(
        "Signup request failed:",
        error
      );


      setError(
        "Unable to connect to the server. Please try again."
      );


    } finally {

      setLoading(false);

    }

  };


  // ==========================================
  // PAGE
  // ==========================================

  return (

    <div className="signup-page">


      {/* =====================================
          HEADER
      ===================================== */}

      <header className="signup-header">

        <div className="signup-header-inner">


          <Link
            to="/"
            className="signup-brand"
          >

            <img
              src={Logo}
              alt="Walang Brown Out Logo"
              width="45"
              height="45"
            />


            <div className="signup-brand-text">

              <span>
                Republic of the Philippines
              </span>

              <strong>
                WALANG BROWN OUT
              </strong>

            </div>

          </Link>


        </div>

      </header>


      {/* =====================================
          SIGNUP FORM
      ===================================== */}

      <main className="signup-container">


        <Link
          to="/"
          className="back-button"
        >
          ← Back to Home
        </Link>


        <div className="signup-title">

          <h2>
            Create an Account
          </h2>

          <p>
            Create your WalangBrownout customer account
          </p>

        </div>


        {/* ERROR MESSAGE */}

        {error && (

          <div className="signup-error">

            {error}

          </div>

        )}


        <form
          className="signup-form"
          onSubmit={handleSubmit}
        >


          {/* FULL NAME */}

          <div className="form-group">

            <label htmlFor="name">
              Full Name
            </label>

            <input
              id="name"
              type="text"
              name="name"
              required
              autoComplete="name"
              placeholder="Juan Dela Cruz"
              value={formData.name}
              onChange={handleChange}
            />

          </div>


          {/* EMAIL */}

          <div className="form-group">

            <label htmlFor="email">
              Email Address
            </label>

            <input
              id="email"
              type="email"
              name="email"
              required
              autoComplete="email"
              placeholder="name@example.com"
              value={formData.email}
              onChange={handleChange}
            />

          </div>


          {/* CONTACT */}

          <div className="form-group">

            <label htmlFor="contactNumber">
              Contact Number
            </label>

            <input
              id="contactNumber"
              type="tel"
              name="contactNumber"
              required
              autoComplete="tel"
              placeholder="09123456789"
              value={formData.contactNumber}
              onChange={handleChange}
            />

          </div>


          {/* PASSWORD */}

          <div className="form-group">

            <label htmlFor="password">
              Password
            </label>

            <input
              id="password"
              type="password"
              name="password"
              required
              minLength="6"
              autoComplete="new-password"
              placeholder="••••••••"
              value={formData.password}
              onChange={handleChange}
            />

          </div>


          {/* CONFIRM PASSWORD */}

          <div className="form-group">

            <label htmlFor="confirmPassword">
              Confirm Password
            </label>

            <input
              id="confirmPassword"
              type="password"
              name="confirmPassword"
              required
              minLength="6"
              autoComplete="new-password"
              placeholder="••••••••"
              value={formData.confirmPassword}
              onChange={handleChange}
            />

          </div>


          {/* SUBMIT */}

          <button
            className="signup-button"
            type="submit"
            disabled={loading}
          >

            {
              loading
                ? "Creating Account..."
                : "Create Account"
            }

          </button>


          <p className="login-text">

            Already have an account?{" "}

            <Link to="/login">
              Login here
            </Link>

          </p>


        </form>

      </main>


      {/* =====================================
          FOOTER
      ===================================== */}

      <footer className="signup-footer">

        <strong>
          © 2026 WalangBrownOut.
        </strong>

        {" "}
        All rights reserved.

      </footer>


    </div>

  );

}


export default Signup;