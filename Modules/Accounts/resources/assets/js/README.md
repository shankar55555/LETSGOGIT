# Accounting Components

This directory contains accounting components copied from @abhishek_eligo/accounting_ecs.

## Structure
- **pages/** - Page components that wrap the main accounting components
- **components/** - Source components from the package (for reference)

## Customization
You can modify any of these components to suit your application's needs:

1. **Edit page components** in the `pages/` directory to customize the wrapper logic
2. **Reference source components** in the `components/` directory to understand the original implementation
3. **Add your own logic** like v-model, custom props, events, etc.

## Example Customization
```vue
<template>
  <div class="custom-accounting-page">
    <AccountAllEntries 
      v-model="entriesData"
      :show-export="true"
      :custom-filters="filters"
      @entry-selected="handleEntrySelection"
    />
  </div>
</template>

<script>
export default {
  data() {
    return {
      entriesData: [],
      filters: { dateRange: null, category: 'all' }
    }
  },
  methods: {
    handleEntrySelection(entry) {
      console.log('Selected entry:', entry);
    }
  }
}
</script>
```

For more information, visit: https://github.com/abhishek-eligo/accounting_vue
