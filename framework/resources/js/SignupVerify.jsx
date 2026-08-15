import React, { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import "../css/Signup.css";

const Logo = "/storage/site/Logo.png";

function LoginOtp() {
    const navigate = useNavigate();

    const [email, setEmail] = useState("");

    const [otp, setOtp] = useState("");

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [loading, setLoading] = useState(false);
    const [resending, setResending] = useState(false);


    useEffect(() => {

        const loadOtpStatus = async () => {

            try {

                const response = await fetch(
                    "/api/login-otp/status",
                    {
                        headers: {
                            Accept: "application/json",
                        },

                        credentials: "same-origin",
                    }
                );

                const result = await response.json();

                if (!response.ok) {
                    navigate("/login");
                    return;
                }

                setEmail(result.email ?? "");

            } catch (error) {

                console.error(error);

            }

        };

        loadOtpStatus();

    }, [navigate]);


    const handleOtpChange = (event) => {

        const value = event.target.value
            .replace(/\D/g, "")
            .slice(0, 6);

        setOtp(value);
    };


    const verifyOtp = async (event) => {

        event.preventDefault();

        setError("");
        setSuccess("");

        if (!/^\d{6}$/.test(otp)) {
            setError(
                "OTP must contain exactly 6 digits."
            );

            return;
        }

        try {

            setLoading(true);

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            const response = await fetch(
                "/login-otp/verify",
                {
                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken ?? "",
                    },

                    credentials: "same-origin",

                    body: JSON.stringify({
                        otp,
                    }),
                }
            );

            const result = await response.json();

            if (!response.ok) {

                setError(
                    result.message ??
                    "Invalid OTP code."
                );

                return;
            }

            /*
             * Role/dashboard migration is intentionally
             * separate from authentication migration.
             */
            window.location.href =
                result.redirect ?? "/";

        } catch (error) {

            console.error(error);

            setError(
                "Unable to verify OTP. Please try again."
            );

        } finally {

            setLoading(false);

        }
    };


    const resendOtp = async () => {

        setError("");
        setSuccess("");

        try {

            setResending(true);

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            const response = await fetch(
                "/login-otp/resend",
                {
                    method: "POST",

                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken ?? "",
                    },

                    credentials: "same-origin",
                }
            );

            const result = await response.json();

            if (!response.ok) {

                setError(
                    result.message ??
                    "Unable to resend OTP."
                );

                return;
            }

            setSuccess(result.message);

        } catch (error) {

            console.error(error);

            setError(
                "Unable to resend OTP. Please try again."
            );

        } finally {

            setResending(false);

        }
    };


    return (
        <div className="auth-page">

            <header className="auth-header">

                <div className="auth-header-content">

                    <Link to="/" className="auth-brand">

                        <img
                            src={Logo}
                            alt="Walang Brown Out Logo"
                            width="45"
                            height="45"
                        />

                        <div>

                            <span className="auth-republic">
                                Republic of the Philippines
                            </span>

                            <div className="auth-brand-title">
                                WALANG BROWN OUT
                            </div>

                        </div>

                    </Link>

                </div>

                <div className="auth-header-bar"></div>

            </header>


            <main className="auth-main">

                <div className="auth-card">

                    <div className="auth-title">

                        <div className="otp-icon">
                            🔑
                        </div>

                        <h1>
                            OTP Verification
                        </h1>

                        <p>
                            We sent a 6-digit verification
                            code to
                        </p>

                        <div className="auth-email">
                            {email}
                        </div>

                    </div>


                    {error && (
                        <div className="auth-error">
                            {error}
                        </div>
                    )}


                    {success && (
                        <div className="auth-success">
                            {success}
                        </div>
                    )}


                    <form onSubmit={verifyOtp}>

                        <div className="auth-field">

                            <label htmlFor="login-otp">
                                6-Digit Verification Code
                            </label>

                            <input
                                id="login-otp"
                                type="text"
                                className="otp-input"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                placeholder="000000"
                                maxLength="6"
                                value={otp}
                                onChange={handleOtpChange}
                                autoFocus
                            />

                        </div>


                        <button
                            type="submit"
                            className="auth-primary-button"
                            disabled={loading}
                        >
                            {loading
                                ? "Verifying..."
                                : "Verify OTP"}
                        </button>

                    </form>


                    <div className="otp-information">

                        OTP expires after 5 minutes.

                        <br />

                        You may resend the OTP
                        a maximum of 2 times.

                    </div>


                    <div className="auth-resend">

                        <span>
                            Didn't receive the code?
                        </span>

                        <button
                            type="button"
                            onClick={resendOtp}
                            disabled={resending}
                        >
                            {resending
                                ? "Sending..."
                                : "Resend OTP"}
                        </button>

                    </div>


                    <div className="auth-back">

                        <Link to="/login">
                            ← Back to Login
                        </Link>

                    </div>

                </div>

            </main>


            <footer className="auth-footer">
                <strong>
                    © 2026 WalangBrownOut.
                </strong>{" "}
                All rights reserved.
            </footer>

        </div>
    );
}

export default LoginOtp;