<?php

declare(strict_types=1);

namespace OCA\IPTV\AppInfo;

use OCA\IPTV\Db\ChannelMapper;
use OCA\IPTV\Db\FolderMapper;
use OCA\IPTV\Service\M3uParser;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\INavigationManager;
use OCP\Security\IContentSecurityPolicyManager;
use OCP\IURLGenerator;

class Application extends App implements IBootstrap {
	public const APP_ID = 'iptv';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(ChannelMapper::class, function ($c) {
			return new ChannelMapper(
				$c->get(\OCP\IDBConnection::class),
			);
		});
		$context->registerService(FolderMapper::class, function ($c) {
			return new FolderMapper(
				$c->get(\OCP\IDBConnection::class),
			);
		});
		$context->registerService(M3uParser::class, function ($c) {
			return new M3uParser();
		});
		$context->registerCapability(\OCA\IPTV\Capabilities::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (
			INavigationManager $navigationManager,
			IURLGenerator $urlGenerator,
		): void {
			$navigationManager->add(function () use ($urlGenerator): array {
				return [
					'id' => self::APP_ID,
					'name' => 'I P T V',
					'href' => $urlGenerator->linkToRoute('iptv.page.index'),
					'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
					'order' => 80,
					'type' => 'link',
				];
			});
		});

		$policy = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$policy->addAllowedConnectDomain('*');
		$policy->addAllowedMediaDomain('*');
		$policy->addAllowedMediaDomain('blob:');
		$policy->addAllowedMediaDomain('data:');
		$policy->addAllowedImageDomain('*');
		$policy->addAllowedWorkerSrcDomain('*');
		$policy->addAllowedFrameDomain('*');
		$policy->addAllowedFrameDomain('blob:');
		\OC::$server->get(IContentSecurityPolicyManager::class)
			->addDefaultPolicy($policy);
	}
}
