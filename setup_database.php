<?php
require_once 'db.php.php';

// Drop tables in correct order to handle foreign key constraints
$tables = [
    'seat_plan',
    'routine',
    'teacher_subjects',
    'subjects',
    'teachers',
    'time_slots',
    'rooms',
    'students'
];

foreach ($tables as $table) {
    $sql = "DROP TABLE IF EXISTS $table";
    if ($conn->query($sql) === TRUE) {
        echo "Table $table dropped successfully<br>";
    } else {
        echo "Error dropping table $table: " . $conn->error . "<br>";
    }
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    semester INT NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Students table created successfully<br>";
} else {
    echo "Error creating students table: " . $conn->error . "<br>";
}

// Create rooms table
$sql = "CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) UNIQUE NOT NULL,
    capacity INT NOT NULL,
    building VARCHAR(50) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Rooms table created successfully<br>";
} else {
    echo "Error creating rooms table: " . $conn->error . "<br>";
}

// Create subjects table
$sql = "CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    semester INT NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Subjects table created successfully<br>";
} else {
    echo "Error creating subjects table: " . $conn->error . "<br>";
}

// Create teachers table
$sql = "CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Teachers table created successfully<br>";
} else {
    echo "Error creating teachers table: " . $conn->error . "<br>";
}

// Create time_slots table
$sql = "CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    UNIQUE KEY unique_slot (day, start_time, end_time)
)";

if ($conn->query($sql) === TRUE) {
    echo "Time slots table created successfully<br>";
} else {
    echo "Error creating time slots table: " . $conn->error . "<br>";
}

// Create routine table
$sql = "CREATE TABLE IF NOT EXISTS routine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    room_id INT NOT NULL,
    time_slot_id INT NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id),
    UNIQUE KEY unique_schedule (room_id, time_slot_id),
    UNIQUE KEY unique_teacher_schedule (teacher_id, time_slot_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Routine table created successfully<br>";
} else {
    echo "Error creating routine table: " . $conn->error . "<br>";
}

// Create seat_plan table
$sql = "CREATE TABLE IF NOT EXISTS seat_plan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_date DATE NOT NULL,
    subject_id INT NOT NULL,
    room_id INT NOT NULL,
    student_id INT NOT NULL,
    seat_number INT NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY unique_seat (exam_date, subject_id, room_id, seat_number)
)";

if ($conn->query($sql) === TRUE) {
    echo "Seat plan table created successfully<br>";
} else {
    echo "Error creating seat plan table: " . $conn->error . "<br>";
}

// Create teacher_subjects table
$sql = "CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Teacher-Subjects table created successfully<br>";
} else {
    echo "Error creating teacher-subjects table: " . $conn->error . "<br>";
}

$departments = array_unique(array_column($students, 'department'));
$debug_info = "Found departments: " . implode(", ", $departments) . "<br>";
$debug_info .= "Total students: " . count($students) . "<br>";
$debug_info .= "Number of color groups: " . count($color_groups) . "<br>";

$conn->close();
?>