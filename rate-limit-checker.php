<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Limit Checker - Smartphone Accessories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            display: none;
        }
        .result.allowed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .result.blocked {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-clock"></i> Rate Limit Checker</h1>
        
        <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
            <strong>Current Time:</strong> <span id="currentTime"><?php echo date('g:i A \o\n M j, Y'); ?></span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter email to check submission status">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-search"></i> Check Submission Status
            </button>
        </form>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
            require_once 'includes/config.php';
            require_once 'includes/db_connect.php';
            
            try {
                $pdo = getDB();
                $email = trim($_POST['email']);
                
                // Check for recent submissions
                $stmt = $pdo->prepare("
                    SELECT id, created_at, status FROM contact_submissions 
                    WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$email]);
                $recentSubmission = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($recentSubmission) {
                    // Calculate time remaining
                    $submissionTime = new DateTime($recentSubmission['created_at']);
                    $now = new DateTime();
                    $allowedTime = clone $submissionTime;
                    $allowedTime->add(new DateInterval('PT24H'));
                    
                    if ($now < $allowedTime) {
                        // Format the allowed time in 12-hour format
                        $allowedTimeFormatted = $allowedTime->format('g:i A \o\n M j, Y');
                        
                        echo '<div class="result blocked" style="display: block;">';
                        echo '<i class="fas fa-ban"></i> <strong>Rate Limited</strong><br>';
                        echo "Previous submission: #{$recentSubmission['id']} at " . $submissionTime->format('g:i A \o\n M j, Y') . '<br>';
                        echo "Status: " . ucfirst($recentSubmission['status']) . '<br>';
                        echo "Next submission allowed: <strong>{$allowedTimeFormatted}</strong><br>";
                        echo '<span style="color: #856404; font-size: 0.9em;">You can request another submission after this time.</span>';
                        echo '</div>';
                    } else {
                        echo '<div class="result allowed" style="display: block;">';
                        echo '<i class="fas fa-check"></i> <strong>Submission Allowed</strong><br>';
                        echo "You can submit a new inquiry now. Previous submission was more than 24 hours ago.";
                        echo '</div>';
                    }
                } else {
                    echo '<div class="result allowed" style="display: block;">';
                    echo '<i class="fas fa-check"></i> <strong>Submission Allowed</strong><br>';
                    echo "No recent submissions found for this email address. You can submit a new inquiry.";
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="result blocked" style="display: block;">';
                echo '<i class="fas fa-exclamation-triangle"></i> <strong>Error</strong><br>';
                echo "Could not check submission status. Please try again.";
                echo '</div>';
            }
        }
        ?>
        
        <div class="back-link">
            <a href="contact.php"><i class="fas fa-arrow-left"></i> Back to Contact Form</a> |
            <a href="test/test-direct-api.html"><i class="fas fa-vial"></i> Test Direct API</a>
        </div>
    </div>

    <script>
        // Update current time every second
        function updateTime() {
            const now = new Date();
            const options = {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            };
            document.getElementById('currentTime').textContent = 
                now.toLocaleString('en-US', options).replace(',', ' on');
        }
        
        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>