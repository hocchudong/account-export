import Vue from 'vue';
import ExportExcelApp from './views/ExportExcelApp.vue';
import router from './router';
import store from './stores'

// Vue.use(router);
Vue.mixin({ methods: { t, n } });

// const View = Vue.extend(ExportExcelApp);
// new View().$mount('#accountexport');

new Vue({
	router,
	store,
	render: (h) => h(ExportExcelApp),
}).$mount('#accountexport');
