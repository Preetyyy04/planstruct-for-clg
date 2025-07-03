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
            $day = $_POST['day'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            
            $stmt = $conn->prepare("INSERT INTO time_slots (day, start_time, end_time) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $day, $start_time, $end_time);
            $stmt->execute();
            
            $success = "Time slot added successfully!";
        } elseif ($_POST['action'] === 'delete') {
            $time_slot_id = $_POST['time_slot_id'];
            $stmt = $conn->prepare("DELETE FROM time_slots WHERE id = ?");
            $stmt->bind_param("i", $time_slot_id);
            $stmt->execute();
        } elseif ($_POST['action'] === 'delete_all') {
            $conn->query("DELETE FROM routine");
            $conn->query("DELETE FROM time_slots");
            $success = "All time slots and related routines deleted successfully!";
        }
    }
}

// Fetch all time slots
$result = $conn->query("SELECT * FROM time_slots ORDER BY 
    CASE day 
        WHEN 'Monday' THEN 1
        WHEN 'Tuesday' THEN 2
        WHEN 'Wednesday' THEN 3
        WHEN 'Thursday' THEN 4
        WHEN 'Friday' THEN 5
        WHEN 'Saturday' THEN 6
        WHEN 'Sunday' THEN 7
    END, start_time");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Time Slots - Exam System</title>
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
                        <h5 class="mb-0">Add New Time Slot</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="day" class="form-label">Day</label>
                                <select class="form-select" id="day" name="day" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Time Slot</button>
                        </form>
                        <!-- Delete all time slots button -->
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="delete_all">
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete ALL time slots?')">
                                Delete All Time Slots
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Existing Time Slots</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['day']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['start_time'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['end_time'])); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="time_slot_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this time slot?')">
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