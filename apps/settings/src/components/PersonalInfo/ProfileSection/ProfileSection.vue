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

		<ProfileCheckbox
			:profile-enabled.sync="profileEnabled" />

		<ProfilePreviewCard
			:company="company"
			:display-name="displayName"
			:profile-enabled="profileEnabled"
			:user-id="userId" />
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import HeaderBar from '../shared/HeaderBar'
import ProfileCheckbox from './ProfileCheckbox'
import ProfilePreviewCard from './ProfilePreviewCard'

import { ACCOUNT_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'
import { validateEnableProfile } from '../../../utils/validate'

const {
	companyMap: { primaryCompany: { value: company } },
	displayNameMap: { primaryDisplayName: { value: displayName } },
	profileEnabled,
	userId,
} = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'ProfileSection',

	components: {
		HeaderBar,
		ProfileCheckbox,
		ProfilePreviewCard,
	},

	data() {
		return {
			accountProperty: ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED,
			company,
			displayName,
			profileEnabled,
			userId,
		}
	},

	computed: {
		isValidSection() {
			return validateEnableProfile(this.profileEnabled)
		},
	},

	mounted() {
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
		subscribe('settings:company:updated', this.handleCompanyUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
		unsubscribe('settings:company:updated', this.handleCompanyUpdate)
	},

	methods: {
		handleDisplayNameUpdate(displayName) {
			this.displayName = displayName
		},

		handleCompanyUpdate(company) {
			this.company = company
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	&::v-deep button:disabled {
		cursor: default;
	}
}
</style>
