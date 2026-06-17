<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\IntegrationGrist\Search;

use OCA\IntegrationGrist\AppInfo\Application;
use OCA\IntegrationGrist\Service\GristAPIService;
use OCA\IntegrationGrist\Service\UtilsService;
use OCP\App\IAppManager;
use OCP\Config\IUserConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IExternalProvider;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class GristSearchDocumentsProvider implements IProvider, IExternalProvider {

	public function __construct(
		private IAppManager $appManager,
		private IL10N $l10n,
		private IUserConfig $userConfig,
		private IURLGenerator $urlGenerator,
		private GristAPIService $service,
		private UtilsService $utilsService,
		private string $userId,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'grist-search-documents';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Grist documents');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, Application::APP_ID . '.') === 0) {
		 	return -1;
		}

		return 20;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$orgs = $this->service->listOrgs($user->getUID());
		error_log(json_encode($orgs));
		$results = [];
		foreach ($orgs as $org) {
			$workspaces = $this->service->listWorkspacesPerOrg($user->getUID(), $org['id']);
			foreach ($workspaces as $workspace) {
				foreach ($workspace['docs'] as $doc) {
					error_log(json_encode($doc));
					if (str_contains(strtolower($doc['name']), strtolower($query->getTerm()))) {
						$results[] = new SearchResultEntry(
										'',
										$doc['name'],
										$org['name'] . ' -> ' . $workspace['name'],
										$this->getDocumentUrl($org['domain'], $doc['urlId']),
										'',
										true
									);
					}
				}
			}
		}
		return SearchResult::complete(
			$this->getName(),
			$results
		);
	}

	private function getDocumentUrl(string $domain, string $urlId): string {
		$baseUrl = $this->userConfig->getValueString($this->userId, Application::APP_ID, 'url');
		if ($baseUrl == 'https://docs.getgrist.com/') {
			$baseUrl = 'getgrist.com/';
		}
		if (!$this->userConfig->getValueString($this->userId, Application::APP_ID, 'use_subdomain', true)) {
			return $baseUrl . 'o/' . $domain . '/' . $urlId;
		}

		return 'https://' . $domain . '.' . $baseUrl . $urlId;

	}

	public function isExternalProvider(): bool {
		return true;
	}
}