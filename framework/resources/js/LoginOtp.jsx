import React, { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import "../css/Otp.css";

const Logo = "/storage/site/Logo.png";

function csrfToken() {
  return (
    document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content") ?? ""
  );
}

async function readJson(response) {
  const text = await response.text();
  if (!text) return {};
  try {
    return JSON.parse(text);
  } catch {
    return { message: text };
  }
}

function LoginOtp() {
  const navigate = useNavigate();

  const [otp, setOtp] = useState("");
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [cooldown, setCooldown] = useState(0);

  const email =
    sessionStorage.getItem("wbo_login_email") || "your registered email";

  useEffect(() => {
    if (cooldown <= 0) return;

    const timer = window.setInterval(() => {
      setCooldown((value) => Math.max(0, value - 1));
    }, 1000);

    return () => window.clearInterval(timer);
  }, [cooldown]);

  const handleOtpChange = (event) => {
    const value = event.target.value.replace(/\D/g, "").slice(0, 6);
    setOtp(value);
  };

  const verifyOtp = async (event) => {
    event.preventDefault();
    setError("");
    setMessage("");

    if (!/^\d{6}$/.test(otp)) {
      setError("OTP must contain exactly 6 digits.");
      return;
    }

    try {
      setLoading(true);

      const response = await fetch("/login/verify-otp", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-TOKEN": csrfToken(),
        },
        body: JSON.stringify({ otp }),
      });

      const data = await readJson(response);

      if (!response.ok || data.success === false) {
        setError(data.message || "Unable to verify the OTP.");
        if (data.redirect) {
          window.setTimeout(() => navigate(data.redirect), 900);
        }
        return;
      }

      sessionStorage.removeItem("wbo_login_email");
      setMessage(data.message || "Login successful.");

      window.setTimeout(() => {
        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          navigate("/");
        }
      }, 600);
    } catch {
      setError("Unable to connect to the server. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const resendOtp = async () => {
    setError("");
    setMessage("");

    try {
      setResending(true);

      const response = await fetch("/login/resend-otp", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-TOKEN": csrfToken(),
        },
        body: JSON.stringify({}),
      });

      const data = await readJson(response);

      if (!response.ok || data.success === false) {
        setError(data.message || "Unable to resend the OTP.");

        if (Number(data.seconds_remaining) > 0) {
          setCooldown(Number(data.seconds_remaining));
        }

        if (data.redirect) {
          window.setTimeout(() => navigate(data.redirect), 900);
        }

        return;
      }

      setOtp("");
      setCooldown(30);
      setMessage(data.message || "A new OTP has been sent.");
    } catch {
      setError("Unable to connect to the server. Please try again.");
    } finally {
      setResending(false);
    }
  };

  return (
    <div className="otp-page">
      <header className="otp-header">
        <div className="otp-header-inner">
          <Link to="/" className="otp-brand">
            <img src={Logo} alt="Walang Brown Out Logo" />

            <div className="otp-brand-text">
              <span>Republic of the Philippines</span>
              <strong>WALANG BROWN OUT</strong>
            </div>
          </Link>
        </div>
      </header>

      <main className="otp-container">
        <Link to="/login" className="otp-back">
          ← Back to Login
        </Link>

        <div className="otp-title">
          <h2>Verify Your Login</h2>
          <p>
            We sent a 6-digit verification code to
            <br />
            <strong>{email}</strong>
          </p>
        </div>

        {error && <div className="otp-error">{error}</div>}
        {message && <div className="otp-message">{message}</div>}

        <form className="otp-form" onSubmit={verifyOtp}>
          <label htmlFor="otp">Enter OTP Code</label>

          <input
            id="otp"
            className="otp-input"
            type="text"
            inputMode="numeric"
            autoComplete="one-time-code"
            placeholder="000000"
            value={otp}
            onChange={handleOtpChange}
            disabled={loading}
            maxLength={6}
            required
          />

          <button
            className="otp-primary"
            type="submit"
            disabled={loading || otp.length !== 6}
          >
            {loading ? "Verifying..." : "Verify OTP"}
          </button>
        </form>

        <div className="otp-info">
          OTP expires after 5 minutes.
          <br />
          You may resend the OTP a maximum of 2 times.
        </div>

        <div className="otp-resend">
          <button
            className="otp-secondary"
            type="button"
            onClick={resendOtp}
            disabled={resending || cooldown > 0}
          >
            {resending
              ? "Sending..."
              : cooldown > 0
                ? `Resend OTP in ${cooldown}s`
                : "Resend OTP"}
          </button>
        </div>
      </main>

      <footer className="otp-footer">
        <strong>© 2026 WalangBrownOut.</strong> All rights reserved.
      </footer>
    </div>
  );
}

export default LoginOtp;
