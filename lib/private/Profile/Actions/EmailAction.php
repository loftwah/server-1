<?php

/**
 * @copyright Copyright (c) 2021 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

namespace OC\Profile\Actions;

use OCP\Accounts\IAccountManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\IAction;

class EmailAction implements IAction {

	/** @var string */
	private $value;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IFactory */
	private $l10nFactory;

	/** @var IUrlGenerator */
	private $urlGenerator;

	public function __construct(
		IAccountManager $accountManager,
		IFactory $l10nFactory,
		IURLGenerator $urlGenerator
	) {
		$this->accountManager = $accountManager;
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
	}

	public function preload(IUser $user): void {
		$account = $this->accountManager->getAccount($user);
		$this->value = $account->getProperty(IAccountManager::PROPERTY_EMAIL)->getValue();
	}

	public function getAppId(): string {
		return 'core';
	}

	public function getId(): string {
		return IAccountManager::PROPERTY_EMAIL;
	}

	public function getTitle(): string {
		return $this->l10nFactory->get('core')->t('Mail %s', [$this->value]);
	}

	public function getLabel(): string {
		return $this->l10nFactory->get('core')->t('Mail');
	}

	public function getPriority(): int {
		return 20;
	}

	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/mail.svg'));
	}

	public function getTarget(): string {
		return 'mailto:' . $this->value;
	}
}
