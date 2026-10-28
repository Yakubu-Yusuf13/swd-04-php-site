<?php
declare(strict_types=1);

$isVercelDeployment = getenv('VERCEL') === '1';
if (!$isVercelDeployment) {
    session_start();
}

$pageTitle = 'Contact';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/data.php';

if (!$isVercelDeployment && (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']))) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $isVercelDeployment ? '' : $_SESSION['csrf_token'];
$fields = ['name', 'email', 'phone', 'service', 'message'];
$values = array_fill_keys($fields, '');
$errors = [];
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isVercelDeployment) {
    foreach ($fields as $field) {
        $postedValue = $_POST[$field] ?? '';

        if (is_string($postedValue)) {
            $values[$field] = trim($postedValue);
        } else {
            $errors[$field] = 'Please enter a valid value.';
        }
    }

    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $errors['csrf'] = 'Your form session has expired. Please review the form and try again.';
    }

    if ($values['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    }

    if ($values['email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($values['service'] === '') {
        $errors['service'] = 'Please choose a service.';
    } else {
        $serviceExists = false;
        foreach ($services as $serviceOption) {
            if ($serviceOption['slug'] === $values['service']) {
                $serviceExists = true;
                break;
            }
        }

        if (!$serviceExists) {
            $errors['service'] = 'Please choose one of the listed services.';
        }
    }

    if ($values['message'] === '') {
        $errors['message'] = 'Please enter a message.';
    } else {
        $messageLength = mb_strlen($values['message'], 'UTF-8');
        if ($messageLength < 10 || $messageLength > 1000) {
            $errors['message'] = 'Your message must be between 10 and 1000 characters.';
        }
    }

    if ($errors === []) {
        $storageDirectory = __DIR__ . '/storage';
        if (!is_dir($storageDirectory) && !@mkdir($storageDirectory, 0750, true) && !is_dir($storageDirectory)) {
            error_log('Contact form could not create the storage directory.');
            $formError = 'We could not save your message right now. Please try again later.';
        } else {
            $submission = [
                'timestamp' => date(DATE_ATOM),
                'name' => $values['name'],
                'email' => $values['email'],
                'phone' => $values['phone'],
                'service' => $values['service'],
                'message' => $values['message'],
            ];

            $jsonLine = json_encode(
                $submission,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            ) . PHP_EOL;
            $bytesWritten = @file_put_contents(
                $storageDirectory . '/messages.jsonl',
                $jsonLine,
                FILE_APPEND | LOCK_EX
            );

            if ($bytesWritten === false || $bytesWritten !== strlen($jsonLine)) {
                error_log('Contact form could not write a complete submission to storage/messages.jsonl.');
                $formError = 'We could not save your message right now. Please try again later.';
            } else {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: contact.php?sent=1', true, 303);
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container page-hero-inner">
        <p class="eyebrow">We’d love to hear from you</p>
        <h1>Tell us what <span>you need.</span></h1>
        <p class="lead">Have a question or want to talk through a cleaning plan? Send us a message and our friendly team will be in touch.</p>
    </div>
</section>
<section class="section section-soft">
    <div class="container contact-grid">
        <div class="contact-card">
            <span class="contact-icon" aria-hidden="true">✉</span>
            <p class="eyebrow">Send us a note</p>
            <h2>Let’s start with a hello.</h2>
            <p>Tell us a little about your space, what kind of cleaning you’re looking for, and a good time to reach you.</p>

            <?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
                <div class="form-success" role="status">Thanks for reaching out. Your message has been sent, and we’ll be in touch soon.</div>
            <?php endif; ?>
            <?php if ($formError !== ''): ?>
                <div class="form-error-summary" role="alert"><?= e($formError) ?></div>
            <?php endif; ?>
            <?php if (isset($errors['csrf'])): ?>
                <div class="form-error-summary" role="alert"><?= e($errors['csrf']) ?></div>
            <?php endif; ?>

            <?php if ($isVercelDeployment): ?>
                <div class="form-success" role="status">
                    The contact form is unavailable in this demo deployment. Please email
                    <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a> instead.
                </div>
            <?php else: ?>
            <form class="contact-form" method="post" action="contact.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-field">
                    <label for="name">Name <span aria-hidden="true">*</span></label>
                    <input id="name" name="name" type="text" autocomplete="name" value="<?= e($values['name']) ?>" required
                        <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                    <?php if (isset($errors['name'])): ?><p class="field-error" id="name-error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="email">Email <span aria-hidden="true">*</span></label>
                    <input id="email" name="email" type="email" autocomplete="email" value="<?= e($values['email']) ?>" required
                        <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                    <?php if (isset($errors['email'])): ?><p class="field-error" id="email-error"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="phone">Phone <span class="optional">(optional)</span></label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e($values['phone']) ?>"
                        <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
                    <?php if (isset($errors['phone'])): ?><p class="field-error" id="phone-error"><?= e($errors['phone']) ?></p><?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="service">Service <span aria-hidden="true">*</span></label>
                    <select id="service" name="service" required
                        <?= isset($errors['service']) ? 'aria-invalid="true" aria-describedby="service-error"' : '' ?>>
                        <option value="">Choose a service</option>
                        <?php foreach ($services as $serviceOption): ?>
                            <option value="<?= e($serviceOption['slug']) ?>"<?= $values['service'] === $serviceOption['slug'] ? ' selected' : '' ?>>
                                <?= e($serviceOption['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['service'])): ?><p class="field-error" id="service-error"><?= e($errors['service']) ?></p><?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="message">Message <span aria-hidden="true">*</span></label>
                    <textarea id="message" name="message" rows="5" required aria-describedby="<?= isset($errors['message']) ? 'message-hint message-error' : 'message-hint' ?>"
                        <?= isset($errors['message']) ? 'aria-invalid="true"' : '' ?>><?= e($values['message']) ?></textarea>
                    <p class="field-hint" id="message-hint">Please use 10 to 1000 characters.</p>
                    <?php if (isset($errors['message'])): ?><p class="field-error" id="message-error"><?= e($errors['message']) ?></p><?php endif; ?>
                </div>

                <button class="button button-primary" type="submit">Send your message</button>
            </form>
            <?php endif; ?>

            <p class="contact-direct">Prefer email? <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></p>
        </div>
        <aside class="contact-aside">
            <p class="eyebrow">A good visit starts here</p>
            <h2>What would you like cleaned?</h2>
            <p>When you get in touch, it helps to share:</p>
            <ul class="contact-checklist"><li>The type of space</li><li>How often you’d like a clean</li><li>Any details or priorities to keep in mind</li></ul>
            <div class="contact-note"><span aria-hidden="true">✦</span><p>No pressure, no fuss—just a friendly conversation about what would work for you.</p></div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
