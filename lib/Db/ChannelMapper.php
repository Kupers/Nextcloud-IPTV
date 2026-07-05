<?php

declare(strict_types=1);

namespace OCA\IPTV\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Channel>
 */
class ChannelMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'iptv_channels');
	}

	/**
	 * @return Channel[]
	 */
	public function findAll(string $userId, ?int $folderId = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		if ($folderId !== null) {
			$qb->andWhere($qb->expr()->eq('folder_id', $qb->createNamedParameter($folderId, IQueryBuilder::PARAM_INT)));
		}

		$qb->orderBy('id', 'ASC');
		return $this->findEntities($qb);
	}

	public function deleteAllForUser(string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		$qb->executeStatement();
	}

	public function find(int $id, string $userId): ?Channel {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		try {
			return $this->findEntity($qb);
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			return null;
		}
	}

	public function createChannel(string $name, string $url, ?string $logo, string $userId, ?int $folderId = null): Channel {
		$channel = new Channel();
		$channel->setName($name);
		$channel->setUrl($url);
		$channel->setLogo($logo);
		$channel->setUserId($userId);
		$channel->setFolderId($folderId);
		return $this->insert($channel);
	}

	public function updateChannel(int $id, string $name, string $url, ?string $logo, string $userId, ?int $folderId = null): ?Channel {
		$channel = $this->find($id, $userId);
		if ($channel === null) {
			return null;
		}
		$channel->setName($name);
		$channel->setUrl($url);
		$channel->setLogo($logo);
		$channel->setFolderId($folderId);
		return $this->update($channel);
	}

	public function deleteChannel(int $id, string $userId): bool {
		$channel = $this->find($id, $userId);
		if ($channel === null) {
			return false;
		}
		$this->delete($channel);
		return true;
	}
}
