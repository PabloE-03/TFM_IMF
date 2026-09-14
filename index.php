<?php
    require_once __DIR__ . '/src/config/session.php';
    require_once __DIR__ . '/src/config/database.php';
    require_once __DIR__ . '/src/includes/auth_guard.php';
    require_once __DIR__ . '/src/includes/helpers.php';
    require_once __DIR__ . '/src/includes/logger.php';



    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT uuid,name,precio,img_url FROM products");
    $products = $stmt->fetchAll();
    $loggedIn = isLoggedIn();
    $role = $loggedIn!=null ? currentUser()['role'] : null;

    // Acceder a administracion
     if (isset($_POST['go-admin'])) {
        if($role!=null  && $role=='admin')
        {
            header('Location: /admin.php');
            exit;
        }
        else 
        {
            logEvent('access_denied', 'failure', ['required_role' => 'admin', 'resource' => 'go-admin']);
            header('Location: /index.php');
            exit;
        }
    }

    // Procesar el formulario si se ha enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loggedIn) {
        $descripcion = trim($_POST['descripcion'] ?? '');
        $uploadOk = true;
        $file_path = '';
        $error_message = '';

        // Validar descripción
        if (empty($descripcion)) {
            $uploadOk = false;
            $error_message = 'La descripción es obligatoria.';
        }

        // Procesar archivo subido
        if ($uploadOk && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['imagen']['tmp_name'];
            $file_name = $_FILES['imagen']['name'];
            $file_size = $_FILES['imagen']['size'];
            
            // Directorio de subida
            $upload_dir = __DIR__ . '/uploads/ideas/';
            
            // Crear directorio si no existe
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
                chmod($upload_dir, 0775); // Se fuerza el modo real
            }

            // Verificar el magic number del archivo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file_tmp);
            
            $allowed_mime_types = [
                'image/png',
                'image/jpeg',
                'image/jfif',
                'image/tiff'
            ];
            
            if (!in_array($mime_type, $allowed_mime_types)) {
                $uploadOk = false;
                $error_message = 'El tipo de archivo no corresponde con una imagen válida.';
            }

            // Limitar tamaño (ejemplo: 2MB máximo)
            if ($uploadOk && $file_size > 2 * 1024 * 1024) {
                $uploadOk = false;
                $error_message = 'El archivo es demasiado grande. Máximo 2MB.';
            }

            if ($uploadOk) {
                // Generar nombre único para el archivo
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_name = uniqid('idea_', true) . '.' . $file_extension;
                $destination = $upload_dir . $unique_name;
                
                if (move_uploaded_file($file_tmp, $destination)) {
                    // Guardar ruta relativa para la base de datos
                    $file_path = '/uploads/ideas/' . $unique_name;
                } else {
                    $uploadOk = false;
                    $error_message = 'Error al subir el archivo.';
                }
            }
        } else {
            $uploadOk = false;
            $error_message = 'Debes seleccionar una imagen.';
        }

        if ($uploadOk) {
            try {
                $uuid = generateUuidV4();
                $user_sess = currentUser();
                $uuid_user = $user_sess['uuid'];
                $sql = "INSERT INTO ideas (uuid, descripcion, file, uuid_user) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$uuid, $descripcion, $file_path, $uuid_user]);
                
                $success_message = '¡Tu idea ha sido enviada correctamente!';
            } catch (PDOException $e) {
                $error_message = 'Error al guardar en la base de datos.';
                // Si hay error, eliminar el archivo subido
                if (file_exists($destination)) {
                    unlink($destination);
                }
            }
        }
    }

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
        height: 75.8vh;
        max-height: 75.8vh;
    }

    .carrousel{
        width:80vw;
        height: 46vh;
        text-align: center;
        display: block;
        margin: 0% auto;
    }

    .products{
        width: 90%;
        border: 1px solid gray;
        border-radius: 5px;
        background-color: white;
        display:block;
        margin: 0% auto;
        height: 40vh;
        overflow: auto;
    }

    .product-card{
        width: 20%;
        display: inline-block;
        margin: 2% 5%;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }

    .product-card img{
        width: 60%;
        transition: 0.5s ease;
        border-radius: 10px;
    }

    .product-card img:hover{
            transform: scale(1.05,1.05);
        }


    .sugerencias{
        display: block;
        margin: 0% auto;
        text-align: center;
        width: 35%;
        padding: 0.2% 0%;
    }

    textarea{
        width:50%;
        height: 8vh;
    }

    .file{
        display: block;
        margin: 1% auto;
        width: 45%;
        padding: 2% 0%;
        border: 1px solid gray; 
        background-color: white;
    }

    .file *{
        display: block;
        margin: 1% auto;
    }

    button{
        width: 25%;
        background-color: white;
        padding: 0.8% 0%;
        border-radius: 5px;
        border: none;
        border: 1px solid gray;
        cursor: pointer;
        transition: 0.3s ease;
    }

    button:hover{
        background-color: lightgray;
    }

    .alert {
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        text-align: center;
    }

    .alert-error {
        background-color: #ffebee;
        color: #c62828;
        border: 1px solid #ef9a9a;
    }

    .alert-success {
        background-color: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }

</style>

<div class="layout">
    <?php require_once './src/components/header.php' ?>
    <main>
        <div class="carrousel">
            <h1>PRODUCTOS A ELEGIR</h1>
            <div class="products">
            <?php foreach ($products as $product): ?>
                <a class="product-card" href="product.php?uuid=<?= urlencode($product['uuid']) ?>">
                    <img src="<?= htmlspecialchars($product['img_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <h4><?= htmlspecialchars(number_format($product['precio'], 2)) ?> €</h4>
                </a>
            <?php endforeach; ?>
            </div>
        </div>
        <form class="sugerencias" method="POST" enctype="multipart/form-data">
            <h2>¿No encuentras tu producto?</h2>
            <h2>¡Nosotros te lo hacemos!</h2>
            
            <?php if(isset($error_message) && $error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <?php if(isset($success_message) && $success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            
            <?php if(!$loggedIn){ ?>
                <h2>Debes de iniciar sessión para acceder a este recurso</h2>
            <?php }
                 else { ?>

                <textarea name="descripcion" placeholder="Describenos tu producto ideal, medidas, colores, material..." ></textarea>
                <div class="file">
                    <label>Mandanos tu idea para tener una referencia</label>
                    <input type="file" name="imagen" accept=".png,.jpeg,.jpg,.jfif,.tiff" >
                </div>
                <button type="submit">Mandar Idea</button>
                <?php if($role!=null && $role=='admin'){ ?>
                <button type="submit" name="go-admin">Acceder a administración</button>
                <?php } ?>
            <?php } ?>
        </form>
    </main>
    <?php require_once './src/components/footer.php'; ?>
</div>