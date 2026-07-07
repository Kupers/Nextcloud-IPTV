<?php

declare(strict_types=1);

namespace OCA\IPTV\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Folder>
 */
class FolderMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'iptv_folders');
	}

	/**
	 * @return Folder[]
	 */
	public function findAll(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->orderBy('id', 'ASC');
		return $this->findEntities($qb);
	}

	public function find(int $id, string $userId): ?Folder {
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

	public function createFolder(string $name, string $userId): Folder {
		$folder = new Folder();
		$folder->setName($name);
		$folder->setUserId($userId);
		return $this->insert($folder);
	}

	public function updateFolder(int $id, string $name, string $userId): ?Folder {
		$folder = $this->find($id, $userId);
		if ($folder === null) {
			return null;
		}
		$folder->setName($name);
		return $this->update($folder);
	}

	public function deleteFolder(int $id, string $userId): bool {
		$folder = $this->find($id, $userId);
		if ($folder === null) {
			return false;
		}
		$this->delete($folder);
		return true;
	}

	public function findByName(string $name, string $userId): ?Folder {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('name', $qb->createNamedParameter($name)));
		try {
			return $this->findEntity($qb);
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			return null;
		}
	}

	public function deleteAllForUser(string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		$qb->executeStatement();
	}

	/**
	 * @return Folder[]
	 */
	public function findAllForAllUsers(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->orderBy('user_id', 'ASC')
			->addOrderBy('id', 'ASC');
		return $this->findEntities($qb);
	}

	public function deleteAllForAllUsers(): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName());
		return $qb->executeStatement();
	}
}
