// Admin Dashboard JavaScript
document.addEventListener("DOMContentLoaded", () => {
  loadAdminDashboard()
  setupNavigation()
  setupLogout()
})

async function loadAdminDashboard() {
  try {
    const user = JSON.parse(localStorage.getItem("user"))
    if (!user) {
      window.location.href = "index.html"
      return
    }

    // Load dashboard statistics
    const response = await fetch("php/get_admin_stats.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
    })

    const data = await response.json()

    if (data.success) {
      document.getElementById("totalStudentsAdmin").textContent = data.stats.total_students
      document.getElementById("totalTeachers").textContent = data.stats.total_teachers
      document.getElementById("totalClassesAdmin").textContent = data.stats.total_classes
      document.getElementById("totalRecords").textContent = data.stats.total_records
    }
  } catch (error) {
    console.error("Error loading dashboard:", error)
  }
}

function setupNavigation() {
  const navLinks = document.querySelectorAll(".admin-sidebar-nav .nav-link")

  navLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      if (link.id === "logoutBtn") return

      e.preventDefault()
      const section = link.dataset.section

      // Hide all sections
      document.querySelectorAll(".admin-section").forEach((sec) => {
        sec.classList.remove("active")
      })

      // Show selected section
      const selectedSection = document.getElementById(`${section}-section`)
      if (selectedSection) {
        selectedSection.classList.add("active")
      }

      // Update active link
      navLinks.forEach((l) => l.classList.remove("active"))
      link.classList.add("active")

      // Load section data
      loadSectionData(section)
    })
  })
}

async function loadSectionData(section) {
  try {
    switch (section) {
      case "students":
        loadStudents()
        break
      case "teachers":
        loadTeachers()
        break
      case "classes":
        loadClassesAdmin()
        break
      case "reports":
        // Reports section
        break
      case "settings":
        // Settings section
        break
    }
  } catch (error) {
    console.error("Error loading section data:", error)
  }
}

async function loadStudents() {
  try {
    const response = await fetch("php/get_all_students.php")
    const data = await response.json()

    if (data.success) {
      const tbody = document.getElementById("studentsTable").querySelector("tbody")
      tbody.innerHTML = ""

      data.students.forEach((student) => {
        const row = tbody.insertRow()
        row.innerHTML = `
          <td>${student.student_id}</td>
          <td>${student.first_name} ${student.last_name}</td>
          <td>${student.email}</td>
          <td>${student.course}</td>
          <td>${student.year_level}</td>
          <td>
            <button class="btn btn-sm btn-secondary" onclick="editStudent('${student.student_id}')">Edit</button>
            <button class="btn btn-sm btn-error" onclick="deleteStudent('${student.student_id}')">Delete</button>
          </td>
        `
      })
    }
  } catch (error) {
    console.error("Error loading students:", error)
  }
}

async function loadTeachers() {
  try {
    const response = await fetch("php/get_all_teachers.php")
    const data = await response.json()

    if (data.success) {
      const tbody = document.getElementById("teachersTable").querySelector("tbody")
      tbody.innerHTML = ""

      data.teachers.forEach((teacher) => {
        const row = tbody.insertRow()
        row.innerHTML = `
          <td>${teacher.teacher_id}</td>
          <td>${teacher.first_name} ${teacher.last_name}</td>
          <td>${teacher.email}</td>
          <td>${teacher.department}</td>
          <td>
            <button class="btn btn-sm btn-secondary" onclick="editTeacher('${teacher.teacher_id}')">Edit</button>
            <button class="btn btn-sm btn-error" onclick="deleteTeacher('${teacher.teacher_id}')">Delete</button>
          </td>
        `
      })
    }
  } catch (error) {
    console.error("Error loading teachers:", error)
  }
}

async function loadClassesAdmin() {
  try {
    const response = await fetch("php/get_all_classes.php")
    const data = await response.json()

    if (data.success) {
      const tbody = document.getElementById("classesTableAdmin").querySelector("tbody")
      tbody.innerHTML = ""

      data.classes.forEach((cls) => {
        const row = tbody.insertRow()
        row.innerHTML = `
          <td>${cls.class_name}</td>
          <td>${cls.course}</td>
          <td>${cls.teacher_name}</td>
          <td>${cls.student_count}</td>
          <td>
            <button class="btn btn-sm btn-secondary" onclick="editClass('${cls.id}')">Edit</button>
            <button class="btn btn-sm btn-error" onclick="deleteClass('${cls.id}')">Delete</button>
          </td>
        `
      })
    }
  } catch (error) {
    console.error("Error loading classes:", error)
  }
}

function editStudent(studentId) {
  alert("Edit student: " + studentId)
}

function deleteStudent(studentId) {
  if (confirm("Are you sure you want to delete this student?")) {
    alert("Student deleted: " + studentId)
  }
}

function editTeacher(teacherId) {
  alert("Edit teacher: " + teacherId)
}

function deleteTeacher(teacherId) {
  if (confirm("Are you sure you want to delete this teacher?")) {
    alert("Teacher deleted: " + teacherId)
  }
}

function editClass(classId) {
  alert("Edit class: " + classId)
}

function deleteClass(classId) {
  if (confirm("Are you sure you want to delete this class?")) {
    alert("Class deleted: " + classId)
  }
}

function setupLogout() {
  const logoutBtns = document.querySelectorAll("#logoutBtn, #logoutBtnHeader")
  logoutBtns.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault()
      localStorage.removeItem("user")
      localStorage.removeItem("role")
      window.location.href = "index.html"
    })
  })
}
