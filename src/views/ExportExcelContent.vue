<template>
	<NcAppContent page-heading="Export Accounts">
		<Fragment>
			<VirtualList
				:data-component="UserRow"
				:data-sources="filteredUsers"
				data-key="id"
				data-cy-user-list
				:item-height="rowHeight"
				:style="style"
				:extra-props="{
					users,
					languages,
					groups,
					subAdminsGroups,
				}"
				@scroll-end="handleScrollEnd"
			>
				<template #before>
					<caption class="hidden-visually">
						{{
							'List of accounts. This list is not fully rendered for performance reasons. The accounts will be rendered as you navigate through the list.'
						}}
					</caption>
				</template>
				<template #header>
					<UserListHeader />
				</template>

				<template #footer>
					<UserListFooter :filtered-users="filteredUsers" />
				</template>
			</VirtualList>
		</Fragment>
	</NcAppContent>
</template>
<script>
import { mdiAccount, mdiDownload, mdiPlus, mdiAccountGroup } from '@mdi/js';
import { showError } from '@nextcloud/dialogs';
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js';
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js';
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js';
import NcAppNavigationList from '@nextcloud/vue/dist/Components/NcAppNavigationList.js';
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js';
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js';
import VirtualList from './VirtualList.vue';
import UserRow from './UserRow.vue';
import UserListHeader from './UserListHeader.vue';
import UserListFooter from './UserListFooter.vue';

import { Fragment } from 'vue-frag';
export default {
	name: 'ExportExcelContent',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcAppContent,
		Fragment,
		NcEmptyContent,
		NcLoadingIcon,
		NcIconSvgWrapper,
		VirtualList,
		UserListHeader,
		UserListFooter,
	},
	data() {
		return {
			loading: {
				users: false,
			},
		};
	},
	setup() {
		return {
			mdiAccount,
			mdiPlus,
			mdiDownload,
			mdiAccountGroup,
			UserRow,
			rowHeight: 55,
		};
	},
	async created() {
		await this.loadUsers();
	},
	computed: {
		usersOffset() {
			return this.$store.getters.getUsersOffset;
		},

		usersLimit() {
			return this.$store.getters.getUsersLimit;
		},
		disabledUsersOffset() {
			return this.$store.getters.getDisabledUsersOffset;
		},

		disabledUsersLimit() {
			return this.$store.getters.getDisabledUsersLimit;
		},
		filteredUsers() {
			if (this.selectedGroup === 'disabled') {
				return this.users.filter((user) => user.enabled === false);
			}
			return this.users.filter((user) => user.enabled !== false);
		},
		users() {
			return this.$store.getters.getUsers;
		},
		style() {
			return {
				'--row-height': `${this.rowHeight}px`,
			};
		},
		settings() {
			return this.$store.getters.getServerData;
		},
		selectedGroup() {
			return this.$route.params.selectedGroup;
		},
		loadingUsers() {
			return this.$store.getters.getLoadingUsers;
		},
		languages() {
			return [
				{
					label: 'Common languages',
					languages: this.settings.languages.commonLanguages,
				},
				{
					label: 'Other languages',
					languages: this.settings.languages.otherLanguages,
				},
			];
		},
		groups() {
			return this.$store.getters.getGroups
				.filter(
					(group) =>
						group.id !== '__nc_internal_recent' &&
						group.id !== 'disabled'
				)
				.sort((a, b) => a.name.localeCompare(b.name));
		},
		subAdminsGroups() {
			return this.$store.getters.getSubadminGroups;
		},
	},
	methods: {
		async loadUsers() {
			try {
				if (this.selectedGroup === 'disabled') {
					await this.$store.dispatch('getDisabledUsers', {
						offset: this.disabledUsersOffset,
						limit: this.disabledUsersLimit,
						search: '',
					});
				} else if (this.selectedGroup === '__nc_internal_recent') {
					await this.$store.dispatch('getRecentUsers', {
						offset: this.usersOffset,
						limit: this.usersLimit,
						search: '',
					});
				} else {
					await this.$store.dispatch('getUsers', {
						offset: this.usersOffset,
						limit: this.usersLimit,
						group: this.selectedGroup,
						search: '',
					});
				}
			} catch (error) {
				console.error('Failed to load accounts', { error });
				showError('Failed to load accounts');
			}
		},
		async handleScrollEnd() {
			await this.loadUsers();
		},
	},
	watch: {
		async selectedGroup(val) {
			// this.isInitialLoad = true
			// if selected is the disabled group but it's empty
			this.$store.commit('resetUsers');
			await this.loadUsers();
			// this.setNewUserDefaultGroup(val)
		},
	},
};
</script>
<style scoped>
.download-group {
	display: flex;
}
</style>
