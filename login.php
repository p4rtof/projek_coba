<?php
include 'koneksi.php';

$error = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cek di database
    // FIX: Change id to id_user
    $q = pg_query($conn, "SELECT id_user, username, password FROM users WHERE username = '$username'");
    $user = pg_fetch_assoc($q);

    if ($user && $user['password'] === $password) { 
        // Login Sukses! Buat sesi
        // FIX: Change id to id_user
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        
        header("Location: index.php"); // Arahkan ke dashboard
        exit();
    } else {
        $error = "Username atau Password salah! Coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg bg-opacity-50">
<div class="container mt-5 pt-5">
    <div class="card shadow col-md-3 mx-auto">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">Login</h4>
            <small>Masuk sebagai Administrator</small>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 fw-bold">Login</button>
                <!-- <div class="mt-3 text-center small">
                    Belum punya akun? <a href="register.php">Daftar Akun Admin</a>
                </div> -->
            </form>
        </div>
    </div>
</div>
</body>
</html>