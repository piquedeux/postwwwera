<?php
require_once __DIR__ . '/../config.php';

function default_site_content(): array
{
    return [
        'site_title' => 'Olafur Mowa',
        'instagram_url' => '',
        'hero' => [
            'kicker' => 'Musician',
            'headline' => 'Olafur Mowa',
            'intro' => 'Release notes, picture and film, demos, concerts and shop.'
        ],
        'nav' => [
            'music' => 'Music',
            'gallery' => 'Picture & Film',
            'demos' => 'Demos',
            'concerts' => 'Concerts',
            'shop' => 'Shop',
            'message' => 'Message',
        ],
        'music_releases' => [],
        'gallery_items' => [],
        'demos' => [],
        'concerts' => [],
        'shop' => [
            'iframe_url' => 'https://r2s.bigcartel.com',
            'heading' => 'Shop',
            'note' => '',
        ],
        'smtp' => [
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'from_email' => '',
            'from_name' => 'Olafur Mowa',
        ],
        'contact' => [
            'heading' => 'Message',
            'intro' => 'Send a message using the form below.',
            'thank_you' => 'Message sent. I will get back to you soon.',
            'recipient_email' => '',
        ],
    ];
}

function load_content(): array
{
    $default = default_site_content();
    if (!file_exists(CONTENT_FILE)) {
        return $default;
    }
    $fp = fopen(CONTENT_FILE, 'r');
    if (!$fp) {
        return $default;
    }
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($content, true);
    return is_array($data) ? array_replace_recursive($default, $data) : $default;
}

function save_content(array $content): bool
{
    $fp = fopen(CONTENT_FILE, 'c+');
    if (!$fp) {
        return false;
    }
    $data = array_replace_recursive(default_site_content(), $content);
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function load_media(): array
{
    $content = load_content();
    return is_array($content['gallery_items'] ?? null) ? $content['gallery_items'] : [];
}

function save_media(array $items): bool
{
    $content = load_content();
    $content['gallery_items'] = array_values($items);
    return save_content($content);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['sz_admin']);
}

function load_json_file(string $path, array $default): array
{
    if (!file_exists($path)) {
        return $default;
    }
    $fp = fopen($path, 'r');
    if (!$fp) {
        return $default;
    }
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($content, true);
    return is_array($data) ? array_merge($default, $data) : $default;
}

function save_json_file(string $path, array $data): bool
{
    $fp = fopen($path, 'c+');
    if (!$fp) {
        return false;
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function load_pages(): array
{
    return ['music' => '', 'gallery' => '', 'demos' => '', 'concerts' => '', 'shop' => '', 'message' => ''];
}

function save_pages(array $pages): bool
{
    $content = load_content();
    $content['legacy_pages'] = $pages;
    return save_content($content);
}

function load_settings(): array
{
    $content = load_content();
    return [
        'site_title' => $content['site_title'] ?? 'Olafur Mowa',
        'instagram_url' => $content['instagram_url'] ?? '',
    ];
}

function save_settings(array $settings): bool
{
    $content = load_content();
    $content['site_title'] = trim($settings['site_title'] ?? '') ?: 'Olafur Mowa';
    $content['instagram_url'] = trim($settings['instagram_url'] ?? '');
    return save_content($content);
}

function load_messages(): array
{
    if (!file_exists(MESSAGES_FILE)) {
        return [];
    }
    $fp = fopen(MESSAGES_FILE, 'r');
    if (!$fp) {
        return [];
    }
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function save_message(array $message): bool
{
    $messages = load_messages();
    $messages[] = $message;
    $fp = fopen(MESSAGES_FILE, 'c+');
    if (!$fp) {
        return false;
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function smtp_encode_header_value(string $value): string
{
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }
    return $value;
}

function smtp_format_address(string $email, string $name = ''): string
{
    $email = trim($email);
    if ($name === '') {
        return '<' . $email . '>';
    }
    return smtp_encode_header_value($name) . ' <' . $email . '>';
}

function smtp_read_response($stream): array
{
    $lines = [];
    while (($line = fgets($stream, 1024)) !== false) {
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            continue;
        }
        $lines[] = $line;
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }
    $last = $lines[count($lines) - 1] ?? '000 Unknown response';
    $code = (int) substr($last, 0, 3);
    return [
        'ok' => $code >= 200 && $code < 400,
        'code' => $code,
        'text' => implode("\n", $lines),
    ];
}

function smtp_send_command($stream, string $command, array $expectedCodes): array
{
    fwrite($stream, $command . "\r\n");
    $response = smtp_read_response($stream);
    if (!in_array($response['code'], $expectedCodes, true)) {
        return ['ok' => false, 'error' => $response['text']];
    }
    return ['ok' => true, 'response' => $response];
}

function smtp_dot_stuff(string $data): string
{
    $lines = preg_split('/\r\n|\r|\n/', $data);
    foreach ($lines as &$line) {
        if ($line !== '' && str_starts_with($line, '.')) {
            $line = '.' . $line;
        }
    }
    unset($line);
    return implode("\r\n", $lines);
}

function smtp_send_mail(array $smtp, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $textBody, string $htmlBody, ?string $replyToEmail = null, ?string $replyToName = null): array
{
    $host = trim($smtp['host'] ?? '');
    $port = (int) ($smtp['port'] ?? 587);
    $encryption = strtolower(trim($smtp['encryption'] ?? 'tls'));
    $username = trim($smtp['username'] ?? '');
    $password = (string) ($smtp['password'] ?? '');
    $fromEmail = trim($fromEmail);
    $toEmail = trim($toEmail);

    if ($host === '' || $fromEmail === '' || $toEmail === '') {
        return ['ok' => false, 'error' => 'SMTP configuration is incomplete.'];
    }

    $transport = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $stream = @stream_socket_client($transport, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$stream) {
        return ['ok' => false, 'error' => $errstr ?: 'Could not connect to SMTP server.'];
    }

    stream_set_timeout($stream, 15);
    $response = smtp_read_response($stream);
    if (!$response['ok']) {
        fclose($stream);
        return ['ok' => false, 'error' => $response['text']];
    }

    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $command = smtp_send_command($stream, 'EHLO ' . $hostname, [250]);
    if (!$command['ok']) {
        fclose($stream);
        return ['ok' => false, 'error' => $command['error']];
    }

    if ($encryption === 'tls') {
        $command = smtp_send_command($stream, 'STARTTLS', [220]);
        if (!$command['ok']) {
            fclose($stream);
            return ['ok' => false, 'error' => $command['error']];
        }
        if (!stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($stream);
            return ['ok' => false, 'error' => 'Could not enable TLS for SMTP connection.'];
        }
        $command = smtp_send_command($stream, 'EHLO ' . $hostname, [250]);
        if (!$command['ok']) {
            fclose($stream);
            return ['ok' => false, 'error' => $command['error']];
        }
    }

    if ($username !== '') {
        $command = smtp_send_command($stream, 'AUTH LOGIN', [334]);
        if (!$command['ok']) {
            fclose($stream);
            return ['ok' => false, 'error' => $command['error']];
        }
        $command = smtp_send_command($stream, base64_encode($username), [334]);
        if (!$command['ok']) {
            fclose($stream);
            return ['ok' => false, 'error' => $command['error']];
        }
        $command = smtp_send_command($stream, base64_encode($password), [235]);
        if (!$command['ok']) {
            fclose($stream);
            return ['ok' => false, 'error' => $command['error']];
        }
    }

    $command = smtp_send_command($stream, 'MAIL FROM:<' . $fromEmail . '>', [250]);
    if (!$command['ok']) {
        fclose($stream);
        return ['ok' => false, 'error' => $command['error']];
    }

    $command = smtp_send_command($stream, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
    if (!$command['ok']) {
        fclose($stream);
        return ['ok' => false, 'error' => $command['error']];
    }

    $command = smtp_send_command($stream, 'DATA', [354]);
    if (!$command['ok']) {
        fclose($stream);
        return ['ok' => false, 'error' => $command['error']];
    }

    $boundary = 'b1_' . bin2hex(random_bytes(8));
    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . smtp_format_address($fromEmail, $fromName),
        'To: ' . smtp_format_address($toEmail, $toName),
        'Subject: ' . smtp_encode_header_value($subject),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    if ($replyToEmail !== null && trim($replyToEmail) !== '') {
        $headers[] = 'Reply-To: ' . smtp_format_address($replyToEmail, $replyToName ?? '');
    }

    $body = [];
    $body[] = '--' . $boundary;
    $body[] = 'Content-Type: text/plain; charset=UTF-8';
    $body[] = 'Content-Transfer-Encoding: 8bit';
    $body[] = '';
    $body[] = smtp_dot_stuff($textBody);
    $body[] = '--' . $boundary;
    $body[] = 'Content-Type: text/html; charset=UTF-8';
    $body[] = 'Content-Transfer-Encoding: 8bit';
    $body[] = '';
    $body[] = smtp_dot_stuff($htmlBody);
    $body[] = '--' . $boundary . '--';

    $payload = implode("\r\n", array_merge($headers, [''], $body)) . "\r\n.";
    fwrite($stream, $payload . "\r\n");

    $response = smtp_read_response($stream);
    if (!$response['ok']) {
        fwrite($stream, "QUIT\r\n");
        fclose($stream);
        return ['ok' => false, 'error' => $response['text']];
    }

    fwrite($stream, "QUIT\r\n");
    fclose($stream);
    return ['ok' => true];
}

/**
 * Wandelt freien Text (mit Leerzeilen als Absatztrennung) in escapte
 * <p>-Absätze um, ohne beliebiges HTML zuzulassen.
 */
function render_text_as_paragraphs(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $blocks = preg_split('/\R\R+/', $text);
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $html .= '<p>' . nl2br(e($block)) . '</p>' . "\n";
    }
    return $html;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function media_type_for_ext(string $ext): ?string
{
    $ext = strtolower($ext);
    if (in_array($ext, ALLOWED_IMAGE_EXT, true)) {
        return 'image';
    }
    if (in_array($ext, ALLOWED_VIDEO_EXT, true)) {
        return 'video';
    }
    if (in_array($ext, ALLOWED_AUDIO_EXT, true)) {
        return 'audio';
    }
    return null;
}

function upload_url_for_file(string $file): string
{
    return UPLOAD_URL . '/' . rawurlencode($file);
}

function generate_id(): string
{
    return bin2hex(random_bytes(6));
}

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
