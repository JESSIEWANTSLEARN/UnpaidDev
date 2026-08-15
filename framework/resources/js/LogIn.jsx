import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import "../css/LogIn.css";

const Logo =
    "/storage/site/Logo.png";

function LogIn() {

    const navigate =
        useNavigate();

    const [email, setEmail] =
        useState("");

    const [password, setPassword] =
        useState("");

    const [error, setError] =
        useState("");

    const [loading, setLoading] =
        useState(false);


    const handleSubmit =
        async (event) => {

        event.preventDefault();

        setError("");

        if (!email || !password) {

            setError(
                "Please enter your email and password."
            );

            return;
        }


        try {

            setLoading(true);

            const csrfToken =
                document
                    .querySelector(
                        'meta[name="csrf-token"]'
                    )
                    ?.getAttribute(
                        "content"
                    );


            const response =
                await fetch(
                    "/login",
                    {

                        method: "POST",

                        credentials:
                            "same-origin",

                        headers: {

                            "Content-Type":
                                "application/json",

                            "Accept":
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrfToken ?? ""

                        },

                        body:
                            JSON.stringify({
                                email,
                                password
                            })

                    }
                );


            const result =
                await response
                    .json()
                    .catch(() => ({}));


            if (!response.ok) {

                setError(
                    result.message ??
                    "Invalid email or password."
                );

                return;
            }


            navigate(
                "/login-otp"
            );

        }

        catch (error) {

            console.error(error);

            setError(
                "Unable to connect to the server."
            );

        }

        finally {

            setLoading(false);

        }

    };


    return (

        <div className="auth-page">

            <header className="auth-header">

                <Link
                    to="/"
                    className="auth-brand"
                >

                    <img
                        src={Logo}
                        alt="Walang Brown Out Logo"
                    />

                    <div>

                        <span className="auth-republic">
                            Republic of the Philippines
                        </span>

                        <strong className="auth-brand-title">
                            WALANG BROWN OUT
                        </strong>

                    </div>

                </Link>

                <div className="auth-header-bar" />

            </header>


            <main className="auth-main">

                <section className="auth-card">

                    <div className="auth-title">

                        <h1>
                            Welcome Back
                        </h1>

                        <p>
                            Sign in to access your account portal
                        </p>

                    </div>


                    {error && (

                        <div className="auth-error">
                            {error}
                        </div>

                    )}


                    <form
                        className="auth-form"
                        onSubmit={handleSubmit}
                    >

                        <div className="auth-field">

                            <label>
                                Email Address
                            </label>

                            <input
                                type="email"
                                placeholder="name@example.com"
                                value={email}
                                onChange={
                                    (event) =>
                                        setEmail(
                                            event.target.value
                                        )
                                }
                                required
                            />

                        </div>


                        <div className="auth-field">

                            <label>
                                Password
                            </label>

                            <input
                                type="password"
                                placeholder="••••••••"
                                value={password}
                                onChange={
                                    (event) =>
                                        setPassword(
                                            event.target.value
                                        )
                                }
                                required
                            />

                        </div>


                        <button
                            type="submit"
                            className="auth-primary-button"
                            disabled={loading}
                        >

                            {
                                loading
                                    ? "Sending OTP..."
                                    : "Continue to OTP"
                            }

                        </button>


                        <p className="auth-account-link">

                            Sign up if you don't have an account{" "}

                            <Link to="/signup">
                                Create Account
                            </Link>

                        </p>

                    </form>

                </section>

            </main>


            <footer className="auth-footer">

                <strong>
                    © 2026 WalangBrownOut.
                </strong>

                {" "}All rights reserved.

            </footer>

        </div>

    );

}