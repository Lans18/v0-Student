document.addEventListener("DOMContentLoaded", () => {
  const today = new Date()
  document.getElementById("markDate").valueAsDate = today
  document.getElementById("exportStartDate").valueAsDate = new Date(today.getFullYear(), today.getMonth(), 1)
  document.getElementById("exportEndDate").valueAsDate = today

  // Tab switching
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const tabName = this.dataset.tab
      switchTab(tabName)
    })
  })

  loadOperationHistory()
})

function switchTab(tabName) {
  document.querySelectorAll(".tab-content").forEach((tab) => tab.classList.remove("active"))
  document.querySelectorAll(".tab-btn").forEach((btn) => btn.classList.remove("active"))

  document.getElementById(tabName).classList.add("active")
  event.target.classList.add("active")
}

async function bulkMarkAttendance() {
  const date = document.getElementById("markDate").value
  const course = document.getElementById("markCourse").value
  const status = document.getElementById("markStatus").value

  if (!date) {
    alert("Please select a date")
    return
  }

  showProgress("Marking attendance...")

  try {
    const response = await fetch("php/bulk_operations.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=bulk_mark_attendance&date=${date}&course=${course}&status=${status}&created_by=admin`,
    })

    const result = await response.json()

    if (result.success) {
      showMessage(`Successfully marked attendance for ${result.result.success} students`)
      hideProgress()
    } else {
      showMessage("Error: " + result.error, true)
    }
  } catch (error) {
    console.error("Error:", error)
    showMessage("Failed to mark attendance", true)
  }
}

async function bulkUpdateAttendance() {
  const file = document.getElementById("updateFile").files[0]

  if (!file) {
    alert("Please select a CSV file")
    return
  }

  const formData = new FormData()
  formData.append("action", "bulk_update_attendance")
  formData.append("csv_file", file)
  formData.append("created_by", "admin")

  showProgress("Updating attendance...")

  try {
    const response = await fetch("php/bulk_operations.php", {
      method: "POST",
      body: formData,
    })

    const result = await response.json()

    if (result.success) {
      showMessage(`Successfully updated ${result.result.success} records`)
      hideProgress()
    } else {
      showMessage("Error: " + result.error, true)
    }
  } catch (error) {
    console.error("Error:", error)
    showMessage("Failed to update attendance", true)
  }
}

async function bulkSendNotifications() {
  const type = document.getElementById("notificationType").value

  showProgress("Sending notifications...")

  try {
    const response = await fetch("php/bulk_operations.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=bulk_send_notifications&notification_type=${type}&created_by=admin`,
    })

    const result = await response.json()

    if (result.success) {
      showMessage(`Successfully sent ${result.result.success} notifications`)
      hideProgress()
    } else {
      showMessage("Error: " + result.error, true)
    }
  } catch (error) {
    console.error("Error:", error)
    showMessage("Failed to send notifications", true)
  }
}

async function importCSV() {
  const file = document.getElementById("importFile").files[0]

  if (!file) {
    alert("Please select a CSV file")
    return
  }

  const formData = new FormData()
  formData.append("action", "import_csv")
  formData.append("csv_file", file)
  formData.append("created_by", "admin")

  showProgress("Importing attendance...")

  try {
    const response = await fetch("php/bulk_operations.php", {
      method: "POST",
      body: formData,
    })

    const result = await response.json()

    if (result.success) {
      showMessage(`Successfully imported ${result.result.success} records`)
      hideProgress()
    } else {
      showMessage("Error: " + result.error, true)
    }
  } catch (error) {
    console.error("Error:", error)
    showMessage("Failed to import attendance", true)
  }
}

function exportCSV() {
  const startDate = document.getElementById("exportStartDate").value
  const endDate = document.getElementById("exportEndDate").value

  if (!startDate || !endDate) {
    alert("Please select date range")
    return
  }

  window.location.href = `php/bulk_operations.php?action=export_csv&start_date=${startDate}&end_date=${endDate}`
}

async function loadOperationHistory() {
  try {
    const response = await fetch("php/bulk_operations.php?action=get_history&limit=50")
    const result = await response.json()

    if (result.success && result.data) {
      displayOperationHistory(result.data)
    }
  } catch (error) {
    console.error("Error loading history:", error)
  }
}

function displayOperationHistory(operations) {
  const tableBody = document.getElementById("historyTableBody")
  tableBody.innerHTML = operations
    .map((op) => {
      return `
        <tr>
          <td>#${op.id}</td>
          <td>${op.operation_type}</td>
          <td><span class="status-badge ${op.status}">${op.status.toUpperCase()}</span></td>
          <td>${op.total_records}</td>
          <td>${op.processed_records}</td>
          <td>${new Date(op.created_at).toLocaleDateString()}</td>
          <td><button class="btn-secondary" onclick="viewOperation(${op.id})">View</button></td>
        </tr>
      `
    })
    .join("")
}

function viewOperation(operationId) {
  alert(`Operation #${operationId} details`)
}

function showProgress(message = "Processing...") {
  const section = document.getElementById("progressSection")
  document.getElementById("progressText").textContent = message
  section.style.display = "block"
}

function hideProgress() {
  document.getElementById("progressSection").style.display = "none"
}

function showMessage(message, isError = false) {
  const msgDiv = document.getElementById("successMessage")
  const msgText = document.getElementById("messageText")
  msgText.textContent = message
  msgDiv.className = isError ? "message error" : "message"
  msgDiv.style.display = "block"

  setTimeout(() => {
    msgDiv.style.display = "none"
  }, 5000)
}
