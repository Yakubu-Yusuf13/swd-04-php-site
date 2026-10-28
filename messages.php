<?php
declare(strict_types=1);

if (getenv('VERCEL') === '1') {
    http_response_code(404);
    exit;
}

session_start();
header('Cache-Control: no-store, private');

$pageTitle = 'Messages';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/data.php';

if (!isset($_SESSION['messages_csrf_token']) || !is_string($_SESSION['messages_csrf_token'])) {
    $_SESSION['messages_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['messages_csrf_token'];
$loginError = '';
$storageError = '';
$messages = [];
$isAuthenticated = ($_SESSION['messages_authenticated'] ?? false) === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $loginError = 'Your session expired. Refresh the page and try again.';
    } elseif (isset($_POST['logout'])) {
        unset($_SESSION['messages_authenticated']);
        session_regenerate_id(true);
        header('Location: messages.php', true, 303);
        exit;
    } else {
        $password = $_POST['password'] ?? '';
        if (is_string($password) && hash_equals(MESSAGES_ADMIN_PASSWORD, $password)) {
            session_regenerate_id(true);
            $_SESSION['messages_authenticated'] = true;
            header('Location: messages.php', true, 303);
            exit;
        }

        $loginError = 'That password was not correct. Please try again.';
    }
}

$isAuthenticated = ($_SESSION['messages_authenticated'] ?? false) === true;

if ($isAuthenticated) {
    $messagesFile = __DIR__ . '/storage/messages.jsonl';
    if (is_file($messagesFile)) {
        $lines = file($messagesFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            error_log('Messages page could not read storage/messages.jsonl.');
            $storageError = 'Messages could not be loaded right now.';
        } else {
            foreach ($lines as $lineNumber => $line) {
                if ($line === '') {
                    continue;
                }

                try {
                    $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    error_log('Messages page skipped invalid JSON on line ' . ($lineNumber + 1) . '.');
                    continue;
                }

                if (
                    !is_array($record)
                    || !isset($record['timestamp'], $record['name'], $record['email'], $record['phone'], $record['service'], $record['message'])
                    || !is_string($record['timestamp'])
                    || !is_string($record['name'])
                    || !is_string($record['email'])
                    || !is_string($record['phone'])
                    || !is_string($record['service'])
                    || !is_string($record['message'])
                ) {
                    error_log('Messages page skipped an invalid record on line ' . ($lineNumber + 1) . '.');
                    continue;
                }

                try {
                    $record['_timestamp_sort'] = (new DateTimeImmutable($record['timestamp']))->getTimestamp();
                } catch (Exception $exception) {
                    error_log('Messages page skipped a record with an invalid timestamp on line ' . ($lineNumber + 1) . '.');
                    continue;
                }

                $messages[] = $record;
            }

            usort(
                $messages,
                static fn (array $first, array $second): int => $second['_timestamp_sort'] <=> $first['_timestamp_sort']
            );
        }
    } elseif (!is_dir(__DIR__ . '/storage')) {
        $storageError = 'There are no messages yet.';
    }
}

$pageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container page-hero-inner">
        <p class="eyebrow">Local learning tool</p>
        <h1>Contact <span>messages.</span></h1>
        <p class="lead">Review submissions sent through the Prime Cleaners contact form.</p>
    </div>
</section>
<section class="section section-soft">
    <div class="container messages-panel">
        <?php if (!$isAuthenticated): ?>
            <div class="messages-login">
                <p class="eyebrow">Restricted page</p>
                <h2>Enter the local admin password.</h2>
                <p>This page is intended for local learning only. Do not expose it on a public server.</p>
                <?php if ($loginError !== ''): ?>
                    <div class="form-error-summary" role="alert"><?= e($loginError) ?></div>
                <?php endif; ?>
                <form class="contact-form" method="post" action="messages.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <div class="form-field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="button button-primary" type="submit">View messages</button>
                </form>
            </div>
        <?php else: ?>
            <div class="messages-heading">
                <div>
                    <p class="eyebrow">Submission inbox</p>
                    <h2>Recent messages <span class="count-badge"><?= count($messages) ?></span></h2>
                </div>
                <form method="post" action="messages.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <button class="button button-primary" type="submit" name="logout" value="1">Log out</button>
                </form>
            </div>

            <?php if ($storageError !== ''): ?>
                <div class="form-error-summary" role="status"><?= e($storageError) ?></div>
            <?php elseif ($messages === []): ?>
                <p class="messages-empty">No contact submissions yet.</p>
            <?php else: ?>
                <div class="messages-table-wrap">
                    <table class="messages-table">
                        <thead>
                            <tr>
                                <th scope="col">Received</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Service</th>
                                <th scope="col">Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <?php
                                $serviceTitle = $message['service'];
                                foreach ($services as $serviceOption) {
                                    if ($serviceOption['slug'] === $message['service']) {
                                        $serviceTitle = $serviceOption['title'];
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= e($message['timestamp']) ?></td>
                                    <td><?= e($message['name']) ?></td>
                                    <td><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></td>
                                    <td><?= e($message['phone'] !== '' ? $message['phone'] : '—') ?></td>
                                    <td><?= e($serviceTitle) ?></td>
                                    <td class="message-content"><?= e($message['message']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
