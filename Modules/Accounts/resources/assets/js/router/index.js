import AllEntries from '../pages/AllEntries/index.vue'
import BalanceSheet from '../pages/balance-sheet/index.vue'
import GroupsAndLedgers from '../pages/groups-and-ledgers/index.vue'
import GstReport from '../pages/gst-reports/index.vue'
import GstSummary from '../pages/gst-summary/index.vue'
import Ledger from '../pages/ledgers/index.vue'
import ProfitAndLoss from '../pages/profit-and-loss/index.vue'

import PurchaseBillsCreate from '../pages/purchase-bills/create.vue'
import PurchaseBillsDetails from '../pages/purchase-bills/detail/[id].vue'
import PurchaseBillsEdit from '../pages/purchase-bills/edit/[id].vue'
import PurchaseBills from '../pages/purchase-bills/index.vue'


export default [
    // ============================== Purchase Bills route
    {
        path: '/accounts/purchase-bills',
        name: 'account-pages-PurchaseBills',
        component: PurchaseBills,
        meta: { title: 'Purchase Bills' },
    },
    {
        path: '/accounts/purchase-bills/create',
        name: 'account-pages-PurchaseBillsCreate',
        component: PurchaseBillsCreate,
        meta: { title: 'Create new Purchase Bill' },
    },
    {
        path: '/accounts/purchase-bills/edit/:id',
        name: 'purchase-bills-edit-id',
        component: PurchaseBillsEdit,
        meta: { title: 'Edit Purchase Bill' },
    },
    {
        path: '/accounts/purchase-bills/detail/:id',
        name: 'purchase-bills-detail-id',
        component: PurchaseBillsDetails,
        meta: { title: 'Purchase Bill Details' },
    },

    // ============================== Accounts route
    {
        path: '/accounts/all-entries',
        name: 'account-pages-AllEntries',
        component: AllEntries,
        meta: { title: 'Accounts' },
    },
    {
        path: '/accounts/balance-sheet',
        name: 'account-pages-BalanceSheet',
        component: BalanceSheet,
        meta: { title: 'Balance Sheet' },
    },
    {
        path: '/accounts/profit-and-loss',
        name: 'account-pages-ProfitAndLoss',
        component: ProfitAndLoss,
        meta: { title: 'Profit and Loss' },
    },
    {
        path: '/accounts/ledgers',
        name: 'account-pages-Ledgers',
        component: Ledger,
        meta: { title: 'Ledgers' },
    },
    {
        path: '/accounts/groups-and-ledgers',
        name: 'account-pages-GroupsAndLedgers',
        component: GroupsAndLedgers,
        meta: { title: 'Groups and Ledgers' },
    },
    {
        path: '/accounts/gst-report',
        name: 'account-pages-GstReport',
        component: GstReport,
        meta: { title: 'Transaction' },
    },
    {
        path: '/accounts/gst-summary',
        name: 'account-pages-GstSummary',
        component: GstSummary,
        meta: { title: 'Gst Summary' },
    },

    // {
    //     path: '/accounts/profit-and-loss',
    //     name: 'account-pages-ProfitAndLoss',
    //     component: ProfitAndLoss,
    //     meta: { title: 'Profit and Loss' },
    // },
    // {
    //     path: '/accounts/profit-and-loss',
    //     name: 'account-pages-ProfitAndLoss',
    //     component: ProfitAndLoss,
    //     meta: { title: 'Profit and Loss' },
    // },
    // {
    //     path: '/accounts/profit-and-loss',
    //     name: 'account-pages-ProfitAndLoss',
    //     component: ProfitAndLoss,
    //     meta: { title: 'Profit and Loss' },
    // },
    // {
    //     path: '/accounts/profit-and-loss',
    //     name: 'account-pages-ProfitAndLoss',
    //     component: ProfitAndLoss,
    //     meta: { title: 'Profit and Loss' },
    // },

]

