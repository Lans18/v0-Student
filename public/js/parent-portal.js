let selectedStudent = null

document.addEventListener("DOMContentLoaded", () => {
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)

  document.getElementById("startDate").valueAsDate = firstDay
  document.getElementById("endDate").valueAsDate = today

  loadStudents()
})

async function loadStudents() {
  try {
    const parentEmail = localStorage.getItem("parentEmail") || "parent@example.com"

    const response = await fetch(
      `php/parent_dashboard.php?action=get_students&parent_email=${encodeURIComponent(parentEmail)}`,
    )
    const result = await response.json()

    if (result.success && result.data) {
      const studentSelect = document.getElementById("studentSelect")
      studentSelect.innerHTML = '<option value="">Choose a student...</option>'

      result.data.forEach((student) => {
        const option = document.createElement("option")
        option.value = student.student_id
        option.textContent = `${student.first_name} ${student.last_name} (${student.student_id})`
        studentSelect.appendChild(option)
      })
    }
  } catch (error) {
    console.error("Error loading students:", error)
  }
}

async function loadStudentData() {
  selectedStudent = document.getElementById("studentSelect").value

  if (!selectedStudent) {
    document.getElementById("summarySection").style.display = "none"
    document.getElementById("filterSection").style.display = "none"
    document.getElementById("recordsSection").style.display = "none"
    document.getElementById("alertsSection").style.display = "none"
    return
  }

  document.getElementById("summarySection").style.display = "block"
  document.getElementById("filterSection").style.display = "block"
  document.getElementById("recordsSection").style.display = "block"

  await loadAttendanceSummary()
  await loadAttendanceData()
}

async function loadAttendanceSummary() {
  try {
    const response = await fetch(`php/parent_dashboard.php?action=get_summary&student_id=${selectedStudent}`)
    const result = await response.json()

    if (result.success && result.data) {
      const data = result.data
      document.getElementById("presentCount").textContent = data.present || 0
      document.getElementById("absentCount").textContent = data.absent || 0
      document.getElementById("lateCount").textContent = data.late || 0
      document.getElementById("attendancePercentage").textContent = (data.percentage || 0) + "%"

      // Show alerts if attendance is low
      if (data.percentage < 75) {
        showLowAttendanceAlert(data.percentage)
      }
    }
  } catch (error) {
    console.error("Error loading summary:", error)
  }
}

async function loadAttendanceData() {
  try {
    const startDate = document.getElementById("startDate").value
    const endDate = document.getElementById("endDate").value

    const response = await fetch(
      `php/parent_dashboard.php?action=get_attendance&student_id=${selectedStudent}&start_date=${startDate}&end_date=${endDate}`,
    )
    const result = await response.json()

    if (result.success && result.data) {
      displayAttendanceRecords(result.data)
    }
  } catch (error) {
    console.error("Error loading attendance:", error)
  }
}

function displayAttendanceRecords(records) {
  const tableBody = document.getElementById("recordsTableBody")
  const noData = document.getElementById("noData")

  if (!records || records.length === 0) {
    tableBody.innerHTML = ""
    noData.style.display = "block"
    return
  }

  noData.style.display = "none"
  tableBody.innerHTML = records
    .map((record) => {
      const statusClass = record.status.toLowerCase()
      return `
        <tr>
          <td>${new Date(record.date).toLocaleDateString()}</td>
          <td><span class="status-badge ${statusClass}">${record.status.toUpperCase()}</span></td>
          <td>${record.check_in_time || "-"}</td>
          <td>${record.check_out_time || "-"}</td>
          <td>${record.duration_minutes ? record.duration_minutes + " min" : "-"}</td>
        </tr>
      `
    })
    .join("")
}

function showLowAttendanceAlert(percentage) {
  const alertsList = document.getElementById("alertsList")
  const alertItem = document.createElement("div")
  alertItem.className = "alert-item warning"
  alertItem.innerHTML = `
    <div class="alert-title">Low Attendance Warning</div>
    <div class="alert-message">Current attendance rate is ${percentage}%. Please ensure regular attendance.</div>
  `
  alertsList.innerHTML = ""
  alertsList.appendChild(alertItem)
  document.getElementById("alertsSection").style.display = "block"
}
