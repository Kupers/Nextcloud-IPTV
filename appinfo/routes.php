<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'Admin#exportM3u', 'url' => '/admin/export-m3u', 'verb' => 'GET'],
		['name' => 'Admin#importM3u', 'url' => '/admin/import', 'verb' => 'POST'],
		['name' => 'Admin#clearChannels', 'url' => '/admin/channels', 'verb' => 'POST'],
		['name' => 'Admin#clearFolders', 'url' => '/admin/folders', 'verb' => 'POST'],
	],
];
