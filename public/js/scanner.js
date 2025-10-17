// QR Scanner JavaScript
let video = null
let canvas = null
let scanning = false
let animationId = null
let jsQR = null // Declare jsQR variable

document.addEventListener("DOMContentLoaded", () => {
  video = document.getElementById("scanner-video")
  canvas = document.getElementById("scanner-canvas")
  const startBtn = document.getElementById("startScannerBtn")
  const stopBtn = document.getElementById("stopScannerBtn")
  const uploadBtn = document.getElementById("uploadQRBtn")
  const fileInput = document.getElementById("qrFileInput")
  const manualForm = document.getElementById("manualEntryForm")
  const successMsg = document.getElementById("successMessage")
  const errorMsg = document.getElementById("errorMessage")

  // Start scanner
  startBtn.addEventListener("click", startScanner)

  // Stop scanner
  stopBtn.addEventListener("click", stopScanner)

  // Upload QR image
  uploadBtn.addEventListener("click", () => fileInput.click())
  fileInput.addEventListener("change", handleFileUpload)

  // Manual entry
  manualForm.addEventListener("submit", handleManualEntry)

  // Check for camera permission on load
  checkCameraPermission()

  // Import jsQR library
  import("https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js")
    .then((module) => {
      jsQR = module.default
    })
    .catch((err) => {
      console.error("Failed to load jsQR library", err)
    })
})

async function checkCameraPermission() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
    stream.getTracks().forEach((track) => track.stop())
  } catch (error) {
    console.log("Camera permission denied or not available")
  }
}

async function startScanner() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "environment" },
    })

    video.srcObject = stream
    video.play()

    document.getElementById("startScannerBtn").style.display = "none"
    document.getElementById("stopScannerBtn").style.display = "inline-block"

    scanning = true
    scanQRCode()
  } catch (error) {
    showError("Unable to access camera. Please check permissions.")
    console.error("Camera error:", error)
  }
}

function stopScanner() {
  scanning = false
  if (video.srcObject) {
    video.srcObject.getTracks().forEach((track) => track.stop())
  }
  if (animationId) {
    cancelAnimationFrame(animationId)
  }

  document.getElementById("startScannerBtn").style.display = "inline-block"
  document.getElementById("stopScannerBtn").style.display = "none"
}

function scanQRCode() {
  if (!scanning) return

  const ctx = canvas.getContext("2d")
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight

  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
  const code = jsQR(imageData.data, imageData.width, imageData.height)

  if (code) {
    console.log("QR Code detected:", code.data)
    processQRData(code.data)
    stopScanner()
  } else {
    animationId = requestAnimationFrame(scanQRCode)
  }
}

function handleFileUpload(event) {
  const file = event.target.files[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    const img = new Image()
    img.onload = () => {
      const canvas = document.getElementById("scanner-canvas")
      const ctx = canvas.getContext("2d")
      canvas.width = img.width
      canvas.height = img.height
      ctx.drawImage(img, 0, 0)

      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
      const code = jsQR(imageData.data, imageData.width, imageData.height)

      if (code) {
        processQRData(code.data)
      } else {
        showError("No QR code found in the image")
      }
    }
    img.src = e.target.result
  }
  reader.readAsDataURL(file)
}

function handleManualEntry(e) {
  e.preventDefault()
  const qrData = document.getElementById("qrData").value.trim()
  if (qrData) {
    processQRData(qrData)
    document.getElementById("qrData").value = ""
  }
}

async function processQRData(qrData) {
  try {
    const response = await fetch("php/mark_attendance.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        qr_data: qrData,
      }),
    })

    const data = await response.json()

    if (data.success) {
      showSuccess("Attendance marked successfully!")
      addScanToHistory("Success", data.message)
    } else {
      showError(data.message || "Failed to mark attendance")
      addScanToHistory("Failed", data.message)
    }
  } catch (error) {
    console.error("Error:", error)
    showError("An error occurred. Please try again.")
    addScanToHistory("Error", error.message)
  }
}

function showSuccess(message) {
  const successMsg = document.getElementById("successMessage")
  successMsg.textContent = message
  successMsg.style.display = "flex"
  setTimeout(() => {
    successMsg.style.display = "none"
  }, 3000)
}

function showError(message) {
  const errorMsg = document.getElementById("errorMessage")
  errorMsg.textContent = message
  errorMsg.style.display = "flex"
  setTimeout(() => {
    errorMsg.style.display = "none"
  }, 3000)
}

function addScanToHistory(status, details) {
  const table = document.getElementById("scanHistoryTable").querySelector("tbody")
  const time = new Date().toLocaleTimeString()
  const row = table.insertRow(0)
  row.innerHTML = `
    <td>${time}</td>
    <td><span class="badge badge-${status === "Success" ? "success" : "error"}">${status}</span></td>
    <td>${details}</td>
  `
}
