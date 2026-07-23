<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/includes/dashboard_stats.php";
// require_once __DIR__ . "/report_fault.php";


requireLogin();

$fullName = $_SESSION["full_name"];
$role = $_SESSION["role"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - TelOne FMS</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body>

<div class="dashboard-container">
    <h1>TelOne Fault Management System</h1>

    <div class="welcome-box">
        <h2>Welcome, <?php echo htmlspecialchars($fullName); ?></h2>
        <p>Your role: <strong><?php echo htmlspecialchars($role); ?></strong></p>
    </div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_fault.php">Report Fault</a>
        <a href="view_faults.php">View Faults</a>
        <a href="assign_fault.php">Assign Fault</a>
        <a href="technician_faults.php">My Faults</a>
        <a href="reports.php">Reports</a>
        <a href="fault_history.php">Fault History</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Total Faults</h3>
            <p><?php echo $totalFaults; ?></p>
        </div>

        <div class="card pending">
            <h3>Pending Faults</h3>
            <p><?php echo $pendingFaults; ?></p>
        </div>

        <div class="card">
            <h3>Assigned Faults</h3>
            <p><?php echo $assignedFaults; ?></p>
        </div>

        <div class="card">
            <h3>In Progress Faults</h3>
            <p><?php echo $inProgressFaults; ?></p>
        </div>

        <div class="card">
            <h3>Resolved Faults</h3>
            <p><?php echo $resolvedFaults; ?></p>
        </div>

        <div class="card">
            <h3>Total Operators</h3>
            <p><?php echo $resolvedFaults; ?></p>
        </div>

        <div class="card">
            <h3>Total Technicians</h3>
            <p><?php echo $totalTechnicians; ?></p>
        </div>

        <div class="card">
            <h3>Total Users</h3>
            <p><?php echo $totalUsers; ?></p>
        </div>
    </div>
</div>

</body>
</html>