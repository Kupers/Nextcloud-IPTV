<?php

declare(strict_types=1);

namespace OCA\IPTV\Controller;

use OCA\IPTV\Db\ChannelMapper;
use OCA\IPTV\Db\FolderMapper;
use OCA\IPTV\Service\M3uParser;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;

class AdminController extends Controller {
	private ChannelMapper $channelMapper;
	private FolderMapper $folderMapper;
	private IURLGenerator $urlGenerator;
	private IUserManager $userManager;
	private M3uParser $m3uParser;

	public function __construct(
		IRequest $request,
		ChannelMapper $channelMapper,
		FolderMapper $folderMapper,
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		M3uParser $m3uParser,
	) {
		parent::__construct('iptv', $request);
		$this->channelMapper = $channelMapper;
		$this->folderMapper = $folderMapper;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->m3uParser = $m3uParser;
	}

	private function adminUrl(array $params = []): string {
		$params = array_merge(['section' => 'iptv'], $params);
		return $this->urlGenerator->linkToRoute('settings.AdminSettings.index', $params);
	}

	#[NoCSRFRequired]
	#[AdminRequired]
	public function exportM3u(): DataResponse {
		$channels = $this->channelMapper->findAllForAllUsers();
		$lines = ['#EXTM3U'];
		$currentUser = null;
		foreach ($channels as $channel) {
			if ($channel->getUserId() !== $currentUser) {
				$currentUser = $channel->getUserId();
				$lines[] = "# ---- {$currentUser} ----";
			}
			$logo = $channel->getLogo();
			$logoAttr = $logo ? " tvg-logo=\"{$logo}\"" : '';
			$lines[] = "#EXTINF:-1{$logoAttr},{$channel->getName()}";
			$lines[] = $channel->getUrl();
		}
		$content = implode("\n", $lines) . "\n";
		return new DataResponse(
			$content,
			Http::STATUS_OK,
			[
				'Content-Type' => 'audio/x-mpegurl',
				'Content-Disposition' => 'attachment; filename="iptv-all-playlists.m3u"',
			]
		);
	}

	#[AdminRequired]
	public function clearChannels(): RedirectResponse {
		$this->channelMapper->deleteAllForAllUsers();
		if ($this->request->getParam('clearFolders')) {
			$this->folderMapper->deleteAllForAllUsers();
			return new RedirectResponse($this->adminUrl(['iptv-cleared' => 'all']));
		}
		return new RedirectResponse($this->adminUrl(['iptv-cleared' => 'channels']));
	}

	#[AdminRequired]
	public function clearFolders(): RedirectResponse {
		$this->folderMapper->deleteAllForAllUsers();
		return new RedirectResponse($this->adminUrl(['iptv-cleared' => 'folders']));
	}

	#[AdminRequired]
	public function importM3u(): RedirectResponse {
		if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
			return new RedirectResponse($this->adminUrl(['iptv-import' => 'error']));
		}

		$content = file_get_contents($_FILES['importFile']['tmp_name']);
		if ($content === false || $content === '') {
			return new RedirectResponse($this->adminUrl(['iptv-import' => 'empty']));
		}

		$entries = $this->m3uParser->parse($content);
		if (empty($entries)) {
			return new RedirectResponse($this->adminUrl(['iptv-import' => 'noentries']));
		}

		$target = $this->request->getParam('targetUser', '');
		$userIds = [];

		if ($target === 'all') {
			foreach ($this->userManager->search('') as $user) {
				$userIds[] = $user->getUID();
			}
		} elseif ($target !== '') {
			$userIds[] = $target;
		} else {
			return new RedirectResponse($this->adminUrl(['iptv-import' => 'nouser']));
		}

		$added = 0;
		$errors = 0;

		foreach ($userIds as $userId) {
			$folderCache = [];
			foreach ($entries as $entry) {
				$folderId = null;
				if ($entry['groupTitle'] !== null) {
					if (isset($folderCache[$entry['groupTitle']])) {
						$folderId = $folderCache[$entry['groupTitle']];
					} else {
						$existing = $this->folderMapper->findByName($entry['groupTitle'], $userId);
						if ($existing !== null) {
							$folderId = $existing->getId();
							$folderCache[$entry['groupTitle']] = $folderId;
						} else {
							try {
								$folder = $this->folderMapper->createFolder($entry['groupTitle'], $userId);
								$folderId = $folder->getId();
								$folderCache[$entry['groupTitle']] = $folderId;
							} catch (\Exception $e) {
								$errors++;
								continue;
							}
						}
					}
				}
				try {
					$this->channelMapper->createChannel(
						$entry['name'],
						$entry['url'],
						$entry['logo'],
						$userId,
						$folderId,
					);
					$added++;
				} catch (\Exception $e) {
					$errors++;
				}
			}
		}

		return new RedirectResponse($this->adminUrl([
			'iptv-import' => 'ok',
			'added' => $added,
			'errors' => $errors,
		]));
	}
}
