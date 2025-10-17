// Student Dashboard JavaScript
document.addEventListener("DOMContentLoaded", () => {
  loadStudentDashboard()
  setupLogout()
  setupMobileMenu()
})

async function loadStudentDashboard() {
  try {
    const user = JSON.parse(localStorage.getItem("user"))
    if (!user) {
      window.location.href = "index.html"
      return
    }

    document.getElementById("studentName").textContent = user.first_name

    // Load attendance statistics
    const response = await fetch("php/get_student_stats.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        student_id: user.student_id,
      }),
    })

    const data = await response.json()

    if (data.success) {
      document.getElementById("totalAttendance").textContent = data.stats.total_attendance
      document.getElementById("presentDays").textContent = data.stats.present_days
      document.getElementById("absentDays").textContent = data.stats.absent_days
      document.getElementById("attendancePercentage").textContent = data.stats.percentage + "%"

      // Load recent attendance
      loadRecentAttendance(data.recent_records)
    }
  } catch (error) {
    console.error("Error loading dashboard:", error)
  }
}

function loadRecentAttendance(records) {
  const tbody = document.getElementById("recentAttendanceTable").querySelector("tbody")
  tbody.innerHTML = ""

  if (records.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No records yet</td></tr>'
    return
  }

  records.slice(0, 5).forEach((record) => {
    const row = tbody.insertRow()
    const date = new Date(record.time_in).toLocaleDateString()
    const timeIn = new Date(record.time_in).toLocaleTimeString()
    const timeOut = record.time_out ? new Date(record.time_out).toLocaleTimeString() : "-"
    const status = record.time_out ? "Present" : "Incomplete"

    row.innerHTML = `
      <td>${date}</td>
      <td>${timeIn}</td>
      <td>${timeOut}</td>
      <td><span class="badge badge-${status === "Present" ? "success" : "warning"}">${status}</span></td>
    `
  })
}

function setupLogout() {
  const logoutBtn = document.getElementById("logoutBtn")
  if (logoutBtn) {
    logoutBtn.addEventListener("click", (e) => {
      e.preventDefault()
      localStorage.removeItem("user")
      localStorage.removeItem("role")
      window.location.href = "index.html"
    })
  }
}

function setupMobileMenu() {
  const mobileMenuBtn = document.getElementById("mobileMenuBtn")
  const headerNav = document.querySelector(".header-nav")

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener("click", () => {
      headerNav.classList.toggle("active")
    })
  }
}
