<?php
$manifestPath = FCPATH . 'build/.vite/manifest.json';
$jsFile = $cssFile = null;

if (file_exists($manifestPath)) {
    $content = file_get_contents($manifestPath);
    $manifest = json_decode($content, true);

    $entry = $manifest['main.js'] ?? null;
    if ($entry) {
        $jsFile = $entry['file'] ?? null;
        $cssFile = $entry['css'][0] ?? null;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JamWithJamie - Children's Entertainment Services</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('img/logos/icon.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('img/logos/icon.png') ?>">

    <!-- Meta tags for SEO -->
    <meta name="description" content="Professional children's entertainment services for birthdays and special events. Book your magical experience with JamWithJamie.">
    <meta name="keywords" content="children entertainment, birthday parties, kids events, performers, party services">
    <meta name="author" content="JamWithJamie">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= base_url() ?>">
    <meta property="og:title" content="JamWithJamie - Children's Entertainment Services">
    <meta property="og:description" content="Professional children's entertainment services for birthdays and special events.">
    <meta property="og:image" content="<?= base_url('img/logos/JWJ_logo-05.png') ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= base_url() ?>">
    <meta property="twitter:title" content="JamWithJamie - Children's Entertainment Services">
    <meta property="twitter:description" content="Professional children's entertainment services for birthdays and special events.">
    <meta property="twitter:image" content="<?= base_url('img/logos/JWJ_logo-05.png') ?>">

    <?php if ($cssFile): ?>
        <link rel="stylesheet" href="<?= base_url('build/' . $cssFile) ?>">
    <?php endif; ?>
</head>

<body>
    <div id="app"></div>

    <?php if ($jsFile): ?>
        <script type="module" src="<?= base_url('build/' . $jsFile) ?>"></script>
    <?php endif; ?>
</body>

</html>