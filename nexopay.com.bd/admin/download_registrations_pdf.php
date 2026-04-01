<?php
// Remove this line: session_start();
require_once 'includes/auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all registrations with event details
$query = "SELECT r.*, e.title as event_title FROM registrations r 
          LEFT JOIN events e ON r.event_id = e.id 
          ORDER BY r.registration_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for HTML download (can be converted to PDF)
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="registrations_' . date('Y-m-d') . '.html"');

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Event Registrations Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; margin-bottom: 5px; }
        .header p { color: #666; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .total-row { font-weight: bold; background-color: #e8f4f8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nexopay Event Registrations Report</h1>
        <p>Generated on ' . date('F d, Y H:i:s') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Event</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Guardian Number</th>
                <th>Tickets</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>';

$totalAmount = 0;
$totalTickets = 0;

foreach ($registrations as $registration) {
    $amount = $registration['total_amount'] ?? 0;
    $tickets = $registration['ticket_quantity'] ?? 0;
    $totalAmount += $amount;
    $totalTickets += $tickets;
    
    $html .= '
            <tr>
                <td>' . htmlspecialchars($registration['full_name']) . '</td>
                <td>' . htmlspecialchars($registration['event_title'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($registration['email']) . '</td>
                <td>' . htmlspecialchars($registration['phone']) . '</td>
                <td>' . htmlspecialchars($registration['guardian_number']) . '</td>
                <td>' . $tickets . '</td>
                <td>&#2547;' . number_format($amount, 2) . '</td>
                <td>' . date('M d, Y', strtotime($registration['registration_date'])) . '</td>
            </tr>';
}

$html .= '
            <tr class="total-row">
                <td colspan="5"><strong>TOTAL</strong></td>
                <td><strong>' . $totalTickets . '</strong></td>
                <td><strong>&#2547;' . number_format($totalAmount, 2) . '</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
        <p>This report contains ' . count($registrations) . ' registration(s)</p>
    </div>
</body>
</html>';

echo $html;
?>
