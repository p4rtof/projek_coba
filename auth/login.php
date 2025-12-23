<?php
include '../config/koneksi.php';

$error = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cek user di database
    $q = pg_query($conn, "SELECT id_user, username, password FROM users WHERE username = '$username'");
    $user = pg_fetch_assoc($q);

    if ($user && $user['password'] === $password) { 
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        
        header("Location: /projek_coba/index.php"); // Ke Dashboard Utama
        exit();
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 pt-5">
    <div class="card shadow col-md-4 mx-auto border-0">
        <div class="card-header bg-primary text-white text-center py-3">
            <h4 class="mb-0">Zaddy Printing</h4>
            <small>Login Administrator</small>
        </div>
        <div class="card-body p-4">
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
                <button type="submit" name="login" class="btn btn-primary w-100 fw-bold">Masuk</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>