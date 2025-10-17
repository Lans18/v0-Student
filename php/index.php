<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>JAVERIANS - Student Management System</title>

  <!-- Google Fonts: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Custom Styles -->
  <style>
    :root {
      --primary-color: #4f46e5;
      --primary-dark: #4338ca;
      --secondary-color: #10b981;
      --accent-color: #f97316;
      --admin-color: #dc2626;
      --admin-dark: #b91c1c;
      --text-dark: #1f2937;
      --text-light: #6b7280;
      --white: #ffffff;
      --light-bg: #f9fafb;

      --border-radius: 12px;
      --border-radius-xl: 24px;

      --spacing: 1rem;
      --spacing-lg: 1.5rem;
      --spacing-2xl: 3rem;
      --spacing-3xl: 4rem;

      --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

      --gradient-primary: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
      --gradient-secondary: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
      --gradient-accent: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
      --gradient-admin: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
      overflow-x: hidden;
    }

    .welcome-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: var(--spacing);
      position: relative;
    }

    .particles-container {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: 0;
    }

    .particle {
      position: absolute;
      border-radius: 50%;
      background: var(--gradient-primary);
      opacity: 0.3;
      animation: floatParticle 20s infinite linear;
    }

    @keyframes floatParticle {
      0% {
        transform: translateY(0) rotate(0deg);
      }
      100% {
        transform: translateY(-80vh) rotate(360deg);
      }
    }

    .floating-icons {
      position: absolute;
      font-size: 2rem;
      opacity: 0.08;
      color: var(--primary-color);
      z-index: 1;
    }

    .floating-icons i {
      position: absolute;
      animation: floatIcon 25s infinite linear;
    }

    .floating-icons i:nth-child(1) { top: 10%; left: 15%; animation-delay: 0s; }
    .floating-icons i:nth-child(2) { top: 70%; left: 75%; animation-delay: 5s; }
    .floating-icons i:nth-child(3) { top: 40%; left: 85%; animation-delay: 10s; }
    .floating-icons i:nth-child(4) { top: 80%; left: 10%; animation-delay: 15s; }
    .floating-icons i:nth-child(5) { top: 20%; left: 80%; animation-delay: 7s; }

    @keyframes floatIcon {
      0% { transform: translateY(0) rotate(0deg); }
      100% { transform: translateY(-100vh) rotate(360deg); }
    }

    .welcome-container {
      position: relative;
      z-index: 10;
      max-width: 900px;
      width: 100%;
      padding: var(--spacing-3xl);
      background: rgba(255, 255, 255, 0.95);
      border-radius: var(--border-radius-xl);
      box-shadow: var(--shadow-xl);
      backdrop-filter: blur(12px);
      text-align: center;
    }

    .welcome-logo-img {
      width: 14rem;
      height: 14rem;
      border-radius: 50%;
      object-fit: cover;
      border: 6px solid transparent;
      background: linear-gradient(var(--white), var(--white)) padding-box, var(--gradient-primary) border-box;
      box-shadow: 0 15px 35px rgba(79, 70, 229, 0.2);
      margin-bottom: var(--spacing-lg);
      transition: all 0.4s ease;
      animation: float 4s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .welcome-title {
      font-size: 3.5rem;
      font-weight: 800;
      margin-bottom: var(--spacing);
      background: var(--gradient-primary);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .welcome-subtitle {
      font-size: 1.3rem;
      max-width: 36rem;
      margin: 0 auto var(--spacing-2xl);
      color: var(--text-dark);
      opacity: 0.9;
    }

    .btn-group {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: var(--spacing);
    }

    .welcome-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 1rem 2rem;
      border-radius: var(--border-radius);
      font-weight: 600;
      font-size: 1.1rem;
      gap: 0.5rem;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .welcome-btn-primary {
      background: var(--gradient-primary);
      color: white;
      box-shadow: var(--shadow-md);
    }

    .welcome-btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-lg);
    }

    .welcome-btn-secondary {
      background: transparent;
      color: var(--primary-color);
      border: 2px solid var(--primary-color);
    }

    .welcome-btn-secondary:hover {
      background: rgba(79, 70, 229, 0.08);
      transform: translateY(-3px);
    }

    .welcome-btn-admin {
      background: var(--gradient-admin);
      color: white;
      box-shadow: var(--shadow-md);
    }

    .welcome-btn-admin:hover {
      background: var(--admin-dark);
      transform: translateY(-3px);
      box-shadow: var(--shadow-lg);
    }

    .admin-section {
      margin-top: var(--spacing-2xl);
      padding-top: var(--spacing-lg);
      border-top: 2px solid rgba(0, 0, 0, 0.1);
    }

    .admin-label {
      font-size: 0.9rem;
      color: var(--text-light);
      margin-bottom: var(--spacing);
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
    }

    @media (max-width: 768px) {
      .welcome-logo-img {
        width: 10rem;
        height: 10rem;
      }

      .welcome-title {
        font-size: 2.3rem;
      }

      .welcome-subtitle {
        font-size: 1.1rem;
      }

      .btn-group {
        flex-direction: column;
        align-items: center;
      }

      .welcome-btn {
        width: 90%;
        max-width: 300px;
      }
    }
  </style>
</head>
<body class="welcome-page">

  <!-- Animated Background -->
  <div class="particles-container" id="particles"></div>

  <!-- Floating Icons -->
  <div class="floating-icons">
    <i class="fas fa-graduation-cap"></i>
    <i class="fas fa-book"></i>
    <i class="fas fa-user-graduate"></i>
    <i class="fas fa-chalkboard-teacher"></i>
    <i class="fas fa-cog"></i>
  </div>

  <!-- Main Container -->
  <div class="welcome-container">
    <img
      src="assets/css/image/Logo.jpg"
      onerror="this.onerror=null;this.src='https://via.placeholder.com/300x300.png?text=JAVERIANS';"
      alt="JAVERIANS Logo"
      class="welcome-logo-img"
    />

    <h1 class="welcome-title">WELCOME JAVERIANS</h1>
    <p class="welcome-subtitle">
      Manage your academic journey with our comprehensive student management system. Access courses, track progress, and connect with educators.
    </p>

    <div class="btn-group">
      <a href="signin.php" class="welcome-btn welcome-btn-primary">
        <i class="fas fa-sign-in-alt"></i> SIGN IN
      </a>
      <a href="signup.php" class="welcome-btn welcome-btn-secondary">
        <i class="fas fa-user-plus"></i> SIGN UP
      </a>
    </div>

    <!-- Admin Section -->
    <div class="admin-section">
      <div class="admin-label">Administrator Access</div>
      <div class="btn-group">
        <a href="admin/admin_login.php" class="welcome-btn welcome-btn-admin">
          <i class="fas fa-user-shield"></i> ADMIN LOGIN
        </a>
      </div>
    </div>
  </div>

  <!-- Particle JS -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const particlesContainer = document.getElementById('particles');
      const particleCount = 20;

      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');

        const size = Math.random() * 30 + 10;
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        const delay = Math.random() * 15;
        const duration = Math.random() * 10 + 15;

        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${posX}vw`;
        particle.style.top = `${posY}vh`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;

        const gradients = [
          'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
          'linear-gradient(135deg, #10b981 0%, #3b82f6 100%)',
          'linear-gradient(135deg, #f97316 0%, #ef4444 100%)',
          'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)'
        ];
        particle.style.background = gradients[Math.floor(Math.random() * gradients.length)];

        particlesContainer.appendChild(particle);
      }
    });
  </script>
</body>
</html>
