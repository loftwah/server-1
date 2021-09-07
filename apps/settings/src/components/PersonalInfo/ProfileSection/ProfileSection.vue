<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<section>
		<HeaderBar
			:account-property="accountProperty"
			:is-valid-section="isValidSection" />

		<a
			:class="{ disabled: buttonDisabled }"
			:href="profilePageLink">
			{{ t('settings', 'View your profile') }}
		</a>

		<ProfileCheckbox
			:profile-enabled.sync="profileEnabled" />
	</section>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'

import HeaderBar from '../shared/HeaderBar'
import ProfileCheckbox from './ProfileCheckbox'

import { ACCOUNT_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'
import { validateEnableProfile } from '../../../utils/validate'

const { profileEnabled, userId } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'ProfileSection',

	components: {
		HeaderBar,
		ProfileCheckbox,
	},

	data() {
		return {
			accountProperty: ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED,
			profileEnabled,
			userId,
		}
	},

	computed: {
		buttonDisabled() {
			return !this.profileEnabled
		},

		isValidSection() {
			return validateEnableProfile(this.profileEnabled)
		},

		profilePageLink() {
			if (this.profileEnabled) {
				return generateUrl('u/{userId}', { userId: getCurrentUser().uid })
			}
			// Since an anchor element is used rather than a button for better UX,
			// this hack removes href if the profile is disabled so that disabling pointer-events is not needed to prevent a click from opening a page
			// and to allow the hover event for styling
			return null
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;
	display: flex;
	flex-direction: column;

	a {
		display: inline-block;
		margin: 5px auto 16px auto;
    padding: 10px 20px;
    background-color: #e6f3fa;
    color: var(--color-primary);
    border-radius: var(--border-radius-pill);
		font-weight: bold;

		&:hover {
			box-shadow: 0 2px 9px var(--color-box-shadow);
		}

		&.disabled {
			cursor: default;
			background-color: transparent;
			color: var(--color-text-maxcontrast);
			opacity: 0.5;

			&:hover {
				background-color: rgba(127, 127, 127, .15);
				box-shadow: none;
			}
		}
	}

	&::v-deep button:disabled {
		cursor: default;
	}
}
</style>
