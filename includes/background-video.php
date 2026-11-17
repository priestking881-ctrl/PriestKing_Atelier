<?php
// Fashion video used for the current page background.
$backgroundVideo = $backgroundVideo ?? 'fashion-runway-red.mp4';
?>
<video class="page-background-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
    <source src="<?= PRIEST_BASE_URL ?>/assets/videos/<?= e($backgroundVideo) ?>" type="video/mp4">
</video>
<div class="page-background-overlay" aria-hidden="true"></div>
