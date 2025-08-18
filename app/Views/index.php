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
    <title>Template</title>
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