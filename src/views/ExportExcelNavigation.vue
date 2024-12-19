<template>
	<NcAppNavigation aria-label="Account Export">
		<NcAppNavigationNew
			button-id="export-account"
			text="Download export accounts"
			@click="showModal"
		>
			<template #icon>
				<NcIconSvgWrapper :path="mdiDownload" />
			</template>
		</NcAppNavigationNew>
		<NcAppNavigationList
			class="account-management__system-list"
			data-cy-users-settings-navigation-groups="system"
		>
			<NcAppNavigationItem
				:exact="true"
				name="All accounts"
				:to="{ name: 'all' }"
			>
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccount" />
				</template>
				<template #counter>
					<NcCounterBubble>
						{{ $store.getters.getAllAccountCount }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				id="admins"
				v-if="$store.getters.getIsAdmin"
				:exact="true"
				name="Admins"
				:to="{ name: 'group', params: { selectedGroup: 'admin' } }"
			>
				<template #icon>
					<NcIconSvgWrapper :path="mdiShieldAccount" />
				</template>
				<template #counter>
					<NcCounterBubble>
						{{ getUserCountByGroup('admin') }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				id="recent"
				:exact="true"
				name="Recently active"
				:to="{
					name: 'group',
					params: { selectedGroup: '__nc_internal_recent' },
				}"
			>
				<template #icon>
					<NcIconSvgWrapper :path="mdiHistory" />
				</template>
				<template #counter>
					<NcCounterBubble>
						{{ getUserCountByGroup('__nc_internal_recent') }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				id="disabled"
				:exact="true"
				name="Disabled accounts"
				:to="{ name: 'group', params: { selectedGroup: 'disabled' } }"
			>
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountOff" />
				</template>
				<template #counter>
					<NcCounterBubble>
						{{ getUserCountByGroup('disabled') }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>
		</NcAppNavigationList>
		<NcAppNavigationCaption name="Groups" force-menu is-heading />
		<NcAppNavigationList
			class="account-management__group-list"
			data-cy-users-settings-navigation-groups="custom"
		>
			<NcAppNavigationItem
				v-for="group in $store.getters.getListGroup"
				:name="group.name"
				:key="group.id"
				:to="{
					name: 'group',
					params: { selectedGroup: encodeURIComponent(group.id) },
				}"
			>
				<template #icon>
					<AccountGroup :size="20" />
				</template>
				<template #counter>
					<NcCounterBubble> {{ group.usercount }} </NcCounterBubble>
				</template>
			</NcAppNavigationItem>
		</NcAppNavigationList>
		<NcModal
			v-if="modal"
			ref="modalRef"
			@close="closeModal"
			name="Select fields"
		>
			<div class="modal__content">
				<div class="grid">
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="displayName"
						name="displayField"
						v-model="displayColumn"
					>
						Display name
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="accountName"
						name="displayField"
						v-model="displayColumn"
					>
						Account name
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="password"
						name="displayField"
						v-model="displayColumn"
					>
						Password
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="email"
						name="displayField"
						v-model="displayColumn"
					>
						Email
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="groups"
						name="displayField"
						v-model="displayColumn"
					>
						Groups
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="groupAdminFor"
						name="displayField"
						v-model="displayColumn"
					>
						Group admin for
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="quota"
						name="displayField"
						v-model="displayColumn"
					>
						Quota
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="manager"
						name="displayField"
						v-model="displayColumn"
					>
						Manager
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="language"
						name="displayField"
						v-model="displayColumn"
					>
						Language
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="accountBackend"
						name="displayField"
						v-model="displayColumn"
					>
						Account backend
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						type="checkbox"
						value="lastLogin"
						name="displayField"
						v-model="displayColumn"
					>
						Last login
					</NcCheckboxRadioSwitch>
				</div>

				<NcButton @click="downloadExportAccount" type="primary">
					<template v-if="isDownloading" #icon>
						<NcLoadingIcon title="Loading accounts …" :size="32" />
					</template>
					Download
				</NcButton>
			</div>
		</NcModal>
	</NcAppNavigation>
</template>
<script>
import {
	mdiAccount,
	mdiAccountOff,
	mdiDownload,
	mdiHistory,
	mdiShieldAccount,
} from '@mdi/js';
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js';
import NcAppNavigationCaption from '@nextcloud/vue/dist/Components/NcAppNavigationCaption.js';
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js';
import NcAppNavigationList from '@nextcloud/vue/dist/Components/NcAppNavigationList.js';
import NcAppNavigationNew from '@nextcloud/vue/dist/Components/NcAppNavigationNew.js';
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js';
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js';
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js';
import { ref } from 'vue';
import AccountGroup from 'vue-material-design-icons/AccountGroup.vue';
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js';
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js';
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';
import { generateOcsUrl, generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';

const defaultDisplayFields = [
	'no',
	'displayName',
	'accountName',
	'password',
	'email',
	'groups',
	'groupAdminFor',
	'quota',
	'manager',
	'language',
	'accountBackend',
	'lastLogin',
];

export default {
	name: 'ExportExcelNavigation',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcIconSvgWrapper,
		NcCounterBubble,
		NcAppNavigationNew,
		NcAppNavigationCaption,
		AccountGroup,
		NcModal,
		NcCheckboxRadioSwitch,
		NcButton,
		NcLoadingIcon,
	},
	data() {
		return {
			modal: false,
			displayColumn: defaultDisplayFields,
			isDownloading: false,
		};
	},
	setup() {
		return {
			mdiAccount,
			mdiDownload,
			mdiShieldAccount,
			mdiHistory,
			mdiAccountOff,
			modalRef: ref(null),
		};
	},
	created() {},
	methods: {
		getUserCountByGroup(groupId) {
			const foundGroup = this.$store.getters.getSortedGroups.find(
				(group) => {
					return group.id === groupId;
				}
			);
			return foundGroup['usercount'];
		},
		showModal() {
			this.isDownloading = false;
			this.modal = true;
		},
		closeModal() {
			this.modal = false;
		},
		downloadExportAccount() {
			this.isDownloading = true;
			console.log(this.$route.params.selectedGroup);
			const selectedGroup = this.$route.params.selectedGroup || 'all';

			let url = generateOcsUrl(
				`apps/accountexport/group/${selectedGroup}/download-export`
			);

			if (selectedGroup === 'all') {
				url = generateOcsUrl(`apps/accountexport/all/download-export`);
			}

			if (selectedGroup === '__nc_internal_recent') {
				url = generateOcsUrl(
					`apps/accountexport/recenty/download-export`
				);
			}
			if (selectedGroup === 'disabled') {
				url = generateOcsUrl(
					`apps/accountexport/disabled/download-export`
				);
			}
			axios
				.get(url, {
					params: { displayFields: this.displayColumn.join(',') },
					responseType: 'blob',
				})
				.then((response) => {
					const href = URL.createObjectURL(response.data);

					const link = document.createElement('a');
					link.href = href;

					const fileName =
						response.headers['content-disposition'].split(
							'filename='
						)[1];
					link.setAttribute(
						'download',
						fileName.replace(/['"]/g, '')
					);
					document.body.appendChild(link);
					link.click();

					document.body.removeChild(link);
					URL.revokeObjectURL(href);
					this.isDownloading = false;
					this.modal = false;
					this.displayColumn = [...defaultDisplayFields];
				});
		},
	},
};
</script>
<style scoped>
.modal__content {
	margin: 50px;
}

.modal__content h2 {
	text-align: center;
}

.form-group {
	margin: calc(var(--default-grid-baseline) * 4) 0;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
}

.grid {
	display: grid;
	gap: 12px;
	grid-template-columns: 1fr 1fr 1fr 1fr;
	grid-template-rows: repeat(auto-fill, auto);
	position: relative;
	margin: 12px 0;
}
</style>
