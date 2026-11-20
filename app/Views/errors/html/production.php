<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="robots" content="noindex">

	<title>Whoops!</title>

	<style type="text/css">
		<?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug.css')) ?>
	</style>
</head>
<body>

	<div class="container text-center">

		<h1 class="headline">Whoops!</h1>

<p class="lead">
    Permintaan Anda tidak dapat diproses saat ini.
    Silakan coba lagi nanti atau hubungi administrator.
</p>

<?php if (! empty($event_id)): ?>
    <div class="event-id-badge">
        ID Kejadian: <strong><?= esc($event_id) ?></strong>
    </div>
<?php endif; ?>

	</div>

</body>

</html>
