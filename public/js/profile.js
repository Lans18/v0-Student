// Profile JavaScript
document.addEventListener("DOMContentLoaded", () => {
  loadUserProfile()

  document.getElementById("editProfileForm").addEventListener("submit", updateProfile)
  document.getElementById("changePasswordForm").addEventListener("submit", changePassword)
})

async function loadUserProfile() {
  try {
    const user = JSON.parse(localStorage.getItem("user"))
    if (!user) {
      window.location.href = "index.html"
      return
    }

    // Display profile info
    document.getElementById("fullName").textContent = `${user.first_name} ${user.last_name}`
    document.getElementById("userRole").textContent = localStorage.getItem("role") || "User"
    document.getElementById("userEmail").textContent = user.email
    document.getElementById("studentId").textContent = user.student_id || user.teacher_id || "-"
    document.getElementById("course").textContent = user.course || "-"
    document.getElementById("yearLevel").textContent = user.year_level || "-"
    document.getElementById("phone").textContent = user.phone || "-"
    document.getElementById("joinDate").textContent = new Date(user.created_at).toLocaleDateString()

    if (user.profile_picture) {
      document.getElementById("profileImage").src = user.profile_picture
    }

    // Fill edit form
    document.getElementById("editFirstName").value = user.first_name
    document.getElementById("editLastName").value = user.last_name
    document.getElementById("editEmail").value = user.email
    document.getElementById("editPhone").value = user.phone || ""
  } catch (error) {
    console.error("Error loading profile:", error)
  }
}

async function updateProfile(e) {
  e.preventDefault()

  const formData = new FormData()
  formData.append("first_name", document.getElementById("editFirstName").value)
  formData.append("last_name", document.getElementById("editLastName").value)
  formData.append("email", document.getElementById("editEmail").value)
  formData.append("phone", document.getElementById("editPhone").value)

  const fileInput = document.getElementById("profilePictureInput")
  if (fileInput.files.length > 0) {
    formData.append("profile_picture", fileInput.files[0])
  }

  try {
    const response = await fetch("php/update_profile.php", {
      method: "POST",
      body: formData,
    })

    const data = await response.json()

    if (data.success) {
      alert("Profile updated successfully!")
      loadUserProfile()
    } else {
      alert("Error updating profile: " + data.message)
    }
  } catch (error) {
    console.error("Error:", error)
    alert("An error occurred while updating profile")
  }
}

async function changePassword(e) {
  e.preventDefault()

  const newPassword = document.getElementById("newPassword").value
  const confirmPassword = document.getElementById("confirmPassword").value

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match!")
    return
  }

  try {
    const response = await fetch("php/change_password.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        current_password: document.getElementById("currentPassword").value,
        new_password: newPassword,
      }),
    })

    const data = await response.json()

    if (data.success) {
      alert("Password changed successfully!")
      document.getElementById("changePasswordForm").reset()
    } else {
      alert("Error: " + data.message)
    }
  } catch (error) {
    console.error("Error:", error)
    alert("An error occurred while changing password")
  }
}
