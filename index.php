

<?php
function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self'; connect-src 'self';");
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $user_type = $_SESSION['user_type'];
    if (isMobile()) {
        switch ($user_type) {
            case 'student':
                header('Location: student_dashboard_mobile.php');
                break;
            case 'staff':
                header('Location: staff_dashboard_mobile.php');
                break;
            case 'admin':
                header('Location: admin_dashboard_mobile.php');
                break;
            default:
                header('Location: index.php');
                break;
        }
    } else {
        switch ($user_type) {
            case 'student':
                header('Location: student_dashboard.php');
                break;
            case 'staff':
                header('Location: staff_dashboard.php');
                break;
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            default:
                header('Location: index.php');
                break;
        }
    }
    exit();
}

$login_type = isset($_GET['type']) ? $_GET['type'] : 'student';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['user'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE student_number = ? AND user_type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user, $login_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user_data = $result->fetch_assoc();
        if (password_verify($password, $user_data['password'])) {
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['user_name'] = $user_data['name'];
            $_SESSION['user_type'] = $user_data['user_type'];

            if (isMobile()) {
                switch ($_SESSION['user_type']) {
                    case 'admin':
                        header("Location: admin_dashboard_mobile.php");
                        break;
                    case 'staff':
                        header("Location: staff_dashboard_mobile.php");
                        break;
                    case 'student':
                        header("Location: student_dashboard_mobile.php");
                        break;
                    default:
                        header("Location: index.php");
                        break;
                }
            } else {
                switch ($_SESSION['user_type']) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;
                    case 'staff':
                        header("Location: staff_dashboard.php");
                        break;
                    case 'student':
                        header("Location: student_dashboard.php");
                        break;
                    default:
                        header("Location: index.php");
                        break;
                }
            }
            exit();
        } else {
            $error = "Invalid user or password";
        }
    } else {
        $error = "Invalid user or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <div id="navbar">
            <h1 id="name"> SAS - Student Appointment System</h1>
        </div>
    </header>
    <div id="login">
        <div class="container">
            <h2>LOGIN</h2>
            <div class="login-type-switcher">
                <a href="?type=student" class="<?php echo $login_type == 'student' ? 'active' : ''; ?>">Student</a>
                <a href="?type=staff" class="<?php echo $login_type == 'staff' ? 'active' : ''; ?>">Staff</a>
                <a href="?type=admin" class="<?php echo $login_type == 'admin' ? 'active' : ''; ?>">Admin</a>
            </div>
            <?php
            if (isset($error)) {
                echo "<p class='error'>$error</p>";
            }
            ?>
            <form action="" method="post">
                <div class="user">
                    <label for="user"><?php echo ucfirst($login_type); ?> Number:</label>
                    <input type="text" id="user" name="user" placeholder="Enter Your <?php echo ucfirst($login_type); ?> Number" required>
                </div>
                <div class="pass">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="Enter Your Password" required>
                </div>
                <div>
                    <input type="submit" class="submit" value="Log In">
                </div>
            </form>
            <?php if ($login_type == 'student'): ?>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            <?php endif; ?>
            <?php if ($login_type == 'staff'): ?>
                <p>Don't have an account? <a href="register_staff.php">Register here</a></p>
            <?php endif; ?>
            <?php if ($login_type == 'admin'): ?>
                <p>Don't have an account? <a href="register_first_admin.php">Register here</a></p>
            <?php endif; ?>
        </div>
    </div>
    <script>
  document.addEventListener('DOMContentLoaded', function() {
    const switcher = document.querySelector('.login-type-switcher');
    const currentType = '<?php echo $login_type; ?>';
    switcher.setAttribute('data-active', currentType);
  });
</script>
</body>
</html>