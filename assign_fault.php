<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

requireRole(["Admin"]);

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $faultId = $_POST["fault_id"];
    $technicianId = $_POST["technician_id"];
    $adminId = $_SESSION["user_id"];

    if (empty($faultId) || empty($technicianId)) {
        $error = "Please select both a fault and a technician.";
    } else {
        try {
            $sql = "UPDATE faults
                    SET assigned_to = :technician_id,
                        status = 'Assigned'
                    WHERE fault_id = :fault_id";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":technician_id" => $technicianId,
                ":fault_id" => $faultId
            ]);

            $updateSql = "INSERT INTO fault_updates
                          (fault_id, updated_by, update_note, new_status)
                          VALUES
                          (:fault_id, :updated_by, :update_note, :new_status)";

            $updateStmt = $conn->prepare($updateSql);

            $updateStmt->execute([
                ":fault_id" => $faultId,
                ":updated_by" => $adminId,
                ":update_note" => "Fault assigned to technician.",
                ":new_status" => "Assigned"
            ]);

            $success = "Fault assigned successfully.";

        } catch (PDOException $e) {
            $error = "Error assigning fault: " . $e->getMessage();
        }
    }
}

$faultSql = "SELECT fault_id, fault_title, location, priority, date_reported
             FROM faults
             WHERE status = 'Pending'
             ORDER BY date_reported DESC";

$faultStmt = $conn->prepare($faultSql);
$faultStmt->execute();
$pendingFaults = $faultStmt->fetchAll(PDO::FETCH_ASSOC);

$techSql = "SELECT user_id, full_name
            FROM users
            WHERE role = 'Technician'
            ORDER BY full_name ASC";

$techStmt = $conn->prepare($techSql);
$techStmt->execute();
$technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Fault - TelOne FMS</title>
    <link rel=stylesheet href ="./assets/css/styles.css"/>
</head>
<body>

<div class="dashboard-container">
    <h1>Assign Fault to Technician</h1>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_fault.php">Report Fault</a>
        <a href="view_faults.php">View Faults</a>
        <a href="assign_fault.php">Assign Fault</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="form-card">
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

        <?php if (count($pendingFaults) > 0 && count($technicians) > 0): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Select Pending Fault</label>
                    <select name="fault_id">
                        <option value="">-- Select Fault --</option>

                        <?php foreach ($pendingFaults as $fault): ?>
                            <option value="<?php echo htmlspecialchars($fault["fault_id"]); ?>">
                                <?php echo htmlspecialchars(
                                    "#" . $fault["fault_id"] . " - " .
                                    $fault["fault_title"] . " - " .
                                    $fault["location"] . " - " .
                                    $fault["priority"]
                                ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Technician</label>
                    <select name="technician_id">
                        <option value="">-- Select Technician --</option>

                        <?php foreach ($technicians as $technician): ?>
                            <option value="<?php echo htmlspecialchars($technician["user_id"]); ?>">
                                <?php echo htmlspecialchars($technician["full_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Assign Fault</button>
            </form>
        <?php else: ?>
            <p>No pending faults or technicians available.</p>
        <?php endif; ?>
    </div>

    <div class="table-card" style="margin-top: 20px;">
        <h2>Pending Faults</h2>

        <?php if (count($pendingFaults) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fault Title</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Date Reported</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($pendingFaults as $fault): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fault["fault_id"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["fault_title"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["location"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["priority"]); ?></td>
                            <td><?php echo htmlspecialchars($fault["date_reported"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>There are no pending faults at the moment.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>