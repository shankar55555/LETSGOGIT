// D:\projects\modular-crm\resources\js\plugins\1.router\guards.js
import { canNavigate } from '@layouts/plugins/casl'

export const setupGuards = router => {
  // Docs: https://router.vuejs.org/guide/advanced/navigation-guards.html#global-before-guards
  router.beforeEach(to => {
    if (to.meta.public)
      return
    const isLoggedIn = !!(useCookie('userData').value && useCookie('accessToken').value)
    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn)
        return '/dashboard/crm'
      else
        return undefined
    }
    if (!canNavigate(to) && to.matched.length) {
      /* eslint-disable indent */
      // return isLoggedIn
      //   ? { name: 'access-control' }
      //   : {
      //     name: 'login',
      //     query: {
      //       ...to.query,
      //       to: to.fullPath !== '/' ? to.path : undefined,
      //     },
      //   }
      /* eslint-enable indent */
    }
  })
}
