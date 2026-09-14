<?php 
    require_once __DIR__ . '/src/config/session.php';
    require_once __DIR__ . '/src/config/database.php';
    require_once __DIR__ . '/src/includes/auth_guard.php';
    require_once __DIR__ . '/src/includes/logger.php';


    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }

    $userData = currentUser();

    if ($userData['role'] != 'admin') {
        logEvent('access_denied', 'failure', ['required_role' => 'admin', 'resource' => 'admin.php']);
        header('Location: /index.php');
        exit;
    }

    $pdo = getDBConnection();

    $errors  = [];
    $success = '';

    // ---------- ALTA DE PRODUCTO ----------
    if (isset($_POST['add_product'])) {
        $name   = trim($_POST['name'] ?? '');
        $precio = $_POST['precio'] ?? '';

        if ($name === '' || $precio === '') {
            $errors[] = 'Rellena todos los campos del producto.';
        } elseif (!is_numeric($precio) || $precio <= 0) {
            $errors[] = 'El precio debe ser un número mayor que 0.';
        } elseif (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Debes subir una imagen válida.';
        } else {
            $allowed = ['png', 'jpg', 'jpeg', 'tiff'];
            $ext     = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Formato de imagen no permitido.';
            } else {
                // Normalizar nombre del producto para el archivo
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
                $safeName = trim(preg_replace('/-+/', '-', $safeName), '-');
                if ($safeName === '') {
                    $safeName = 'producto';
                }

                $fileName  = $safeName . '.' . $ext;
                $uploadDir = __DIR__ . '/uploads/products/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destPath = $uploadDir . $fileName;
                $imgUrl   = '/uploads/products/' . $fileName;

                if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destPath)) {
                    $errors[] = 'No se pudo guardar la imagen.';
                } else {
                    $uuid = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );

                    $stmt = $pdo->prepare(
                        'INSERT INTO products (uuid, name, precio, img_url, updated) VALUES (?, ?, ?, ?, NOW())'
                    );
                    $stmt->execute([$uuid, $name, (int)$precio, $imgUrl]);

                    $success = 'Producto añadido correctamente.';
                }
            }
        }
    }

    // ---------- LISTADO DE IDEAS ----------
    $ideasStmt = $pdo->prepare(
        'SELECT i.uuid, i.descripcion, i.file, u.username
         FROM ideas i
         INNER JOIN users u ON u.uuid = i.uuid_user
         ORDER BY i.uuid DESC'
    );
    $ideasStmt->execute();
    $ideas = $ideasStmt->fetchAll();
?>
<style>
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
        height: 73.5vh;
        max-height: 73.5vh;
        overflow-y: auto;
    }

    .products,.ideas{
        width: 40%;
        background-color: lightgray;
        display: block;
        margin: 2% auto;
        border: 2px solid black;
        border-radius: 5px;
        text-align: center;
    }

    .products label{
        width: 100%;
        display: block;
    }

    .products input{
        display: block;
        margin: 2% auto;
        border-radius: 5px;
        border: 1px solid gray;
        padding: 0.8% 0.5%;
    }

    .products button{
        margin: 2% auto 3%;
        display: block;
        padding: 0.6% 2%;
        border-radius: 5px;
        border: 1px solid gray;
        cursor: pointer;
    }

    .ideas{
        width: 60%;
        height: 40vh;
        overflow-y: auto;
        padding-bottom: 1%;
    }

    .idea-card{
        background-color: #fff;
        border: 1px solid #999;
        border-radius: 5px;
        margin: 2% auto;
        padding: 1%;
        width: 90%;
        text-align: left;
    }

    .idea-card img{
        max-width: 100%;
        max-height: 200px;
        display: block;
        margin: 0 auto 1%;
        object-fit: contain;
    }

    .idea-card .username{
        font-weight: bold;
        display: block;
        margin-bottom: 0.5%;
    }

    .idea-card textarea{
        width: 100%;
        resize: vertical;
        border-radius: 5px;
        border: 1px solid gray;
        padding: 0.5%;
        box-sizing: border-box;
    }

    .msg-error{ color: #b00020; font-weight: bold; text-align: center; }
    .msg-success{ color: #0a7a0a; font-weight: bold; text-align: center; }
</style>

<div class="layout">
    <?php require_once './src/components/header.php' ?>
    <main>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $e): ?>
                <p class="msg-error"><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p class="msg-success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form class="products" method="POST" enctype="multipart/form-data">
            <h2>SUBIR PRODUCTO</h2>
            <label>NOMBRE</label>
            <input type="text" name="name" placeholder="Nombre del producto..." required>
            <label>PRECIO</label>
            <input type="number" name="precio" min="1" required>
            <label>IMAGEN</label>
            <input type="file" name="imagen" accept=".png,.jpg,.jpeg,.tiff" required>
            <button type="submit" name="add_product">AÑADIR PRODUCTO</button>
        </form>

        <div class="ideas">
            <h2>IDEAS DE LOS USUARIOS</h2>
            <?php if (empty($ideas)): ?>
                <p>No hay ideas registradas.</p>
            <?php else: ?>
                <?php foreach ($ideas as $idea): ?>
                    <div class="idea-card">
                        <img src="<?= htmlspecialchars($idea['file']) ?>" alt="Idea">
                        <span class="username"><?= htmlspecialchars($idea['username']) ?></span>
                        <textarea readonly><?= htmlspecialchars($idea['descripcion']) ?></textarea>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once './src/components/footer.php' ?>
</div>