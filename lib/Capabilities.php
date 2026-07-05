<?php

declare(strict_types=1);

namespace OCA\IPTV;

use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
	public function getCapabilities(): array {
		return [
			'iptv' => [
				'v1' => [
					'channels',
					'folders',
				],
			],
		];
	}
}
