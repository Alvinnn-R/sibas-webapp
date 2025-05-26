<?php
    session_start();
    include '../app/config.php';

    $errors = [];

    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, password, role, nama_lengkap, profile FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id']      = $row['id'];
                $_SESSION['user_role']    = $row['role'];
                $_SESSION['user_name']    = $row['nama_lengkap'];
                $_SESSION['user_profile'] = $row['profile'];
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Password salah.";
            }
        } else {
            $errors[] = "Username tidak ditemukan.";
        }
        $stmt->close();
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - SIBAS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="./css/style.css">
</head>
<body>
  <div class="overlay"></div>
  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card card-login shadow-sm p-4" style="width: 400px;">
      <!-- Logo di atas form -->
      <img src="./assets/logo_sibas.png" class="logo-sibas" alt="Logo SIBAS">
      <!-- <h3 class="mb-2 text-center">Masuk ke SIBAS</h3> -->

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" id="username" name="username" class="form-control" required value="<?php echo isset($username) ? htmlspecialchars($username) : '' ?>">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>

      <hr>
      <div class="text-center">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
      </div>
    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
