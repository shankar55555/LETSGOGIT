export default [
    {
        path: '/lead-site-risk-management/:id',
        name: 'lead-site-risk-management',
        component: () => import('../pages/SiteRiskManagement.vue'),
        meta: {
            requiresAuth: true,
            pageTitle: 'Site Risk Management'
        }
    },
    {
        path: '/client-site-risk-management/:id',
        name: 'client-site-risk-management',
        component: () => import('../pages/SiteRiskManagement.vue'),
        meta: {
            requiresAuth: true,
            pageTitle: 'Site Risk Management'
        }
    },
]

