<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

namespace OC\Core\Controller;

use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Profile\IProfileManager;
use OCP\UserStatus\IManager as IUserStatusManager;

class ProfileController extends Controller {
	use \OC\Profile\TProfileHelper;

	/** @var IInitialState */
	private $initialStateService;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IProfileManager */
	private $profileManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var IUserStatusManager */
	private $userStatusManager;

	public function __construct(
		$appName,
		IRequest $request,
		IInitialState $initialStateService,
		IAccountManager $accountManager,
		IProfileManager $profileManager,
		IUserManager $userManager,
		IUserSession $userSession,
		IUserStatusManager $userStatusManager
	) {
		parent::__construct($appName, $request);
		$this->initialStateService = $initialStateService;
		$this->accountManager = $accountManager;
		$this->profileManager = $profileManager;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->userStatusManager = $userStatusManager;
	}

	/**
	 * @PublicPage
	 * @UseSession
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
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

		$visitingUser = $this->userSession->getUser();
		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);

		if (!$this->isProfileEnabled($account)) {
			return new TemplateResponse(
				'core',
				'404-page',
				[],
				TemplateResponse::RENDER_AS_GUEST,
			);
		}

		$status = array_shift($this->userStatusManager->getUserStatuses([$userId]));
		if (!empty($status)) {
			$this->initialStateService->provideInitialState('status', [
				'icon' => $status->getIcon(),
				'message' => $status->getMessage(),
			]);
		}

		$this->initialStateService->provideInitialState('profileParameters', $this->profileManager->getProfileParams($user, $visitingUser));

		\OCP\Util::addScript('core', 'dist/profile');

		return new TemplateResponse(
			'core',
			'profile',
			[],
			$this->userSession->isLoggedIn() ? TemplateResponse::RENDER_AS_USER : TemplateResponse::RENDER_AS_PUBLIC,
		);
	}
}
