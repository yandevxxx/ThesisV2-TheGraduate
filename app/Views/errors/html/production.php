<!doctype html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="robots" content="noindex">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>Terjadi Kesalahan Sistem | TheSIS &amp; The Graduate</title>

	<style type="text/css">
		<?= preg_replace('#[\r\n\t ]+#', ' ', (string) @file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug.css')) ?>
		.event-id-badge {
			display: inline-block;
			margin-top: 1rem;
			padding: 0.65rem 1.25rem;
			background: #f3f6fa;
			border: 1px solid #cbd5e1;
			border-radius: 0.375rem;
			font-family: Consolas, monospace;
			font-size: 0.95rem;
			color: #1c476c;
		}
	</style>
</head>
<body>

	<div class="container text-center">

		<h1 class="headline">Terjadi Kesalahan Sistem</h1>

		<p class="lead">
			<?= esc($message ?? 'Permintaan Anda tidak dapat diproses saat ini. Silakan coba lagi nanti atau hubungi administrator.') ?>
		</p>

		<?php if (! empty($event_id)): ?>
			<div class="event-id-badge">
				ID Kejadian: <strong><?= esc($event_id) ?></strong>
			</div>
		<?php endif; ?>

	</div>

</body>

</html>
