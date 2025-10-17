document.addEventListener("DOMContentLoaded", () => {
  const emailEnabled = document.getElementById("emailEnabled")
  const smsEnabled = document.getElementById("smsEnabled")
  const reminderEnabled = document.getElementById("reminderEnabled")
  const emailOptions = document.getElementById("emailOptions")
  const smsOptions = document.getElementById("smsOptions")
  const reminderOptions = document.getElementById("reminderOptions")

  // Toggle email options visibility
  emailEnabled.addEventListener("change", function () {
    emailOptions.style.display = this.checked ? "block" : "none"
  })

  // Toggle SMS options visibility
  smsEnabled.addEventListener("change", function () {
    smsOptions.style.display = this.checked ? "block" : "none"
  })

  // Toggle reminder options visibility
  reminderEnabled.addEventListener("change", function () {
    reminderOptions.style.display = this.checked ? "block" : "none"
  })

  // Load saved preferences
  loadPreferences()
})

async function savePreferences() {
  const preferences = {
    email_enabled: document.getElementById("emailEnabled").checked,
    sms_enabled: document.getElementById("smsEnabled").checked,
    reminder_enabled: document.getElementById("reminderEnabled").checked,
    reminder_time: document.getElementById("reminderTime").value,
    email_types: Array.from(document.querySelectorAll('input[name="emailType"]:checked')).map((el) => el.value),
    sms_types: Array.from(document.querySelectorAll('input[name="smsType"]:checked')).map((el) => el.value),
  }

  try {
    const response = await fetch("php/save_preferences.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(preferences),
    })

    const result = await response.json()

    if (result.success) {
      showSuccessMessage()
    } else {
      alert("Error: " + result.error)
    }
  } catch (error) {
    console.error("Error saving preferences:", error)
    alert("Failed to save preferences")
  }
}

function resetPreferences() {
  if (confirm("Are you sure you want to reset to default preferences?")) {
    document.getElementById("emailEnabled").checked = true
    document.getElementById("smsEnabled").checked = false
    document.getElementById("reminderEnabled").checked = true
    document.getElementById("reminderTime").value = "08:00"

    document.querySelectorAll('input[name="emailType"]').forEach((el) => {
      el.checked = ["attendance_marked", "late_arrival", "absence", "daily_summary"].includes(el.value)
    })

    document.querySelectorAll('input[name="smsType"]').forEach((el) => {
      el.checked = ["late_arrival", "absence"].includes(el.value)
    })

    savePreferences()
  }
}

function showSuccessMessage() {
  const message = document.getElementById("successMessage")
  message.style.display = "block"
  setTimeout(() => {
    message.style.display = "none"
  }, 3000)
}

async function loadPreferences() {
  try {
    const response = await fetch("php/get_preferences.php")
    const result = await response.json()

    if (result.success && result.data) {
      const prefs = result.data
      document.getElementById("emailEnabled").checked = prefs.email_enabled
      document.getElementById("smsEnabled").checked = prefs.sms_enabled
      document.getElementById("reminderEnabled").checked = prefs.reminder_enabled
      document.getElementById("reminderTime").value = prefs.reminder_time || "08:00"
    }
  } catch (error) {
    console.error("Error loading preferences:", error)
  }
}
