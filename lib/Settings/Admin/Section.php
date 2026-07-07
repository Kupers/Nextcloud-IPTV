<?php

declare(strict_types=1);

namespace OCA\IPTV\Settings\Admin;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	private IURLGenerator $url;
	private IL10N $l;

	public function __construct(IURLGenerator $url, IL10N $l) {
		$this->url = $url;
		$this->l = $l;
	}

	public function getID(): string {
		return 'iptv';
	}

	public function getName(): string {
		return $this->l->t('I P T V');
	}

	public function getPriority(): int {
		return 90;
	}

	public function getIcon(): string {
		return $this->url->imagePath('iptv', 'app.svg');
	}
}
