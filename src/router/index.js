import Vue from 'vue';
import VueRouter from 'vue-router';
import { generateUrl } from '@nextcloud/router';

import ExportExcelContent from '../views/ExportExcelContent.vue';

const routes = [
	{
		name: 'all',
		path: '/',
		component: ExportExcelContent,
		props: true,
		children: [
			{
				path: ':selectedGroup',
				name: 'group',
			},
		],
	},
];

Vue.use(VueRouter);

const router = new VueRouter({
	mode: 'hash',
	routes,
	base: generateUrl('/apps/accountexport/'),
	linkActiveClass: 'active',
});

export default router;
