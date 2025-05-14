<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = 'mysql.go.strayboots.com';
$dbname = 'newplay';
$username = 'newplay';
$password = 'YFI1W9m$CYi4sd.h';
$charset = 'utf8mb4';

// Function to generate CSV file
function generateCSV($data, $headers) {
    $filename = "exported_data.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers); // Add headers to CSV

    foreach ($data as $row) {
        fputcsv($output, $row); // Add data rows to CSV
    }

    fclose($output);
    exit();
}

try {
    // Set up the DSN (Data Source Name)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    
    // Set up the PDO options
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);

    // Prepare the SQL statement with JOIN to get all related data
    $stmt = $pdo->prepare("
        SELECT 
            p.email, 
            p.first_name, 
            p.last_name, 
            p.team_id, 
            t.name AS team_name, 
            DATE(t.activation) AS team_activation_date, 
            t.order_hunt_id, 
            t.route_id, 
            oh.order_id AS order_id_from_order_hunts, 
            oh.hunt_id AS hunt_id_from_order_hunts, 
            h.city_id AS hunt_city_id, 
            h.name AS hunt_name, 
            ci.name AS city_name, 
            o.name AS order_name, 
            o.client_id, 
            c.company
        FROM 
            players p
        LEFT JOIN 
            teams t ON p.team_id = t.id
        LEFT JOIN 
            order_hunts oh ON t.order_hunt_id = oh.id
        LEFT JOIN 
            hunts h ON oh.hunt_id = h.id
        LEFT JOIN 
            cities ci ON h.city_id = ci.id
        LEFT JOIN 
            orders o ON oh.order_id = o.id
        LEFT JOIN 
            clients c ON o.client_id = c.id
        ORDER BY 
            t.activation DESC -- Order by the most recent data
        LIMIT 
           60000
    ");

    // Execute the statement
    $stmt->execute();

    // Fetch the results
    $results = $stmt->fetchAll();

    // If the download button is clicked, generate and download the CSV file
    if (isset($_POST['download_csv'])) {
        $headers = [
            'Email', 'First Name', 'Last Name', 'Team ID', 'Team Name', 
            'Team Activation Date', 'Order Hunt ID', 'Route ID', 
            'Order ID from Order Hunts', 'Hunt ID from Order Hunts', 
            'Hunt City ID', 'City Name', 'Hunt Name', 
            'Order Name', 'Client ID', 'Company'
        ];
        generateCSV($results, $headers);
    }

    // Display the download button and the results in an HTML table
    echo '<form method="post"><button type="submit" name="download_csv">Download CSV</button></form>';
    echo "<h1>Players Table</h1>";
    echo "<table border='1' style='display:none;'>";
    echo "<tr>";
    // Define column headers
    $headers = [
        'Email', 'First Name', 'Last Name', 'Team ID', 'Team Name', 
        'Team Activation Date', 'Order Hunt ID', 'Route ID', 
        'Order ID from Order Hunts', 'Hunt ID from Order Hunts', 
        'Hunt City ID', 'City Name', 'Hunt Name', 
        'Order Name', 'Client ID', 'Company'
    ];
    foreach ($headers as $header) {
        echo "<th>$header</th>";
    }
    echo "</tr>";

    // Output data of each row
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['first_name']}</td>";
        echo "<td>{$row['last_name']}</td>";
        echo "<td>{$row['team_id']}</td>";
        echo "<td>{$row['team_name']}</td>";
        echo "<td>{$row['team_activation_date']}</td>";
        echo "<td>{$row['order_hunt_id']}</td>";
        echo "<td>{$row['route_id']}</td>";
        echo "<td>{$row['order_id_from_order_hunts']}</td>";
        echo "<td>{$row['hunt_id_from_order_hunts']}</td>";
        echo "<td>{$row['hunt_city_id']}</td>";
        echo "<td>{$row['city_name']}</td>";
        echo "<td>{$row['hunt_name']}</td>";
        echo "<td>{$row['order_name']}</td>";
        echo "<td>{$row['client_id']}</td>";
        echo "<td>{$row['company']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (\PDOException $e) {
    // Handle any errors
    echo 'Connection failed: ' . $e->getMessage();
}
?>

