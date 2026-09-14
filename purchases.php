<?php 
    require_once __DIR__ . '/src/config/session.php';
    require_once __DIR__ . '/src/config/database.php';
    require_once __DIR__ . '/src/includes/auth_guard.php';
    require_once __DIR__ . '/src/includes/helpers.php';

     // Solicitar un listado de compras requiere de una sesión activa ya que se necesita el uuid del usuario
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }

    $data_user = currentUser();

    if($data_user == null)
    {
        header('Location: /login.php');
        exit;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        'SELECT pu.uuid, pu.final_price, pu.finished, pu.cantidad, pu.uuid_user,
                pr.name AS product_name, pr.img_url
         FROM purchases pu
         JOIN products pr ON pr.uuid = pu.uuid_product
         WHERE pu.uuid_user = ?');
    $stmt->execute([$data_user['uuid']]);
    $purchases = $stmt->fetchAll();

    if(!$purchases)
    {
        header('Location: /index.php');
        exit;
    }

?>

<style>

    body{
        margin: 0% 0%;
        padding: 0% 0%;
        background-color: whitesmoke;
    }


    h1{
        text-align: center;
    }

    section{
        width:60%;
        padding: 1% 0.5%;
        background-color: gray;
        display: block;
        margin: 2% auto;
        overflow: auto;
        border: 2px solid black;
        border-radius: 3px;
    }

    section div{
        width:80%;
        background-color: lightgray;
        text-align: center;
        display: block;
        margin: 2% auto; 
        border: 2px solid black;
        border-radius: 3px;
    }

    section div img{
        width: 40%;
        border-radius: 5px;
        cursor: pointer;
        
    }

    .finished{
        color: forestgreen;
        font-weight: bolder;
    }

    .not-finished{
        color: darkred;
        font-weight: bolder;
    }

    button{
        width: 10%;
        background-color: whitesmoke;
        padding: 1% 0.5%;
        margin-bottom: 2%;
        border: 1px solid gray;
        border-radius: 5px;
        transition: 0.5s ease;
        cursor: pointer;
    }

    button:hover{
        background-color: lightgreen;
    }

</style>

<?php require_once __DIR__ . '/src/components/header.php' ?>
<main>
    <h1>TUS COMPRAS</h1>
    <section>
        <?php foreach($purchases as $purchase): ?>
            <div>
                <h3><?= htmlspecialchars($purchase['product_name']) ?></h3>
                <img src=<?= $purchase['img_url'] ?>>
                <h3>x<?= htmlspecialchars($purchase['cantidad']) ?> - <?= htmlspecialchars($purchase['final_price']) ?>€</h3>
                <?php if($purchase['finished']) { ?>
                <h4 class="finished">COMPRA REALIZADA</h4>
                <?php } else { ?>
                <h4 class="not-finished">COMPRA NO REALIZADA</h4>
                <a href="payment.php?uuid=<?= urlencode($purchase['uuid']) ?>"><button>Proceder al pago</button></a>
                <?php }?>
            </div>
        <?php endforeach; ?>
        
    </section>
</main>
<?php require_once __DIR__ . '/src/components/footer.php'; ?>
