import { Routes, Route } from "react-router-dom";
import LandingPage from "./LandingPage.jsx";
import LogIn from "./LogIn.jsx";
import Signup from "./Signup.jsx";
import SignupVerify from "./SignupVerify.jsx";
import LoginOtp from "./LoginOtp.jsx";

function App() {
  return (
    <Routes>
      <Route path="/" element={<LandingPage />} />

      <Route path="/login" element={<LogIn />} />

      <Route path="/signup" element={<Signup />} />

      <Route
        path="/signup-verify"
        element={<SignupVerify />}
      />

      <Route
        path="/login-otp"
        element={<LoginOtp />}
      />
    </Routes>
  );
}

export default App;