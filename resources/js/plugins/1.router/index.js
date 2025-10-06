// // D:\projects\modular-crm\resources\js\plugins\1.router\index.js
// import { setupLayouts } from 'virtual:generated-layouts';
// import { createRouter, createWebHistory } from 'vue-router/auto';
// import { redirects, routes } from './additional-routes';
// import { setupGuards } from './guards';

// // Step 1: Dynamically import all module router files
// const moduleRoutes = import.meta.glob('@modules/*/resources/assets/js/router/index.{js,ts}', { eager: true })

// // Step 2: Merge all routes (dynamic + additionalRoutes)
// const mergedModuleRoutes = Object.values(moduleRoutes).flatMap(mod => {
//   const r = mod.default || []
//   return Array.isArray(r) ? r : [r]
// })

// const mergedRoutes = [...routes, ...mergedModuleRoutes]


// function recursiveLayouts(route) {
//   if (route.children) {
//     alert(JSON.stringify(route))
//     for (let i = 0; i < route.children.length; i++)
//       route.children[i] = recursiveLayouts(route.children[i])

//     return route
//   }

//   return setupLayouts([route])[0]
// }


// const router = createRouter({
//   history: createWebHistory(import.meta.env.BASE_URL),
//   scrollBehavior(to) {
//     if (to.hash)
//       return { el: to.hash, behavior: 'smooth', top: 60 }

//     return { top: 0 }
//   },

//   extendRoutes: pages => [
//     ...redirects,
//     ...[
//       ...pages,
//       ...mergedRoutes,
//     ].map(route => recursiveLayouts(route)),
//   ],
// })

// setupGuards(router)
// export { router };
// export default function (app) {
//   app.use(router)
// }

import crmMenu from '@/navigation/vertical/crm'
import { setupLayouts } from 'virtual:generated-layouts'
import { createRouter, createWebHistory } from 'vue-router/auto'
import { routes as additionalRoutes, redirects } from './additional-routes'
import { setupGuards } from './guards'

const allMenuItems = [...crmMenu]

const moduleRoutes = import.meta.glob('@modules/*/resources/assets/js/router/index.{js,ts}', { eager: true })

const mergedModuleRoutes = Object.values(moduleRoutes).flatMap(mod => mod.default || [])
const mergedRoutes = [...additionalRoutes, ...mergedModuleRoutes]

function recursiveLayouts(route) {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i])
    return route
  }
  return setupLayouts([route])[0]
}

function injectMetaPermissionssss(routes, menuItems) {
  const mapByName = menuItems.reduce((map, item) => {
    if (item.to) {
      map[item.to] = {
        permission: item.permission ?? null,
        extra_permission: item.extra_permission ?? [],
        show_permission: item.show_permission ?? [],
      }
    }
    return map
  }, {})

  const recurse = route => {
    if (route.name && mapByName[route.name]) {
      route.meta = {
        ...(route.meta || {}),
        ...mapByName[route.name],
      }
    }

    // children will be kept untouched regarding permissions
    if (route.children)
      route.children = route.children.map(child => recurse(child))

    return route
  }

  return routes.map(recurse)
}

function injectMetaPermissions(routes, menuItems) {
  const mapByName = menuItems.reduce((map, item) => {
    if (item.to) {
      map[item.to] = {
        permission: item.permission ?? null,
        extra_permission: item.extra_permission ?? [],
        show_permission: item.show_permission ?? [],
      }
    }
    return map
  }, {})

  const recurse = route => {
    const metaFromMenu = route.name && mapByName[route.name] ? mapByName[route.name] : {}

    // Find additional permission from OTHER_ROUTE_PERMISSION_LIST
    const extraFromOtherList = OTHER_ROUTE_PERMISSION_LIST.find(r => r.name === route.name)

    // Combine extra_permission from both sources (menu and OTHER_ROUTE_PERMISSION_LIST)
    const combinedExtraPermissions = [
      ...(metaFromMenu.extra_permission || []),
      ...(extraFromOtherList?.extra_permission || [])
    ]

    if (route.name && (metaFromMenu || extraFromOtherList)) {
      route.meta = {
        ...(route.meta || {}),
        ...metaFromMenu,
        extra_permission: combinedExtraPermissions
      }
    }

    if (route.children) {
      route.children = route.children.map(child => recurse(child))
    }

    return route
  }

  return routes.map(recurse)
}
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  extendRoutes: pages => {
    const routes = [
      ...pages,
      ...mergedRoutes,
    ].map(route => recursiveLayouts(route))

    const routesWithPermissions = injectMetaPermissions(routes, allMenuItems)

    return [...redirects, ...routesWithPermissions]
  },
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }
    return { top: 0 }
  },
})

setupGuards(router)
export { router }
export default app => app.use(router)
