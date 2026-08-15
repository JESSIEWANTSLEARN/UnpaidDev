import React, {
    useEffect,
    useState
} from "react";

import {
    Link,
    useNavigate
} from "react-router-dom";

import "../css/Signup.css";

const Logo =
    "/storage/site/Logo.png";


function LoginOtp() {

    const navigate =
        useNavigate();

    const [email, setEmail] =
        useState("");

    const [otp, setOtp] =
        useState("");

    const [error, setError] =
        useState("");

    const [success, setSuccess] =
        useState("");

    const [loading, setLoading] =
        useState(false);

    const [resending, setResending] =
        useState(false);


    useEffect(() => {

        async function loadStatus() {

            try {

                const response =
                    await fetch(
                        "/api/login-otp/status",
                        {

                            credentials:
                                "same-origin",

                            headers: {
                                Accept:
                                    "application/json"
                            }

                        }
                    );


                const result =
                    await response
                        .json()
                        .catch(() => ({}));


                if (!response.ok) {

                    navigate(
                        "/login",
                        {
                            replace: true
                        }
                    );

                    return;
                }


                setEmail(
                    result.email ?? ""
                );

            }

            catch (error) {

                console.error(error);

                setError(
                    "Unable to load OTP session."
                );

            }

        }


        loadStatus();

    }, [navigate]);


    const handleVerify =
        async (event) => {

        event.preventDefault();

        setError("");
        setSuccess("");


        if (
            !/^[0-9]{6}$/.test(otp)
        ) {

            setError(
                "OTP must contain exactly 6 digits."
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
                    "/login-otp/verify",
                    {

                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        headers: {

                            "Content-Type":
                                "application/json",

                            Accept:
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrfToken ?? ""

                        },

                        body:
                            JSON.stringify({
                                otp
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
                    "Invalid OTP code."
                );

                return;

            }


            window.location.href =
                result.redirect ?? "/";

        }

        catch (error) {

            console.error(error);

            setError(
                "Unable to verify OTP."
            );

        }

        finally {

            setLoading(false);

        }

    };


    const handleResend =
        async () => {

        setError("");
        setSuccess("");


        try {

            setResending(true);


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
                    "/login-otp/resend",
                    {

                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        headers: {

                            Accept:
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrfToken ?? ""

                        }

                    }
                );


            const result =
                await response
                    .json()
                    .catch(() => ({}));


            if (!response.ok) {

                setError(
                    result.message ??
                    "Unable to resend OTP."
                );

                return;

            }


            setSuccess(
                result.message
            );

        }

        catch (error) {

            console.error(error);

            setError(
                "Unable to resend OTP."
            );

        }

        finally {

            setResending(false);

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
                            OTP Verification
                        </h1>

                        <p>

                            We sent a 6-digit verification code to

                            <br />

                            <strong className="auth-email">
                                {email}
                            </strong>

                        </p>

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


                    <form
                        onSubmit={handleVerify}
                        className="auth-form"
                    >

                        <div className="auth-field">

                            <label>
                                Enter OTP Code
                            </label>

                            <input
                                className="otp-input"
                                type="text"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                placeholder="000000"
                                maxLength="6"
                                value={otp}
                                onChange={
                                    (event) =>
                                        setOtp(
                                            event.target.value
                                                .replace(
                                                    /\D/g,
                                                    ""
                                                )
                                                .slice(
                                                    0,
                                                    6
                                                )
                                        )
                                }
                                autoFocus
                                required
                            />

                        </div>


                        <button
                            className="auth-primary-button"
                            type="submit"
                            disabled={loading}
                        >

                            {
                                loading
                                    ? "Verifying..."
                                    : "Verify OTP"
                            }

                        </button>

                    </form>


                    <div className="otp-information">

                        OTP expires after 5 minutes.

                        <br />

                        You may resend the OTP a maximum of 2 times.

                    </div>


                    <div className="auth-resend">

                        <span>
                            Didn't receive the code?
                        </span>

                        <button
                            type="button"
                            onClick={handleResend}
                            disabled={resending}
                        >

                            {
                                resending
                                    ? "Sending..."
                                    : "Resend OTP"
                            }

                        </button>

                    </div>


                    <div className="auth-back">

                        <Link to="/login">
                            ← Back to Login
                        </Link>

                    </div>

                </section>

            </main>

        </div>

    );

}

export default LoginOtp;