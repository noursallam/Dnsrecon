<?php
set_time_limit(300); // Increase execution time limit to 300 seconds

if (isset($_POST['submit'])) {
    // Get the target IP or domain from the form
    $target = base64_encode(escapeshellarg($_POST['target']));

    // Run the ping command and capture the output
    $output = shell_exec("ping -n 4 " . base64_decode($target));

    // Initialize variables for response handling
    $ip_address = '';
    $ttl = '';
    $dns_records = [];
    $open_ports = [];
    $result_message = '';

    // If output is empty or error, display a message
    if (!$output || trim($output) == "") {
        $result_message = "<div class='alert alert-danger'>Ping failed or invalid address!</div>";
    } else {
        // Extract the IP address and TTL from the output
        preg_match('/\[(.*?)\]/', $output, $matches);
        $ip_address = isset($matches[1]) ? $matches[1] : 'IP address not found';
        preg_match('/TTL=(\d+)/', $output, $ttl_matches);
        $ttl = isset($ttl_matches[1]) ? $ttl_matches[1] : 'TTL not found';

        // DNS Lookup
        $dns_records = dns_get_record($_POST['target']);

        // Basic Port Scan with limited ports
        $ports = [22, 80, 443, 3306]; // Common ports (SSH, HTTP, HTTPS, MySQL)
        foreach ($ports as $port) {
            $connection = @fsockopen($_POST['target'], $port, $errno, $errstr, 1); // 1 second timeout
            if (is_resource($connection)) {
                $open_ports[] = $port;
                fclose($connection);
            }
        }

        // Success message
        $result_message = "<div class='alert alert-success'>Ping successful!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>dnsnour</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .ping-form {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-top: 100px;
        }
        .ping-output {
            white-space: pre-wrap;
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="">
            <div class="ping-form">
                <h1 class="text-center mb-4">search Host</h1>
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="target" class="form-label">Enter IP or Domain</label>
                        <input type="text" name="target" class="form-control" id="target" placeholder="e.g. google.com" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg">discover</button>
                    </div>
                </form>
                <?php if (isset($result_message)): ?>
                    <div class="ping-output mt-4">
                        <?php echo $result_message; ?>
                        <?php if (isset($ip_address) && $ip_address !== ''): ?>
                            <div class="alert alert-info">
                                <strong>IP Address:</strong> <?php echo htmlspecialchars($ip_address); ?><br>
                                <strong>Time To Live (TTL):</strong> <?php echo htmlspecialchars($ttl); ?><br>
                                <strong>Open Ports:</strong> <?php echo (count($open_ports) > 0 ? implode(', ', $open_ports) : 'None'); ?><br>
                                <strong>DNS Records:</strong> <pre><?php echo print_r($dns_records, true); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
