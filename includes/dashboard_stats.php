<?php
require_once __DIR__ . "/../config/db.php";

function getCount($conn, $sql)
{
    $stmt = $conn->query($sql);
    return $stmt->fetchColumn();
}

$totalFaults = getCount(
    $conn,
    "SELECT COUNT(*) FROM faults"
);

$pendingFaults = getCount(
    $conn,
    "SELECT COUNT(*) FROM faults WHERE status = 'Pending'"
);

$assignedFaults = getCount(
    $conn,
    "SELECT COUNT(*) FROM faults WHERE status = 'Assigned'"
);

$inProgressFaults = getCount(
    $conn,
    "SELECT COUNT(*) FROM faults WHERE status = 'In Progress'"
);

$resolvedFaults = getCount(
    $conn,
    "SELECT COUNT(*) FROM faults WHERE status = 'Resolved'"
);
$totalUsers = getCount(
    $conn,
    "SELECT COUNT(*) FROM users"
);
$totalTechnicians = getCount(
    $conn,
    "SELECT COUNT(*) FROM users WHERE role = 'Technician'"
);
?>