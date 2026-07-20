<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

requireLogin();

$sql = "
    SELECT 
        faults.fault_id,
        faults.fault_title,
        faults.fault_description,
        faults.location,
        faults.priority,
        faults.status,
        faults.date_reported,
        faults.date_resolved,
        reporter.full_name AS reported_by_name,
        technician.full_name AS assigned_to_name
    FROM faults
    LEFT JOIN users AS reporter
        ON faults.reported_by = reporter.user_id
    LEFT JOIN users AS technician
        ON faults.assigned_to = technician.user_id
    ORDER BY faults.date_reported DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$faults = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Faults - TelOne FMS</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="dashboard-container">
    <h1>Fault Records</h1>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_fault.php">Report Fault</a>
        <a href="view_faults.php">View Faults</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="table-card">
        <h2>Reported Faults</h2>

        <?php if (count($faults) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fault Title</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Reported By</th>
                        <th>Assigned To</th>
                        <th>Date Reported</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($faults as $fault): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fault["fault_id"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["fault_title"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["location"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["priority"]); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $fault["status"])); ?>">
                                    <?php echo htmlspecialchars($fault["status"]); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($fault["reported_by_name"] ?? "Unknown"); ?></td>
                            <td><?php echo htmlspecialchars($fault["assigned_to_name"] ?? "Not Assigned"); ?></td>
                            <td><?php echo htmlspecialchars($fault["date_reported"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No faults have been reported yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>