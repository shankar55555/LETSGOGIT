import { useAbility } from '@casl/vue';


/**
 * Returns ability result if ACL is configured or else just return true
 * We should allow passing string | undefined to can because for admin ability we omit defining action & subject
 *
 * Useful if you don't know if ACL is configured or not
 * Used in @core files to handle absence of ACL without errors
 *
 * @param {string} action CASL Actions // https://casl.js.org/v4/en/guide/intro#basics
 * @param {string} subject CASL Subject // https://casl.js.org/v4/en/guide/intro#basics
 */
export const can = (action, subject) => {
  try {
    const ability = useAbility();
    // console.log('action 2 : ', action, " subject : ", subject, ' = ', ability.can(action, subject));
    return ability.can(action, subject);
  } catch (e) {
    const vm = getCurrentInstance();
    const localCan = vm?.proxy?.$can;
    return localCan ? vm.proxy.$can(action, subject) : true;
  }
};

/**
 * Check if user can view item based on it's ability
 * Based on item's action and subject & Hide group if all of it's children are hidden
 * @param {object} item navigation object item
 */
export const canViewNavMenuGroup = item => {
  const hasMainPermission = item.permission ? can(item.permission.action, item.permission.subject) : false;
  // console.log('hasMainPermission 1 ', 'action : ', item?.permission?.action, " subject : ", item?.permission?.subject);

  const hasExtraPermission = Array.isArray(item.extra_permission)
    ? item.extra_permission.some(perm => can(perm.action, perm.subject))
    : false;

  const hasAnyVisibleChild = Array.isArray(item.children)
    ? item.children.some(child => {
      const childHasPermission = child.permission
        ? can(child.permission.action, child.permission.subject)
        : false;

      const childHasExtra = Array.isArray(child.extra_permission)
        ? child.extra_permission.some(extra => can(extra.action, extra.subject))
        : false;

      return childHasPermission || childHasExtra;
    })
    : false;

  return hasMainPermission || hasExtraPermission || hasAnyVisibleChild;
};

export const canNavigate = to => {
  const ability = useAbility()
  // console.log('canNavigate to:', JSON.stringify(to.name))   

  for (const route of to.matched) {
    const meta = route.meta || {}

    if (meta.permission) {
      const { action, subject } = meta.permission
      // console.log('canNavigate permission 1 : ', action, " subject : ", subject, ' = ', ability.can(action, subject));
      if (ability.can(action, subject)) return true
    }


    if (Array.isArray(meta.extra_permission)) {
      // console.log('canNavigate to:', JSON.stringify(to.name), JSON.stringify(meta.extra_permission))
      const hasExtra = meta.extra_permission.some(p => {
        console.log('canNavigate permission 2 : ', p.action, " subject : ", p.subject, ' = ', ability.can(p.action, p.subject));
        return ability.can(p.action, p.subject)
      })
      if (hasExtra) return true
    }
  }

  // If no permission was granted, deny navigation
  console.log('Access Denied to:', to.name)
  return false
}
