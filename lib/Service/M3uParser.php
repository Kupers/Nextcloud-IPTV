<?php

declare(strict_types=1);

namespace OCA\IPTV\Service;

class M3uParser {
	/**
	 * @return array{name: string, url: string, logo: ?string, groupTitle: ?string}[]
	 */
	public function parse(string $content): array {
		$entries = [];
		$lines = preg_split('/\r?\n/', $content);
		$current = null;

		foreach ($lines as $raw) {
			$line = trim($raw);
			if ($line === '' || $line === '#EXTM3U') {
				continue;
			}
			if (str_starts_with($line, '#EXTINF:')) {
				if ($current !== null && $current['url'] !== '') {
					$entries[] = $current;
				}
				$current = $this->parseExtinf($line);
			} elseif (str_starts_with($line, '#')) {
				continue;
			} elseif ($current !== null) {
				$current['url'] = $line;
			}
		}

		if ($current !== null && $current['url'] !== '') {
			$entries[] = $current;
		}

		return $entries;
	}

	/**
	 * @return array{name: string, url: string, logo: ?string, groupTitle: ?string}
	 */
	private function parseExtinf(string $line): array {
		$name = '';
		$logo = null;
		$groupTitle = null;

		if (preg_match('/tvg-logo="([^"]*)"/i', $line, $m)) {
			$logo = $m[1] ?: null;
		}
		if (preg_match('/group-title="([^"]*)"/i', $line, $m)) {
			$groupTitle = $m[1] ?: null;
		}
		$idx = strrpos($line, ',');
		if ($idx !== false) {
			$name = trim(substr($line, $idx + 1));
		}

		return ['name' => $name, 'url' => '', 'logo' => $logo, 'groupTitle' => $groupTitle];
	}
}
