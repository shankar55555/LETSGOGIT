// resources/js/main.js
import App from '@/App.vue';
// import AccountingModule from '@abhishek_eligo/accounting_ecs';
import '@abhishek_eligo/accounting_ecs/dist/accounting_ecs.css';
import { registerPlugins } from '@core/utils/plugins';
import * as TablerIcons from '@tabler/icons-vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import { createApp } from 'vue';

import { resolveStatusVariant, typeAccordingDateFormatChange } from "@/utils/common";

// Styles
import '@core-scss/template/index.scss';
// import '@modules/Accounts/resources/assets/css/accounting_ecs.scss';
import '@modules/Accounts/resources/assets/css/accounting_styles.scss';
import '@styles/styles.scss';

// Toast 
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// Create vue app
const app = createApp(App);

// 🟢 Set global properties correctly for Vue 3
app.config.globalProperties.$typeAccordingDateFormatChange = typeAccordingDateFormatChange;
app.config.globalProperties.$resolveStatusVariant = resolveStatusVariant;

// Use Toastify
app.use(Vue3Toastify, {
    autoClose: 3000,
    position: "top-right",
});

// Register plugins
registerPlugins(app);

// Register the AccountingModule
// app.use(AccountingModule);
// Register all Tabler icons globally
for (const [key, component] of Object.entries(TablerIcons)) {
    app.component(key, component)
}

// Register the AccountingModule
// app.use(AccountingModule);

//element plus
app.use(ElementPlus)

// Mount the app
app.mount('#app');

// main.ts or main.js
app.config.errorHandler = (err, instance, info) => {
    // console.log('Global Error Handler:', err);
    // console.error('Global instance Handler:', JSON.stringify(instance));
    // console.error('Global info Handler:', info);
};

