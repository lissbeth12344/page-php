<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombre_original = basename($archivo['name']);
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    $cliente_nombre = $_SESSION['usuario'];
    $cliente_id = $_SESSION['user_id'];
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Extensiones permitidas en la subida de archivos
    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'web'];
    
    if (!in_array($extension, $permitidas)) {
        $mensaje = " Tipo de archivo no permitido. Extensiones: " . implode(', ', $permitidas);
        $tipo_mensaje = 'error';
    } elseif ($archivo['size'] > 10 * 1024 * 1024) {
        $mensaje = " El archivo es demasiado grande (máx 10MB)";
        $tipo_mensaje = 'error';
    } else {
        // Guardar el diseño temporalmente en el servidor
        $timestamp = time();
        $nombre_unico = $timestamp . '_' . $cliente_nombre . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $nombre_original);
        $ruta_temporal = '/var/www/html/uploads_temp/' . $nombre_unico;
        
        if (move_uploaded_file($archivo['tmp_name'], $ruta_temporal)) {
            // Guarda el registro del diseño en la BBDD y en su tabla disenos
            $stmt = $pdo->prepare("INSERT INTO disenos (cliente_id, cliente_nombre, archivo_nombre, archivo_temporal, descripcion, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmt->execute([$cliente_id, $cliente_nombre, $nombre_original, $nombre_unico, $descripcion]);
            
            $mensaje = "¡Diseño enviado correctamente! El administrador lo revisará pronto.";
            $tipo_mensaje = 'exito';
        } else {
            $mensaje = " Error al guardar el archivo";
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Diseño - LUZ 360</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        .logo { font-size: 24px; font-weight: bold; background: linear-gradient(45deg, #00d4ff, #7b2cbf); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .badge { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; }
        .btn { background: transparent; border: 2px solid #00d4ff; padding: 8px 20px; border-radius: 30px; color: white; text-decoration: none; transition: 0.3s; }
        .btn:hover { background: #00d4ff; color: #0a0a2a; }
        .btn-danger { border-color: #ff4444; }
        .btn-danger:hover { background: #ff4444; color: white; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .upload-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .upload-icon { font-size: 64px; margin-bottom: 20px; }
        .upload-card h1 { font-size: 28px; margin-bottom: 10px; background: linear-gradient(45deg, #00d4ff, #7b2cbf); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .upload-card p { margin-bottom: 20px; opacity: 0.8; }
        .dropzone {
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 20px;
        }
        .dropzone:hover { border-color: #00d4ff; background: rgba(0,212,255,0.1); }
        .file-input { display: none; }
        textarea {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border-radius: 10px;
            border: none;
            background: rgba(255,255,255,0.1);
            color: white;
            font-family: inherit;
        }
        textarea::placeholder { color: rgba(255,255,255,0.5); }
        .btn-subir {
            background: linear-gradient(45deg, #00d4ff, #7b2cbf);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }
        .mensaje { padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .exito { background: rgba(40,167,69,0.2); border: 1px solid #28a745; color: #28a745; }
        .error { background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ff8888; }
        .archivo-seleccionado { background: rgba(255,255,255,0.05); padding: 10px; border-radius: 10px; margin: 15px 0; display: none; }
        .volver { display: inline-block; margin-top: 20px; color: #00d4ff; text-decoration: none; }
        .footer { text-align: center; padding: 30px; background: rgba(0,0,0,0.5); margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">LUZ 360</div>
        <div>
            <span class="badge">USUARIO <?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
            <a href="mis_disenos.php" class="btn">Mis diseños</a>
            <a href="perfil.php" class="btn">Mi perfil</a>
            <a href="logout.php" class="btn btn-danger">Salir</a>
        </div>
    </div>

    <div class="container">
        <div class="upload-card">
            <h1>Enviar mi diseño</h1>
            <p>Envía tu diseño al administrador para revisión</p>

            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="dropzone" id="dropzone">
                    <div>Arrastra o haz clic para subir tu diseño</div>
                    <div style="font-size: 12px; margin-top: 10px;">JPG, PNG, GIF, PDF (máx 10MB)</div>
                    <input type="file" name="archivo" id="archivo" class="file-input" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" required>
                </div>
                <div class="archivo-seleccionado" id="archivoInfo">
                        Archivo seleccionado: <span id="nombreArchivo"></span>
                </div>
                <textarea name="descripcion" rows="3" placeholder="Breve descripción de tu diseño (opcional)"></textarea>
                <button type="submit" class="btn-subir">Enviar diseño</button>
            </form>
            <a href="mis_disenos.php" class="volver">Volver a mis diseños</a>
        </div>
    </div>

    <div class="footer">
        <p>LUZ 360 - La vida brilla más con tus colores y tus diseños </p>
    </div>

    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('archivo');
        const archivoInfo = document.getElementById('archivoInfo');
        const nombreArchivo = document.getElementById('nombreArchivo');

        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.style.borderColor = '#00d4ff'; });
        dropzone.addEventListener('dragleave', () => { dropzone.style.borderColor = 'rgba(255,255,255,0.3)'; });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = 'rgba(255,255,255,0.3)';
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                nombreArchivo.textContent = e.dataTransfer.files[0].name;
                archivoInfo.style.display = 'block';
            }
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                nombreArchivo.textContent = e.target.files[0].name;
                archivoInfo.style.display = 'block';
            }
        });
    </script>
</body>
</html>
