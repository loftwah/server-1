<?php

/**
 * @copyright Copyright (c) 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OC\Core\Controller;

use TalkAction;
use EmailAction;
use PhoneAction;
use WesbsiteAction;
use TwitterAction;
use function Safe\substr;
use \OCP\AppFramework\Controller;
use OCA\Federation\TrustedServers;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Profile\IActionManager;
use OCP\UserStatus\IManager as IUserStatusManager;
use Psr\Container\ContainerInterface;

class ProfileController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var TrustedServers */
	private $trustedServers;

	/** @var IL10N */
	private $l10n;

	/** @var ContainerInterface */
	private $containerInterface;

	/** @var IUserSession */
	private $userSession;

	/** @var IUserManager */
	private $userManager;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IInitialState */
	private $initialStateService;

	/** @var IAppManager */
	private $appManager;

	/** @var IUserStatusManager */
	private $userStatusManager;

	/** @var IActionManager */
	private $actionManager;

	public function __construct(
		$appName,
		IRequest $request,
		IL10N $l10n,
		ContainerInterface $containerInterface,
		IURLGenerator $urlGenerator,
		TrustedServers $trustedServers,
		IUserSession $userSession,
		IUserManager $userManager,
		IAccountManager $accountManager,
		IInitialState $initialStateService,
		IAppManager $appManager,
		IUserStatusManager $userStatusManager,
		IActionManager $actionManager
	) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->containerInterface = $containerInterface;
		$this->urlGenerator = $urlGenerator;
		$this->trustedServers = $trustedServers;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->accountManager = $accountManager;
		$this->initialStateService = $initialStateService;
		$this->appManager = $appManager;
		$this->userStatusManager = $userStatusManager;
		$this->actionManager = $actionManager;
	}

	public const PROFILE_DISPLAY_PROPERTIES = [
		IAccountManager::PROPERTY_DISPLAYNAME,
		IAccountManager::PROPERTY_ADDRESS,
		IAccountManager::PROPERTY_COMPANY,
		IAccountManager::PROPERTY_JOB_TITLE,
		IAccountManager::PROPERTY_HEADLINE,
		IAccountManager::PROPERTY_BIOGRAPHY,
	];

	public const PROFILE_DISPLAY_PROPERTY_JSON_MAP = [
		IAccountManager::PROPERTY_DISPLAYNAME => 'displayName',
		IAccountManager::PROPERTY_ADDRESS => 'address',
		IAccountManager::PROPERTY_COMPANY => 'company',
		IAccountManager::PROPERTY_JOB_TITLE => 'jobTitle',
		IAccountManager::PROPERTY_HEADLINE => 'headline',
		IAccountManager::PROPERTY_BIOGRAPHY => 'biography',
	];

	public const PROFILE_ACTION_PROPERTIES = [
		IAccountManager::PROPERTY_EMAIL,
		IAccountManager::PROPERTY_PHONE,
		IAccountManager::PROPERTY_WEBSITE,
		IAccountManager::PROPERTY_TWITTER,
	];

	/**
	 * Useful annotations
	 * @NoAdminRequired
	 */

	/**
	 * FIXME Public page annotation blocks the user session somehow
	 * @PublicPage
	 * @UseSession
	 * @NoCSRFRequired
	 */
	public function index(string $userId): TemplateResponse {
		if (!$this->userManager->userExists($userId)) {
			return new TemplateResponse(
				'core',
				'404-page',
				[],
				TemplateResponse::RENDER_AS_GUEST,
			);
		}

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$profileEnabled = filter_var(
			$account->getProperty(IAccountManager::PROPERTY_PROFILE_ENABLED)->getValue(),
			FILTER_VALIDATE_BOOLEAN,
			FILTER_NULL_ON_FAILURE,
		);

		if (empty($profileEnabled)) {
			return new TemplateResponse(
				'core',
				'404-page',
				[],
				TemplateResponse::RENDER_AS_GUEST,
			);
		}

		$status = array_shift($this->userStatusManager->getUserStatuses([$userId]));
		if ($status) {
			$this->initialStateService->provideInitialState('status', [
				'icon' => $status->getIcon(),
				'message' => $status->getMessage(),
			]);
		}

		$this->initialStateService->provideInitialState('profileParameters', $this->getProfileParams($account));

		\OCP\Util::addScript('core', 'dist/profile');
		return new TemplateResponse(
			'core',
			'profile',
			[],
			$this->userSession->isLoggedIn() ? TemplateResponse::RENDER_AS_USER : TemplateResponse::RENDER_AS_PUBLIC
		);
	}

	/**
	 * returns the profile parameters in an
	 * associative array
	 */
	private function getProfileParams(IAccount $account): array {
		// TODO remove as profiles aren't visible by federated users, and display as if they were a guest
		$isLoggedIn = $this->userSession->isLoggedIn();
		$serverBaseUrl = $this->urlGenerator->getBaseUrl();
		$reqProtocol = $this->request->getServerProtocol();
		$reqHost = $this->request->getInsecureServerHost();
		$reqUri = $this->request->getRequestUri();
		$reqBaseUrl = substr("$reqProtocol://$reqHost$reqUri", 0, strlen($serverBaseUrl));
		$isSameServerInstance = $serverBaseUrl === $reqBaseUrl;

		$additionalEmails = array_map(
			function (IAccountProperty $property) {
				return $property->getValue();
			},
			$account->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)->getProperties()
		);

		$profileParameters = [
			'userId' => $account->getUser()->getUID(),
		];

		// for scope, if:
		// 1) Private   - visible to users on same server instance
		// 2) Local     - visible to users and public link visitors on same server instance
		// 3) Federated - visible to users and public link visitors on same server instance and trusted servers
		// 4) Published - same as Federated but also published to public lookup server
		foreach (self::PROFILE_DISPLAY_PROPERTIES as $property) {
			$scope = $account->getProperty($property)->getScope();
			switch ($scope) {
				case IAccountManager::SCOPE_PRIVATE:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] =
						($isLoggedIn && $isSameServerInstance) ? $account->getProperty($property)->getValue() : null;
					break;
				case IAccountManager::SCOPE_LOCAL:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] =
						$isSameServerInstance ? $account->getProperty($property)->getValue() : null;
					break;
				case IAccountManager::SCOPE_FEDERATED:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] =
						$this->trustedServers->isTrustedServer($serverBaseUrl) ? $account->getProperty($property)->getValue() : null;
					break;
				case IAccountManager::SCOPE_PUBLISHED:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] =
						$this->trustedServers->isTrustedServer($serverBaseUrl) ? $account->getProperty($property)->getValue() : null;
					break;
				default:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] = null;
					break;
			}
		}

		$avatarScope = $account->getProperty(IAccountManager::PROPERTY_AVATAR)->getScope();
		switch ($avatarScope) {
			case IAccountManager::SCOPE_PRIVATE:
				$profileParameters['isAvatarDisplayed'] = ($isLoggedIn && $isSameServerInstance);
				break;
			case IAccountManager::SCOPE_LOCAL:
				$profileParameters['isAvatarDisplayed'] = $isSameServerInstance;
				break;
			case IAccountManager::SCOPE_FEDERATED:
				$profileParameters['isAvatarDisplayed'] = $this->trustedServers->isTrustedServer($serverBaseUrl);
				break;
			case IAccountManager::SCOPE_PUBLISHED:
				$profileParameters['isAvatarDisplayed'] = $this->trustedServers->isTrustedServer($serverBaseUrl);
				break;
			default:
				$profileParameters['isAvatarDisplayed'] = false;
				break;
		}

		$actionParameters = $this->initActions($account);
		$profileParameters = [
			'userId' => $account->getUser()->getUID(),
			'displayName' => $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
			'address' => $account->getProperty(IAccountManager::PROPERTY_ADDRESS)->getValue(),
			'company' => $account->getProperty(IAccountManager::PROPERTY_COMPANY)->getValue(),
			'jobTitle' => $account->getProperty(IAccountManager::PROPERTY_JOB_TITLE)->getValue(),
			// Ordered by precedence, order is preserved in PHP and modern JavaScript
			'actionParameters' => $actionParameters,
		];

		return $profileParameters;
	}

	// TODO test out new implementation
	protected function initActions(IAccount $account) {
		$isLoggedIn = $this->userSession->isLoggedIn();
		$userId = $account->getUser()->getUID();
		$talkEnabled = $this->appManager->isEnabledForUser('spreed', $account->getUser());

		if ($talkEnabled) {
			$this->actionManager->registerAction($this->containerInterface->get(TalkAction::class), $userId);
		}

		foreach (self::PROFILE_ACTION_PROPERTIES as $property) {
			$scope = $account->getProperty($property)->getScope();
			$value = $account->getProperty($property)->getValue();

			if ($scope === IAccountManager::SCOPE_PRIVATE && !$isLoggedIn) {
				return;
			}
			// The other less strict scopes all allow public link access

			switch ($property) {
				case IAccountManager::PROPERTY_EMAIL:
					$this->actionManager->registerAction($this->containerInterface->get(EmailAction::class), $value);
					break;
				case IAccountManager::PROPERTY_PHONE:
					$this->actionManager->registerAction($this->containerInterface->get(PhoneAction::class), $value);
					break;
				case IAccountManager::PROPERTY_WEBSITE:
					$this->actionManager->registerAction($this->containerInterface->get(WebsiteAction::class), $value);
					break;
				case IAccountManager::PROPERTY_TWITTER:
					$this->actionManager->registerAction($this->containerInterface->get(TwitterAction::class), $value);
					break;
				default:
					break;
			}
		}

		return $this->actionManager->getActions();
	}
}
