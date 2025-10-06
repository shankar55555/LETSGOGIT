// Local implementation of the AccountingModule
// This file provides a default export to replace the missing default export from @abhishek_eligo/accounting_ecs

// Import any necessary components or utilities
import { createApp } from 'vue';

// Create the AccountingModule object
const AccountingModule = {
  // Install method that Vue plugins require
  install(app) {
    // Register any global components, directives, or provide any global properties
    console.log('AccountingModule installed successfully');
    
    // You can add more functionality here as needed
    // For example, registering components, directives, etc.
  }
};

// Export the module as default
export default AccountingModule;