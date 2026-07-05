<?php

declare(strict_types=1);

namespace OCA\IPTV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20240705000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('iptv_folders')) {
			$table = $schema->createTable('iptv_folders');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id'], 'iptv_folders_user_id');
		}

		if (!$schema->hasTable('iptv_channels')) {
			$table = $schema->createTable('iptv_channels');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('url', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('logo', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('folder_id', Types::INTEGER, [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id'], 'iptv_channels_user_id');
			$table->addIndex(['folder_id'], 'iptv_channels_folder_id');
		}

		return $schema;
	}
}
