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

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nama, $username, $hash);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success = "Registrasi berhasil. <a href='login.php'>Klik di sini untuk login</a>.";
            } else {
                $errors[] = "Registrasi gagal, username mungkin sudah terdaftar.";
            }
            $stmt->close();
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
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-sm p-4" style="width: 400px;">
    <h3 class="mb-4 text-center">Daftar Akun Baru</h3>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error)?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo $success?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required value="<?php echo isset($nama) ? htmlspecialchars($nama) : ''?>">
      </div>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" id="username" class="form-control" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''?>">
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
