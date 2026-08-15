try {
  setLoading(true);

  const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  const response = await fetch("/register", {
    method: "POST",

    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-CSRF-TOKEN": csrfToken,
    },

    credentials: "same-origin",

    body: JSON.stringify({
      name: name,
      email: email,
      contact_number: contactNumber,
      password: password,
      password_confirmation: confirmPassword,
    }),
  });

  const result = await response.json();

  if (!response.ok) {
    if (result.errors) {
      const firstError =
        Object.values(result.errors)[0]?.[0];

      setError(
        firstError ||
          "Please check your information."
      );
    } else {
      setError(
        result.message ||
          "Unable to create your account."
      );
    }

    return;
  }

  navigate("/signup-verify");

} catch (error) {

  console.error(error);

  setError(
    "Unable to connect to the server."
  );

} finally {

  setLoading(false);
}