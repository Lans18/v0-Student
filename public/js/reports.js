let currentReportType = "student"
let reportData = []

// Initialize date inputs
document.addEventListener("DOMContentLoaded", () => {
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)

  document.getElementById("startDate").valueAsDate = firstDay
  document.getElementById("endDate").valueAsDate = today

  // Report type selector
  document.querySelectorAll(".report-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".report-btn").forEach((b) => b.classList.remove("active"))
      this.classList.add("active")
      currentReportType = this.dataset.report
      updateFilters()
    })
  })
})

function updateFilters() {
  const studentFilter = document.getElementById("studentFilter")
  const courseFilter = document.getElementById("courseFilter")

  studentFilter.style.display = currentReportType === "student" ? "block" : "none"
  courseFilter.style.display = currentReportType === "class" ? "block" : "none"
}

async function generateReport() {
  const startDate = document.getElementById("startDate").value
  const endDate = document.getElementById("endDate").value

  if (!startDate || !endDate) {
    alert("Please select date range")
    return
  }

  try {
    let url = "php/get_attendance_reports.php?"

    switch (currentReportType) {
      case "student":
        const studentId = document.getElementById("studentId").value
        if (!studentId) {
          alert("Please enter student ID")
          return
        }
        url += `action=student_report&student_id=${studentId}&start_date=${startDate}&end_date=${endDate}`
        break

      case "class":
        const courseId = document.getElementById("courseId").value
        if (!courseId) {
          alert("Please select course")
          return
        }
        url += `action=class_report&course_id=${courseId}&start_date=${startDate}&end_date=${endDate}`
        break

      case "daily":
        url += `action=daily_report&date=${startDate}`
        break

      case "low-attendance":
        url += `action=low_attendance&start_date=${startDate}&end_date=${endDate}`
        break
    }

    const response = await fetch(url)
    const result = await response.json()

    if (result.success) {
      reportData = result.data
      displayReport(result.data)
    } else {
      alert("Error: " + result.error)
    }
  } catch (error) {
    console.error("Error generating report:", error)
    alert("Failed to generate report")
  }
}

function displayReport(data) {
  const tableHeader = document.getElementById("tableHeader")
  const tableBody = document.getElementById("tableBody")
  const noData = document.getElementById("noData")
  const statsSummary = document.getElementById("statsSummary")

  if (!data || data.length === 0) {
    noData.style.display = "block"
    tableHeader.innerHTML = ""
    tableBody.innerHTML = ""
    statsSummary.style.display = "none"
    return
  }

  noData.style.display = "none"

  // Build table header
  const headers = Object.keys(data[0])
  tableHeader.innerHTML = headers.map((h) => `<th>${h.replace(/_/g, " ").toUpperCase()}</th>`).join("")

  // Build table body
  tableBody.innerHTML = data
    .map((row) => {
      return `<tr>${headers.map((h) => `<td>${row[h] || "-"}</td>`).join("")}</tr>`
    })
    .join("")

  // Show statistics if available
  if (currentReportType === "student" && data[0].status) {
    updateStatistics(data)
    statsSummary.style.display = "grid"
  }
}

function updateStatistics(data) {
  const present = data.filter((d) => d.status === "present").length
  const absent = data.filter((d) => d.status === "absent").length
  const late = data.filter((d) => d.status === "late").length
  const total = data.length
  const percentage = total > 0 ? ((present / total) * 100).toFixed(2) : 0

  document.getElementById("presentCount").textContent = present
  document.getElementById("absentCount").textContent = absent
  document.getElementById("lateCount").textContent = late
  document.getElementById("attendancePercentage").textContent = percentage + "%"
}

function exportReport() {
  if (reportData.length === 0) {
    alert("No data to export")
    return
  }

  const csv = convertToCSV(reportData)
  downloadCSV(csv, `attendance-report-${new Date().toISOString().split("T")[0]}.csv`)
}

function convertToCSV(data) {
  const headers = Object.keys(data[0])
  const csv = [headers.join(",")]

  data.forEach((row) => {
    csv.push(headers.map((h) => `"${row[h] || ""}"`).join(","))
  })

  return csv.join("\n")
}

function downloadCSV(csv, filename) {
  const blob = new Blob([csv], { type: "text/csv" })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement("a")
  a.href = url
  a.download = filename
  a.click()
  window.URL.revokeObjectURL(url)
}
