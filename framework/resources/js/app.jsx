import React, { useEffect, useRef, useState } from "react";

import { createRoot } from "react-dom/client";

import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";

import LandingPage from "./LandingPage.jsx";
import LogIn from "./LogIn.jsx";
import Signup from "./Signup.jsx";
import LoginOtp from "./LoginOtp.jsx";
import SignupVerify from "./SignupVerify.jsx";

import SystemUser from "./pages/users/SystemUser.jsx";
import SuperAdmin from "./pages/SuperAdmin.jsx";

import PresenceTracker from "./components/PresenceTracker.jsx";

import "../css/AuthTransitions.css";

/* =========================================================
   AUTH PAGE ORDER

   0 = Home
   1 = Login
   2 = Signup

   Higher number = forward
   Lower number = backward
   ========================================================= */

const AUTH_PAGE_ORDER = {
    "/": 0,
    "/login": 1,
    "/signup": 2,
};

const AUTH_TRANSITION_PATHS = new Set(["/", "/login", "/signup"]);

/* =========================================================
   ANIMATED ROUTES
   ========================================================= */

function AnimatedRoutes() {
    const location = useLocation();

    /* ---------------------------------------------------------
       The location currently displayed on screen.
       --------------------------------------------------------- */

    const [displayLocation, setDisplayLocation] = useState(location);

    /* ---------------------------------------------------------
       The next route waiting to appear.
       --------------------------------------------------------- */

    const pendingLocation = useRef(null);

    /* ---------------------------------------------------------
       Transition state:

       idle
       exit
       prepare
       enter
       --------------------------------------------------------- */

    const [phase, setPhase] = useState("idle");

    /* ---------------------------------------------------------
       forward:
       Home -> Login -> Signup

       backward:
       Signup -> Login -> Home
       --------------------------------------------------------- */

    const [direction, setDirection] = useState("forward");

    /* =========================================================
       DETECT ROUTE CHANGE
       ========================================================= */

    useEffect(() => {
        const currentPath = displayLocation.pathname;

        const nextPath = location.pathname;

        /*
         * Same page.
         * Nothing to animate.
         */

        if (
            currentPath === nextPath &&
            displayLocation.search === location.search
        ) {
            return;
        }

        /*
         * We only animate:
         *
         * /
         * /login
         * /signup
         *
         * OTP/dashboard pages change normally.
         */

        const shouldAnimate =
            AUTH_TRANSITION_PATHS.has(currentPath) &&
            AUTH_TRANSITION_PATHS.has(nextPath);

        if (!shouldAnimate) {
            pendingLocation.current = null;

            setDisplayLocation(location);

            setPhase("idle");

            return;
        }

        /*
         * Save where we are going.
         */

        pendingLocation.current = location;

        const currentOrder = AUTH_PAGE_ORDER[currentPath] ?? 0;

        const nextOrder = AUTH_PAGE_ORDER[nextPath] ?? 0;

        /*
         * Example:
         *
         * Home 0 -> Login 1
         * forward
         *
         * Login 1 -> Signup 2
         * forward
         *
         * Signup 2 -> Login 1
         * backward
         */

        const nextDirection = nextOrder > currentOrder ? "forward" : "backward";

        setDirection(nextDirection);

        setPhase("exit");
    }, [location, displayLocation]);

    /* =========================================================
       TRANSITION FINISHED
       ========================================================= */

    const handleTransitionEnd = (event) => {
        /*
         * Ignore transitions coming from children.
         */

        if (event.target !== event.currentTarget) {
            return;
        }

        /*
         * We only care about transform finishing.
         */

        if (event.propertyName !== "transform") {
            return;
        }

        /* -----------------------------------------------------
           OLD PAGE FINISHED EXITING
           ----------------------------------------------------- */

        if (phase === "exit" && pendingLocation.current) {
            const nextLocation = pendingLocation.current;

            /*
             * Replace the old page with the new page.
             */

            setDisplayLocation(nextLocation);

            /*
             * Put the new page just outside the screen.
             */

            setPhase("prepare");

            /*
             * Wait two animation frames.
             *
             * This lets the browser position the page outside
             * the screen before starting the slide animation.
             */

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    setPhase("enter");
                });
            });

            return;
        }

        /* -----------------------------------------------------
           NEW PAGE FINISHED ENTERING
           ----------------------------------------------------- */

        if (phase === "enter") {
            pendingLocation.current = null;

            setPhase("idle");
        }
    };

    /* =========================================================
       CSS CLASS
       ========================================================= */

    let transitionClass = "auth-transition-page";

    if (phase === "exit") {
        transitionClass +=
            direction === "forward" ? " exit-forward" : " exit-backward";
    }

    if (phase === "prepare") {
        transitionClass +=
            direction === "forward" ? " prepare-forward" : " prepare-backward";
    }

    if (phase === "enter") {
        transitionClass +=
            direction === "forward" ? " enter-forward" : " enter-backward";
    }

    /* =========================================================
       ROUTES
       ========================================================= */

    return (
        <div className="auth-transition-root">
            <div
                className={transitionClass}
                onTransitionEnd={handleTransitionEnd}
            >
                <Routes location={displayLocation}>
                    {/* PUBLIC */}

                    <Route path="/" element={<LandingPage />} />

                    <Route path="/login" element={<LogIn />} />

                    <Route path="/signup" element={<Signup />} />

                    {/* OTP */}

                    <Route path="/login-otp" element={<LoginOtp />} />

                    <Route path="/signup-verify" element={<SignupVerify />} />

                    {/* SYSTEM USER */}

                    <Route path="/user" element={<SystemUser />} />

                    {/* SUPER ADMIN */}

                    <Route path="/super-admin" element={<SuperAdmin />} />
                </Routes>
            </div>
        </div>
    );
}

/* =========================================================
   APPLICATION
   ========================================================= */

function App() {
    return (
        <>
            <PresenceTracker />

            <AnimatedRoutes />
        </>
    );
}

/* =========================================================
   REACT ROOT
   ========================================================= */

createRoot(document.getElementById("root")).render(
    <React.StrictMode>
        <BrowserRouter>
            <App />
        </BrowserRouter>
    </React.StrictMode>,
);
