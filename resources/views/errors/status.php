<?php
/**
 * Generic user-facing HTTP error. It deliberately exposes no exception text,
 * SQL errors or private paths. All template variables are escaped.
 * @var int $status
 * @var string $message
 * @var string $url
 * @var string $asset
 */
declare(strict_types=1);

use Vereinssystem\Core\View;
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::escape($status) ?> – <?= View::escape($message) ?></title>
    <link rel="stylesheet" href="<?= View::escape($asset) ?>">
</head>
<body class="vs-app">
    <main class="vs-container vs-error">
        <div class="vs-error__status"><?= View::escape($status) ?></div>
        <h1><?= View::escape($message) ?></h1>
        <p>Die angeforderte Seite kann nicht angezeigt werden.</p>
        <a class="vs-button vs-button--primary" href="<?= View::escape($url) ?>">Zum Kernel-Start</a>
    </main>
</body>
</html>
