<template>
	<tr class="user-list__row" :data-cy-user-row="user.id">
		<td class="row__cell row__cell--avatar" data-cy-user-list-cell-avatar>
			<NcAvatar disable-menu :show-user-status="false" :user="user.id" />
		</td>

		<td
			class="row__cell row__cell--displayname"
			data-cy-user-list-cell-displayname
		>
			<strong
				:title="user.displayname?.length > 20 ? user.displayname : null"
			>
				{{ user.displayname }}
			</strong>
		</td>

		<td
			class="row__cell row__cell--username"
			data-cy-user-list-cell-username
		>
			<span class="row__subtitle">{{ user.id }}</span>
		</td>

		<td
			data-cy-user-list-cell-password
			class="row__cell"
			:class="{ 'row__cell--obfuscated': true }"
		>
			<span> </span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-email>
			<span :title="user.email?.length > 20 ? user.email : null">
				{{ user.email }}
			</span>
		</td>

		<td
			class="row__cell row__cell--large row__cell--multiline"
			data-cy-user-list-cell-groups
		>
			<span
				:title="userGroupsLabels?.length > 40 ? userGroupsLabels : null"
			>
				{{ userGroupsLabels }}
			</span>
		</td>

		<td
			data-cy-user-list-cell-subadmins
			class="row__cell row__cell--large row__cell--multiline"
		>
			<span
				:title="
					userSubAdminsGroupsLabels?.length > 40
						? userSubAdminsGroupsLabels
						: null
				"
			>
				{{ userSubAdminsGroupsLabels }}
			</span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-quota>
			<template>
				<span :id="'quota-progress' + uniqueId"
					>{{ userQuota }} ({{ usedSpace }})</span
				>
				<NcProgressBar
					:aria-labelledby="'quota-progress' + uniqueId"
					class="row__progress"
					:class="{
						'row__progress--warn': usedQuota > 80,
					}"
					:value="usedQuota"
				/>
			</template>
		</td>

		<td class="row__cell row__cell--large" data-cy-user-list-cell-language>
			<span>
				{{ userLanguage.name }}
			</span>
		</td>

		<td
			data-cy-user-list-cell-storage-location
			class="row__cell row__cell--large"
		>
			<template>
				<span>{{ user.backend }}</span>
				<span :title="user.storageLocation" class="row__subtitle">
					{{ user.storageLocation }}
				</span>
			</template>
		</td>

		<td
			:title="userLastLoginTooltip"
			class="row__cell"
			data-cy-user-list-cell-last-login
		>
			<span>{{ userLastLogin }}</span>
		</td>
		<td
			class="row__cell row__cell--large row__cell--fill"
			data-cy-user-list-cell-manager
		>
			<span>
				{{ user.manager }}
			</span>
		</td>
	</tr>
</template>

<script>
import { formatFileSize, parseFileSize } from '@nextcloud/files';
import { getCurrentUser } from '@nextcloud/auth';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { confirmPassword } from '@nextcloud/password-confirmation';

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js';
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';
import NcProgressBar from '@nextcloud/vue/dist/Components/NcProgressBar.js';
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js';
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js';

import UserRowMixin from '../mixins/UserRowMixin';
import { isObfuscated, unlimitedQuota } from '../utils/userUtils';

export default {
	name: 'UserRow',

	components: {
		NcAvatar,
		NcLoadingIcon,
		NcProgressBar,
		NcSelect,
		NcTextField,
	},

	mixins: [UserRowMixin],

	props: {
		user: {
			type: Object,
			required: true,
		},
		groups: {
			type: Array,
			default: () => [],
		},
		visible: {
			type: Boolean,
			required: true,
		},
		users: {
			type: Array,
			required: true,
		},
		subAdminsGroups: {
			type: Array,
			default: () => [],
		},
		languages: {
			type: Array,
			required: true,
		},
	},

	data() {
		return {
			selectedQuota: false,
			rand: Math.random().toString(36).substring(2),
			loading: {
				all: false,
				displayName: false,
				password: false,
				mailAddress: false,
				groups: false,
				subadmins: false,
				quota: false,
				delete: false,
				disable: false,
				languages: false,
				wipe: false,
				manager: false,
			},
		};
	},

	computed: {
		uniqueId() {
			return encodeURIComponent(this.user.id + this.rand);
		},

		userGroupsLabels() {
			return this.userGroups.map((group) => group.name).join(', ');
		},

		userSubAdminsGroupsLabels() {
			return this.userSubAdminsGroups
				.map((group) => group.name)
				.join(', ');
		},

		usedSpace() {
			if (this.user.quota?.used) {
				return t('settings', '{size} used', {
					size: formatFileSize(this.user.quota?.used),
				});
			}
			return t('settings', '{size} used', { size: formatFileSize(0) });
		},

		userQuota() {
			let quota = this.user.quota?.quota;

			if (quota === 'default') {
				quota = this.settings.defaultQuota;
				if (quota !== 'none') {
					quota = parseFileSize(quota, true);
				}
			}

			if (quota === 'none' || quota === -3) {
				return t('settings', 'Unlimited');
			} else if (quota >= 0) {
				return formatFileSize(quota);
			}
			return formatFileSize(0);
		},
		usedQuota() {
			let quota = this.user.quota.quota;
			if (quota > 0) {
				quota = Math.min(
					100,
					Math.round((this.user.quota.used / quota) * 100)
				);
			} else {
				const usedInGB = this.user.quota.used / (10 * Math.pow(2, 30));
				// asymptotic curve approaching 50% at 10GB to visualize used stace with infinite quota
				quota = 95 * (1 - 1 / (usedInGB + 1));
			}
			return isNaN(quota) ? 0 : quota;
		},
	},

	async beforeMount() {},
};
</script>

<style lang="scss" scoped>
@import '../shared/styles.scss';

.user-list__row {
	@include row;

	&:hover {
		background-color: var(--color-background-hover);

		.row__cell:not(.row__cell--actions) {
			background-color: var(--color-background-hover);
		}
	}

	// Limit width of select in fill cell
	.select--fill {
		max-width: calc(var(--cell-width-large) - (2 * var(--cell-padding)));
	}
}

.row {
	@include cell;

	&__cell {
		border-bottom: 1px solid var(--color-border);

		:deep {
			.v-select.select {
				min-width: var(--cell-min-width);
			}
		}
	}

	&__progress {
		margin-top: 4px;

		&--warn {
			&::-moz-progress-bar {
				background: var(--color-warning) !important;
			}
			&::-webkit-progress-value {
				background: var(--color-warning) !important;
			}
		}
	}
}
</style>
