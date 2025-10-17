document.addEventListener("DOMContentLoaded", () => {
  loadSMSStatus()
})

function togglePhoneForm() {
  const form = document.getElementById("phoneForm")
  form.style.display = form.style.display === "none" ? "block" : "none"
}

async function updatePhoneNumber() {
  const phoneNumber = document.getElementById("phoneNumber").value.trim()

  if (!phoneNumber) {
    alert("Please enter a phone number")
    return
  }

  try {
    const response = await fetch("php/manage_sms_settings.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=add_phone&phone_number=${encodeURIComponent(phoneNumber)}&user_id=USER_ID&user_type=student`,
    })

    const result = await response.json()

    if (result.success) {
      document.getElementById("currentPhone").textContent = phoneNumber
      document.getElementById("verificationForm").style.display = "block"
      document.getElementById("phoneForm").style.display = "none"
      showSuccessMessage("Verification code sent to your phone")
    } else {
      alert("Error: " + result.error)
    }
  } catch (error) {
    console.error("Error updating phone:", error)
    alert("Failed to update phone number")
  }
}

async function verifyPhone() {
  const verificationCode = document.getElementById("verificationCode").value.trim()

  if (!verificationCode || verificationCode.length !== 6) {
    alert("Please enter a valid 6-digit code")
    return
  }

  try {
    const response = await fetch("php/manage_sms_settings.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=verify_phone&verification_code=${encodeURIComponent(verificationCode)}&user_id=USER_ID&user_type=student`,
    })

    const result = await response.json()

    if (result.success) {
      document.getElementById("verificationStatus").textContent = "Verified"
      document.getElementById("verificationStatus").className = "status-badge verified"
      document.getElementById("verificationForm").style.display = "none"
      showSuccessMessage("Phone number verified successfully!")
    } else {
      alert("Error: " + result.error)
    }
  } catch (error) {
    console.error("Error verifying phone:", error)
    alert("Failed to verify phone number")
  }
}

async function saveSMSSettings() {
  const smsTypes = Array.from(document.querySelectorAll('input[name="smsType"]:checked')).map((el) => el.value)

  try {
    const response = await fetch("php/save_sms_settings.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        sms_types: smsTypes,
      }),
    })

    const result = await response.json()

    if (result.success) {
      showSuccessMessage()
    } else {
      alert("Error: " + result.error)
    }
  } catch (error) {
    console.error("Error saving settings:", error)
    alert("Failed to save settings")
  }
}

function resetSMSSettings() {
  if (confirm("Are you sure you want to reset SMS settings?")) {
    document.querySelectorAll('input[name="smsType"]').forEach((el) => {
      el.checked = ["late_arrival", "absence"].includes(el.value)
    })
    saveSMSSettings()
  }
}

function showSuccessMessage(message = "Settings saved successfully!") {
  const msg = document.getElementById("successMessage")
  msg.textContent = message
  msg.style.display = "block"
  setTimeout(() => {
    msg.style.display = "none"
  }, 3000)
}

async function loadSMSStatus() {
  try {
    const response = await fetch("php/manage_sms_settings.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "action=get_sms_status&user_id=USER_ID&user_type=student",
    })

    const result = await response.json()

    if (result.success && result.phone) {
      document.getElementById("currentPhone").textContent = result.phone
      document.getElementById("verificationStatus").textContent = "Verified"
      document.getElementById("verificationStatus").className = "status-badge verified"
    }
  } catch (error) {
    console.error("Error loading SMS status:", error)
  }
}
