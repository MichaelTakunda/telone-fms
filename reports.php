<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

requireRole(["Admin"]);

function getCount($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$totalFaults = getCount($conn, "SELECT COUNT(*) FROM faults");

$pendingFaults = getCount($conn, "SELECT COUNT(*) FROM faults WHERE status = :status", [
    ":status" => "Pending"
]);

$assignedFaults = getCount($conn, "SELECT COUNT(*) FROM faults WHERE status = :status", [
    ":status" => "Assigned"
]);

$inProgressFaults = getCount($conn, "SELECT COUNT(*) FROM faults WHERE status = :status", [
    ":status" => "In Progress"
]);

$resolvedFaults = getCount($conn, "SELECT COUNT(*) FROM faults WHERE status = :status", [
    ":status" => "Resolved"
]);

$highPriorityFaults = getCount($conn, "SELECT COUNT(*) FROM faults WHERE priority = :priority", [
    ":priority" => "High"
]);

$unassignedFaults = getCount($conn, "SELECT COUNT(*) FROM faults WHERE assigned_to IS NULL");

$avgResolutionTime = getCount($conn, "
    SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, date_reported, date_resolved)), 1)
    FROM faults
    WHERE status = 'Resolved'
    AND date_resolved IS NOT NULL
");

if ($avgResolutionTime === null) {
    $avgResolutionTime = 0;
}

$statusSql = "
    SELECT status, COUNT(*) AS total
    FROM faults
    GROUP BY status
    ORDER BY total DESC
";

$statusStmt = $conn->prepare($statusSql);
$statusStmt->execute();
$statusReports = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

$prioritySql = "
    SELECT priority, COUNT(*) AS total
    FROM faults
    GROUP BY priority
    ORDER BY total DESC
";

$priorityStmt = $conn->prepare($prioritySql);
$priorityStmt->execute();
$priorityReports = $priorityStmt->fetchAll(PDO::FETCH_ASSOC);

$technicianSql = "
    SELECT
        users.full_name,
        COUNT(faults.fault_id) AS total_assigned,
        SUM(CASE WHEN faults.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
        SUM(CASE WHEN faults.status != 'Resolved' THEN 1 ELSE 0 END) AS active_count
    FROM users
    LEFT JOIN faults
        ON users.user_id = faults.assigned_to
    WHERE users.role = 'Technician'
    GROUP BY users.user_id, users.full_name
    ORDER BY total_assigned DESC
";

$technicianStmt = $conn->prepare($technicianSql);
$technicianStmt->execute();
$technicianReports = $technicianStmt->fetchAll(PDO::FETCH_ASSOC);

$recentFaultsSql = "
    SELECT
        fault_id,
        fault_title,
        location,
        priority,
        status,
        date_reported,
        date_resolved
    FROM faults
    ORDER BY date_reported DESC
    LIMIT 5
";

$recentFaultsStmt = $conn->prepare($recentFaultsSql);
$recentFaultsStmt->execute();
$recentFaults = $recentFaultsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - TelOne FMS</title>
    <link rel=stylesheet href ="./assets/css/styles.css"/>
</head>
<body>

<div class="dashboard-container">
    <h1>Reports Dashboard</h1>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_fault.php">Report Fault</a>
        <a href="view_faults.php">View Faults</a>
        <a href="assign_fault.php">Assign Fault</a>
        <a href="technician_faults.php">My Faults</a>
        <a href="fault_history.php">Fault History</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Faults</h3>
            <p><?php echo htmlspecialchars($totalFaults); ?></p>
        </div>

        <div class="stat-card">
            <h3>Pending</h3>
            <p><?php echo htmlspecialchars($pendingFaults); ?></p>
        </div>

        <div class="stat-card">
            <h3>Assigned</h3>
            <p><?php echo htmlspecialchars($assignedFaults); ?></p>
        </div>

        <div class="stat-card">
            <h3>In Progress</h3>
            <p><?php echo htmlspecialchars($inProgressFaults); ?></p>
        </div>

        <div class="stat-card">
            <h3>Resolved</h3>
            <p><?php echo htmlspecialchars($resolvedFaults); ?></p>
        </div>

        <div class="stat-card">
            <h3>High Priority</h3>
            <p><?php echo htmlspecialchars($highPriorityFaults); ?></p>
        </div>

        <div class="stat-card">
            <h3>Unassigned</h3>
            <p><?php echo htmlspecialchars($unassignedFaults); ?></p>
        </div>

        <div class="stat-card">
            <h3>Avg Resolution Time</h3>
            <p><?php echo htmlspecialchars($avgResolutionTime); ?> hrs</p>
        </div>
    </div>

    <div class="table-card">
        <h2>Faults by Status</h2>

        <?php if (count($statusReports) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total Faults</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statusReports as $row): ?>
                        <tr>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row["status"])); ?>">
                                    <?php echo htmlspecialchars($row["status"]); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row["total"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No status report data available.</p>
        <?php endif; ?>
    </div>

    <div class="table-card" style="margin-top: 20px;">
        <h2>Faults by Priority</h2>

        <?php if (count($priorityReports) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Total Faults</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($priorityReports as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["priority"]); ?></td>
                            <td><?php echo htmlspecialchars($row["total"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No priority report data available.</p>
        <?php endif; ?>
    </div>

    <div class="table-card" style="margin-top: 20px;">
        <h2>Technician Workload</h2>

        <?php if (count($technicianReports) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Technician</th>
                        <th>Total Assigned</th>
                        <th>Active Faults</th>
                        <th>Resolved Faults</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($technicianReports as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["total_assigned"]); ?></td>
                            <td><?php echo htmlspecialchars($row["active_count"]); ?></td>
                            <td><?php echo htmlspecialchars($row["resolved_count"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No technician report data available.</p>
        <?php endif; ?>
    </div>

    <div class="table-card" style="margin-top: 20px;">
        <h2>Recent Faults</h2>

        <?php if (count($recentFaults) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fault Title</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Reported</th>
                        <th>Date Resolved</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentFaults as $fault): ?>
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
                            <td><?php echo htmlspecialchars($fault["date_reported"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["date_resolved"] ?? "Not Resolved"); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No recent faults available.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>