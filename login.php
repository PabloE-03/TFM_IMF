<?php
require_once __DIR__ . '/src/config/session.php';
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/includes/auth_guard.php';

// Si ya hay sesión activa, no tiene sentido ver el login
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$pdo = getDBConnection();
$errors = [];
$isLogin = true;

if (isset($_POST['showRegister'])) {
    $isLogin = false;
}
if (isset($_POST['showLogin'])) {
    $isLogin = true;
}

function generateUuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ---------- LOGIN ----------
if (isset($_POST['login'])) {
    $isLogin = true;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Rellena todos los campos.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['uuid']     = $user['uuid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            $update = $pdo->prepare('UPDATE users SET access = NOW(), ip_access = ? WHERE uuid = ?');
            $update->execute([$_SERVER['REMOTE_ADDR'], $user['uuid']]);

            header('Location: /index.php');
            exit;
        } else {
            $errors[] = 'Usuario o contraseña incorrectos.';
        }
    }
}

// ---------- REGISTRO ----------
if (isset($_POST['register'])) {
    $isLogin = false;
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = 'Rellena todos los campos.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $check = $pdo->prepare('SELECT uuid FROM users WHERE username = ? OR email = ?');
        $check->execute([$username, $email]);

        if ($check->fetch()) {
            $errors[] = 'El usuario o el email ya están registrados.';
        } else {
            $uuid = generateUuidV4();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare(
                'INSERT INTO users (uuid, username, email, password, role, ip_access) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([$uuid, $username, $email, $hash, 'client', $_SERVER['REMOTE_ADDR']]);

            $isLogin = true; // tras registrar, mostramos directamente el login
        }
    }
}
?>

<style>
     header{
        width: 100%;
        padding: 1% 0%;
        position: relative;
        background-color:lightcoral;
        margin: 0 0;
    }

    .bienvenida{
        width: fit-content;
        display: block;
        margin: 0% auto;
        text-align: center;
    }
    .layout{
        height: 100vh;
        max-height: 100vh;
    }

    body{
        margin: 0% 0%;
        padding: 0% 0%;
        background-color: whitesmoke;
    }

    main{
        height: 77.5vh;
        max-height: 77.5vh;
    }

    form{
        width: 35%;
        background-color: lightgray;
        padding: 1% 0.5%;
        border: solid 1px gray;
        border-radius: 5px;
        display: block;
        margin: 0% auto;
        text-align: center;
        position: absolute;
        top: 30%;
        left: 32.5%;
    }

    form input{
        width: 40%;
        border: 1px solid gray;
        border-radius: 5px;
        padding: 1% 0.5%;
        display: block;
        margin: 1.5% auto;
    }

    form h5:hover{
        text-decoration: underline;
        cursor: pointer;
    }

    form button{
        width: 20%;
        padding: 0.5% 0.2%;
        background-color: whitesmoke;
        margin-top: 1%;
        transition: 0.3 ease;
        cursor: pointer;
        border-radius: 5px;
        border: 1px solid gray;
    }

    form button:hover{
        background-color: lightgray;
    }

    .form-errors{
        width: 35%;
        display: block;
        margin: 0% auto;
        text-align: center;
        color: red;
        position: relative;
        top: 22%;
    }
</style>
<header>
    <div class="bienvenida">
        <h1>Muebles Vuln</h1>
        <h2>Muebles para todo tipo de habitaciones</h2>
        <h2>¡No te quedes sin el tuyo!</h2>
    </div>
</header>
<main>
    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if($isLogin) {?>
        <form method="POST" action="">
            <input type="text" placeholder="Nombre de usuario o email" name="username">
            <input type="password" placeholder="Contraseña" name="password">
            <h5 onclick="this.closest('form').submit()">
                <input type="hidden" name="showRegister" value="1">
                ¿No tienes cuenta? Regístrate
            </h5>
            <button type="submit" name="login">Iniciar Sesión</button>
        </form>
    <?php }else{?>
        <form method="POST" action="">
            <input type="text" placeholder="Nombre de usuario" name="username">
            <input type="email" placeholder="Email" name="email">
            <input type="password" placeholder="Contraseña" name="password">
            <input type="password" placeholder="Confirmar contraseña" name="confirm_password">
            <h5 onclick="this.closest('form').submit()">
                <input type="hidden" name="showLogin" value="1">
                ¿Ya tienes cuenta? Inicia sesión
            </h5>
            <button type="submit" name="register">Registrarse</button>
        </form>
    <?php } ?>
</main>
<?php require_once __DIR__ . '/src/components/footer.php'; ?>