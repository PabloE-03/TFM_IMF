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
        cursor: pointer;
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

    .options{
        width: 7.5%;
        height:10vh;
        background-color: lightgray;
        border: 1px solid gray;
        
        border-radius: 5px;
        position: absolute;
        top: 79%;
        right: 6.2%;
        transition: 0.5s ease-in-out;
        text-align: center;
        display: none; /* Oculto por defecto */
    }

    .options.show{
        display: block; /* Visible cuando tiene la clase show */
    }

    .square{
        width: 0.75vw;
        height: 1.5vh;
        background-color: red;
        position: inherit;
        top:-9%;
        left: 45%;
        transform: rotate(45deg);
        border-top: 2px solid black;
        border-left: 2px solid black;
        background-color: lightgray;
        transition: 0.5s ease-in-out;
    }

    .options label{
        display: block;
        margin: 0% auto;
        width:100%;
        height:3vh;
        border-top: 1px solid black;
        border-bottom: 1px solid black;
        padding: 1% 0%;
        cursor: pointer;
        font-size: 0.9vw;
        font-weight: bolder;
        align-self: center;
        transition: 0.3s ease;
    }

    .options label:hover{
        padding: 1.2% 0%;
        font-size:1vw;
    }
</style>
<header>
    <div class="options" id="userOptions">
        <div class="square"></div>
        <label>Inicio</label>
        <label>Mis compras</label>
        <label>Cerrar sesión</label>
    </div>
    <div class="bienvenida">
        <h1>Muebles Vuln</h1>
        <h2>Muebles para todo tipo de habitaciones</h2>
        <h2>¡No te quedes sin el tuyo!</h2>
    </div>
    <?php if($loggedIn){ ?>
    <div class="usuario">
        <img src="../../assets/img/usuario.png" id="userIcon" style="cursor: pointer;">
        <h2> <?php echo(htmlspecialchars($userData['username']))?></h2>
    </div>
    <?php } else { ?>
    <form class="usuario" method="POST" action="">
        <button type="submit" name="login">Iniciar Sesion</button>
    </form>
    <?php } ?>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userIcon = document.getElementById('userIcon');
        const optionsMenu = document.getElementById('userOptions');
        let isMenuOpen = false;

        function toggleMenu(e) {
            e.stopPropagation(); 
            isMenuOpen = !isMenuOpen;
            if (isMenuOpen) {
                optionsMenu.classList.add('show');
            } else {
                optionsMenu.classList.remove('show');
            }
        }

        function hideMenu() {
            if (isMenuOpen) {
                optionsMenu.classList.remove('show');
                isMenuOpen = false;
            }
        }

        if (userIcon) {
            userIcon.addEventListener('click', toggleMenu);
        }

        document.addEventListener('click', function(e) {
            if (isMenuOpen) {
                const isClickInsideMenu = optionsMenu.contains(e.target);
                const isClickOnIcon = userIcon ? userIcon.contains(e.target) : false;
                
                if (!isClickInsideMenu && !isClickOnIcon) {
                    hideMenu();
                }
            }
        });

        if (optionsMenu) {
            optionsMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        const menuLabels = optionsMenu ? optionsMenu.querySelectorAll('label') : [];
        menuLabels.forEach(label => {
            label.addEventListener('click', function(e) {
                console.log('Opción seleccionada:', this.textContent);
                
                if (this.textContent.trim() === 'Cerrar sesión') {
                    window.location.href = 'http://vuln-app.test/logout.php';
                } else if (this.textContent.trim() === 'Mis compras') {
                    window.location.href = 'http://vuln-app.test/purchases.php';
                }
                else if (this.textContent.trim() === 'Inicio') {
                    window.location.href = 'http://vuln-app.test/index.php';
                }
                
                hideMenu();
            });
        });
    });
</script>