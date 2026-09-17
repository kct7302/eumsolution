<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
header('Cache-Control: no-store');

// HTML pages get only their own session's form state from this backend endpoint.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'form-state') {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $state = [
        'csrf_token' => $_SESSION['csrf_token'],
        'flash' => $_SESSION['flash'] ?? null,
        'old' => $_SESSION['old'] ?? [],
    ];
    unset($_SESSION['flash'], $_SESSION['old']);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($state, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: consult.html', true, 303);
    exit;
}

function redirectWithFlash(string $type, string $message, array $old = []): never
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    $_SESSION['old'] = $old;
    header('Location: consult.html', true, 303);
    exit;
}

function postedString(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}

$input = [
    'name' => postedString('name'),
    'company' => postedString('company'),
    'phone' => postedString('phone'),
    'email' => postedString('email'),
    'message' => postedString('message'),
];
$inquiryType = postedString('inquiry_type');
$allowedInquiryTypes = ['정책자금', '보증 · 기술금융', '기업인증 · 연구소', '창업 지원', '재무 · 세무 전략', '노무 · 고용지원', '수출 · 판로', '법인전환 · 구조설계', '경영 진단', '사후관리'];
if ($inquiryType !== '' && !in_array($inquiryType, $allowedInquiryTypes, true)) {
    $inquiryType = '';
}
$input['inquiry_type'] = $inquiryType;

$token = postedString('csrf_token');
$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
    redirectWithFlash('error', '요청이 만료되었습니다. 내용을 확인한 뒤 다시 보내 주세요.', $input);
}

// Bots often fill invisible fields. Return a normal success response without saving.
if (postedString('website') !== '') {
    redirectWithFlash('success', '상담 신청이 접수되었습니다. 빠르게 연락드리겠습니다.');
}

$errors = [];
if ($input['name'] === '' || mb_strlen($input['name']) > 50) {
    $errors[] = '이름을 50자 이내로 입력해 주세요.';
}
if ($input['company'] !== '' && mb_strlen($input['company']) > 100) {
    $errors[] = '회사명은 100자 이내로 입력해 주세요.';
}
if ($input['phone'] === '' || mb_strlen($input['phone']) > 30) {
    $errors[] = '연락처를 30자 이내로 입력해 주세요.';
}
if ($input['email'] !== '' && (strlen($input['email']) > 255 || filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false)) {
    $errors[] = '이메일 형식을 확인해 주세요.';
}
if ($input['message'] === '' || mb_strlen($input['message']) > 2000) {
    $errors[] = '상담 내용을 2,000자 이내로 입력해 주세요.';
}
if (postedString('privacy_consent') !== '1') {
    $errors[] = '개인정보 수집 및 이용에 동의해 주세요.';
}

if ($errors !== []) {
    redirectWithFlash('error', implode(' ', $errors), $input);
}

try {
    require_once __DIR__ . '/config/db.php';

    $statement = db()->prepare(
        'INSERT INTO contact_requests (name, company, phone, email, message, ip_address, user_agent) '
        . 'VALUES (:name, :company, :phone, :email, :message, :ip_address, :user_agent)'
    );
    $statement->execute([
        ':name' => $input['name'],
        ':company' => $input['company'] !== '' ? $input['company'] : null,
        ':phone' => $input['phone'],
        ':email' => $input['email'] !== '' ? $input['email'] : null,
        ':message' => $inquiryType === '' ? $input['message'] : '[' . $inquiryType . '] ' . $input['message'],
        ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    unset($_SESSION['old']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    redirectWithFlash('success', '상담 신청이 접수되었습니다. 빠르게 연락드리겠습니다.');
} catch (Throwable $exception) {
    error_log('[eumsolution] Contact insert failed: ' . $exception->getMessage());
    redirectWithFlash('error', '현재 상담 신청을 저장할 수 없습니다. 잠시 후 다시 시도해 주세요.', $input);
}
