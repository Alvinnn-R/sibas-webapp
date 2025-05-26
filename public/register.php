<?php
    include '../app/config.php';

    $errors  = [];
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nama             = trim($_POST['nama_lengkap']);
        $username         = trim($_POST['username']);
        $password         = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if ($password !== $password_confirm) {
            $errors[] = "Password dan konfirmasi password tidak cocok.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password minimal 6 karakter.";
        }

        if (empty($nama) || empty($username)) {
            $errors[] = "Nama lengkap dan username wajib diisi.";
        }

                               // Handle file upload
        $relative_path = null; // Default jika tidak upload
        if (isset($_FILES['profile']) && $_FILES['profile']['error'] == 0) {
            $profile_tmp_name  = $_FILES['profile']['tmp_name'];
            $profile_name      = $_FILES['profile']['name'];
            $profile_extension = pathinfo($profile_name, PATHINFO_EXTENSION);
            $profile_new_name  = uniqid() . '.' . $profile_extension;

            // PASTIKAN path ke Luar Public!
            $profile_dir = '../uploads/profiles/';
            if (! is_dir($profile_dir)) {
                mkdir($profile_dir, 0777, true);
            }

            $profile_path = $profile_dir . $profile_new_name;

            if (! move_uploaded_file($profile_tmp_name, $profile_path)) {
                $errors[] = "Gambar profil gagal di-upload.";
            } else {
                // RELATIVE PATH ke database untuk <img src="">
                $relative_path            = 'uploads/profiles/' . $profile_new_name;
                $_SESSION['user_profile'] = $relative_path;
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Validasi username sudah dipakai belum
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Username sudah terdaftar, silakan gunakan username lain.";
            }
            $stmt_check->close();

            // Insert ke DB hanya jika username belum dipakai!
            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, username, password, profile) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nama, $username, $hash, $relative_path);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $success = "Registrasi berhasil. <a href='login.php'>Klik di sini untuk login</a>.";
                } else {
                    $errors[] = "Registrasi gagal, silakan coba lagi.";
                }
                $stmt->close();
            }
        }
    }
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - SIBAS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="./css/style.css">
</head>
<body>
  <div class="overlay"></div>
  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card card-login shadow-sm p-4" style="width: 400px;">
      <!-- Logo di atas -->
      <img src="./assets/logo_sibas.png" class="logo-sibas" alt="Logo SIBAS">
      <!-- <h3 class="mb-2 text-center">Daftar Akun Baru</h3> -->

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success ?></div>
      <?php endif; ?>

      <form method="post" action="" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required value="<?php echo isset($nama) ? htmlspecialchars($nama) : '' ?>">
        </div>
        <div class="mb-3">
          <label for="profile" class="form-label">Gambar Profil</label>
          <input type="file" name="profile" id="profile" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" name="username" id="username" class="form-control" required value="<?php echo isset($username) ? htmlspecialchars($username) : '' ?>">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="password_confirm" class="form-label">Konfirmasi Password</label>
          <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Daftar</button>
      </form>

      <hr>
      <div class="text-center">
        Sudah punya akun? <a href="login.php">Login di sini</a>
      </div>
    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
