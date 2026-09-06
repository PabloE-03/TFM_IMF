<?php
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}

function currentUser(): ?array {
    if(!isset($_SESSION['uuid'])){
        return null;
    }

    return [
        'uuid' => $_SESSION['uuid'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ];
}

function isLoggedIn(): bool {
    return isset($_SESSION['uuid']);
}

?>