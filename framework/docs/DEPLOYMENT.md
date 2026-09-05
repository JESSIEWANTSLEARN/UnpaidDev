# Deployment Notes

Current stack:

- Laravel + PHP backend
- React + Vite frontend
- MySQL on Railway
- Render deployment
- Brevo for OTP/email

Real credentials belong only in environment variables. Never commit `.env`, database passwords, `APP_KEY`, or `BREVO_API_KEY`.

After the future repository split, configure CORS, CSRF, cookies/session domains, and frontend API base URLs for the deployed frontend/backend domains.
