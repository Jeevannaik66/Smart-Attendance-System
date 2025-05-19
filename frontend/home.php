
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance System | Face Recognition</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #f6c23e;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: all 0.3s;
            height: 100%;
            background-color: white;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .navbar-brand {
            font-weight: 800;
        }
        
        .nav-link {
            font-weight: 600;
        }
        
        .footer {
            background-color: #f8f9fc;
            padding: 2rem 0;
            margin-top: 3rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .feature-img {
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem 0.5rem 0 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-people-fill me-2"></i>
                Smart Attendance
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <?php if($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container mt-4">
        <section class="hero-section">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-3">Smart Attendance System</h1>
                    <p class="lead mb-4">
                        A revolutionary PHP-based attendance system using face recognition technology to automate 
                        and simplify attendance tracking for organizations, schools, and businesses.
                    </p>
                    <div class="d-flex gap-3">
                        <?php if($is_logged_in): ?>
                            <a href="dashboard.php" class="btn btn-light btn-lg">
                                <i class="bi bi-speedometer2 me-2"></i> Go to Dashboard
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-light btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Get Started
                            </a>
                        <?php endif; ?>
                        <a href="#features" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-info-circle me-2"></i> Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-block">
                    <img src="https://images.unsplash.com/photo-1551650975-87deedd944c3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="Face Recognition" class="img-fluid rounded">
                </div>
            </div>
        </section>
    </div>

    <!-- Features Section -->
    <div class="container my-5" id="features">
        <h2 class="text-center mb-5">
            <i class="bi bi-stars me-2"></i> Key Features
        </h2>
        
        <div class="row g-4">
            <!-- Face Recognition -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1610563166150-b34df4f3bcd6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="Face Recognition" class="feature-img w-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="bi bi-camera-fill feature-icon"></i>
                        </div>
                        <h4 class="card-title text-center">Face Recognition</h4>
                        <p class="card-text">
                            Advanced facial recognition technology that identifies users in seconds with 99% accuracy.
                            Our system uses PHP-powered algorithms to quickly and reliably recognize faces.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Real-time Tracking -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="Real-time Tracking" class="feature-img w-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="bi bi-clock-fill feature-icon"></i>
                        </div>
                        <h4 class="card-title text-center">Real-time Tracking</h4>
                        <p class="card-text">
                            Monitor attendance in real-time with live updates and instant notifications.
                            Our PHP backend processes data immediately as users check in.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Secure & Reliable -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="Security" class="feature-img w-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="bi bi-shield-lock-fill feature-icon"></i>
                        </div>
                        <h4 class="card-title text-center">Secure & Reliable</h4>
                        <p class="card-text">
                            Enterprise-grade security with encrypted data storage and role-based access.
                            Built with PHP security best practices to protect your data.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Analytics -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="Analytics" class="feature-img w-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="bi bi-graph-up-arrow feature-icon"></i>
                        </div>
                        <h4 class="card-title text-center">Detailed Analytics</h4>
                        <p class="card-text">
                            Comprehensive reports and analytics to track attendance patterns and trends.
                            Our PHP system generates insightful reports with just a few clicks.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Friendly -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1555774698-0b77e0d5fac6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="Mobile Friendly" class="feature-img w-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="bi bi-phone-fill feature-icon"></i>
                        </div>
                        <h4 class="card-title text-center">Mobile Friendly</h4>
                        <p class="card-text">
                            Fully responsive design that works perfectly on all devices and screen sizes.
                            Access the system from anywhere with our mobile-optimized PHP application.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- PHP Powered -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1633356122544-f134324a6cee?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="PHP Powered" class="feature-img w-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="bi bi-filetype-php feature-icon"></i>
                        </div>
                        <h4 class="card-title text-center">PHP Powered</h4>
                        <p class="card-text">
                            Built with modern PHP for robust performance and scalability.
                            Our system leverages PHP's strengths for secure, efficient attendance management.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div class="container my-5" id="about">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                     alt="About Us" class="img-fluid rounded">
            </div>
            <div class="col-lg-6">
                <h2><i class="bi bi-info-circle me-2"></i> About The Project</h2>
                <p class="lead">
                    The Smart Attendance System is a PHP-based solution designed to modernize attendance tracking.
                </p>
                <p>
                    Our system replaces traditional manual methods with automated face recognition technology,
                    providing accurate, efficient, and secure attendance management for organizations of all sizes.
                </p>
                <p>
                    Built with PHP, MySQL, and modern web technologies, the system offers:
                </p>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Contactless attendance tracking</li>
                    <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Real-time data processing</li>
                    <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Comprehensive reporting tools</li>
                    <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Role-based access control</li>
                    <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Mobile accessibility</li>
                </ul>
                <?php if(!$is_logged_in): ?>
                <a href="register.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-person-plus me-2"></i> Register Now
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="bi bi-people-fill me-2"></i> Smart Attendance</h5>
                    <p>Advanced face recognition attendance system for modern organizations.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-decoration-none"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-decoration-none"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-decoration-none"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="#features" class="text-decoration-none">Features</a></li>
                        <li class="mb-2"><a href="#about" class="text-decoration-none">About</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="privacy.php" class="text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="terms.php" class="text-decoration-none">Terms of Service</a></li>
                        <li class="mb-2"><a href="security.php" class="text-decoration-none">Security</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> info@smartattendance.com</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i> +1 (555) 123-4567</li>
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> 123 Tech Street, Silicon Valley</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="small mb-0">© <?php echo date("Y"); ?> Smart Attendance System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>