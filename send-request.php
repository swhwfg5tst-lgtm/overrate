<?php
header('Content-Type: application/json; charset=UTF-8');

// --- настройки Telegram ---
$telegram_token = getenv('TELEGRAM_BOT_TOKEN') ?: '';
$telegram_chat  = getenv('TELEGRAM_CHAT_ID') ?: '';

if ($telegram_token === '' || $telegram_chat === '') {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Настройки Telegram не заданы. Проверьте переменные окружения TELEGRAM_BOT_TOKEN и TELEGRAM_CHAT_ID.'
    ]);
    exit;
}

function sanitize_text(string $value, int $maxLength): string {
    $clean = trim(strip_tags($value));
    $clean = preg_replace('/\s+/', ' ', $clean);
    if ($maxLength > 0) {
        $clean = mb_substr($clean, 0, $maxLength);
    }
    return $clean;
}

function starts_with(string $value, string $prefix): bool {
    return substr($value, 0, strlen($prefix)) === $prefix;
}

function is_valid_phone(string $digits, string $locale): bool {
    if ($locale === 'en') {
        return strlen($digits) >= 10 && strlen($digits) <= 15;
    }
    if ($locale === 'zh') {
        if (strlen($digits) === 11) return true;
        return strlen($digits) === 13 && starts_with($digits, '86');
    }
    if (strlen($digits) !== 11) return false;
    return starts_with($digits, '7') || starts_with($digits, '8');
}

function localized_message(string $locale, string $key): string {
    $messages = [
        'ru' => [
            'required' => 'Не заполнены обязательные поля (город отправки, город доставки или телефон).',
            'phone' => 'Проверьте корректность номера телефона.',
            'rate' => 'Слишком частые заявки. Попробуйте через минуту.',
        ],
        'en' => [
            'required' => 'Required fields are missing (origin city, destination city, or phone).',
            'phone' => 'Please check the phone number format.',
            'rate' => 'Too many requests. Please try again in a minute.',
        ],
        'zh' => [
            'required' => '缺少必填字段（出发城市、到达城市或电话）。',
            'phone' => '请检查联系电话格式。',
            'rate' => '提交过于频繁，请稍后再试。',
        ],
    ];
    $lang = $messages[$locale] ?? $messages['ru'];
    return $lang[$key] ?? $messages['ru'][$key];
}

function rate_limit(string $ip, int $limitSeconds): bool {
    if ($ip === '') return true;
    $key = 'gruzoplaneta_rl_' . sha1($ip);
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key;
    $now = time();
    if (file_exists($path)) {
        $last = (int) file_get_contents($path);
        if ($now - $last < $limitSeconds) {
            return false;
        }
    }
    file_put_contents($path, (string) $now);
    return true;
}

// --- получение полей формы ---
$from_city   = sanitize_text($_POST['from_city'] ?? '', 60);
$to_city     = sanitize_text($_POST['to_city'] ?? '', 60);
$cargo_type  = sanitize_text($_POST['cargo_type'] ?? '', 80);
$weight_vol  = sanitize_text($_POST['weight_volume'] ?? '', 80);
$phone       = sanitize_text($_POST['phone'] ?? '', 30);
$client_name = sanitize_text($_POST['client_name'] ?? '', 60);
$messenger   = sanitize_text($_POST['messenger'] ?? '', 20);
$comment     = sanitize_text($_POST['comment'] ?? '', 400);
$honeypot    = sanitize_text($_POST['company'] ?? '', 10);
$locale      = sanitize_text($_POST['locale'] ?? 'ru', 5);

// защита от ботов
if ($honeypot !== '') {
    echo json_encode(['status' => 'ok']);
    exit;
}

if (!rate_limit($_SERVER['REMOTE_ADDR'] ?? '', 30)) {
    http_response_code(429);
    echo json_encode([
        'status'  => 'error',
        'message' => localized_message($locale, 'rate')
    ]);
    exit;
}

// проверка обязательных полей
if ($from_city === '' || $to_city === '' || $phone === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => localized_message($locale, 'required')
    ]);
    exit;
}

$digits = preg_replace('/\D+/', '', $phone);
if (!is_valid_phone($digits, $locale)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => localized_message($locale, 'phone')
    ]);
    exit;
}

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

$response = $result !== false ? json_decode($result, true) : null;
$ok = $result !== false && $status >= 200 && $status < 300 && is_array($response) && ($response['ok'] ?? false);

if ($ok) {
    echo json_encode(['status' => 'ok']);
} else {
    error_log('TELEGRAM ERROR: ' . ($err ?: ($response['description'] ?? 'Unknown error')));
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Не удалось отправить заявку в Telegram. Попробуйте позже.'
    ]);
}
