<?php
header('Content-Type: application/json; charset=UTF-8');

// --- настройки Telegram ---
$telegram_token = '8021827160:AAFECefKtX5UtQoVVfrem07QcrYZO3vcjR0';
$telegram_chat  = '-5099041826';

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

// защита от ботов
if ($honeypot !== '') {
    echo json_encode(['status' => 'ok']);
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
curl_close($ch);

if ($result !== false) {
    echo json_encode(['status' => 'ok']);
} else {
    error_log('TELEGRAM ERROR: ' . $err);
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Не удалось отправить заявку в Telegram. Попробуйте позже.'
    ]);
}
