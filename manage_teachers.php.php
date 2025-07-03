<?php
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

// Debug statement to check data
var_dump($teachers_by_dept_sem);
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Manage Teachers</h5>
                </div>
                <div class="card-body">
                    <div class="mt-4">
                        <h5>Teachers with Assigned Subjects</h5>
                        <?php foreach ($teachers_by_dept_sem as $dept => $semesters): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($dept); ?></h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($semesters as $sem => $teachers): ?>
                                        <h6>Semester <?php echo $sem; ?></h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Teacher Name</th>
                                                        <th>Subject Code</th>
                                                        <th>Subject Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($teachers as $teacher): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($teacher['subject_code']); ?></td>
                                                            <td><?php echo htmlspecialchars($teacher['subject_name']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 