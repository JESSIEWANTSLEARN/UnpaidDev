import React from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";

import LandingPage from "./LandingPage.jsx";
import LogIn from "./LogIn.jsx";
import Signup from "./Signup.jsx";
import LoginOtp from "./LoginOtp.jsx";
import SignupVerify from "./SignupVerify.jsx";
import SystemUser from "./pages/users/SystemUser.jsx";
import SuperAdmin from "./pages/SuperAdmin.jsx";
import PresenceTracker from "./components/PresenceTracker.jsx";

function App() {
    return (
        <>
            <PresenceTracker />

            <Routes>
                <Route path="/" element={<LandingPage />} />
                <Route path="/login" element={<LogIn />} />
                <Route path="/signup" element={<Signup />} />
                <Route path="/login-otp" element={<LoginOtp />} />
                <Route path="/signup-verify" element={<SignupVerify />} />
                <Route path="/user" element={<SystemUser />} />
                <Route path="/super-admin" element={<SuperAdmin />} />
            </Routes>
        </>
    );
}

createRoot(document.getElementById("root")).render(
    <React.StrictMode>
        <BrowserRouter>
            <App />
        </BrowserRouter>
    </React.StrictMode>,
);
