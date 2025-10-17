// Teacher Dashboard JavaScript
document.addEventListener("DOMContentLoaded", () => {
  loadTeacherDashboard()
  setupLogout()
  setupMobileMenu()
})

async function loadTeacherDashboard() {
  try {
    const user = JSON.parse(localStorage.getItem("user"))
    if (!user) {
      window.location.href = "index.html"
      return
    }

    document.getElementById("teacherName").textContent = user.first_name

    // Load teacher statistics
    const response = await fetch("php/get_teacher_stats.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        teacher_id: user.teacher_id,
      }),
    })

    const data = await response.json()

    if (data.success) {
      document.getElementById("totalStudents").textContent = data.stats.total_students
      document.getElementById("totalClasses").textContent = data.stats.total_classes
      document.getElementById("todayPresent").textContent = data.stats.today_present
      document.getElementById("todayAbsent").textContent = data.stats.today_absent

      // Load classes
      loadClasses(data.classes)
    }
  } catch (error) {
    console.error("Error loading dashboard:", error)
  }
}

function loadClasses(classes) {
  const tbody = document.getElementById("classesTable").querySelector("tbody")
  tbody.innerHTML = ""

  if (classes.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No classes assigned</td></tr>'
    return
  }

  classes.forEach((cls) => {
    const row = tbody.insertRow()
    row.innerHTML = `
      <td>${cls.class_name}</td>
      <td>${cls.course}</td>
      <td>${cls.student_count}</td>
      <td><a href="class-attendance.html?class_id=${cls.id}" class="btn btn-sm btn-primary">View</a></td>
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
