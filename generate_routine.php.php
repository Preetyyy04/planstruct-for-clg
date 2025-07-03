<?php
session_start();
require_once 'db.php.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'generate') {
        // Clear existing routine
        $conn->query("DELETE FROM routine");
        
        // Get selected department and semester
        $selected_department = $_POST['department'] ?? 'all';
        $selected_semester = $_POST['semester'] ?? 'all';

        // Get all subjects for the department/semester
        $subject_query = "SELECT s.*, t.id as teacher_id, t.name as teacher_name
                          FROM subjects s
                          LEFT JOIN teachers t ON s.department = t.department
                          WHERE 1=1";
        if ($selected_department !== 'all') {
            $subject_query .= " AND s.department = '" . $conn->real_escape_string($selected_department) . "'";
        }
        if ($selected_semester !== 'all') {
            $subject_query .= " AND s.semester = '" . $conn->real_escape_string($selected_semester) . "'";
        }
        $subject_query .= " ORDER BY s.subject_code";
        $subjects = $conn->query($subject_query)->fetch_all(MYSQLI_ASSOC);
        
        // Get all available time slots and rooms
        $time_slots = $conn->query("SELECT * FROM time_slots ORDER BY day, start_time")->fetch_all(MYSQLI_ASSOC);
        $rooms = $conn->query("SELECT * FROM rooms ORDER BY building, room_number")->fetch_all(MYSQLI_ASSOC);
        
        // Simple round-robin scheduling
        $routine = [];
        $slot_index = 0;
        $room_index = 0;
        foreach ($subjects as $subject) {
            $hours = $subject['contact_hours'] ?? 1; // Default to 1 if not set
            for ($h = 0; $h < $hours; $h++) {
                // Find next available slot and room
                $slot = $time_slots[$slot_index % count($time_slots)];
                $room = $rooms[$room_index % count($rooms)];

                // Check if teacher is already scheduled in this time slot
                $check = $conn->prepare("SELECT id FROM routine WHERE teacher_id = ? AND time_slot_id = ?");
                $check->bind_param("ii", $subject['teacher_id'], $slot['id']);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) {
                    // Teacher is already scheduled in this slot, skip to next slot
                    $slot_index++;
                    $room_index++;
                    $h--; // Try again for this hour
                    continue;
                }
                $check->close();

                // Insert into routine table
                $stmt = $conn->prepare("INSERT INTO routine (subject_id, teacher_id, room_id, time_slot_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiii", $subject['id'], $subject['teacher_id'], $room['id'], $slot['id']);
                $stmt->execute();

                $slot_index++;
                $room_index++;
            }
        }
        $success = "Routine generated and subjects scheduled successfully!";
        $_SESSION['success'] = $success;
    }
}

// Fetch all subjects for display
$subjects = $conn->query("
    SELECT s.*, t.name as teacher_name 
    FROM subjects s 
    LEFT JOIN teachers t ON s.department = t.department 
    ORDER BY s.department, s.semester
")->fetch_all(MYSQLI_ASSOC);

// Fetch all time slots
$time_slots = $conn->query("SELECT * FROM time_slots ORDER BY day, start_time")->fetch_all(MYSQLI_ASSOC);

// Build a unique, sorted list of time slots (by start_time, end_time)
$unique_time_slots = [];
foreach ($time_slots as $slot) {
    $key = $slot['start_time'] . '-' . $slot['end_time'];
    if (!isset($unique_time_slots[$key])) {
        $unique_time_slots[$key] = [
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time']
        ];
    }
}
// Sort by start_time
usort($unique_time_slots, function($a, $b) {
    return strcmp($a['start_time'], $b['start_time']);
});

// Fetch all departments for filter
$departments = $conn->query("SELECT DISTINCT department FROM subjects ORDER BY department")->fetch_all(MYSQLI_ASSOC);
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';

// Fetch all semesters for filter
$semesters = range(1, 8); // Use range to get all 8 semesters
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : '';

// Fetch current routine
$routine = $conn->query("
    SELECT r.*, s.subject_code, s.subject_name, s.department, s.semester,
           t.name as teacher_name, rm.room_number, rm.building,
           ts.day, ts.start_time, ts.end_time
    FROM routine r
    JOIN subjects s ON r.subject_id = s.id
    JOIN teachers t ON r.teacher_id = t.id
    JOIN rooms rm ON r.room_id = rm.id
    JOIN time_slots ts ON r.time_slot_id = ts.id
    ORDER BY ts.day, ts.start_time, s.department
")->fetch_all(MYSQLI_ASSOC);

// Function to get teacher initials
function getTeacherInitials($fullName) {
    $words = explode(' ', $fullName);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}

// Group routine by day
$routine_by_day = [];
foreach ($routine as $class) {
    if (!isset($routine_by_day[$class['day']])) {
        $routine_by_day[$class['day']] = [];
    }
    $routine_by_day[$class['day']][] = $class;
}

// Sort days in correct order
$day_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
// Build a set of days present in the data
$present_days = array_keys($routine_by_day);
$ordered_days = array_values(array_filter($day_order, function($d) use ($present_days) { return in_array($d, $present_days); }));

// Filter routine by department and semester if selected
$filtered_routine = [];
if (($selected_department && $selected_department !== 'all') || ($selected_semester && $selected_semester !== 'all')) {
    foreach ($routine_by_day as $day => $classes) {
        foreach ($classes as $class) {
            if (($selected_department === 'all' || $class['department'] === $selected_department) && 
                ($selected_semester === 'all' || $class['semester'] == $selected_semester)) {
                $filtered_routine[$day][] = $class;
            }
        }
    }
} else {
    $filtered_routine = $routine_by_day;
}

// Fetch teachers with their assigned subjects
$teachers_with_subjects = $conn->query("
    SELECT t.*, s.subject_code, s.subject_name, s.semester
    FROM teachers t
    LEFT JOIN subjects s ON t.department = s.department
    ORDER BY t.department, s.semester
")->fetch_all(MYSQLI_ASSOC);

// Group teachers by department and semester
$teachers_by_dept_sem = [];
foreach ($teachers_with_subjects as $row) {
    $dept = $row['department'];
    $sem = $row['semester'];
    if (!isset($teachers_by_dept_sem[$dept])) {
        $teachers_by_dept_sem[$dept] = [];
    }
    if (!isset($teachers_by_dept_sem[$dept][$sem])) {
        $teachers_by_dept_sem[$dept][$sem] = [];
    }
    $teachers_by_dept_sem[$dept][$sem][] = $row;

}
echo '</tbody></table></div>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Routine - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        .container { margin-top: 40px; }
        .table { margin-top: 20px; }
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Generate Routine</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_SESSION['success'])): ?>
                          <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['error'])): ?>
                          <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>
                        <?php endif; ?>
                        
                        <form method="get" class="mb-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <label for="department" class="col-form-label">Filter by Department:</label>
                                </div>
                                <div class="col-auto">
                                    <select name="department" id="department" class="form-select" onchange="this.form.submit()">
                                        <option value="all">All Departments</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php if ($selected_department === $dept['department']) echo 'selected'; ?>><?php echo htmlspecialchars($dept['department']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label for="semester" class="col-form-label">Filter by Semester:</label>
                                </div>
                                <div class="col-auto">
                                    <select name="semester" id="semester" class="form-select" onchange="this.form.submit()">
                                        <option value="all">All Semesters</option>
                                        <?php foreach ($semesters as $sem): ?>
                                            <option value="<?php echo $sem; ?>" <?php if ($selected_semester == $sem) echo 'selected'; ?>><?php echo $sem; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="generate">
                            <button type="submit" class="btn btn-primary">Generate Routine</button>
                        </form>
                        <button class="btn btn-primary" onclick="window.print()">Print</button>
                        <button class="btn btn-success mt-2" onclick="window.print()"><i class="fa fa-print"></i> Print Routine</button>
                        <style>
                        @media print {
                            body * { visibility: hidden; }
                            .card, .card * { visibility: visible; }
                            .btn, nav, form, .alert, .mt-4 > h5:not(:last-child) { display: none !important; }
                            .card { position: absolute; left: 0; top: 0; width: 100%; }
                        }
                        </style>
                        <div class="mt-4">
                            <?php if ($selected_department && $selected_department !== 'all'): ?>
                                <div class="text-center mb-3">
                                    <h4>
                                        <?php echo strtoupper($selected_department); ?>
                                    </h4>
                                    <h5>Class Routine</h5>
                                    <?php if ($selected_semester && $selected_semester !== 'all'): ?>
                                        <h6>Semester: <?php echo htmlspecialchars($selected_semester); ?></h6>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center" id="routineTable">
                                    <thead>
                                        <tr>
                                            <th>Day / Time</th>
                                            <?php foreach ($unique_time_slots as $slot): ?>
                                                <th>
                                                    <?php echo htmlspecialchars($slot['start_time']) . ' - ' . htmlspecialchars($slot['end_time']); ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ordered_days as $day): ?>
                                            <tr>
                                                <th><?php echo $day; ?></th>
                                                <?php foreach ($unique_time_slots as $slot): ?>
                                                    <td>
                                                        <?php
                                                        $found = false;
                                                        if (isset($filtered_routine[$day])) {
                                                            foreach ($filtered_routine[$day] as $class) {
                                                                if (
                                                                    $class['start_time'] == $slot['start_time'] &&
                                                                    $class['end_time'] == $slot['end_time'] &&
                                                                    $class['department'] === $selected_department
                                                                ) {
                                                                    $teacher_initials = getTeacherInitials($class['teacher_name']);
                                                                    echo htmlspecialchars($class['subject_name']) . ' (' . $teacher_initials . ')';
                                                                    $found = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                        if (!$found) echo "-";
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Current Subjects</h5>
                            <div class="table-responsive">
                                <table id="subjectsTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Semester</th>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                            <th>Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjects as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['department']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not Assigned'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Available Time Slots</h5>
                            <div class="table-responsive">
                                <table id="timeSlotsTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($time_slots as $slot): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($slot['day']); ?></td>
                                                <td><?php echo htmlspecialchars($slot['start_time']); ?></td>
                                                <td><?php echo htmlspecialchars($slot['end_time']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#routineTable').DataTable({
                "paging": true,
                "ordering": true,
                "info": true,
                "searching": true,
                "responsive": true,
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[0, 'asc'], [1, 'asc']] // Sort by day and time
            });
            
            $('#subjectsTable').DataTable({
                "paging": true,
                "ordering": true,
                "info": true,
                "searching": true,
                "responsive": true,
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            
            $('#timeSlotsTable').DataTable({
                "paging": true,
                "ordering": true,
                "info": true,
                "searching": true,
                "responsive": true,
                "pageLength": 6,
                "lengthMenu": [[6, 10, 25, -1], [6, 10, 25, "All"]]
            });
        });

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
