<?php

declare(strict_types=1);

namespace OCA\IPTV\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class Settings implements ISettings {
	public function getForm(): TemplateResponse {
		return new TemplateResponse('iptv', 'settings-admin', [], '');
	}

	public function getSection(): string {
		return 'iptv';
	}

	public function getPriority(): int {
		return 50;
	}
}
