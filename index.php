<?php
session_start();
require_once 'db.php.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam System Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: #343a40 !important;
            box-shadow: 0 2px 8px rgba(32, 201, 151, 0.08);
        }
        .navbar .navbar-brand, .navbar .nav-link, .navbar .navbar-toggler-icon {
            color: #20c997 !important;
        }
        .navbar .nav-link.active, .navbar .nav-link:focus, .navbar .nav-link:hover {
            color: #17a589 !important;
        }
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: none;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(32, 201, 151, 0.08);
        }
        .card:hover {
            transform: translateY(-7px) scale(1.02);
            box-shadow: 0 8px 32px rgba(32, 201, 151, 0.16);
        }
        .card-body i {
            color: #20c997 !important;
        }
        .btn-primary, .btn-primary:active, .btn-primary:focus {
            background: linear-gradient(90deg, #20c997 0%, #17a589 100%);
            border: none;
            border-radius: 30px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #17a589 0%, #20c997 100%);
        }
        .card-header {
            background: linear-gradient(90deg, #20c997 0%, #17a589 100%);
            color: #fff;
            border-radius: 18px 18px 0 0 !important;
            font-weight: 700;
            letter-spacing: 1px;
        }
        h2, h5, .card-title {
            font-weight: 700;
            color: #343a40;
        }
        .container {
            margin-top: 40px;
        }
        .mb-3.text-primary, .fa-3x.text-primary {
            color: #20c997 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">Exam System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Dashboard</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='upload_students.php.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Manage Students</h5>
                        <p class="card-text">Add, view, and manage student records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='upload_subjects.php.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-book fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Manage Subjects</h5>
                        <p class="card-text">Add, view, and manage subject records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='upload_teachers.php.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Manage Teachers</h5>
                        <p class="card-text">Add, view, and manage teacher records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='upload_rooms.php.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-door-open fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Manage Rooms</h5>
                        <p class="card-text">Add, view, and manage room records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='manage_time_slots.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Manage Time Slots</h5>
                        <p class="card-text">Add, view, and manage class time slots</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='generate_seatplan.php.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-chair fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Generate Seat Plan</h5>
                        <p class="card-text">Generate and view exam seat plans</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='generate_routine.php.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Generate Class Routine</h5>
                        <p class="card-text">Generate and manage class routines</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100" onclick="window.location.href='manage_teacher_subjects.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-link fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Manage Teacher-Subject Mappings</h5>
                        <p class="card-text">Edit and manage teacher-subject associations</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>