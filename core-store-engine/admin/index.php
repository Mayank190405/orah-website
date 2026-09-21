<?php
require_once __DIR__ . '/auth.php';
require_auth();

// Placeholder for visual control panel
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Storefront Admin</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 40px; }
        .alert { background: #e0f2fe; color: #0284c7; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Storefront Engine Control Panel</h1>
    
    <div class="alert">
        This is a placeholder for the visual control panel where store managers would configure settings.json and catalog.json.
    </div>
    
    <p>Authentication skeleton is active.</p>
    <p><a href="../public/index.php" target="_blank">View Storefront</a></p>
</body>
</html>
