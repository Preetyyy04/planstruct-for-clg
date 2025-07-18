<?php
session_start();
require_once 'db.php.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $roll_number = $_POST['roll_number'];
            $name = $_POST['name'];
            $department = $_POST['department'];
            $semester = $_POST['semester'];
            
            $stmt = $conn->prepare("INSERT INTO students (roll_number, name, department, semester) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $roll_number, $name, $department, $semester);
            $stmt->execute();
            
            $success = "Student added successfully!";
        } elseif ($_POST['action'] === 'delete') {
            $student_id = $_POST['student_id'];
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
        }
    }
}

// Fetch all students
$result = $conn->query("SELECT * FROM students ORDER BY department, semester, roll_number");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Exam System</title>
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
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(32, 201, 151, 0.08);
            border: none;
        }
        .card-header {
            background: linear-gradient(90deg, #20c997 0%, #17a589 100%);
            color: #fff;
            border-radius: 18px 18px 0 0 !important;
            font-weight: 700;
            letter-spacing: 1px;
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
        h5, .card-title {
            font-weight: 700;
            color: #343a40;
        }
        .btn-danger.btn-sm {
            border-radius: 30px;
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
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Add New Student</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="roll_number" class="form-label">Roll Number</label>
                                <input type="text" class="form-control" id="roll_number" name="roll_number" required>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" required>
                            </div>
                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <input type="number" class="form-control" id="semester" name="semester" min="1" max="8" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Student</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Existing Students</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Roll Number</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Semester</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                                            <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>