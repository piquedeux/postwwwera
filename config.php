<?php
/**
 * Konfiguration
 *
 * Passwort ändern: neuen Hash erzeugen mit z. B.
 *   php -r "echo password_hash('DEIN-NEUES-PASSWORT', PASSWORD_DEFAULT);"
 * und den Wert unten ersetzen.
 */

define('ADMIN_PASSWORD_HASH', '$2y$10$c3Q.2UGHXsdBQzuo.sPJRumU0vWethaXQLQYgaNw.3HjF/m0ybWXC'); // aktuell: zugang1

define('BASE_PATH', __DIR__);
define('CONTENT_FILE', BASE_PATH . '/data/content.json');
define('MESSAGES_FILE', BASE_PATH . '/data/messages.json');
define('DATA_FILE', BASE_PATH . '/data/media.json');
define('PAGES_FILE', BASE_PATH . '/data/pages.json');
define('SETTINGS_FILE', BASE_PATH . '/data/settings.json');
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('UPLOAD_URL', 'uploads');

define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('ALLOWED_VIDEO_EXT', ['mp4', 'webm', 'mov']);
define('ALLOWED_AUDIO_EXT', ['mp3', 'wav', 'ogg', 'm4a']);
define('MAX_FILE_SIZE', 300 * 1024 * 1024); // 300 MB

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
