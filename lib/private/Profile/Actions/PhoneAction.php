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

use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Profile\IProfileAction;

class PhoneAction implements IProfileAction {

	/** @var string */
	private $value;

	/** @var IFactory */
	private $l10nFactory;

	/** @var IUrlGenerator */
	private $urlGenerator;

	/**
	 * Action constructor
	 *
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IFactory $l10nFactory,
		IURLGenerator $urlGenerator
	) {
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
	}

	public function getName(): string {
		return 'phone';
	}

	public function getTitle(): string {
		return $this->l10nFactory->get('core')->t('Call %s', [$this->value]);
	}

	public function getLabel(): string {
		return $this->l10nFactory->get('core')->t('Call phone number');
	}

	public function getPriority(): int {
		return 30;
	}

	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/phone.svg'));
	}

	public function getTarget(): string {
		return 'tel:' . $this->value;
	}

	public function setValue(string $value): string {
		return $this->value = $value;
	}
}
