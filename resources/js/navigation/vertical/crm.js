// D:\projects\modular-crm\resources\js\navigation\vertical\crm.js

export default [

  {
    title: 'Dashboard',
    icon: { icon: 'tabler-smart-home' },
    to: 'dashboard',
    permission: { action: 'dashboard', subject: 'view' },
  },

  // ========================================================= Customer Management
  {
    header: true,
    title: 'Customer Management',
  },
  // {
  //   title: 'Leads',
  //   icon: { icon: 'tabler-users' },
  //   to: 'lead-list',
  //   permission: { action: 'leads', subject: 'view' },
  //   otherRouteList: ['lead-details-id', 'lead-site-risk-management'],
  // },
  // {
  //   title: 'Table Component',
  //   icon: { icon: 'tabler-users' },
  //   to: 'leads-dummy',
  //   permission: { action: 'leads', subject: 'view' },
  // },
  {
    title: 'Clients',
    icon: { icon: 'tabler-user-star' },
    to: 'clients-list',
    permission: { action: 'client', subject: 'view' },
    otherRouteList: ['client-details-id', 'client-site-risk-management'],
  },

  // ========================================================= Sales & Invoicing
  {
    header: true,
    title: 'Sales & Invoicing',
  },
  {
    title: 'Quotations',
    icon: { icon: 'tabler-file-description' },
    to: 'quotation-list',
    permission: { action: 'quotation', subject: 'view' },
    otherRouteList: ['quotation-details-id', 'quotation-edit', 'quotation-create'],
  },
  {
    title: 'Invoices',
    icon: { icon: 'tabler-file-invoice' },
    to: 'invoice-list',
    permission: { action: 'invoice', subject: 'view' },
    otherRouteList: ['invoice-details-id', 'invoice-edit', 'invoice-create'],
  },

  // ========================================================= Inventory & Services 
  {
    header: true,
    title: 'Inventory & Stock ',
  },
  {
    title: 'Product',
    icon: { icon: 'tabler-brand-producthunt' },
    to: 'product-list',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
    // permission: { action: 'product', subject: 'view' },
    // otherRouteList: ['product-create', 'product-edit', 'product-details-id'],
  },
  {
    title: 'Vendors',
    icon: { icon: 'tabler-package' },
    to: 'vendor-list',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
    // permission: { action: 'product', subject: 'view' },
    // otherRouteList: ['product-create', 'product-edit', 'product-details-id'],
  },
  {
    title: 'Purchase Bills',
    icon: { icon: 'tabler-users' },
    to: 'account-pages-PurchaseBills',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },

  // ========================================================= Accounting
  {
    header: true,
    title: 'Accounting',
  },
  {
    title: 'Groups and Ledgers',
    icon: { icon: 'tabler-users' },
    to: 'account-pages-GroupsAndLedgers',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },
  // {
  //   title: 'Ledgers',
  //   icon: { icon: 'tabler-users' },
  //   to: 'account-pages-Ledgers',
  //   permission: { action: 'user', subject: 'view' },
  //   otherRouteList: ['user-view-id'],
  // },
  {
    title: 'All Entries',
    icon: { icon: 'tabler-logs' },
    to: 'account-pages-AllEntries',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },
  {
    title: 'Balance Sheet',
    icon: { icon: 'tabler-scale' },
    to: 'account-pages-BalanceSheet',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },
  {
    title: 'Profit and Loss',
    icon: { icon: 'tabler-cloud-dollar' },
    to: 'account-pages-ProfitAndLoss',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },
  {
    title: 'GST Report',
    icon: { icon: 'tabler-users' },
    to: 'account-pages-GstReport',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },
  {
    title: 'GST Summary',
    icon: { icon: 'tabler-users' },
    to: 'account-pages-GstSummary',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },
  // {
  //   title: 'Vendors',
  //   icon: { icon: 'tabler-users' },
  //   to: 'account-pages-Vendors',
  //   permission: { action: 'user', subject: 'view' },
  //   otherRouteList: ['user-view-id'],
  // },





  // ========================================================= System Config
  {
    header: true,
    title: 'System Config',
  },

  {
    title: 'User List',
    icon: { icon: 'tabler-users' },
    to: 'user-list',
    permission: { action: 'user', subject: 'view' },
    otherRouteList: ['user-view-id'],
  },
  {
    title: 'Role and Permission',
    icon: { icon: 'tabler-shield-lock' },
    to: 'role-list',
    permission: { action: 'role', subject: 'view' },
    otherRouteList: ['role-edit', 'role-create'],
  },

  {
    title: 'Alert & Notification',
    icon: { icon: 'tabler-bell' },
    extra_permission: [
      { action: 'emailLog', subject: 'view' },
      { action: 'email', subject: 'view' },
      { action: 'smsLog', subject: 'view' },
      { action: 'sms', subject: 'view' },
      { action: 'whatsAppLog', subject: 'view' },
      { action: 'whatsApp', subject: 'view' },
      { action: 'bellNotificationLog', subject: 'view' },
      { action: 'bellNotification', subject: 'view' },
      { action: 'appLog', subject: 'view' },
      { action: 'appUtility', subject: 'view' },
      { action: 'rule', subject: 'view' },
    ],
    children: [
      {
        title: 'Notification Log',
        to: 'notification-logs',
        extra_permission: [
          { action: 'emailLog', subject: 'view' },
          { action: 'smsLog', subject: 'view' },
          { action: 'whatsAppLog', subject: 'view' },
          { action: 'bellNotificationLog', subject: 'view' },
          { action: 'appLog', subject: 'view' },
        ],
      },
      {
        title: 'Notification Utilities',
        to: 'notification-utilities',
        extra_permission: [
          { action: 'email', subject: 'view' },
          { action: 'sms', subject: 'view' },
          { action: 'whatsApp', subject: 'view' },
          { action: 'bellNotification', subject: 'view' },
          { action: 'appUtility', subject: 'view' },
        ],
      },
      {
        title: 'Rules',
        to: 'rule-list',
        permission: { action: 'rule', subject: 'view' },
      },
    ],
  },
  {
    title: 'WhatsApp Campaign',
    icon: { icon: 'tabler-brand-whatsapp' },
    extra_permission: [
      { action: 'reachoutLog', subject: 'view' },
      { action: 'b2b', subject: 'view' },
      { action: 'client', subject: 'view' },
      { action: 'leads', subject: 'view' },
    ],
    children: [
      {
        title: 'Reachout Log',
        to: 'whatsapp-reachout-log',
        permission: { action: 'reachoutLog', subject: 'view' },
      },
      {
        title: 'New Reachout',
        to: 'whatsapp-new-reachout',
        extra_permission: [
          { action: 'client', subject: 'view' },
          { action: 'leads', subject: 'view' },
          { action: 'b2b', subject: 'view' },
        ],
      },
    ],
  },
  {
    title: 'Settings',
    icon: { icon: 'tabler-settings' },
    to: 'settings',
    extra_permission: [
      { action: 'generalSetting', subject: 'view' },
      { action: 'status', subject: 'view' },
    ],
  },



];


