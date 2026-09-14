<?php
    require_once __DIR__ . '/src/config/session.php';
    require_once __DIR__ . '/src/config/database.php';
    require_once __DIR__ . '/src/includes/auth_guard.php';
    require_once __DIR__ . '/src/includes/helpers.php';
    require_once __DIR__ . '/src/includes/logger.php';


    // Debe venir un uuid en el query string; si no hay nada que consultar, fuera.
    if (!isset($_GET['uuid']) || $_GET['uuid'] === '') {
        header('Location: /index.php');
        exit;
    }

    $uuid = $_GET['uuid'];

    // Whitelist estricta de formato UUID (8-4-4-4-12 hexadecimal).
    // Si no cumple el patrón, ni se acerca a la consulta SQL.
    if (!isValidUuid($uuid)) {
        header('Location: /index.php');
        exit;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT uuid, name, precio, img_url FROM products WHERE uuid = ?');
    $stmt->execute([$uuid]);
    $product = $stmt->fetch();

    // uuid con formato válido pero que no existe en base de datos.
    if (!$product) {
        header('Location: /index.php');
        exit;
    }

    $errors = [];

    // ---------- PROCESAR COMPRA ----------
    if (isset($_POST['comprar'])) {
        // Solo un usuario autenticado puede generar una compra.
        if (!isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }

        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 999],
        ]);

        if ($cantidad === false || $cantidad === null) {
            $errors[] = 'La cantidad indicada no es válida.';
        } else {
            $purchaseUuid = generateUuidV4();
            $finalPrice   = $product['precio'] * $cantidad;

            $insert = $pdo->prepare(
                'INSERT INTO purchases (uuid, final_price, finished, uuid_user, uuid_product, cantidad)
                 VALUES (?, ?, 0, ?, ?, ?)'
            );
            $insert->execute([
                $purchaseUuid,
                $finalPrice,
                $_SESSION['uuid'],
                $product['uuid'],
                $cantidad,
            ]);

            logEvent('purchase_created', 'success', [
                'uuid_purchase' => $purchaseUuid,
                'uuid_product'  => $product['uuid'],
                'cantidad'      => $cantidad,
            ]);
            header('Location: /payment.php?uuid=' . urlencode($purchaseUuid));
            exit;
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
        text-align: center;
    }

    .exposicion{
        background-color: lightgray;
        width:40%;
        display: block;
        margin: 0% auto;
        padding: 1% 0.5%;
        border: 1px solid gray;
        border-radius: 5px;
    }

    .exposicion img{
        width: 40%;
        border: 1px solid gray;
        border-radius: 5px;
        padding: 1%;
    }

    input{
        width: 8%;
        border-radius: 5px;
        border: 1px solid gray;
        padding: 0.8% 0.5%;
    }

    button{
        width: 10%;
        background-color: whitesmoke;
        padding: 1% 0.5%;
        border: 1px solid gray;
        border-radius: 5px;
        transition: 0.5s ease;
        cursor: pointer;
    }

    button:hover{
        background-color: lightgreen;
    }

    .acciones{
        display: flex;
        justify-content: center;
        gap: 3%;
        margin-top: 2%;
    }

    .cancelar{
        width: 10%;
        background-color: whitesmoke;
        padding: 1% 0.5%;
        border: 1px solid gray;
        border-radius: 5px;
        transition: 0.5s ease;
        cursor: pointer;
        text-decoration: none;
        color: black;
        text-align: center;
        box-sizing: border-box;
    }

    .cancelar:hover{
        background-color: lightcoral;
    }

</style>
<div class="layout">
    <?php require_once './src/components/header.php' ?>
    <main>
        <h1>Tu Mueble </h1>
        <?php if (!empty($errors)): ?>
            <div style="color:red; text-align:center;">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="" class="exposicion">
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($product['uuid']) ?>">
            <img src="<?= htmlspecialchars($product['img_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <h3><?= htmlspecialchars($product['name']) ?> - <?= htmlspecialchars(number_format($product['precio'], 2)) ?> €</h3>
            <label>Cantidad - <input type="number" name="cantidad" min="1" max="999" value="1"></label>
            <div class="acciones">
                <button type="submit" name="comprar">Comprar</button>
                <a href="/index.php" class="cancelar">Cancelar</a>
            </div>
        </form>
    </main>
    <?php require_once './src/components/footer.php'; ?>
</div>