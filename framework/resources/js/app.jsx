import React from "react";
import { createRoot } from "react-dom/client";
import {
  BrowserRouter,
  Routes,
  Route,
} from "react-router-dom";

import LandingPage from "./LandingPage.jsx";
import LogIn from "./LogIn.jsx";
import Signup from "./Signup.jsx";
import LoginOtp from "./LoginOtp.jsx";
import SignupVerify from "./SignupVerify.jsx";

function App() {
  return (
    <Routes>

      <Route
        path="/"
        element={<LandingPage />}
      />

      <Route
        path="/login"
        element={<LogIn />}
      />

      <Route
        path="/signup"
        element={<Signup />}
      />

      <Route
        path="/login-otp"
        element={<LoginOtp />}
      />

      <Route
        path="/signup-verify"
        element={<SignupVerify />}
      />

    </Routes>
  );
}


const rootElement =
  document.getElementById("root");


createRoot(rootElement).render(
  <React.StrictMode>

    <BrowserRouter>

      <App />

    </BrowserRouter>

  </React.StrictMode>
);