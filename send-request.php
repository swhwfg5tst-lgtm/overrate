<?php
header('Content-Type: application/json; charset=UTF-8');

// --- настройки Telegram ---
function get_env_value(string $key): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? '';
    }
    if ($value === '') {
        $value = $_SERVER[$key] ?? '';
    }

    return is_string($value) ? trim($value) : '';
}

function load_env_file(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    $data = [];
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        $v = trim($v, "\"'");
        if ($k !== '') {
            $data[$k] = $v;
        }
    }

    return $data;
}

$telegram_token = get_env_value('TELEGRAM_TOKEN');
$telegram_chat  = get_env_value('TELEGRAM_CHAT_ID');

if ($telegram_token === '' || $telegram_chat === '') {
    $env_data = load_env_file(__DIR__ . '/.env');
    $telegram_token = $telegram_token ?: ($env_data['TELEGRAM_TOKEN'] ?? '');
    $telegram_chat = $telegram_chat ?: ($env_data['TELEGRAM_CHAT_ID'] ?? '');
}

// --- получение полей формы ---
$from_city   = trim($_POST['from_city']    ?? '');
$to_city     = trim($_POST['to_city']      ?? '');
$cargo_type  = trim($_POST['cargo_type']   ?? '');
$weight_vol  = trim($_POST['weight_volume']?? '');
$phone       = trim($_POST['phone']        ?? '');
$client_name = trim($_POST['client_name']  ?? '');
$messenger   = trim($_POST['messenger']    ?? '');
$comment     = trim($_POST['comment']      ?? '');
$honeypot    = trim($_POST['company']      ?? '');
$locale      = trim($_POST['locale']       ?? '');

// защита от ботов
if ($honeypot !== '') {
    sleep(2);
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($telegram_token === '' || $telegram_chat === '') {
    error_log('TELEGRAM ERROR: missing TELEGRAM_TOKEN or TELEGRAM_CHAT_ID env vars.');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Не настроены переменные TELEGRAM_TOKEN и TELEGRAM_CHAT_ID.'
    ]);
    exit;
}

// проверка обязательных полей
if ($from_city === '' || $to_city === '' || $phone === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Не заполнены обязательные поля (город отправки, город доставки или телефон).'
    ]);
    exit;
}

if ($locale === 'ru') {
    $phone_digits = preg_replace('/\D+/', '', $phone);
    if (!preg_match('/^(7|8)\d{10}$/', $phone_digits)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Пожалуйста, укажите корректный номер телефона.'
        ]);
        exit;
    }
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_window = 30;
$rate_limit_dir = sys_get_temp_dir() . '/gruzoplaneta_rate';
if (!is_dir($rate_limit_dir)) {
    mkdir($rate_limit_dir, 0700, true);
}
$rate_key = hash('sha256', $ip_address);
$rate_file = $rate_limit_dir . '/' . $rate_key . '.txt';
$last_time = 0;
if (is_file($rate_file)) {
    $last_time = (int) file_get_contents($rate_file);
}
if ($last_time > 0 && (time() - $last_time) < $rate_limit_window) {
    http_response_code(429);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Слишком много запросов. Попробуйте снова чуть позже.'
    ]);
    exit;
}
file_put_contents($rate_file, (string) time(), LOCK_EX);

// собираем текст заявки
$text  = "🆕 Новая заявка с сайта Грузовая Планета\n\n";
$text .= "Маршрут:\n";
$text .= "Откуда: {$from_city}\n";
$text .= "Куда: {$to_city}\n";
$text .= "Тип груза: {$cargo_type}\n";
$text .= "Вес/объём: {$weight_vol}\n\n";
$text .= "Контакты:\n";
$text .= "Телефон: {$phone}\n";
$text .= "Имя: {$client_name}\n";
$text .= "Мессенджер: {$messenger}\n\n";
$text .= "Комментарий:\n";
$text .= ($comment !== '' ? $comment : '—') . "\n";

// --- отправляем в Telegram через cURL ---
$data = [
    'chat_id' => $telegram_chat,
    'text'    => $text,
];

$url = "https://api.telegram.org/bot{$telegram_token}/sendMessage";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS     => $data,
]);

$result = curl_exec($ch);
$err    = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result !== false && $status >= 200 && $status < 300) {
    echo json_encode(['status' => 'ok']);
} else {
    error_log('TELEGRAM ERROR: ' . ($err ?: 'HTTP ' . $status . ' response: ' . $result));
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Не удалось отправить заявку в Telegram. Попробуйте позже.'
    ]);
}
