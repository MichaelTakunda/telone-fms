<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/dashboard_stats.php";

requireLogin();

$userId = $_SESSION["user_id"];
$userRole = $_SESSION["role"] ?? "";

$selectedFaultId = $_GET["fault_id"] ?? "";

$accessSql = "";
$accessParams = [];

if ($userRole === "Technician") {
    $accessSql = " AND faults.assigned_to = :user_id";
    $accessParams[":user_id"] = $userId;
} elseif ($userRole !== "Admin") {
    $accessSql = " AND faults.reported_by = :user_id";
    $accessParams[":user_id"] = $userId;
}

$faultListSql = "
    SELECT
        faults.fault_id,
        faults.fault_title,
        faults.location,
        faults.priority,
        faults.status,
        faults.date_reported,
        reporter.full_name AS reported_by_name,
        technician.full_name AS assigned_to_name
    FROM faults
    LEFT JOIN users AS reporter
        ON faults.reported_by = reporter.user_id
    LEFT JOIN users AS technician
        ON faults.assigned_to = technician.user_id
    WHERE 1 = 1
    $accessSql
    ORDER BY faults.date_reported DESC
";

$faultListStmt = $conn->prepare($faultListSql);
$faultListStmt->execute($accessParams);
$faults = $faultListStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedFault = null;
$updates = [];

if (!empty($selectedFaultId)) {
    $faultDetailsSql = "
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
        WHERE faults.fault_id = :fault_id
        $accessSql
        LIMIT 1
    ";

    $faultParams = $accessParams;
    $faultParams[":fault_id"] = $selectedFaultId;

    $faultDetailsStmt = $conn->prepare($faultDetailsSql);
    $faultDetailsStmt->execute($faultParams);
    $selectedFault = $faultDetailsStmt->fetch(PDO::FETCH_ASSOC);

    if ($selectedFault) {
        $historySql = "
            SELECT
                fault_updates.update_id,
                fault_updates.update_note,
                fault_updates.new_status,
                fault_updates.date_updated,
                users.full_name AS updated_by_name,
                users.role AS updated_by_role
            FROM fault_updates
            LEFT JOIN users
                ON fault_updates.updated_by = users.user_id
            WHERE fault_updates.fault_id = :fault_id
            ORDER BY fault_updates.update_id ASC
        ";

        $historyStmt = $conn->prepare($historySql);
        $historyStmt->execute([
            ":fault_id" => $selectedFaultId
        ]);

        $updates = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fault History - TelOne FMS</title>
    <link rel=stylesheet href ="./assets/css/styles.css"/>
</head>
<body>

<div class="dashboard-container">
    <h1>Fault History</h1>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_fault.php">Report Fault</a>
        <a href="view_faults.php">View Faults</a>
        <a href="assign_fault.php">Assign Fault</a>
        <a href="technician_faults.php">My Faults</a>
        <a href="fault_history.php">Fault History</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="table-card">
        <h2>Select a Fault</h2>

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
                        <th>Action</th>
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
                            <td>
                                <a href="fault_history.php?fault_id=<?php echo htmlspecialchars($fault["fault_id"]); ?>">
                                    View History
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No faults available.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($selectedFaultId)): ?>
        <?php if ($selectedFault): ?>
            <div class="details-card">
                <h2>Fault Details</h2>

                <p><strong>Fault ID:</strong> <?php echo htmlspecialchars($selectedFault["fault_id"]); ?></p>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($selectedFault["fault_title"]); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($selectedFault["fault_description"]); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($selectedFault["location"]); ?></p>
                <p><strong>Priority:</strong> <?php echo htmlspecialchars($selectedFault["priority"]); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($selectedFault["status"]); ?></p>
                <p><strong>Reported By:</strong> <?php echo htmlspecialchars($selectedFault["reported_by_name"] ?? "Unknown"); ?></p>
                <p><strong>Assigned To:</strong> <?php echo htmlspecialchars($selectedFault["assigned_to_name"] ?? "Not Assigned"); ?></p>
                <p><strong>Date Reported:</strong> <?php echo htmlspecialchars($selectedFault["date_reported"]); ?></p>
                <p><strong>Date Resolved:</strong> <?php echo htmlspecialchars($selectedFault["date_resolved"] ?? "Not Resolved Yet"); ?></p>
            </div>

            <div class="details-card">
                <h2>Update Timeline</h2>

                <?php if (count($updates) > 0): ?>
                    <div class="timeline">
                        <?php foreach ($updates as $update): ?>
                            <div class="timeline-item">
                                <div class="timeline-status">
                                    <?php echo htmlspecialchars($update["new_status"]); ?>
                                </div>

                                <div class="timeline-content">
                                    <p>
                                        <strong>Updated By:</strong>
                                        <?php echo htmlspecialchars($update["updated_by_name"] ?? "Unknown"); ?>
                                        <?php if (!empty($update["updated_by_role"])): ?>
                                            (<?php echo htmlspecialchars($update["updated_by_role"]); ?>)
                                        <?php endif; ?>
                                    </p>

                                    <p>
                                        <strong>Note:</strong>
                                        <?php echo htmlspecialchars($update["update_note"]); ?>
                                    </p>

                                    <p>
                                        <strong>Date:</strong>
                                        <?php echo htmlspecialchars($update["date_updated"]); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No update history available for this fault yet.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="error-message">
                Fault not found or you do not have permission to view this fault.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>