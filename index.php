<?php
session_start();
require_once 'config.php';

// Si no está logueado, mostrar login
if (!isset($_SESSION['usuario'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['usuario'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['departamento'] = $user['departamento'];
            $_SESSION['user_id'] = $user['id'];
            
            // Redirigir según el rol
            if ($user['rol'] == 'admin') {
                header('Location: index.php');
            } else {
                header('Location: mis_disenos.php');
            }
            exit();
        } else {
            $error = " Usuario o contraseña incorrectos";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login - LUZ 360</title>
        <style>
            body { font-family: Arial; margin: 50px; background: linear-gradient(135deg, #0a0a2a 0%, #1a1a4a 100%); }
            .login-box { max-width: 350px; margin: auto; padding: 25px; background: rgba(255,255,255,0.1); border-radius: 10px; }
            input, button { width: 100%; padding: 10px; margin: 8px 0; border-radius: 5px; border: none; }
            button { background: #00d4ff; color: #0a0a2a; font-weight: bold; cursor: pointer; }
            .error { color: #ff8888; text-align: center; }
            h2 { text-align: center; color: white; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Iniciar Sesión - LUZ 360</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Entrar</button>
            </form>
            <p style="text-align: center; margin-top: 15px;">
                <a href="landing.php" style="color: #00d4ff;">← Volver al inicio</a>
            </p>
        </div>
    </body>
    </html>
    <?php
    exit();
}


// DASHBOARD SEGÚN ROL

$rol = $_SESSION['rol'];
$username = $_SESSION['usuario'];
$departamento = $_SESSION['departamento'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - LUZ 360</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0a0a2a 0%, #1a1a4a 100%);
            color: white;
            min-height: 100vh;
        }
        .header {
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(45deg, #00d4ff, #7b2cbf);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        .badge-admin {
            background: #00d4ff;
            color: #0a0a2a;
        }
        .btn {
            background: transparent;
            border: 2px solid #00d4ff;
            padding: 8px 20px;
            border-radius: 30px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn:hover {
            background: #00d4ff;
            color: #0a0a2a;
        }
        .btn-danger {
            border-color: #ff4444;
        }
        .btn-danger:hover {
            background: #ff4444;
            color: white;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .welcome-card {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        .welcome-card h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .menu-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .menu-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            width: 220px;
            text-align: center;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
            text-decoration: none;
            color: white;
        }
        .menu-card:hover {
            transform: translateY(-10px);
            border-color: #00d4ff;
            box-shadow: 0 0 30px rgba(0,212,255,0.3);
        }
        .menu-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .menu-card h3 {
            margin-bottom: 10px;
        }
        .footer {
            text-align: center;
            padding: 30px;
            background: rgba(0,0,0,0.5);
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo"> LUZ 360</div>
        <div class="user-info">
            <span class="badge"><?php echo htmlspecialchars($username); ?></span>
            <span class="badge"><?php echo htmlspecialchars($departamento); ?></span>
            <?php if ($rol == 'admin'): ?>
                <span class="badge badge-admin">ADMINISTRADOR</span>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-danger">Salir</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h1>¡Bienvenido, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Panel de control de <?php echo $rol == 'admin' ? 'administración' : 'cliente'; ?> - LUZ 360</p>
        </div>

        <?php if ($rol == 'admin'): ?>
            <!-- Menu para el administrador -->
            <div class="menu-grid">
                <a href="subir.php" class="menu-card">
                    <h3>Subir diseño</h3>
                    <p>Subir archivos a S3</p>
                </a>
                <a href="admin_usuarios.php" class="menu-card">
                    <h3>Crear usuarios</h3>
                    <p>Dar de alta nuevos clientes</p>
                </a>
                <a href="admin_solicitudes.php" class="menu-card">
                    <h3>Solicitudes</h3>
                    <p>Ver mensajes de contacto</p>
                </a>    
                <a href="perfil.php" class="menu-card">
                    <div class="menu-icon">⚙</div>
                    <h3>Mi perfil</h3>
                    <p>Cambiar contraseña</p>
                </a>
                <a href="admin_disenos.php" class="menu-card">
                    <h3>Diseños clientes</h3>
                    <p>Revisar diseños enviados</p>
                 </a>
            </div>
            <div style="background: #d4edda; padding: 15px; border-radius: 15px; margin-top: 30px; color: #155724; text-align: center;">
                <strong> Panel de administración</strong><br>
                Desde aquí puedes subir diseños, crear clientes y gestionar solicitudes.
            </div>
        <?php else: ?>
            <!-- Menu para el usuario normal o cliente  -->
            <div class="menu-grid">
                <a href="mis_disenos.php" class="menu-card">
                    <h3>Mis diseños</h3>
                    <p>Ver tus diseños subidos</p>
                </a>
                <a href="perfil.php" class="menu-card">
                    <div class="menu-icon">⚙</div>
                    <h3>Mi perfil</h3>
                    <p>Cambiar contraseña</p>
                </a>
            </div>
            <div style="background: #d1ecf1; padding: 15px; border-radius: 15px; margin-top: 30px; color: #0c5460; text-align: center;">
                <strong>Panel de cliente</strong><br>
                Puedes ver y descargar tus diseños. Si quieres enviar nuevos, contacta con el administrador.
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>LUZ 360 - La vida brilla más con tus colores y tus diseños </p>
    </div>
</body>
</html>
