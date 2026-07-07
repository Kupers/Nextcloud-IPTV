<?php

$url = \OC::$server->get(\OCP\IURLGenerator::class);
$l = \OC::$server->getL10N('iptv');
$token = $_['requesttoken'] ?? '';

$exportUrl = $url->linkToRoute('iptv.admin.exportM3u');
$channelsUrl = $url->linkToRoute('iptv.admin.clearChannels');
$foldersUrl = $url->linkToRoute('iptv.admin.clearFolders');
$importUrl = $url->linkToRoute('iptv.admin.importM3u');

try {
	$ch = \OC::$server->get(\OCA\IPTV\Db\ChannelMapper::class);
	$fo = \OC::$server->get(\OCA\IPTV\Db\FolderMapper::class);
	$channelCount = count($ch->findAllForAllUsers());
	$folderCount = count($fo->findAllForAllUsers());
} catch (\Exception $e) {
	$channelCount = 0;
	$folderCount = 0;
}

$users = [];
try {
	$um = \OC::$server->get(\OCP\IUserManager::class);
	foreach ($um->search('') as $u) {
		$users[$u->getUID()] = $u->getDisplayName();
	}
} catch (\Exception $e) {
}

$msg = null;
$cleared = $_GET['iptv-cleared'] ?? null;
if ($cleared === 'channels') $msg = $l->t('Clear all channels') . ': ' . $l->t('done') . '.';
elseif ($cleared === 'folders') $msg = $l->t('Clear all folders') . ': ' . $l->t('done') . '.';
elseif ($cleared === 'all') $msg = $l->t('Reset all data') . ': ' . $l->t('done') . '.';

$import = $_GET['iptv-import'] ?? null;
if ($import === 'ok') $msg = $l->t('Import M3U8') . ': ' . ($_GET['added'] ?? 0) . ' ' . $l->t('added') . ', ' . ($_GET['errors'] ?? 0) . ' ' . $l->t('errors') . '.';
elseif ($import === 'error') $msg = $l->t('Error') . ': ' . $l->t('Failed to upload file') . '.';
elseif ($import === 'empty') $msg = $l->t('Error') . ': ' . $l->t('File is empty') . '.';
elseif ($import === 'noentries') $msg = $l->t('No valid channels found in file');
elseif ($import === 'nouser') $msg = $l->t('Error') . ': ' . $l->t('No user selected') . '.';

?>
<div class="iptv-settings">
	<style>
		.iptv-settings { max-width: 640px; }
		.iptv-settings-stats { display: flex; gap: 24px; margin: 16px 0; }
		.iptv-stat { background: var(--color-background-dark); padding: 8px 16px; border-radius: 6px; }
		.iptv-settings-actions { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
		.iptv-settings-actions form { display: inline; }
		.iptv-settings-msg { padding: 8px 12px; border-radius: 6px; margin-top: 12px; background: var(--color-success, #46ba61); color: #fff; }
		.iptv-settings-msg.error { background: var(--color-error, #e9322d); }
		.iptv-import-form { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--color-border); display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
		.iptv-import-form select, .iptv-import-form input[type=file] { max-width: 240px; }
	</style>

<?php if ($msg): ?>
	<div class="iptv-settings-msg<?php if ($import && $import !== 'ok') echo ' error'; ?>"><?php p($msg) ?></div>
<?php endif ?>

	<h3><?php p($l->t('I P T V — Admin tools')) ?></h3>
	<p><?php p($l->t('Manage all users\' channels and folders at once.')) ?></p>

	<div class="iptv-settings-stats">
		<span class="iptv-stat"><strong><?php p($channelCount) ?></strong> <?php p($l->t('channels')) ?></span>
		<span class="iptv-stat"><strong><?php p($folderCount) ?></strong> <?php p($l->t('folders')) ?></span>
	</div>

	<div class="iptv-settings-actions">
		<a href="<?php p($exportUrl) ?>" class="button primary"><?php p($l->t('Export all playlists (M3U)')) ?></a>

		<form method="POST" action="<?php p($channelsUrl) ?>">
			<input type="hidden" name="requesttoken" value="<?php p($token) ?>">
			<button class="button" name="action" value="clear"><?php p($l->t('Clear all channels')) ?></button>
		</form>

		<form method="POST" action="<?php p($foldersUrl) ?>">
			<input type="hidden" name="requesttoken" value="<?php p($token) ?>">
			<button class="button" name="action" value="clear"><?php p($l->t('Clear all folders')) ?></button>
		</form>

		<form method="POST" action="<?php p($channelsUrl) ?>">
			<input type="hidden" name="requesttoken" value="<?php p($token) ?>">
			<input type="hidden" name="clearFolders" value="1">
			<button class="button" name="action" value="clear"><?php p($l->t('Reset all data')) ?></button>
		</form>
	</div>

	<form class="iptv-import-form" method="POST" action="<?php p($importUrl) ?>" enctype="multipart/form-data">
		<input type="hidden" name="requesttoken" value="<?php p($token) ?>">
		<strong><?php p($l->t('Import M3U8')) ?>:</strong>
		<select name="targetUser">
			<option value="">— <?php p($l->t('Select user')) ?> —</option>
			<option value="all"><?php p($l->t('All users')) ?></option>
<?php foreach ($users as $uid => $displayName): ?>
			<option value="<?php p($uid) ?>"><?php p($displayName) ?> (<?php p($uid) ?>)</option>
<?php endforeach ?>
		</select>
		<input type="file" name="importFile" accept=".m3u8,.m3u,.txt" required>
		<button class="button primary" type="submit"><?php p($l->t('Import')) ?></button>
	</form>
</div>