<?php

declare(strict_types=1);

namespace OCA\IPTV\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class Folder extends Entity implements \JsonSerializable {
	protected string $name = '';
	protected string $userId = '';

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('userId', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'userId' => $this->userId,
		];
	}
}
