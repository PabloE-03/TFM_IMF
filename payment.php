<?php
    require_once __DIR__ . '/src/config/session.php';
    require_once __DIR__ . '/src/config/database.php';
    require_once __DIR__ . '/src/includes/auth_guard.php';
    require_once __DIR__ . '/src/includes/helpers.php';

    // El pago requiere una sesión activa: sin ella no hay uuid_user con el que comparar.
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }

    // Sin uuid que consultar, no hay nada que pagar.
    if (!isset($_GET['uuid']) || $_GET['uuid'] === '') {
        header('Location: /index.php');
        exit;
    }

    $purchaseUuid = $_GET['uuid'];

    // Misma whitelist estricta de formato UUID que en product.php.
    if (!isValidUuid($purchaseUuid)) {
        header('Location: /index.php');
        exit;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        'SELECT pu.uuid, pu.final_price, pu.finished, pu.cantidad, pu.uuid_user,
                pr.name AS product_name, pr.img_url
         FROM purchases pu
         JOIN products pr ON pr.uuid = pu.uuid_product
         WHERE pu.uuid = ?'
    );
    $stmt->execute([$purchaseUuid]);
    $purchase = $stmt->fetch();

    // uuid con formato válido pero que no existe en base de datos.
    if (!$purchase) {
        header('Location: /index.php');
        exit;
    }

    // La compra debe pertenecer al usuario de la sesión actual.
    if ($purchase['uuid_user'] !== $_SESSION['uuid']) {
        header('Location: /index.php');
        exit;
    }

    // Una compra ya finalizada no se vuelve a procesar (evita reintentos/replay).
    if ((int) $purchase['finished'] === 1) {
        header('Location: /index.php');
        exit;
    }

    $errors = [];

    if (isset($_POST['cancelar'])) {
        header('Location: /index.php');
        exit;
    }

    if (isset($_POST['pago'])) {
        $card       = trim($_POST['card'] ?? '');
        $cvv        = trim($_POST['cvv'] ?? '');
        $expiration = trim($_POST['expiration'] ?? '');

        if ($card === '' || $cvv === '' || $expiration === '') {
            $errors[] = 'Todos los campos de pago son obligatorios.';
        } else {
            // Whitelist por regex: solo dígitos (y "/" en la expiración).
            // Esto es lo que evita XSS aquí, no hace falta "limpiar" el
            // input a mano: si no cumple el patrón exacto, se rechaza entero.
            if (!preg_match('/^\d{13,16}$/', $card)) {
                $errors[] = 'El número de tarjeta debe tener entre 13 y 16 dígitos numéricos.';
            }
            if (!preg_match('/^\d{3}$/', $cvv)) {
                $errors[] = 'El CVV debe tener exactamente 3 dígitos numéricos.';
            }
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiration)) {
                $errors[] = 'La expiración debe tener el formato MM/AA.';
            }
        }

        if (empty($errors)) {
            $update = $pdo->prepare(
                'UPDATE purchases
                 SET card = ?, cvv = ?, expiration = ?, finished = 1, purchase_at = NOW()
                 WHERE uuid = ? AND uuid_user = ?'
            );
            $update->execute([$card, $cvv, $expiration, $purchaseUuid, $_SESSION['uuid']]);

            header('Location: /index.php');
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

        .pago{
            background-color: lightgray;
            width:40%;
            display: block;
            margin: 0% auto;
            padding: 1% 0.5%;
            border: 1px solid gray;
            border-radius: 5px;
        }

        .producto,.datos{
            width: 60%;
            padding: 1% 0.5%;
            border: 1px solid gray;
            border-radius: 5px;
            display: block;
            margin: 3% auto;
        }

        .producto label{
            display:block;
            margin: 1% auto;
        }

        .producto label input{
            width: 20%;
            border-radius: 5px;
            border: 1px solid gray;
            padding: 0.8% 0.5%;
        }

        .datos input{
            width: 60%;
            border-radius: 5px;
            border: 1px solid gray;
            padding: 0.8% 0.5%;
            margin-top: 2%;
        }

        img{
            width: 20%;
            border-radius: 5px;
        }

        button{
            width: 15%;
            background-color: whitesmoke;
            padding: 1% 0.5%;
            border: 1px solid gray;
            border-radius: 5px;
            transition: 0.5s ease;
            cursor: pointer;
        }

        .form-errors{
            color: red;
            text-align: center;
        }
</style>
<div class="layout">
    <?php require_once __DIR__ . '/src/components/header.php' ?>
    <main>
        <h1>Pago del Producto</h1>

        <?php if (!empty($errors)): ?>
            <div class="form-errors">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="pago" method="POST" action="">

            <div class="producto">
                <label>Producto - <input type="text" value="<?= htmlspecialchars($purchase['product_name']) ?>" disabled></label>
                <label>Cantidad - <input type="number" value="<?= (int) $purchase['cantidad'] ?>" disabled></label>
                <label>Precio Final - <input type="number" value="<?= htmlspecialchars($purchase['final_price']) ?>" disabled></label>
                <h3>Vista Previa</h3>
                <img src="<?= htmlspecialchars($purchase['img_url']) ?>" alt="<?= htmlspecialchars($purchase['product_name']) ?>">
            </div>

            <div class="datos">
                <input type="text" name="card" placeholder="Numero de tarjeta" maxlength="16" inputmode="numeric" autocomplete="off">
                <input type="text" name="cvv" placeholder="CVV" maxlength="3" inputmode="numeric" autocomplete="off">
                <input type="text" name="expiration" placeholder="MM/AA" maxlength="5" autocomplete="off">
            </div>
            <button type="submit" name="pago">Pagar</button>
            <button type="submit" name="cancelar">Cancelar compra</button>
        </form>
    </main>
    <?php require_once __DIR__ . '/src/components/footer.php'; ?>
</div>