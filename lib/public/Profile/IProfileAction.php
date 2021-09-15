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

namespace OCP\Profile;

/**
 * @since 23
 */
interface IProfileAction {
	/**
	 * returns the unique name of the action,
	 * e.g. 'email'
	 *
	 * @return string
	 * @since 23
	 */
	public function getName(): string;

	/**
	 * returns the translated title as it should be displayed,
	 * e.g. 'Mail john@domain.com'. Use the L10N service to translate it.
	 *
	 * @return string
	 * @since 23
	 */
	public function getTitle(): string;

	/**
	 * returns the translated label as it should be displayed,
	 * e.g. 'Mail'. Use the L10N service to translate it.
	 *
	 * @return string
	 * @since 23
	 */
	public function getLabel(): string;

	/**
	 * returns the priority as an integer between 0 and 99.
	 *
	 * The actions are arranged in ascending order.
	 *
	 * e.g. 60
	 *
	 * @return int
	 * @since 23
	 */
	public function getPriority(): int;

	/**
	 * returns the 16*16 SVG icon URL
	 *
	 * @returns string
	 * @since 23
	 */
	public function getIcon(): string;

	/**
	 * returns the target of the action
	 * e.g. 'mailto:john@domain.com'
	 *
	 * @returns string
	 * @since 23
	 */
	public function getTarget(): string;

	/**
	 * set the action value
	 * e.g. `john@domain.com` for email
	 * or the userId for talk.
	 *
	 * @return string
	 * @since 23
	 */
	public function setValue(string $value): string;
}
