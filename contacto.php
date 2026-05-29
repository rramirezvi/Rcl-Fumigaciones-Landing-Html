<?php
declare(strict_types=1);

const DESTINATION_EMAIL = 'servicios@rclfumigaciones.com';
const SITE_NAME = 'RCL Fumigaciones';
const REDIRECT_OK = 'index.html?form=ok#contacto';
const REDIRECT_ERROR = 'index.html?form=error#contacto';
const MIN_SECONDS_TO_SUBMIT = 4;

function redirect_to(string $url)
{
    header('Location: ' . $url, true, 303);
    exit;
}

function field(string $name): string
{
    return trim((string)($_POST[$name] ?? ''));
}

function clean_line(string $value, int $maxLength): string
{
    $value = preg_replace('/[\r\n]+/', ' ', $value) ?? '';
    $value = trim($value);
    return substr($value, 0, $maxLength);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to(REDIRECT_ERROR);
}

if (field('website') !== '') {
    redirect_to(REDIRECT_OK);
}

$elapsedSeconds = (int)field('form_elapsed_seconds');
if ($elapsedSeconds < MIN_SECONDS_TO_SUBMIT) {
    redirect_to(REDIRECT_ERROR);
}

$nombre = clean_line(field('nombre'), 80);
$telefono = clean_line(field('telefono'), 35);
$servicio = clean_line(field('servicio'), 80);
$mensaje = trim(substr(field('mensaje'), 0, 1200));

$allowedServices = [
    'Desinsectación',
    'Desratización',
    'Fumigación residencial',
    'Control comercial',
    'Plan preventivo',
];

if ($nombre === '' || $telefono === '' || $servicio === '' || !in_array($servicio, $allowedServices, true)) {
    redirect_to(REDIRECT_ERROR);
}

if (!preg_match('/^[0-9+\s().-]{7,35}$/', $telefono)) {
    redirect_to(REDIRECT_ERROR);
}

$subject = 'Nueva solicitud desde la pagina web - ' . SITE_NAME;
$body = implode("\n", [
    'Nueva solicitud desde la pagina web de ' . SITE_NAME,
    '',
    'Nombre: ' . $nombre,
    'Telefono: ' . $telefono,
    'Servicio requerido: ' . $servicio,
    'Mensaje:',
    $mensaje !== '' ? $mensaje : 'Sin mensaje adicional.',
    '',
    'Tiempo en formulario: ' . $elapsedSeconds . ' segundos',
    'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'No disponible'),
    'Fecha: ' . date('Y-m-d H:i:s'),
]);

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . SITE_NAME . ' <' . DESTINATION_EMAIL . '>',
    'Reply-To: ' . DESTINATION_EMAIL,
    'X-Mailer: PHP/' . phpversion(),
];

$sent = mail(DESTINATION_EMAIL, $subject, $body, implode("\r\n", $headers));

redirect_to($sent ? REDIRECT_OK : REDIRECT_ERROR);
