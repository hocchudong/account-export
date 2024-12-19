<?php

declare(strict_types=1);
use OCP\Util;
$appId = OCA\AccountExport\AppInfo\Application::APP_ID;

Util::addScript($appId, 'main');
?>

<div id="accountexport">
</div>
