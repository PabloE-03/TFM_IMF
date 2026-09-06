<?php
    require_once __DIR__ . '/../includes/auth_guard.php';
    
    $loggedIn = isLoggedIn();

    $userData = currentUser();

    if(isset($_POST['login']))
    {
        header("Location: http://vuln-app.test/login.php");

        exit();
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

    .usuario{
        width:10%;
        position: absolute;
        right: 2%;
        top: 27%;
    }

    .usuario img{
        width: 40%;
        display:inline;
    }

    .usuario h2{
        width: fit-content;
        display: inline;
        padding: 1% 0%;
        position: inherit;
        top: 20%;
        left: 45%;
    }

    .usuario button{
        width: 40%;
        padding: 5% 1.5%;
        background-color: whitesmoke;
        margin-top: 8%;
        transition: 0.3 ease;
        cursor: pointer;
    }

    .usuario button:hover{
        background-color: lightgray;
    }
</style>
<header>
    <div class="bienvenida">
        <h1>Muebles Vuln</h1>
        <h2>Muebles para todo tipo de habitaciones</h2>
        <h2>¡No te quedes sin el tuyo!</h2>
    </div>
    <?php if($loggedIn){ ?>
    <div class="usuario">
        <img src="../../assets/img/usuario.png">
        <h2> <?php echo(htmlspecialchars($userData['username']))?></h2>
    </div>
    <?php } else { ?>
    <form class="usuario" method="POST" action="">
        <button type="submit" name="login">Iniciar Sesion</button>
    </form>
    <?php } ?>
</header>
   





