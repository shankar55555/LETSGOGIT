import TargetDetail from '../details/[id].vue'
import TargetList from '../list/index.vue'
export default [
  {
    path: '/targets',
    name: 'target-list',
    component: TargetList,
    meta: { title: 'Targets' },
  },
  {
    path: '/targets/details/:id',
    name: 'target-details-id',
    component: TargetDetail,
    meta: { title: 'Target Detail' },
  },
]

