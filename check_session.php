<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Check</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #2e8a40; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .status { padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px; }
        .yes { background: #d4edda; color: #155724; }
        .no { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Session Status Check</h1>
    
    <div class="box">
        <h2>Session Variables</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="box">
        <h2>Individual Checks</h2>
        <p>logged_in: <span class="status <?= isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'yes' : 'no' ?>">
            <?= isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'TRUE' : 'FALSE') : 'NOT SET' ?>
        </span></p>
        
        <p>username: <span class="status <?= isset($_SESSION['username']) ? 'yes' : 'no' ?>">
            <?= $_SESSION['username'] ?? 'NOT SET' ?>
        </span></p>
        
        <p>user_id: <span class="status <?= isset($_SESSION['user_id']) ? 'yes' : 'no' ?>">
            <?= $_SESSION['user_id'] ?? 'NOT SET' ?>
        </span></p>
        
        <p>email: <span class="status <?= isset($_SESSION['email']) ? 'yes' : 'no' ?>">
            <?= $_SESSION['email'] ?? 'NOT SET' ?>
        </span></p>
        
        <p>role: <span class="status <?= isset($_SESSION['role']) ? 'yes' : 'no' ?>">
            <?= $_SESSION['role'] ?? 'NOT SET' ?>
        </span></p>
    </div>
    
    <div class="box">
        <h2>Actions</h2>
        <a href="oauth/google/login.php" style="padding: 10px 20px; background: #4285f4; color: white; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">Login with Google</a>
        <a href="logout.php" style="padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">Logout</a>
        <a href="index.php" style="padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">Home</a>
    </div>
</body>
</html>
