<!-- auth.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login / Registro</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- FORMULARIO LOGIN -->
    <form action="login.php" method="POST">
        <h2>Iniciar Sesión</h2>
        <input type="text" name="username" placeholder="Usuario" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Iniciar Sesión</button>
    </form>

    <!-- FORMULARIO REGISTRO -->
    <form action="register.php" method="POST">
        <h2>Registrarse</h2>
        <input type="text" name="name" placeholder="Nombre completo" required>
        <input type="text" name="username" placeholder="Usuario" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Registrarse</button>
    </form>
</body>
</html>