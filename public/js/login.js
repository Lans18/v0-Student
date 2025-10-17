// Login Page JavaScript
document.addEventListener("DOMContentLoaded", () => {
  const roleButtons = document.querySelectorAll(".role-btn")
  const loginForm = document.getElementById("loginForm")
  const roleInput = document.getElementById("role")
  const errorMessage = document.getElementById("errorMessage")
  const successMessage = document.getElementById("successMessage")

  // Role selector functionality
  roleButtons.forEach((button) => {
    button.addEventListener("click", function () {
      roleButtons.forEach((btn) => btn.classList.remove("active"))
      this.classList.add("active")
      roleInput.value = this.dataset.role
    })
  })

  // Form submission
  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault()

    const email = document.getElementById("email").value
    const password = document.getElementById("password").value
    const role = roleInput.value

    // Clear previous messages
    errorMessage.classList.remove("show")
    successMessage.classList.remove("show")

    try {
      const response = await fetch(`php/login.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          email: email,
          password: password,
          role: role,
        }),
      })

      const data = await response.json()

      if (data.success) {
        successMessage.textContent = "Login successful! Redirecting..."
        successMessage.classList.add("show")

        // Store user data in session/localStorage
        localStorage.setItem("user", JSON.stringify(data.user))
        localStorage.setItem("role", role)

        // Redirect based on role
        setTimeout(() => {
          if (role === "student") {
            window.location.href = "student-dashboard.html"
          } else if (role === "teacher") {
            window.location.href = "teacher-dashboard.html"
          } else if (role === "admin") {
            window.location.href = "admin-dashboard.html"
          }
        }, 1500)
      } else {
        errorMessage.textContent = data.message || "Login failed. Please try again."
        errorMessage.classList.add("show")
      }
    } catch (error) {
      console.error("Error:", error)
      errorMessage.textContent = "An error occurred. Please try again."
      errorMessage.classList.add("show")
    }
  })
})
