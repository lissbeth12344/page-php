<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header('Location: index.php');
    exit();
}

require_once '/var/www/html/vendor/autoload.php';
use Aws\S3\S3Client;

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => AWS_REGION,
    'credentials' => [
        'key'    => AWS_ACCESS_KEY,
        'secret' => AWS_SECRET_KEY,
    ]
]);

// Subir a S3 el archivo y que se  marque como subido
if (isset($_GET['subir_s3']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM disenos WHERE id = ?");
    $stmt->execute([$id]);
    $diseño = $stmt->fetch();
    
    if ($diseño) {
        $ruta_local = '/var/www/html/uploads_temp/' . $diseño['archivo_temporal'];
        $s3_key = $diseño['cliente_nombre'] . '/aprobados/' . $diseño['archivo_temporal'];
        
        if (file_exists($ruta_local)) {
            try {
                $s3->putObject([
                    'Bucket' => AWS_BUCKET,
                    'Key'    => $s3_key,
                    'SourceFile' => $ruta_local,
                    'ACL'    => 'private'
                ]);
                
                $stmt = $pdo->prepare("UPDATE disenos SET estado = 'subido_s3' WHERE id = ?");
                $stmt->execute([$id]);
                
                // Opcional: eliminar archivo local
                // unlink($ruta_local);
                
                $mensaje = "Diseño subido a S3 correctamente";
            } catch (Exception $e) {
                $mensaje = " Error al subir a S3: " . $e->getMessage();
            }
        } else {
            $mensaje = " Archivo local no encontrado";
        }
    }
}

// Marcar como visto
if (isset($_GET['visto']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE disenos SET estado = 'visto' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

// Eliminar diseño (rechazar)
if (isset($_GET['eliminar']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM disenos WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $diseño = $stmt->fetch();
    
    if ($diseño) {
        $ruta_local = '/var/www/html/uploads_temp/' . $diseño['archivo_temporal'];
        if (file_exists($ruta_local)) {
            unlink($ruta_local);
        }
        $stmt = $pdo->prepare("DELETE FROM disenos WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $mensaje = " Diseño rechazado y eliminado";
    }
}

// Obtener todos los diseños pendientes
$stmt = $pdo->query("SELECT * FROM disenos ORDER BY subido_en DESC");
$disenos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diseños de Clientes - LUZ 360</title>
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
        }
        .logo { font-size: 24px; font-weight: bold; background: linear-gradient(45deg, #00d4ff, #7b2cbf); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .badge { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; }
        .btn { background: transparent; border: 2px solid #00d4ff; padding: 8px 20px; border-radius: 30px; color: white; text-decoration: none; }
        .container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
        .mensaje { padding: 15px; border-radius: 12px; margin-bottom: 20px; background: rgba(40,167,69,0.2); border: 1px solid #28a745; color: #28a745; }
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.05); border-radius: 15px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(0,0,0,0.3); }
        .pendiente { background: #ffc107; color: #333; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .visto { background: #17a2b8; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .subido_s3 { background: #28a745; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .acciones a { margin: 0 5px; text-decoration: none; padding: 5px 10px; border-radius: 5px; display: inline-block; font-size: 12px; }
        .btn-ver { background: #17a2b8; color: white; }
        .btn-subir { background: #28a745; color: white; }
        .btn-eliminar { background: #dc3545; color: white; }
        .btn-descargar { background: #007bff; color: white; }
        .footer { text-align: center; padding: 30px; background: rgba(0,0,0,0.5); margin-top: 40px; }
        pre { max-width: 300px; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">LUZ 360</div>
        <div>
            <span class="badge"> ADMIN</span>
            <a href="index.php" class="btn"> Dashboard</a>
            <a href="logout.php" class="btn"> Salir</a>
        </div>
    </div>

    <div class="container">
        <h1 style="margin-bottom: 20px;"> Diseños enviados por clientes</h1>
        
        <?php if (isset($mensaje)): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Archivo</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($disenos as $d): ?>
                <tr>
                    <td><?php echo $d['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($d['cliente_nombre']); ?></strong></td>
                    <td>
                        <a href="ver_diseno_temp.php?file=<?php echo urlencode($d['archivo_temporal']); ?>" target="_blank" class="btn-ver" style="background: #17a2b8;">
                             <?php echo htmlspecialchars($d['archivo_nombre']); ?>
                        </a>
                    </td>
                    <td><pre><?php echo htmlspecialchars($d['descripcion'] ?? '-'); ?></pre></td>
                    <td><?php echo $d['subido_en']; ?></td>
                    <td><span class="<?php echo $d['estado']; ?>"><?php echo $d['estado']; ?></span></td>
                    <td class="acciones">
                        <?php if ($d['estado'] == 'pendiente'): ?>
                            <a href="?visto=1&id=<?php echo $d['id']; ?>" class="btn-ver">Marcar visto</a>
                        <?php endif; ?>
                        
                        <?php if ($d['estado'] != 'subido_s3'): ?>
                            <a href="?subir_s3=1&id=<?php echo $d['id']; ?>" class="btn-subir" onclick="return confirm('¿Subir este diseño a S3?')">Subir a S3</a>
                        <?php endif; ?>
                        
                        <a href="/uploads_temp/<?php echo $d['archivo_temporal']; ?>" download class="btn-descargar"> Descargar</a>
                        
                        <?php if ($d['estado'] != 'subido_s3'): ?>
                            <a href="?eliminar=1&id=<?php echo $d['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar este diseño?')">Eliminar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (count($disenos) == 0): ?>
            <p style="text-align: center; padding: 40px;">No hay diseños pendientes</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p> LUZ 360 - Administración de diseños </p>
    </div>
</body>
</html>








