<?php
/*
 * FORMULARIO DE CONTACTO DE A2ICD
 * Recibe los datos de la sección Contacto (nombre, correo, teléfono y mensaje)
 * y los envía por correo con PHPMailer, por el SMTP de Titan, desde info@a2icd.com.
 *
 * LA CONTRASEÑA NO ESTÁ AQUÍ: el repositorio es público. Está en un archivo
 * privado, fuera de public_html (no se puede abrir desde el navegador):
 *
 *   /home/u807517947/domains/a2icd.com/privado/formulario.php
 *
 * con este contenido (solo se cambia la contraseña):
 *
 *   <?php
 *   return [
 *       'smtp_contrasena' => 'LA-CONTRASEÑA-DE-info@a2icd.com',
 *       'destinatario'    => 'correo-que-recibe-los-mensajes@a2icd.com',
 *   ];
 *
 * En esa misma carpeta se guardan el conteo de envíos (para los límites) y el
 * registro de errores (formulario-errores.log), sin datos de los visitantes.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

/* ---------- Ajustes (nada secreto) ---------- */

const CONTACTO_RESPALDO = 'escríbenos a info@a2icd.com o llámanos al +52 81 8348 7625';

const LIMITES = [
    'por_persona_hora' => 5,   // mensajes por persona en una hora
    'total_hora'       => 20,  // mensajes de todo el sitio en una hora
    'total_dia'        => 60,  // mensajes de todo el sitio en 24 horas
    // Titan permite 50 por hora y 300 al día desde info@a2icd.com; con estos topes
    // el formulario nunca deja sin envíos al buzón aunque lo ataquen con spam.
];

const LARGO_MAXIMO = [
    'nombre'   => 100,
    'correo'   => 254,
    'telefono' => 30,
    'mensaje'  => 5000,
];

const SEGUNDOS_MINIMOS = 3; // llenar el formulario en menos tiempo = programa automático

$AJUSTES = [
    'smtp_servidor'    => 'smtp.titan.email',
    'smtp_puerto'      => 465,
    'smtp_seguridad'   => 'ssl',   // 'ssl' (puerto 465), 'tls' (587) o '' (solo pruebas)
    'smtp_autenticar'  => true,
    'smtp_usuario'     => 'info@a2icd.com',
    'smtp_contrasena'  => '',      // va en el archivo privado
    'remitente'        => 'info@a2icd.com',
    'remitente_nombre' => 'Sitio web A2ICD',
    'destinatario'     => '',      // va en el archivo privado
];

/* ---------- Carpeta privada ---------- */

/** Carpeta "privado" al lado de public_html (fuera de lo que se publica). */
function carpeta_privada(): ?string
{
    $dir = __DIR__;
    while ($dir !== dirname($dir)) {
        if (basename($dir) === 'public_html') {
            return dirname($dir) . '/privado';
        }
        $dir = dirname($dir);
    }
    $prueba = getenv('A2_PRIVADO'); // solo para pruebas fuera de Hostinger
    return $prueba !== false && $prueba !== '' ? $prueba : null;
}

$PRIVADO = carpeta_privada();
$PRIVADO_ESCRIBIBLE = $PRIVADO !== null && is_dir($PRIVADO) && is_writable($PRIVADO);

ini_set('display_errors', '0');
if ($PRIVADO_ESCRIBIBLE) {
    ini_set('log_errors', '1');
    ini_set('error_log', $PRIVADO . '/formulario-errores.log');
}

/* ---------- Respuestas ---------- */

/** ¿La página pidió la respuesta en JSON (envío con JavaScript)? */
function quiere_json(): bool
{
    return stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

/**
 * Responde y termina.
 * $ok: si se envió; $mensaje: texto para el visitante; $errores: campo => texto.
 */
function responder(int $codigo, bool $ok, string $mensaje, array $errores = []): void
{
    http_response_code($codigo);
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');

    if (quiere_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['ok' => $ok, 'mensaje' => $mensaje, 'errores' => (object) $errores],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // Sin JavaScript: una página sencilla con el resultado y un enlace para regresar.
    header('Content-Type: text/html; charset=utf-8');
    $h = static fn(string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    $lista = '';
    foreach ($errores as $texto) {
        $lista .= '<li>' . $h($texto) . '</li>';
    }
    $titulo = $ok ? 'Mensaje enviado' : 'No se envió el mensaje';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>' . $h($titulo) . ' · A2ICD</title>'
        . '<style>body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Inter","Segoe UI",Roboto,sans-serif;'
        . 'background:#f2f2f2;color:#000;line-height:1.5}main{max-width:40rem;margin:0 auto;padding:64px 16px}'
        . 'h1{font-weight:600;letter-spacing:-0.02em;margin:0 0 16px}a{color:#de0000;font-weight:500}</style>'
        . '</head><body><main><h1>' . $h($titulo) . '</h1><p>' . $h($mensaje) . '</p>'
        . ($lista !== '' ? '<ul>' . $lista . '</ul>' : '')
        . '<p><a href="./#contacto">' . ($ok ? 'Volver al sitio' : 'Regresar al formulario') . '</a></p>'
        . '</main></body></html>';
    exit;
}

/* ---------- Solo se aceptan envíos del formulario ---------- */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ./#contacto', true, 303);
    exit;
}

/* ---------- Datos recibidos ---------- */

/** Texto de un renglón: sin caracteres de control y sin espacios de sobra. */
function renglon(string $campo): string
{
    $v = (string) ($_POST[$campo] ?? '');
    if (!mb_check_encoding($v, 'UTF-8')) {
        return '';
    }
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v);
    return trim(preg_replace('/\s+/u', ' ', $v));
}

/** Texto de varios renglones: conserva los saltos de línea. */
function parrafos(string $campo): string
{
    $v = (string) ($_POST[$campo] ?? '');
    if (!mb_check_encoding($v, 'UTF-8')) {
        return '';
    }
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
    $v = preg_replace("/\n{4,}/", "\n\n\n", $v);
    return trim($v);
}

$nombre   = renglon('nombre');
$correo   = renglon('correo');
$telefono = renglon('telefono');
$mensaje  = parrafos('mensaje');

/* ---------- Anti-spam (no se le avisa al programa que fue detectado) ---------- */

$GRACIAS = 'Gracias. Recibimos tu mensaje y te responderemos pronto.';

// 1. Campo trampa: está oculto para las personas; los programas automáticos lo llenan.
if (renglon('sitio_web') !== '') {
    responder(200, true, $GRACIAS);
}
// 2. Tiempo: la página mide cuánto tardó la persona en llenar el formulario.
$tiempo = $_POST['tiempo'] ?? '';
if (is_string($tiempo) && $tiempo !== '' && is_numeric($tiempo) && (float) $tiempo < SEGUNDOS_MINIMOS) {
    responder(200, true, $GRACIAS);
}

/* ---------- Validación ---------- */

$errores = [];

if ($nombre === '') {
    $errores['nombre'] = 'Escribe tu nombre.';
} elseif (mb_strlen($nombre) > LARGO_MAXIMO['nombre']) {
    $errores['nombre'] = 'Tu nombre es demasiado largo (máximo ' . LARGO_MAXIMO['nombre'] . ' caracteres).';
}

if ($correo === '') {
    $errores['correo'] = 'Escribe tu correo.';
} elseif (mb_strlen($correo) > LARGO_MAXIMO['correo'] || !PHPMailer::validateAddress($correo)) {
    $errores['correo'] = 'Revisa tu correo; parece incompleto (ejemplo: nombre@empresa.com).';
}

$digitos = preg_replace('/\D/', '', $telefono);
if ($telefono === '') {
    $errores['telefono'] = 'Escribe tu teléfono.';
} elseif (mb_strlen($telefono) > LARGO_MAXIMO['telefono']
    || !preg_match('/^\+?[0-9 ()\-.]+$/', $telefono)
    || strlen($digitos) < 10 || strlen($digitos) > 15) {
    $errores['telefono'] = 'Revisa tu teléfono: debe tener 10 dígitos (o la lada del país y el número).';
}

if ($mensaje === '') {
    $errores['mensaje'] = 'Escribe tu mensaje.';
} elseif (mb_strlen($mensaje) > LARGO_MAXIMO['mensaje']) {
    $errores['mensaje'] = 'Tu mensaje es demasiado largo (máximo ' . LARGO_MAXIMO['mensaje'] . ' caracteres).';
}

if ($errores) {
    responder(422, false, 'Revisa los datos marcados.', $errores);
}

/* ---------- Ajustes privados (contraseña y destinatario) ---------- */

$archivo_privado = $PRIVADO !== null ? $PRIVADO . '/formulario.php' : null;
if ($archivo_privado === null || !is_file($archivo_privado)) {
    error_log('Formulario A2ICD: falta el archivo privado ' . ($archivo_privado ?? '(sin carpeta privada)'));
    responder(500, false, 'Por ahora no pudimos enviar tu mensaje. Por favor, ' . CONTACTO_RESPALDO . '.');
}
$privados = include $archivo_privado;
if (!is_array($privados)) {
    error_log('Formulario A2ICD: el archivo privado no regresa un arreglo (return [ ... ];)');
    responder(500, false, 'Por ahora no pudimos enviar tu mensaje. Por favor, ' . CONTACTO_RESPALDO . '.');
}
$AJUSTES = array_merge($AJUSTES, array_intersect_key($privados, $AJUSTES));

if (($AJUSTES['smtp_autenticar'] && $AJUSTES['smtp_contrasena'] === '')
    || !PHPMailer::validateAddress((string) $AJUSTES['destinatario'])) {
    error_log('Formulario A2ICD: faltan la contraseña o el destinatario en el archivo privado');
    responder(500, false, 'Por ahora no pudimos enviar tu mensaje. Por favor, ' . CONTACTO_RESPALDO . '.');
}

/* ---------- Límites de envíos ---------- */

/**
 * Cuenta los envíos recientes y registra este. Guarda solo la hora y una huella
 * (hash) del visitante, nunca su IP ni sus datos. Regresa '' si se puede enviar,
 * o 'persona' / 'sitio' si se pasó de algún límite.
 */
function revisar_limites(?string $carpeta): string
{
    $dir = ($carpeta !== null && is_dir($carpeta) && is_writable($carpeta)) ? $carpeta : sys_get_temp_dir();
    $archivo = $dir . '/formulario-envios.json';
    $f = @fopen($archivo, 'c+');
    if ($f === false || !flock($f, LOCK_EX)) {
        error_log('Formulario A2ICD: no se pudo abrir ' . $archivo . ' para los límites');
        return ''; // si no se puede contar, no se bloquea a nadie
    }
    $ahora = time();
    $datos = json_decode((string) stream_get_contents($f), true);
    $envios = is_array($datos) ? $datos : [];
    $envios = array_values(array_filter($envios, static fn($e) =>
        is_array($e) && isset($e[0], $e[1]) && $e[0] > $ahora - 86400));

    $ip = ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0]);
    $huella = substr(hash('sha256', 'a2icd-formulario|' . $ip), 0, 16);

    $hora = array_filter($envios, static fn($e) => $e[0] > $ahora - 3600);
    $de_persona = count(array_filter($hora, static fn($e) => $e[1] === $huella));

    $resultado = '';
    if ($de_persona >= LIMITES['por_persona_hora']) {
        $resultado = 'persona';
    } elseif (count($hora) >= LIMITES['total_hora'] || count($envios) >= LIMITES['total_dia']) {
        $resultado = 'sitio';
    } else {
        $envios[] = [$ahora, $huella];
    }

    ftruncate($f, 0);
    rewind($f);
    fwrite($f, json_encode($envios));
    fflush($f);
    flock($f, LOCK_UN);
    fclose($f);
    return $resultado;
}

$limite = revisar_limites($PRIVADO);
if ($limite === 'persona') {
    responder(429, false, 'Ya recibimos varios mensajes tuyos en la última hora. Si necesitas algo más, ' . CONTACTO_RESPALDO . '.');
}
if ($limite === 'sitio') {
    error_log('Formulario A2ICD: se alcanzó el límite total de envíos (posible spam)');
    responder(429, false, 'En este momento no podemos recibir más mensajes. Por favor, ' . CONTACTO_RESPALDO . '.');
}

/* ---------- Correo ---------- */

/** Fecha y hora de Monterrey, en español. */
function fecha_monterrey(): string
{
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
              'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $d = new DateTime('now', new DateTimeZone('America/Monterrey'));
    return $d->format('j') . ' de ' . $meses[(int) $d->format('n') - 1] . ' de ' . $d->format('Y')
        . ', ' . $d->format('H:i') . ' h (hora de Monterrey)';
}

$sitio = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'el sitio web'));

$cuerpo = "Nuevo mensaje del formulario de contacto de {$sitio}\n\n"
    . "Nombre:   {$nombre}\n"
    . "Correo:   {$correo}\n"
    . "Teléfono: {$telefono}\n"
    . 'Fecha:    ' . fecha_monterrey() . "\n\n"
    . "Mensaje:\n{$mensaje}\n\n"
    . "--\n"
    . "Para contestar, usa \"Responder\": la respuesta le llega directo a {$nombre} ({$correo}).\n";

$asunto = 'Mensaje del sitio web: ' . mb_substr($nombre, 0, 60);

$correo_salida = new PHPMailer(true);
try {
    $correo_salida->isSMTP();
    $correo_salida->Host     = (string) $AJUSTES['smtp_servidor'];
    $correo_salida->Port     = (int) $AJUSTES['smtp_puerto'];
    $correo_salida->Timeout  = 20;
    $correo_salida->CharSet  = PHPMailer::CHARSET_UTF8;
    $correo_salida->XMailer  = ' ';
    if ($AJUSTES['smtp_seguridad'] === 'ssl') {
        $correo_salida->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($AJUSTES['smtp_seguridad'] === 'tls') {
        $correo_salida->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $correo_salida->SMTPSecure  = '';
        $correo_salida->SMTPAutoTLS = false;
    }
    $correo_salida->SMTPAuth = (bool) $AJUSTES['smtp_autenticar'];
    if ($correo_salida->SMTPAuth) {
        $correo_salida->Username = (string) $AJUSTES['smtp_usuario'];
        $correo_salida->Password = (string) $AJUSTES['smtp_contrasena'];
    }

    $correo_salida->setFrom((string) $AJUSTES['remitente'], (string) $AJUSTES['remitente_nombre']);
    $correo_salida->addAddress((string) $AJUSTES['destinatario']);
    $correo_salida->addReplyTo($correo, $nombre);
    $correo_salida->Subject = $asunto;
    $correo_salida->isHTML(false);
    $correo_salida->Body = $cuerpo;

    $correo_salida->send();
} catch (PHPMailerException $e) {
    error_log('Formulario A2ICD: no se pudo enviar: ' . $correo_salida->ErrorInfo);
    responder(502, false, 'No pudimos enviar tu mensaje. Intenta de nuevo en unos minutos o ' . CONTACTO_RESPALDO . '.');
}

responder(200, true, $GRACIAS);
