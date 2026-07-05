<?php

declare(strict_types=1);

namespace OCA\IPTV\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method string getUrl()
 * @method void setUrl(string $url)
 * @method string|null getLogo()
 * @method void setLogo(?string $logo)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int|null getFolderId()
 * @method void setFolderId(?int $folderId)
 */
class Channel extends Entity implements \JsonSerializable {
	protected string $name = '';
	protected string $url = '';
	protected ?string $logo = null;
	protected string $userId = '';
	protected ?int $folderId = null;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('url', 'string');
		$this->addType('logo', 'string');
		$this->addType('userId', 'string');
		$this->addType('folderId', 'integer');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'url' => $this->url,
			'logo' => $this->logo,
			'userId' => $this->userId,
			'folderId' => $this->folderId,
		];
	}
}
