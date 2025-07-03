<?php
// Fetch students from the database
$students = $conn->query("
    SELECT s.*, d.name as department_name 
    FROM students s 
    JOIN departments d ON s.department = d.id 
    ORDER BY s.roll_number
")->fetch_all(MYSQLI_ASSOC);

// Group students by department
$students_by_dept = [];
foreach ($students as $student) {
    $dept = $student['department'];
    if (!isset($students_by_dept[$dept])) {
        $students_by_dept[$dept] = [];
    }
    $students_by_dept[$dept][] = $student;
}

// Get all departments
$departments = array_keys($students_by_dept);

// Initialize seat plan
$seat_plan = [];
$total_students = count($students);
$rows = ceil($total_students / 6); // 6 students per row (3 benches × 2 students)

// Generate seat plan
$seat_plan = [];
$seat_number = 1;

// Create a pool of students by department
$student_pool = [];
foreach ($students_by_dept as $dept => $students) {
    $student_pool[$dept] = $students;
}

// Pre-arrange students in a way that ensures no same department students are together
$arranged_students = [];
$dept_indices = array_fill(0, count($departments), 0);
$total_students = count($students);
$current_dept = 0;

while (count($arranged_students) < $total_students) {
    $dept = $departments[$current_dept];
    if (isset($student_pool[$dept][$dept_indices[$current_dept]])) {
        $arranged_students[] = $student_pool[$dept][$dept_indices[$current_dept]];
        $dept_indices[$current_dept]++;
    }
    $current_dept = ($current_dept + 1) % count($departments);
}

// 1. Flatten all students into a single array (already done as $arranged_students above)
$seats_per_row = 6;
$total_seats = count($arranged_students);

echo '<div class="table-responsive"><table class="table table-bordered text-center"><tbody>';
for ($i = 0; $i < $total_seats; $i += $seats_per_row) {
    echo '<tr>';
    for ($j = 0; $j < $seats_per_row; $j++) {
        $idx = $i + $j;
        if ($idx < $total_seats) {
            $student = $arranged_students[$idx];
            echo '<td class="text-center bg-light">';
            echo '<strong>Seat ' . htmlspecialchars($student['seat_number']) . '</strong><br>';
            echo '<strong>' . htmlspecialchars($student['roll_number']) . '</strong><br>';
            echo htmlspecialchars($student['name']) . '<br>';
            echo '<small class="text-muted">' . htmlspecialchars($student['department_name']) . '</small>';
            echo '</td>';
        } else {
            echo '<td></td>';
        }
    }
    echo '</tr>';
}
echo '</tbody></table></div>';
?>

<!-- DataTable CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<!-- jQuery (required for DataTable) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTable JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#routineTable').DataTable();
    });
</script> 