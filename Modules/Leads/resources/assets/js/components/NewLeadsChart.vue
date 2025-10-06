<script setup>
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors

</script>

<template>
    <VCard class="px-5 py-2">
        <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-x-2">
                <h4 class="text-h4 text-center">
                    338
                </h4>
                <span class="text-sm text-success">
                   +12.2%
                </span>
            </div>
            <div>
                <VIcon icon="tabler-trending-up" color="success" size="x-large" />
            </div>
        </div>
        <p class="mb-1">New Leads</p>
    </VCard>
</template>
