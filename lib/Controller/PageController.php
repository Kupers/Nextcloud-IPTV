<?php

declare(strict_types=1);

namespace OCA\IPTV\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\INavigationManager;
use OCP\IRequest;

class PageController extends Controller {
	private INavigationManager $navigationManager;

	public function __construct(IRequest $request, INavigationManager $navigationManager) {
		parent::__construct('iptv', $request);
		$this->navigationManager = $navigationManager;
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		$this->navigationManager->setActiveEntry('iptv');
		$response = new TemplateResponse('iptv', 'index');
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedConnectDomain('*');
		$csp->addAllowedMediaDomain('*');
		$csp->addAllowedMediaDomain('blob:');
		$csp->addAllowedMediaDomain('data:');
		$csp->addAllowedImageDomain('*');
		$csp->addAllowedWorkerSrcDomain('*');
		$csp->addAllowedFrameDomain('*');
		$csp->addAllowedFrameDomain('blob:');
		$response->setContentSecurityPolicy($csp);
		return $response;
	}
}
