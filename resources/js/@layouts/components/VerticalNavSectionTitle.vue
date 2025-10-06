<script setup>
import { layoutConfig } from '@layouts'
import { can } from '@layouts/plugins/casl'
import { useLayoutConfigStore } from '@layouts/stores/config'
import { getDynamicI18nProps } from '@layouts/utils'

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
})

const configStore = useLayoutConfigStore()

// Check if nav is in mini mode
const shallRenderIcon = configStore.isVerticalNavMini()
</script>

<template>
  <li v-if="!item.action || can(item.action, item.subject)" class="nav-section-title">
    <div class="title-wrapper">
      <Transition name="vertical-nav-section-title" mode="out-in">
        <Component
          :is="shallRenderIcon ? layoutConfig.app.iconRenderer || 'div' : layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'"
          :key="shallRenderIcon" :class="shallRenderIcon ? 'placeholder-icon' : 'title-text'" v-bind="{
            ...(shallRenderIcon ? layoutConfig.icons.sectionTitlePlaceholder : {}),
            ...(!shallRenderIcon && layoutConfig.app.i18n.enable
              ? getDynamicI18nProps(item.title || item.heading, 'span')
              : {})
          }">
          {{ !shallRenderIcon ? (item.title || item.heading) : null }}
        </Component>
      </Transition>
    </div>
  </li>
</template>

<style scoped lang="scss">
.nav-section-title {
  color: rgba(var(--bs-body-color-rgb), 0.6);
  font-size: 0.625rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  margin-block-start: 1rem;
  padding-inline: 0 !important;
  text-transform: uppercase;

  // border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.title-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.placeholder-icon {
  display: inline-block;
  border-radius: 0.25rem;
  background-color: rgba(0, 0, 0, 15%);
  block-size: 1.25rem;
  inline-size: 1.25rem;
}

.title-text {
  overflow: hidden;
  text-overflow: ellipsis;
  transition: opacity 0.25s ease-in-out;
  white-space: nowrap;
}
</style>
