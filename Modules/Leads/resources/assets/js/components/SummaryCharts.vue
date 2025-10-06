<script setup>
const logisticData = ref([
    {
        icon: 'tabler-users-group',
        color: 'primary',
        title: 'Total Leads',
        value: 1412,
        change: 18.2,
        isHover: false,
    },
    {
        icon: 'tabler-flame',
        color: 'error',
        title: 'Hot Leads',
        value: 233,
        change: +8.7,
        isHover: false,
    },
    {
        icon: 'tabler-bell',
        color: 'warning',
        title: 'Follow-ups Today',
        value: 27,
        change: -5,
        isHover: false,
    },
    {
        icon: 'tabler-circle-check',
        color: 'success',
        title: 'Converted Leads',
        value: 13,
        change: 15,
        isHover: false,
    },
])
</script>

<template>
    <VRow>
        <VCol v-for="(data, index) in logisticData" :key="index" cols="12" md="3" sm="6">
            <div>
                <VCard class="logistics-card-statistics cursor-pointer"
                    :style="data.isHover ? `border-block-end-color: rgb(var(--v-theme-${data.color}))` : `border-block-end-color: rgba(var(--v-theme-${data.color}),0.38)`"
                    @mouseenter="data.isHover = true" @mouseleave="data.isHover = false">
                    <VCardText>
                        <div class="d-flex align-center gap-x-4 mb-1">
                            <VAvatar variant="tonal" :color="data.color" rounded>
                                <VIcon :icon="data.icon" size="28" />
                            </VAvatar>
                            <h4 class="text-h4">
                                {{ data.value }}
                            </h4>
                        </div>
                        <div class="text-body-1 mb-1">
                            {{ data.title }}
                        </div>
                        <div class="d-flex gap-x-2 align-center">
                            <h6 class="text-h6">
                                {{ (data.change > 0) ? '+' : '' }} {{ data.change }}%
                            </h6>
                            <div class="text-disabled">
                                than last week
                            </div>
                        </div>
                    </VCardText>
                </VCard>
            </div>
        </VCol>
    </VRow>
</template>

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
</style>
