<?php

declare(strict_types=1);

namespace OCA\IPTV\Controller;

use OCA\IPTV\Db\ChannelMapper;
use OCA\IPTV\Db\FolderMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends Controller {
	private ChannelMapper $channelMapper;
	private FolderMapper $folderMapper;
	private IClientService $clientService;
	private ?string $userId;

	public function __construct(
		IRequest $request,
		ChannelMapper $channelMapper,
		FolderMapper $folderMapper,
		IClientService $clientService,
		IUserSession $userSession,
	) {
		parent::__construct('iptv', $request);
		$this->channelMapper = $channelMapper;
		$this->folderMapper = $folderMapper;
		$this->clientService = $clientService;
		$this->userId = $userSession->getUser()?->getUID();
	}

	// --- Channels ---

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/channels')]
	public function getChannels(?int $folderId = null): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$channels = $this->channelMapper->findAll($this->userId, $folderId);
		if (empty($channels) && $folderId === null) {
			$this->channelMapper->createChannel(
				'Dance TV Live Channels',
				'https://m1b2.worldcast.tv/dancetelevisionone/2/dancetelevisionone.m3u8',
				'https://onlinetvplayer.com/images/logos/0w7k24gz.png',
				$this->userId,
			);
			$channels = $this->channelMapper->findAll($this->userId, $folderId);
		}
		return new JSONResponse($channels);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/channels/{id}')]
	public function showChannel(int $id): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$channel = $this->channelMapper->find($id, $this->userId);
		if ($channel === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse($channel);
	}

	private function isValidHttpUrl(string $url): bool {
		return filter_var($url, FILTER_VALIDATE_URL)
			&& preg_match('/^https?:\/\//i', $url);
	}

	private function isValidImageUrl(string $url): bool {
		if (!$this->isValidHttpUrl($url)) {
			return false;
		}
		$path = parse_url($url, PHP_URL_PATH);
		if ($path === null) {
			return true;
		}
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if ($ext !== '' && !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'], true)) {
			return false;
		}
		return true;
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/api/v1/channels')]
	public function createChannel(string $name, string $url, ?string $logo = null, ?int $folderId = null): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (trim($name) === '' || trim($url) === '') {
			return new JSONResponse(['error' => 'Name and URL are required'], Http::STATUS_BAD_REQUEST);
		}
		if (!$this->isValidHttpUrl(trim($url))) {
			return new JSONResponse(['error' => 'Stream URL must be a valid http or https URL'], Http::STATUS_BAD_REQUEST);
		}
		if ($logo !== null && trim($logo) !== '' && !$this->isValidImageUrl(trim($logo))) {
			return new JSONResponse(['error' => 'Logo URL must be a valid http or https URL pointing to an image (jpg, png, gif, webp, svg)'], Http::STATUS_BAD_REQUEST);
		}
		$channel = $this->channelMapper->createChannel(
			trim($name),
			trim($url),
			$logo !== null ? trim($logo) : null,
			$this->userId,
			$folderId,
		);
		return new JSONResponse($channel, Http::STATUS_CREATED);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/api/v1/channels/{id}')]
	public function updateChannel(int $id, string $name, string $url, ?string $logo = null, ?int $folderId = null): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (trim($name) === '' || trim($url) === '') {
			return new JSONResponse(['error' => 'Name and URL are required'], Http::STATUS_BAD_REQUEST);
		}
		if (!$this->isValidHttpUrl(trim($url))) {
			return new JSONResponse(['error' => 'Stream URL must be a valid http or https URL'], Http::STATUS_BAD_REQUEST);
		}
		if ($logo !== null && trim($logo) !== '' && !$this->isValidImageUrl(trim($logo))) {
			return new JSONResponse(['error' => 'Logo URL must be a valid http or https URL pointing to an image (jpg, png, gif, webp, svg)'], Http::STATUS_BAD_REQUEST);
		}
		$channel = $this->channelMapper->updateChannel(
			$id,
			trim($name),
			trim($url),
			$logo !== null ? trim($logo) : null,
			$this->userId,
			$folderId,
		);
		if ($channel === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse($channel);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/api/v1/channels/{id}')]
	public function destroyChannel(int $id): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$deleted = $this->channelMapper->deleteChannel($id, $this->userId);
		if (!$deleted) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/api/v1/channels')]
	public function destroyAllChannels(): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$this->channelMapper->deleteAllForUser($this->userId);
		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	// --- Folders ---

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/folders')]
	public function getFolders(): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		return new JSONResponse(
			$this->folderMapper->findAll($this->userId)
		);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/api/v1/folders')]
	public function createFolder(string $name): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (trim($name) === '') {
			return new JSONResponse(['error' => 'Name is required'], Http::STATUS_BAD_REQUEST);
		}
		$folder = $this->folderMapper->createFolder(trim($name), $this->userId);
		return new JSONResponse($folder, Http::STATUS_CREATED);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/api/v1/folders/{id}')]
	public function updateFolder(int $id, string $name): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (trim($name) === '') {
			return new JSONResponse(['error' => 'Name is required'], Http::STATUS_BAD_REQUEST);
		}
		$folder = $this->folderMapper->updateFolder($id, trim($name), $this->userId);
		if ($folder === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse($folder);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/api/v1/folders/{id}')]
	public function destroyFolder(int $id): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$deleted = $this->folderMapper->deleteFolder($id, $this->userId);
		if (!$deleted) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/api/v1/folders')]
	public function destroyAllFolders(): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$this->folderMapper->deleteAllForUser($this->userId);
		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	// --- Image proxy ---

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/proxy-image')]
	public function proxyImage(string $url): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (!$this->isValidImageUrl($url)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		$client = $this->clientService->newClient();
		try {
			$response = $client->get($url, ['timeout' => 10]);
			$content = $response->getBody();
			$contentType = $response->getHeader('Content-Type') ?: 'image/png';
			$headers = ['Content-Type' => $contentType];
			return new DataResponse($content, Http::STATUS_OK, $headers);
		} catch (\Exception $e) {
			return new DataResponse([], Http::STATUS_BAD_GATEWAY);
		}
	}
}
