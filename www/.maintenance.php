<?php

header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 300'); // 5 minutes in seconds

?>
<!DOCTYPE html>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<meta name="generator" content="MobilneTelefony.sk">

<style>
	body { color: #333; background: white; width: 500px; margin: 100px auto }
	h1 { font: bold 47px/1.5 sans-serif; margin: .6em 0 }
	p { font: 21px/1.5 Georgia,serif; margin: 1.5em 0 }
</style>

<title>Stránka je dočasně nedostupná z důvodu údržby</title>

<h1>Omlouváme se</h1>

<p>Stránka je na malou chvíli nedostupná z důvodu údržby. Zkuste stránku za chvíli obnovit.</p>

<?php

exit;
