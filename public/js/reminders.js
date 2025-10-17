document.addEventListener("DOMContentLoaded", () => {
  const dailyReminderEnabled = document.getElementById("dailyReminderEnabled")
  const dailyReminderOptions = document.getElementById("dailyReminderOptions")

  dailyReminderEnabled.addEventListener("change", function () {
    dailyReminderOptions.style.display = this.checked ? "block" : "none"
  })

  loadReminderStats()
  loadReminderHistory()
  loadCustomReminders()
})

function toggleCustomReminderForm() {
  const form = document.getElementById("customReminderForm")
  form.style.display = form.style.display === "none" ? "block" : "none"
}

async function saveCustomReminder() {
  const reminderTime = document.getElementById("customReminderTime").value
  const reminderMessage = document.getElementById("customReminderMessage").value

  if (!reminderTime) {
    alert("Please select a reminder time")
    return
  }

  try {
    const response = await fetch("php/save_custom_reminder.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        reminder_time: reminderTime,
        message: reminderMessage,
      }),
    })

    const result = await response.json()

    if (result.success) {
      document.getElementById("customReminderTime").value = ""
      document.getElementById("customReminderMessage").value = ""
      toggleCustomReminderForm()
      loadCustomReminders()
      showSuccessMessage()
    } else {
      alert("Error: " + result.error)
    }
  } catch (error) {
    console.error("Error saving reminder:", error)
    alert("Failed to save reminder")
  }
}

async function deleteCustomReminder(reminderId) {
  if (confirm("Are you sure you want to delete this reminder?")) {
    try {
      const response = await fetch("php/delete_custom_reminder.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `reminder_id=${reminderId}`,
      })

      const result = await response.json()

      if (result.success) {
        loadCustomReminders()
        showSuccessMessage("Reminder deleted successfully")
      } else {
        alert("Error: " + result.error)
      }
    } catch (error) {
      console.error("Error deleting reminder:", error)
      alert("Failed to delete reminder")
    }
  }
}

async function saveReminderSettings() {
  const dailyReminderEnabled = document.getElementById("dailyReminderEnabled").checked
  const dailyReminderTime = document.getElementById("dailyReminderTime").value

  try {
    const response = await fetch("php/save_reminder_settings.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        daily_reminder_enabled: dailyReminderEnabled,
        daily_reminder_time: dailyReminderTime,
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

function resetReminderSettings() {
  if (confirm("Are you sure you want to reset reminder settings?")) {
    document.getElementById("dailyReminderEnabled").checked = true
    document.getElementById("dailyReminderTime").value = "08:00"
    saveReminderSettings()
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

async function loadReminderStats() {
  try {
    const response = await fetch("php/get_reminder_stats.php")
    const result = await response.json()

    if (result.success && result.data) {
      document.getElementById("totalReminders").textContent = result.data.total_reminders || 0
      document.getElementById("sentReminders").textContent = result.data.sent_reminders || 0
      document.getElementById("failedReminders").textContent = result.data.failed_reminders || 0
    }
  } catch (error) {
    console.error("Error loading reminder stats:", error)
  }
}

async function loadReminderHistory() {
  try {
    const response = await fetch("php/get_reminder_history.php")
    const result = await response.json()

    if (result.success && result.data) {
      const historyDiv = document.getElementById("reminderHistory")
      historyDiv.innerHTML = ""

      if (result.data.length === 0) {
        historyDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No reminders yet</p>'
        return
      }

      result.data.forEach((item) => {
        const historyItem = document.createElement("div")
        historyItem.className = "history-item"
        historyItem.innerHTML = `
          <div>
            <div class="history-time">${item.reminder_date}</div>
          </div>
          <span class="history-status ${item.status}">${item.status.toUpperCase()}</span>
        `
        historyDiv.appendChild(historyItem)
      })
    }
  } catch (error) {
    console.error("Error loading reminder history:", error)
  }
}

async function loadCustomReminders() {
  try {
    const response = await fetch("php/get_custom_reminders.php")
    const result = await response.json()

    if (result.success && result.data) {
      const remindersList = document.getElementById("customRemindersList")
      remindersList.innerHTML = ""

      result.data.forEach((reminder) => {
        const reminderItem = document.createElement("div")
        reminderItem.className = "custom-reminder-item"
        reminderItem.innerHTML = `
          <div class="custom-reminder-info">
            <div class="custom-reminder-time">${reminder.reminder_time}</div>
            ${reminder.message ? `<div class="custom-reminder-message">${reminder.message}</div>` : ""}
          </div>
          <button class="custom-reminder-delete" onclick="deleteCustomReminder(${reminder.id})">Delete</button>
        `
        remindersList.appendChild(reminderItem)
      })
    }
  } catch (error) {
    console.error("Error loading custom reminders:", error)
  }
}
