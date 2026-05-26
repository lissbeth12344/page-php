<?php
session_start();
require_once 'config.php';

// Verificar que está logueado y es admin
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Incluir AWS SDK
require_once '/var/www/html/vendor/autoload.php';
use Aws\S3\S3Client;
// aqui se crea el objeto para comunicarse con S3 
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => AWS_REGION,
    'credentials' => [
        'key'    => AWS_ACCESS_KEY,
        'secret' => AWS_SECRET_KEY,
    ]
]);

$mensaje = '';
$tipo_mensaje = '';
// aqui se guarda los archivos con todos los parametros
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombre = basename($archivo['name']);
    $s3_key = $_SESSION['departamento'] . '/' . $nombre;
    
    // Validar que sea un archivo permitido
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp4', 'mov', 'zip', 'txt', 'html', 'css', 'js'];
    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensiones_permitidas)) {
        $mensaje = " Tipo de archivo no permitido. Extensiones permitidas: " . implode(', ', $extensiones_permitidas);
        $tipo_mensaje = 'error';
    } elseif ($archivo['size'] > 50 * 1024 * 1024) {
        $mensaje = " El archivo es demasiado grande. Máximo 50MB.";
        $tipo_mensaje = 'error';
    } else {
        try {
            $s3->putObject([
                'Bucket' => AWS_BUCKET,
                'Key'    => $s3_key,
                'SourceFile' => $archivo['tmp_name'],
                'ACL'    => 'private'
            ]);
            $mensaje = " ¡Archivo '$nombre' subido correctamente!";
            $tipo_mensaje = 'exito';
        } catch (Exception $e) {
            $mensaje = " Error al subir: " . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Diseño - LUZ 360</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #0a0a2a 0%, #1a1a4a 100%);
            color: white;
            min-height: 100vh;
        }

        /* Header */
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

        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Tarjeta de subida */
        .upload-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }

        .upload-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .upload-card h1 {
            font-size: 28px;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #00d4ff, #7b2cbf);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .upload-card p {
            margin-bottom: 25px;
            opacity: 0.8;
        }

        /* Zona de subida (drag & drop) */
        .dropzone {
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 20px;
        }

        .dropzone:hover {
            border-color: #00d4ff;
            background: rgba(0,212,255,0.1);
        }

        .dropzone .file-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .dropzone .file-text {
            font-size: 14px;
            opacity: 0.7;
        }

        .file-input {
            display: none;
        }

        .btn-subir {
            background: linear-gradient(45deg, #00d4ff, #7b2cbf);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
        }

        .btn-subir:hover {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(0,212,255,0.5);
        }

        /* Mensajes */
        .mensaje {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        .exito {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
        }

        .error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #ff8888;
        }

        /* Info de archivo seleccionado */
        .archivo-seleccionado {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 10px;
            margin: 15px 0;
            font-size: 14px;
            display: none;
        }

        .archivo-seleccionado span {
            color: #00d4ff;
        }

        /* Enlaces */
        .volver {
            display: inline-block;
            margin-top: 20px;
            color: #00d4ff;
            text-decoration: none;
        }

        .volver:hover {
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px;
            background: rgba(0,0,0,0.5);
            margin-top: 40px;
        }

        /* Responsive vista para moviles*/
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
            }
            .upload-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">LUZ 360</div>
        <div class="user-info">
            <span class="badge">ADMIN</span>
            <span class="badge">USUARIO<?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
            <span class="badge">DEPARTAMENTO<?php echo htmlspecialchars($_SESSION['departamento']); ?></span>
            <a href="index.php" class="btn"> Dashboard</a>
            <a href="logout.php" class="btn btn-danger"> Salir</a>
        </div>
    </div>

    <div class="container">
        <div class="upload-card">
            <div class="upload-icon"></div>
            <h1>Subir diseño a S3</h1>
            <p>Arrastra tu archivo o haz clic para seleccionarlo</p>

            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="dropzone" id="dropzone">
                    <div class="file-text">Arrastra y suelta tu archivo aquí</div>
                    <div class="file-text" style="margin-top: 5px;">o</div>
                    <div class="file-text" style="margin-top: 5px; color: #00d4ff;">Haz clic para seleccionar</div>
                    <input type="file" name="archivo" id="archivo" class="file-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.mp4,.mov,.zip,.txt,.html,.css,.js" required>
                </div>
                <div class="archivo-seleccionado" id="archivoInfo">
                    Archivo seleccionado: <span id="nombreArchivo"></span>
                </div>
                <button type="submit" class="btn-subir"> Subir a S3</button>
            </form>
            <a href="index.php" class="volver">←Volver al dashboard</a>
        </div>
    </div>

    <div class="footer">
        <p> LUZ 360 - La vida brilla más con tus colores y tus diseños </p>
    </div>

    <script>
        // Drag & Drop
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('archivo');
        const archivoInfo = document.getElementById('archivoInfo');
        const nombreArchivo = document.getElementById('nombreArchivo');

        dropzone.addEventListener('click', () => fileInput.click());

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#00d4ff';
            dropzone.style.background = 'rgba(0,212,255,0.1)';
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.style.borderColor = 'rgba(255,255,255,0.3)';
            dropzone.style.background = 'transparent';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = 'rgba(255,255,255,0.3)';
            dropzone.style.background = 'transparent';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                mostrarArchivo(files[0].name);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                mostrarArchivo(e.target.files[0].name);
            } else {
                archivoInfo.style.display = 'none';
            }
        });

        function mostrarArchivo(nombre) {
            nombreArchivo.textContent = nombre;
            archivoInfo.style.display = 'block';
        }
    </script>
</body>
</html>












