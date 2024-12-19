<template>
	<NcContent app-name="account-export">
		<ExportExcelNavigation />
		<router-view></router-view>
	</NcContent>
</template>

<script>
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js';

import ExportExcelNavigation from './ExportExcelNavigation.vue';

import { generateOcsUrl, generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';

export default {
	name: 'ExportExcelApp',
	components: {
		NcContent,
		ExportExcelNavigation,
	},

	created() {
		const url = generateOcsUrl('apps/accountexport/group/list');
		axios.get(url).then((response) => {
			const listGroup = response['data']['ocs']['data'].map((group) => {
				return { id: group['name'], label: group['name'] };
			});

			this.selectOrigin.options = [
				...this.selectOrigin.options,
				...listGroup,
			];
		});
	},
	beforeMount() {
	},
	data() {
		return {
			isDownloading: false,
			displayColumn: [
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
			],
			selectOrigin: {
				inputLabel: 'Origin',
				options: [
					{
						id: 'all',
						label: 'All',
					},
					{
						id: 'admin',
						label: 'Admins',
					},
					{
						id: 'recentlyActive',
						label: 'Recently Active',
					},
				],
				value: {
					id: 'all',
					label: 'All',
				},
			},
		};
	},
	methods: {
		downloadFileExport() {
			this.isDownloading = true;
			let url = generateOcsUrl(
				`apps/accountexport/group/${this.selectOrigin.value.id}/download-export`
			);

			if (this.selectOrigin.value.id === 'all') {
				url = generateOcsUrl(`apps/accountexport/all/download-export`);
			}

			if (this.selectOrigin.value.id === 'recentlyActive') {
				url = generateOcsUrl(
					`apps/accountexport/recenty/download-export`
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

					const now = new Date();
					const dateString =
						now.getUTCFullYear() +
						'/' +
						(now.getUTCMonth() + 1) +
						'/' +
						now.getUTCDate() +
						'_' +
						now.getUTCHours() +
						':' +
						now.getUTCMinutes() +
						':' +
						now.getUTCSeconds();

					const fileName =
						response.headers['content-disposition'].split(
							'filename='
						)[1];
					console.log(fileName[fileName.length]);
					console.log(fileName[fileName.length - 1]);
					link.setAttribute('download', fileName);
					document.body.appendChild(link);
					link.click();

					document.body.removeChild(link);
					URL.revokeObjectURL(href);
					this.isDownloading = false;
				});
		},
	},
};
</script>

<style scoped lang="scss">
#accountexport {
	display: flex;
	justify-content: center;
	margin: 16px;
}
</style>
