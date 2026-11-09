<?php
/**
 * Formulario de contacto de A2ICD.
 *
 * Recibe Nombre, Correo electrónico, Asunto y Mensaje desde index.html
 * y los envía por correo usando el servidor SMTP de Titan (correo de a2icd.com).
 *
 * IMPORTANTE: la contraseña del correo NO está aquí (el repositorio es público).
 * Vive en un archivo privado del servidor, fuera de la carpeta del sitio, en
 * cualquiera de estos dos lugares:
 *     (carpeta principal de la cuenta)/privado/contacto-config.php
 *     domains/a2icd.com/privado/contacto-config.php
 * Ese archivo debe devolver un arreglo así:
 *     <?php
 *     return [
 *         'smtp_usuario'    => 'correo@a2icd.com',
 *         'smtp_contrasena' => 'la contraseña de ese correo',
 *         'destinatario'    => 'correo que recibe los mensajes',
 *     ];
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

const SMTP_SERVIDOR      = 'smtp.titan.email';
const SMTP_PUERTO        = 465;
const MAX_ENVIOS_POR_HORA = 5;      // por visitante
const SEGUNDOS_MINIMOS   = 3;       // un robot llena el formulario más rápido que esto

// ---------------------------------------------------------------------------
// Respuesta: JSON si la pide la página (fetch); si no, una página sencilla.
// ---------------------------------------------------------------------------
function responder(bool $ok, string $mensaje, int $codigo = 200): void
{
    http_response_code($codigo);
    $quiereJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

    if ($quiereJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        $texto = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<meta name="robots" content="noindex, nofollow"><title>Contacto | A2ICD</title>'
           . '<style>body{font-family:"IBM Plex Sans",system-ui,sans-serif;background:#b8b5a8;color:#000;'
           . 'display:flex;min-height:100vh;margin:0;align-items:center;justify-content:center;padding:16px;text-align:center}'
           . 'a{color:#000;font-weight:600}</style></head><body><main><p>' . $texto . '</p>'
           . '<p><a href="./#contacto">Regresar al sitio</a></p></main></body></html>';
    }
    exit;
}

// Texto de una línea: sin saltos de línea ni caracteres de control
function linea(string $valor): string
{
    $valor = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $valor) ?? '';
    return trim($valor);
}

function largo(string $valor): int
{
    return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
}

// Busca el archivo privado de configuración fuera de la carpeta pública
function cargarConfiguracion(): ?array
{
    $candidatos = [dirname(__DIR__, 2) . '/privado/contacto-config.php'];
    if (preg_match('#^(/home/[^/]+)#', __DIR__, $m)) {
        $candidatos[] = $m[1] . '/privado/contacto-config.php';
    }
    foreach ($candidatos as $ruta) {
        if (is_file($ruta)) {
            $config = require $ruta;
            if (is_array($config)
                && !empty($config['smtp_usuario'])
                && !empty($config['smtp_contrasena'])
                && !empty($config['destinatario'])) {
                return $config;
            }
        }
    }
    return null;
}

// Límite sencillo de envíos por visitante (se guarda en la carpeta temporal del servidor)
function excedeLimite(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    $archivo = sys_get_temp_dir() . '/a2icd_contacto_' . hash('sha256', $ip) . '.json';
    $ahora = time();
    $envios = [];
    if (is_file($archivo)) {
        $guardado = json_decode((string) @file_get_contents($archivo), true);
        if (is_array($guardado)) {
            $envios = array_values(array_filter($guardado, fn($t) => is_int($t) && $t > $ahora - 3600));
        }
    }
    if (count($envios) >= MAX_ENVIOS_POR_HORA) {
        return true;
    }
    $envios[] = $ahora;
    @file_put_contents($archivo, json_encode($envios), LOCK_EX);
    return false;
}

// ---------------------------------------------------------------------------
// 1. Solo se aceptan envíos del formulario (POST) hechos desde este mismo sitio
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder(false, 'Esta dirección solo recibe mensajes del formulario de contacto.', 405);
}

$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
$sitio  = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '') ?? '');
if ($origen !== '' && strtolower((string) parse_url($origen, PHP_URL_HOST)) !== $sitio) {
    responder(false, 'No se pudo enviar el mensaje.', 403);
}

// ---------------------------------------------------------------------------
// 2. Filtros contra robots: campo trampa y tiempo mínimo para llenar
//    (si parece robot, se responde "enviado" pero no se manda nada)
// ---------------------------------------------------------------------------
$exito = '¡Gracias! Tu mensaje se envió. Te responderemos pronto.';

if (trim((string) ($_POST['sitio_web'] ?? '')) !== '') {
    responder(true, $exito);
}
$abierto = (int) ($_POST['t'] ?? 0);
if ($abierto > 0 && time() - $abierto < SEGUNDOS_MINIMOS) {
    responder(true, $exito);
}

// ---------------------------------------------------------------------------
// 3. Revisar los datos
// ---------------------------------------------------------------------------
$nombre  = linea((string) ($_POST['nombre'] ?? ''));
$correo  = linea((string) ($_POST['correo'] ?? ''));
$asunto  = linea((string) ($_POST['asunto'] ?? ''));
$mensaje = trim(str_replace(["\r\n", "\r"], "\n", (string) ($_POST['mensaje'] ?? '')));
$mensaje = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]+/u', '', $mensaje) ?? '';

$faltan = [];
if ($nombre === '' || largo($nombre) > 100)  { $faltan[] = 'nombre'; }
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || largo($correo) > 150) { $faltan[] = 'correo electrónico'; }
if ($asunto === '' || largo($asunto) > 150)  { $faltan[] = 'asunto'; }
if ($mensaje === '' || largo($mensaje) > 5000) { $faltan[] = 'mensaje'; }

if ($faltan) {
    responder(false, 'Revisa estos campos: ' . implode(', ', $faltan) . '.', 422);
}

if (excedeLimite()) {
    responder(false, 'Ya recibimos varios mensajes tuyos. Intenta de nuevo en una hora o llámanos.', 429);
}

// ---------------------------------------------------------------------------
// 4. Enviar el correo
// ---------------------------------------------------------------------------
$config = cargarConfiguracion();
if ($config === null) {
    error_log('Contacto A2ICD: falta el archivo privado contacto-config.php o está incompleto.');
    responder(false, 'El formulario todavía no está listo. Por favor llámanos o intenta más tarde.', 503);
}

$fecha = (new DateTime('now', new DateTimeZone('America/Monterrey')))->format('d/m/Y H:i');
$cuerpo = "Nuevo mensaje desde el formulario de contacto del sitio web\n"
        . "==========================================================\n\n"
        . "Nombre:  {$nombre}\n"
        . "Correo:  {$correo}\n"
        . "Asunto:  {$asunto}\n"
        . "Fecha:   {$fecha}\n\n"
        . "Mensaje:\n{$mensaje}\n\n"
        . "----------------------------------------------------------\n"
        . "Puedes responder directamente a este correo para contestarle a {$nombre}.\n";

$correoSalida = new PHPMailer(true);
try {
    // El servidor, puerto y cifrado pueden cambiarse desde el archivo privado si hiciera falta
    $seguridad = $config['smtp_seguridad'] ?? 'ssl';
    $correoSalida->isSMTP();
    $correoSalida->Host       = $config['smtp_servidor'] ?? SMTP_SERVIDOR;
    $correoSalida->Port       = (int) ($config['smtp_puerto'] ?? SMTP_PUERTO);
    $correoSalida->SMTPSecure = $seguridad === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS
                              : ($seguridad === 'ninguna' ? '' : PHPMailer::ENCRYPTION_SMTPS);
    $correoSalida->SMTPAutoTLS = $seguridad !== 'ninguna';
    $correoSalida->SMTPAuth   = true;
    $correoSalida->Username   = $config['smtp_usuario'];
    $correoSalida->Password   = $config['smtp_contrasena'];
    $correoSalida->Timeout    = 20;
    $correoSalida->CharSet    = PHPMailer::CHARSET_UTF8;

    $correoSalida->setFrom($config['smtp_usuario'], 'Sitio web A2ICD');
    $correoSalida->addAddress($config['destinatario']);
    $correoSalida->addReplyTo($correo, $nombre);

    $correoSalida->Subject = 'Contacto sitio web: ' . $asunto;
    $correoSalida->isHTML(false);
    $correoSalida->Body = $cuerpo;

    $correoSalida->send();
} catch (PHPMailerException $e) {
    error_log('Contacto A2ICD: no se pudo enviar el correo. ' . $correoSalida->ErrorInfo);
    responder(false, 'No se pudo enviar el mensaje. Intenta de nuevo más tarde o llámanos.', 502);
}

responder(true, $exito);
