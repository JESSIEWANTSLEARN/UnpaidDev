import React, { useEffect } from "react";
import { useLocation } from "react-router-dom";

const PUBLIC_PATHS = new Set([
    "/",
    "/login",
    "/signup",
    "/signup-verify",
    "/login-otp",
]);

const getCsrf = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") ?? "";

const sendPresence = (url, keepalive = false) =>
    fetch(url, {
        method: "POST",
        credentials: "same-origin",
        keepalive,
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": getCsrf(),
        },
    }).catch(() => null);

export default function PresenceTracker() {
    const location = useLocation();
    const isPublicPage = PUBLIC_PATHS.has(location.pathname);

    useEffect(() => {
        if (isPublicPage) return undefined;

        const heartbeat = () => {
            if (document.visibilityState === "visible") {
                sendPresence("/api/presence/heartbeat");
            }
        };

        heartbeat();

        const intervalId = window.setInterval(heartbeat, 60_000);
        document.addEventListener("visibilitychange", heartbeat);

        const markOfflineOnClose = () => {
            sendPresence("/api/presence/offline", true);
        };

        window.addEventListener("pagehide", markOfflineOnClose);

        return () => {
            window.clearInterval(intervalId);
            document.removeEventListener("visibilitychange", heartbeat);
            window.removeEventListener("pagehide", markOfflineOnClose);
        };
    }, [location.pathname, isPublicPage]);

    return null;
}
