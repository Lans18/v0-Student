// Attendance Records JavaScript
document.addEventListener("DOMContentLoaded", () => {
  const filterBtn = document.getElementById("filterBtn")
  const filterMonth = document.getElementById("filterMonth")
  const filterYear = document.getElementById("filterYear")

  // Load initial records
  loadRecords()

  // Filter button
  filterBtn.addEventListener("click", loadRecords)

  // Auto-load on filter change
  filterMonth.addEventListener("change", loadRecords)
  filterYear.addEventListener("change", loadRecords)

  // Export buttons
  document.getElementById("exportPdfBtn").addEventListener("click", exportPDF)
  document.getElementById("exportCsvBtn").addEventListener("click", exportCSV)
})

async function loadRecords() {
  const month = document.getElementById("filterMonth").value
  const year = document.getElementById("filterYear").value

  try {
    const response = await fetch("php/get_attendance_records.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        month: month,
        year: year,
      }),
    })

    const data = await response.json()

    if (data.success) {
      displayRecords(data.records)
      updateStatistics(data.records)
    } else {
      console.error("Error loading records:", data.message)
    }
  } catch (error) {
    console.error("Error:", error)
  }
}

function displayRecords(records) {
  const tbody = document.getElementById("recordsTable").querySelector("tbody")
  tbody.innerHTML = ""

  if (records.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No records found</td></tr>'
    return
  }

  records.forEach((record) => {
    const row = tbody.insertRow()
    const date = new Date(record.time_in)
    const day = date.toLocaleDateString("en-US", { weekday: "long" })
    const timeIn = new Date(record.time_in).toLocaleTimeString()
    const timeOut = record.time_out ? new Date(record.time_out).toLocaleTimeString() : "-"
    const duration = record.time_out ? calculateDuration(record.time_in, record.time_out) : "-"
    const status = record.time_out ? "Present" : "Incomplete"

    row.innerHTML = `
      <td>${date.toLocaleDateString()}</td>
      <td>${day}</td>
      <td>${timeIn}</td>
      <td>${timeOut}</td>
      <td>${duration}</td>
      <td><span class="badge badge-${status === "Present" ? "success" : "warning"}">${status}</span></td>
    `
  })
}

function updateStatistics(records) {
  const totalDays = records.length
  const presentDays = records.filter((r) => r.time_out).length
  const absentDays = totalDays - presentDays
  const percentage = totalDays > 0 ? Math.round((presentDays / totalDays) * 100) : 0

  document.getElementById("totalDays").textContent = totalDays
  document.getElementById("presentDays").textContent = presentDays
  document.getElementById("absentDays").textContent = absentDays
  document.getElementById("percentage").textContent = percentage + "%"
}

function calculateDuration(timeIn, timeOut) {
  const start = new Date(timeIn)
  const end = new Date(timeOut)
  const diff = end - start
  const hours = Math.floor(diff / 3600000)
  const minutes = Math.floor((diff % 3600000) / 60000)
  return `${hours}h ${minutes}m`
}

function exportPDF() {
  alert("PDF export functionality would be implemented here")
}

function exportCSV() {
  alert("CSV export functionality would be implemented here")
}
