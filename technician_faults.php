<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

requireRole(["Technician"]);

$success = "";
$error = "";

$technicianId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $faultId = $_POST["fault_id"] ?? "";
    $newStatus = $_POST["status"] ?? "";
    $updateNote = trim($_POST["update_note"] ?? "");

    $allowedStatuses = ["In Progress", "Resolved"];

    if (empty($faultId) || empty($newStatus)) {
        $error = "Please select a valid status.";
    } elseif (!in_array($newStatus, $allowedStatuses)) {
        $error = "Invalid status selected.";
    } else {
        try {
            if ($newStatus === "Resolved") {
                $sql = "UPDATE faults
                        SET status = :status,
                            date_resolved = NOW()
                        WHERE fault_id = :fault_id
                        AND assigned_to = :technician_id";
            } else {
                $sql = "UPDATE faults
                        SET status = :status
                        WHERE fault_id = :fault_id
                        AND assigned_to = :technician_id";
            }

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":status" => $newStatus,
                ":fault_id" => $faultId,
                ":technician_id" => $technicianId
            ]);

            if ($stmt->rowCount() > 0) {
                if (empty($updateNote)) {
                    $updateNote = "Status updated to " . $newStatus . ".";
                }

                $updateSql = "INSERT INTO fault_updates
                              (fault_id, updated_by, update_note, new_status)
                              VALUES
                              (:fault_id, :updated_by, :update_note, :new_status)";

                $updateStmt = $conn->prepare($updateSql);

                $updateStmt->execute([
                    ":fault_id" => $faultId,
                    ":updated_by" => $technicianId,
                    ":update_note" => $updateNote,
                    ":new_status" => $newStatus
                ]);

                $success = "Fault status updated successfully.";
            } else {
                $error = "Fault could not be updated. It may not be assigned to you.";
            }

        } catch (PDOException $e) {
            $error = "Error updating fault: " . $e->getMessage();
        }
    }
}

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
        reporter.full_name AS reported_by_name
    FROM faults
    LEFT JOIN users AS reporter
        ON faults.reported_by = reporter.user_id
    WHERE faults.assigned_to = :technician_id
    ORDER BY faults.date_reported DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":technician_id" => $technicianId
]);

$faults = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Assigned Faults - TelOne FMS</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>

<div class="dashboard-container">
    <h1>My Assigned Faults</h1>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="view_faults.php">View Faults</a>
        <a href="technician_faults.php">My Faults</a>
        <a href="logout.php">Logout</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="table-card">
        <h2>Assigned Fault Records</h2>

        <?php if (count($faults) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fault Title</th>
                        <th>Description</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Reported By</th>
                        <th>Date Reported</th>
                        <th>Update</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($faults as $fault): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fault["fault_id"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["fault_title"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["fault_description"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["location"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["priority"]); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $fault["status"])); ?>">
                                    <?php echo htmlspecialchars($fault["status"]); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($fault["reported_by_name"] ?? "Unknown"); ?></td>
                            <td><?php echo htmlspecialchars($fault["date_reported"]); ?></td>
                            <td>
                                <?php if ($fault["status"] !== "Resolved"): ?>
                                    <form method="POST" action="" class="small-form">
                                        <input type="hidden" name="fault_id" value="<?php echo htmlspecialchars($fault["fault_id"]); ?>">

                                        <select name="status">
                                            <option value="">-- Select Status --</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Resolved">Resolved</option>
                                        </select>

                                        <textarea name="update_note" placeholder="Write update note"></textarea>

                                        <button type="submit">Update</button>
                                    </form>
                                <?php else: ?>
                                    <span>Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No faults have been assigned to you yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>