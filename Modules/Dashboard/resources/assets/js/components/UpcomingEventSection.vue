<template>
  <div class="mt-6">
    <div class="d-flex justify-space-between align-center mb-2">
      <div class="text-subtitle-1 font-weight-medium">
        {{ title }}
      </div>
      <div class="ml-2 mr-2" style="flex-grow: 1;"
        :style="`background-color: rgba(var(--v-theme-${themeColor}), 0.38); block-size: 1px;`"></div>
    </div>

    <VRow class="flex-nowrap overflow-auto" style="gap: 16px;" v-if="members.length">
      <VCol v-for="member in members" :key="member.id" cols="12" sm="4" md="3" lg="2"
        style="max-inline-size: 250px; min-inline-size: 220px;">
        <VCard class="logistics-card-statistics birthday-card cursor-pointer d-flex flex-column align-center pa-4"
          :style="`border-block-end-color: rgba(var(--v-theme-${themeColor}),0.38)`">

          <v-avatar size="72" class="mb-3 birthday-avatar">
            {{ avatarText(member.name) }}
          </v-avatar>

          <div class="text-h6 font-weight-bold mb-1 birthday-name">{{ member.name }}</div>
          <div class="text-body-2 mb-1 birthday-position">{{ position }}</div>

          <div class="d-flex align-center birthday-date">
            <span class="text-body-2 font-weight-medium" v-if="column == 'birthday'">{{
              formatDate(member.date_of_birth)
              }}</span>
            <span class="text-body-2 font-weight-medium" v-else>{{ formatDate(member.anniversary_date) }}</span>
            <span v-if="isTodayFn(member)" class="birthday-badge">🎉 Today!</span>
          </div>
        </VCard>
      </VCol>
    </VRow>
    <div class="flex-nowrap overflow-auto text-center py-4" style="gap: 16px; min-block-size: 30px;" v-else>
      <div class="d-flex flex-column align-center justify-center gap-2">
        <VAvatar icon="tabler-cancel" color="secondary" variant="tonal" size="default" rounded="3" />
        <h5 class="text-h5 text-secondary">No data found </h5>
      </div>
    </div>
  </div>
</template>

<script setup>

defineProps({
  title: String,
  members: Array,
  icon: String,
  themeColor: String,
  formatDate: Function,
  isTodayFn: Function,
  avatarText: Function,
  column: String,
  position: String,
  themeColor: String
})
</script>


<style lang="scss" scoped>
@use "@core-scss/base/mixins" as mixins;

.logistics-card-statistics {
  border-block-end-style: solid;
  border-block-end-width: 2px;

  &:hover {
    border-block-end-width: 3px;
    margin-block-end: -1px;

    @include mixins.elevation(8);

    transition: all 0.1s ease-out;
  }
}

.skin--bordered {
  .logistics-card-statistics {
    border-block-end-width: 2px;

    &:hover {
      border-block-end-width: 3px;
      margin-block-end: -2px;
      transition: all 0.1s ease-out;
    }
  }
}

.birthday-card {
  position: relative;
  border-radius: 12px;

  // Use the same base as logistics-card-statistics, but allow theme override
  background: var(--v-theme-surface);
  border-block-end: 2px solid rgba(var(--v-theme-primary), 0.38);
  transition: all 0.1s ease-out;

  &:hover {
    border-block-end-width: 3px;
    box-shadow: 0 8px 32px 0 rgba(var(--v-theme-primary), 0.18);
    margin-block-end: -1px;
    transition: all 0.1s ease-out;
  }
}

.birthday-avatar {
  border: 3px solid rgba(var(--v-theme-primary), 0.5);
  box-shadow: 0 2px 8px 0 rgba(var(--v-theme-primary), 0.18);
}

.birthday-name {
  color: rgb(var(--v-theme-primary));
  letter-spacing: 0.5px;
}

.birthday-position {
  color: rgb(var(--v-theme-primary));
}

.birthday-date {
  color: rgb(var(--v-theme-on-surface));
  margin-block-start: 6px;
}

.birthday-badge {
  border-radius: 12px;
  background: rgb(var(--v-theme-primary));
  box-shadow: 0 1px 4px 0 rgba(var(--v-theme-primary), 0.18);
  color: rgb(var(--v-theme-primary));
  font-size: 0.85em;
  font-weight: bold;
  margin-inline-start: 8px;
  padding-block: 2px;
  padding-inline: 8px;
}

.birthday-confetti {
  position: absolute;
  display: flex;
  align-items: center;
  inset-block-start: 10px;
  inset-inline-end: 10px;
}

@keyframes confetti-pop {
  0% {
    opacity: 0;
    transform: scale(0.7) rotate(-10deg);
  }

  60% {
    opacity: 1;
    transform: scale(1.2) rotate(10deg);
  }

  80% {
    transform: scale(0.95) rotate(-5deg);
  }

  100% {
    transform: scale(1) rotate(0deg);
  }
}

.birthday-confetti {
  animation: confetti-pop 0.8s cubic-bezier(0.23, 1.02, 0.67, 1.01);
}

@keyframes birthday-glow {

  0%,
  100% {
    box-shadow: 0 0 0 0 rgba(var(--v-theme-primary), 0.18);
  }

  50% {
    box-shadow: 0 0 16px 4px rgba(var(--v-theme-primary), 0.38);
  }
}

.birthday-card {
  animation: birthday-glow 2s infinite;
}

.confetti-container {
  position: absolute;
  z-index: 2;
  overflow: visible;
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
  pointer-events: none;
}

.confetti {
  position: absolute;
  border-radius: 50%;
  animation: confetti-fall 2.5s linear forwards;
  inset-block-start: -10px;
  opacity: 0.85;
  will-change: transform, opacity;
}

@keyframes confetti-fall {
  0% {
    opacity: 1;
    transform: translateY(0) rotate(0deg) scale(1);
  }

  80% {
    opacity: 1;
  }

  100% {
    opacity: 0;
    transform: translateY(120px) rotate(360deg) scale(0.8);
  }
}
</style>
