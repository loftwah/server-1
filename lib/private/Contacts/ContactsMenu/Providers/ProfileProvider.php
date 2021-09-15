<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OC\Contacts\ContactsMenu\Providers;

use OCP\Accounts\IAccountManager;
use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory as IL10NFactory;

class ProfileProvider implements IProvider {
	use \OC\Accounts\TAccountsHelper;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IActionFactory */
	private $actionFactory;

	/** @var IL10NFactory */
	private $l10nFactory;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserManager */
	private $userManager;

	/**
	 * @param IActionFactory $actionFactory
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IAccountManager $accountManager,
		IActionFactory $actionFactory,
		IL10NFactory $l10nFactory,
		IURLGenerator $urlGenerator,
		IUserManager $userManager
	) {
		$this->accountManager = $accountManager;
		$this->actionFactory = $actionFactory;
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
	}

	/**
	 * @param IEntry $entry
	 */
	public function process(IEntry $entry) {
		$userId = $entry->getProperty('UID');
		$user = $this->userManager->get($userId);
		if (!empty($user)) {
			$account = $this->accountManager->getAccount($user);
			if ($this->isProfileEnabled($account)) {
				$iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/profile.svg'));
				$profileActionText = $this->l10nFactory->get('core')->t('Open profile of') . ' ' . $entry->getProperty('FN');
				$profileUrl = $this->urlGenerator->linkToRouteAbsolute('core.profile.index', ['userId' => $entry->getProperty('UID')]);
				$action = $this->actionFactory->newLinkAction($iconUrl, $profileActionText, $profileUrl);
				// Set highest priority (by descending order), other actions have the default priority 10 as defined in lib/private/Contacts/ContactsMenu/Actions/LinkAction.php
				$action->setPriority(20);
				$entry->addAction($action);
			}
		}
	}
}
