<?php
require_once __DIR__ . '/includes/functions.php';
$content = load_content();
$active = 'message';
$contact = $content['contact'] ?? [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expired. Please reload and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            $error = 'Please fill in all fields.';
        } else {
            save_message([
                'id' => generate_id(),
                'created_at' => date('c'),
                'name' => $name,
                'email' => $email,
                'message' => $message,
            ]);
            $success = $contact['thank_you'] ?? 'Message sent.';
            $_POST = [];
        }
    }
}
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($content['nav']['message'] ?? 'Message') . ' – ' . ($content['site_title'] ?? 'Olafur Mowa')) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-message">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="section-page">
  <section class="section-intro">
    <?php if (!empty($contact['intro'])): ?><p><?= e($contact['intro']) ?></p><?php endif; ?>
  </section>

  <?php if ($error): ?><p class="form-message error-message"><?= e($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p class="form-message success-message"><?= e($success) ?></p><?php endif; ?>

  <form method="post" class="contact-form">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" placeholder="Name" required>
    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="Email">
    <textarea name="message" rows="7" placeholder="Message" required><?= e($_POST['message'] ?? '') ?></textarea>
    <button type="submit"><?= e($contact['button_label'] ?? 'Send message') ?></button>
  </form>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>