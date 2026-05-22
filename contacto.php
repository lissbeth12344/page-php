contacto.php
<?php
require_once 'config.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_raw = trim($_POST['email']);
    $comentario = trim($_POST['mensaje']);
    
    // Validacion del email , no se verifica tal cuál el correo 
    if (empty($email_raw)) {
        $error = " El email es obligatorio";
    } elseif (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        $error = " Formato de email inválido. Ejemplo: nombre@dominio.com";
    } elseif (strlen($email_raw) > 100) {
        $error = " El email no puede tener más de 100 caracteres";
    } elseif (preg_match('/[<>\(\)\[\]\\\^\{\}]/', $email_raw)) {
        $error = " El email contiene caracteres no permitidos";
    } elseif (!preg_match('/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/', $email_raw)) {
        $error = " El email debe tener un dominio válido (ej: @gmail.com, @empresa.es)";
    } else {
        $email = $email_raw;
        
        // Validar mensaje
        if (strlen($comentario) < 10) {
            $error = " El mensaje debe tener al menos 10 caracteres";
        } elseif (strlen($comentario) > 500) {
            $error = "El mensaje no puede tener más de 500 caracteres";
        } else {
            $stmt = $pdo->prepare("INSERT INTO contactos (email, mensaje) VALUES (?, ?)");
            if ($stmt->execute([$email, $comentario])) {
                $mensaje = "Mensaje enviado correctamente. Te contactaremos pronto.";
            } else {
                $error = " Error al enviar. Intenta de nuevo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contacto - LUZ 360</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0a0a2a 0%, #1a1a4a 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .contacto-box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255,255,255,0.2);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #00d4ff, #7b2cbf);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            color: white;
        }
        input::placeholder, textarea::placeholder {
            color: rgba(255,255,255,0.6);
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #00d4ff, #7b2cbf);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        .mensaje-exito { 
            background: #d4edda; 
            color: #155724; 
            padding: 10px; 
            border-radius: 10px; 
            margin-bottom: 15px; 
            text-align: center;
        }
        .mensaje-error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 10px; 
            border-radius: 10px; 
            margin-bottom: 15px; 
            text-align: center;
        }
        .volver { text-align: center; margin-top: 15px; }
        .volver a { color: #00d4ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="contacto-box">
        <h2>Contáctanos</h2>
        <p style="text-align: center; margin-bottom: 20px;">¿Quieres trabajar con nosotros? Déjanos tu mensaje</p>
        
        <?php if ($mensaje): ?>
            <div class="mensaje-exito"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="contactoForm">
            <input type="email" name="email" id="email" placeholder="Tu correo electrónico" required>
            <textarea name="mensaje" id="mensaje" rows="5" placeholder="¿Por qué quieres trabajar con nosotros? Cuéntanos tu proyecto..." required></textarea>
            <button type="submit">Enviar mensaje →</button>
        </form>
        <div class="volver">
            <a href="landing.php">← Volver al inicio</a>
        </div>
    </div>

    <script>
        // Validación adicional en el navegador (mejor experiencia)
        document.getElementById('contactoForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                alert('Por favor, introduce un email válido (ej: nombre@dominio.com)');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
