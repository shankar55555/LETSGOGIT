import NotificationLog from '../notification-log/index.vue';
import NotificationUtility from '../notification-utility/index.vue';
import Reachout from '../reachout/list/index.vue';
import RuleList from '../rule/list/index.vue';
import whatsAppLog from '../whats-app/log/index.vue';
export default [
  {
    path: '/notification-logs',
    name: 'notification-logs',
    component: NotificationLog,
    meta: {
      title: 'Notification Log', permission: [{ action: "emailLog", subject: "view" },
      { action: "smsLog", subject: "view" },
      { action: "whatsAppLog", subject: "view" },
      { action: "bellNotificationLog", subject: "view" },
      { action: "appLog", subject: "view" },]
    },
  },
  {
    path: '/notification-utilities',
    name: 'notification-utilities',
    component: NotificationUtility,
    meta: {
      title: 'Notification Utilities', permission: [{ action: "email", subject: "view" },
      { action: "sms", subject: "view" },
      { action: "whatsApp", subject: "view" },
      { action: "bellNotification", subject: "view" },
      { action: "appUtility", subject: "view" },]
    },
  },
  {
    path: '/reachout-log',
    name: 'whatsapp-reachout-log',
    component: whatsAppLog,
    meta: {
      title: 'Reachout Log', permission: [
        { action: "whatsAppLog", subject: "view" },
      ]
    },
  },
  {
    path: '/new-reachout',
    name: 'whatsapp-new-reachout',
    component: Reachout,
    meta: {
      title: 'New Reachout', permission: [
        { action: "b2b", subject: "view" },
        { action: "client", subject: "view" },
        { action: "leads", subject: "view" },
      ]
    },
  },
  {
    path: '/rule-list',
    name: 'rule-list',
    component: RuleList,
    meta: { title: 'Rule List', permission: [{ action: 'rule', subject: 'view' }] },
  },
]
