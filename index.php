<?php
    require_once __DIR__ . '/src/config/session.php';
    require_once __DIR__ . '/src/config/database.php';
    require_once __DIR__ . '/src/includes/auth_guard.php';

    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT uuid,name,precio,img_url FROM products");
    $products = $stmt->fetchAll();
    $loggedIn = isLoggedIn();
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
        width: 15%;
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
        <form class="sugerencias">
            <h2>¿No encuentras tu producto?</h2>
            <h2>¡Nosotros te lo hacemos!</h2>
            <?php if(!$loggedIn){ ?>
                <h2>Debes de iniciar sessión para acceder a este recurso</h2>
            <?php }
                 else { ?>

                <textarea placeholder="Describenos tu producto ideal, medidas, colores, material..."></textarea>
                <div class="file">
                    <label>Mandanos tu idea para tener una referencia</label>
                    <input type="file" accept=".png,.jpeg,.jpg,.jfif,.tiff">
                </div>
                <button>Mandar Idea</button>
            <?php } ?>
        </form>
    </main>
    <?php require_once './src/components/footer.php'; ?>
</div>