<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

requireLogin();

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $faultTitle = trim($_POST["fault_title"]);
    $faultDescription = trim($_POST["fault_description"]);
    $location = trim($_POST["location"]);
    $priority = trim($_POST["priority"]);
    $reportedBy = $_SESSION["user_id"];

    if (empty($faultTitle) || empty($faultDescription) || empty($location) || empty($priority)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $sql = "INSERT INTO faults 
                    (fault_title, fault_description, location, priority, status, reported_by)
                    VALUES 
                    (:fault_title, :fault_description, :location, :priority, 'Pending', :reported_by)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":fault_title" => $faultTitle,
                ":fault_description" => $faultDescription,
                ":location" => $location,
                ":priority" => $priority,
                ":reported_by" => $reportedBy
            ]);

            $success = "Fault reported successfully.";

        } catch (PDOException $e) {
            $error = "Error reporting fault: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Fault - TelOne FMS</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="dashboard-container">
    <h1>Report New Fault</h1>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_fault.php">Report Fault</a>
        <a href="view_faults.php">View Faults</a>
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

        <form method="POST" action="">
            <div class="form-group">
                <label>Fault Title</label>
                <input type="text" name="fault_title" placeholder="Example: Fibre cable fault">
            </div>

            <div class="form-group">
                <label>Fault Description</label>
                <textarea name="fault_description" rows="5" placeholder="Describe the fault in detail"></textarea>
            </div>

            <div class="form-group">
                <label>Location / Zone</label>
                <input type="text" name="location" placeholder="Example: TelOne Main Exchange">
            </div>

            <div class="form-group">
                <label>Priority</label>
                <select name="priority">
                    <option value="">-- Select Priority --</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <button type="submit">Submit Fault</button>
        </form>
    </div>
</div>

</body>
</html>