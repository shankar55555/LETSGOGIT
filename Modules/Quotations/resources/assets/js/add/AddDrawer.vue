<template>
  <VCard flat class="scrollable-content">
    <VCardText>
      <VForm ref="refForm" @submit.prevent="onSubmit">
        <VRow>
          <!-- Selecting Quotation Type -->
          <!-- <VCol cols="12" lg="2" md="2">
            <VLabel>Select Person Type <span style="color: red;">*</span></VLabel>
            <AppAutocomplete v-model="selectQuotationType" :items="filterQuotationUserTypeList" item-title="title"
              item-value="slug" :rules="[requiredValidator]" :readonly="type ? true : false" />
          </VCol> -->

          <!-- Selecting Client -->
          <VCol cols="12" lg="4" md="4" v-if="selectQuotationType == QUOTATION_CLIENT">
            <VLabel>Quotation To <span style="color: red;">*</span></VLabel>
            <AppSelect v-model="record.client_id" :items="clientList" item-value="id" :rules="[requiredValidator]"
              item-title="name" :readonly="type ? true : false" :error-messages="errors.client_id"
              @update:modelValue="() => clearFieldError('client_id')" />
          </VCol>

          <!-- Select Lead -->
          <VCol cols="12" md="6" v-if="selectQuotationType == QUOTATION_LEAD">
            <VLabel>Quotation To <span style="color: red;">*</span></VLabel>
            <AppSelect v-model="record.lead_id" :items="leadList" item-value="id" item-title="name"
              :readonly="type ? true : false" :error-messages="errors.lead_id" :rules="[requiredValidator]"
              @update:modelValue="() => clearFieldError('lead_id')" />
          </VCol>

          <VCol cols="12">
            <VLabel>Quotation Title <span style="color: red;">*</span></VLabel>
            <AppTextField v-model="record.title" placeholder="Write Quotation Title" class="mb-5"
              :rules="[requiredValidator]" style="flex: 1 1 15rem; min-inline-size: 15rem;"
              :error-messages="errors.title" @update:modelValue="() => clearFieldError('title')" />
          </VCol>
        </VRow>
        <!-- Selected Client or Lead Info -->
        <VRow v-if="(record.lead_id || record.client_id) && personInfo">
          <VCol class="py-0" cols="12" lg="4" md="4">
            <div>
              <p class="mb-0">
                <span style="font-weight: 700;">Name:</span>
                {{ personInfo.name }}
              </p>
              <p class="mb-0">
                <span style="font-weight: 700;">Phone:</span>
                {{ personInfo.phone }}
              </p>
              <p class="mb-0">
                <span style="font-weight: 700;">email: </span>
                {{ personInfo.email }}
              </p>
              <p class="mb-0">
                <span style="font-weight: 700;">Address:</span>
                {{ personInfo?.address }}
              </p>
            </div>
          </VCol>
        </VRow>

        <VRow>
          <!-- Quotation Type -->
          <VCol cols="12" md="6">
            <VLabel>Quotation Type <span style="color: red;">*</span></VLabel>
            <VAutocomplete v-model="record.quotation_type" :items="filteredQuotationTypes" :rules="[requiredValidator]"
              density="comfortable" placeholder="Select Quotation Type *" clearable
              @update:search="handleQuotationTypeSearchUpdate">
              <template #no-data>
                <div class="pa-4 text-center">
                  <VIcon icon="tabler-plus" class="mb-2" />
                  <div>
                    No matching quotation type found
                  </div>
                  <VBtn v-if="quotationTypeSearchText" variant="outlined" size="small" class="mt-2"
                    @click="addNewQuotationType">
                    Add "{{ quotationTypeSearchText }}"
                  </VBtn>
                </div>
              </template>
            </VAutocomplete>
          </VCol>

          <!-- Validity Date -->
          <VCol cols="12" md="6">
            <VLabel>Valid Until <span style="color: red;">*</span></VLabel>
            <AppDateTimePicker v-model="record.valid_uptil" :rules="[requiredValidator]" :config="datePickerOptions"
              :error-messages="errors.valid_uptil" @update:modelValue="() => clearFieldError('valid_uptil')" />
          </VCol>

          <!-- Select Contract Type -->
          <!-- <VCol cols="12">
            <VLabel>Custom Header Text</VLabel>
            <VTextarea v-model="record.custom_header_text" placeholder="Enter Header Text" rows="1" outlined dense
              auto-grow />
          </VCol> -->



          <!-- Item Details -->
          <VCol cols="12" v-if="record.items.length" class="d-flex justify-space-between align-center">
            <strong class="text-primary">Items</strong>
            <!-- <v-tooltip text="Add Item">
              <template v-slot:activator="{ props }">
                <VBtn variant="tonal" icon="tabler-plus" v-bind="props" @click="productDrawer = true" />
              </template>
</v-tooltip> -->
          </VCol>

          <VCol cols="12" v-for="(item, index) in record.items" :key="item.item_id">
            <VRow class="border rounded pa-3 mb-3">
              <VCol cols="12" md="12">
                <AppAutocomplete label="Product/Service" :items="attributeItems" item-title="name"
                  :loading="loadingAttributes" :searchable="true" @update:search="fetchAttributes" return-object
                  v-model="item.product" @update:modelValue="(val) => onProductSelected(val, item)"
                  placeholder="Search Product Service ">
                  <template #no-data>
                    <div class="d-flex align-center justify-space-between pa-3">
                      <div class="d-flex flex-column">
                        <span class="text-body-2 text-medium-emphasis">No products found</span>
                        <span class="text-caption text-disabled">Click "Add New" to create a new product</span>
                      </div>
                      <VBtn size="small" variant="tonal" color="primary" prepend-icon="tabler-plus"
                        @click="openProductDrawerWithSearch(item, $event)" class="ml-2 add-new-product-btn">
                        Add New
                      </VBtn>
                    </div>
                  </template>
                </AppAutocomplete>
              </VCol>
              <VCol cols="12" md="4">
                <VLabel>Name <span style="color: red;">*</span></VLabel>
                <AppTextField v-model="item.name" />
              </VCol>
              <VCol cols="12" md="4">
                <VLabel>Quantity <span style="color: red;">*</span></VLabel>
                <AppTextField v-model="item.quantity" type="number" min="1" />
              </VCol>
              <VCol cols="12" md="4">
                <VLabel>Unit Price <span style="color: red;">*</span></VLabel>
                <AppTextField v-model="item.unit_price" type="number" />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="item.tax_rate" label="GST Rate (%)" type="number" />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="item.discount_rate" label="Discount Rate (%)" type="number" />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="item.subtotal" label="Subtotal" type="number" readonly />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="item.total" label="Total" type="number" readonly />
              </VCol>
              <VCol cols="12" md="8">
                <AppTextField v-model="item.description" label="Description" />
              </VCol>
              <VCol cols="12">
                <VRow align="center" class="mb-2">
                  <VCol cols="6">
                    <strong class="text-primary">Attributes</strong>
                  </VCol>
                  <VCol cols="6" class="d-flex justify-end">
                    <VBtn size="small" variant="tonal" color="primary" prepend-icon="tabler-plus"
                      @click="addAttribute(index)">
                      Add Attribute
                    </VBtn>
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12" md="12" v-for="(attribute, i) in item.attributes" :key="i">
                <VRow>
                  <VCol cols="12" lg="6" md="6">
                    <AppTextField v-model="attribute.key" :label="`${i + 1}.Attribute Key`" />
                  </VCol>
                  <VCol cols="12" lg="6" md="6">
                    <AppTextField v-model="attribute.value" :label="`Attribute Value`">
                      <template #append>
                        <VBtn icon="tabler-trash" size="small" color="error" @click="removeAttribute(index, i)" />
                      </template>
                    </AppTextField>
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12" class="d-flex justify-end">
                <VBtn color="error" @click="removeItem(index)" variant="tonal" prepend-icon="tabler-trash">
                  Delete Item
                </VBtn>
              </VCol>
            </VRow>
          </VCol>

          <VCol cols="12" md="12">
            <VCard class="summary-card" variant="outlined">
              <VCardText class="pa-4">
                <div class="summary-item">
                  <span class="label">Subtotal:</span>
                  <span class="value">₹{{ Number(calculateSubtotal()).toFixed(2) }}</span>
                </div>
                <div class="summary-item">
                  <span class="label">Total Discount:</span>
                  <span class="value text-error">-₹{{
                    Number(calculateTotalDiscount()).toFixed(2)
                  }}</span>
                </div>
                <div class="summary-item">
                  <span class="label">Total GST:</span>
                  <span class="value">₹{{ Number(calculateTotalTax()).toFixed(2) }}</span>
                </div>
                <VDivider class="my-3" />
                <div class="summary-item total">
                  <span class="label">Total Amount:</span>
                  <span class="value">₹{{ Number(record.amount_due || 0).toFixed(2) }}</span>
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <VCol cols="12" class="amount-words">
            <strong>Amount in Words:</strong> {{ numberToWords(record.amount_due) }} Rupees Only
          </VCol>

          <VCol cols="12" class="d-flex justify-end">
            <VBtn color="primary" @click="addItem" prepend-icon="tabler-plus">
              Add Item
            </VBtn>
          </VCol>

          <!-- Payment term -->
          <VCol cols="12">
            <VLabel>Payment Terms</VLabel>
            <ProductDescriptionEditor class="border rounded" v-model="record.payment_terms"
              placeholder="Enter Payment Terms" rows="1" outlined dense auto-grow />
          </VCol>

          <!-- Terms & Conditions -->
          <VCol cols="12">
            <VLabel>Terms & Conditions</VLabel>
            <ProductDescriptionEditor class="border rounded" v-model="record.terms_conditions"
              placeholder="Enter Terms & Conditions" rows="1" outlined dense auto-grow />
          </VCol>

          <!-- Add and Discard Quotation -->
          <VCol cols="12" class="d-flex gap-4 justify-start pt-6 pb-10">
            <VBtn type="submit" color="primary" :loading="isLoading">
              Add
            </VBtn>
            <VBtn color="error" variant="tonal" @click="router.go(-1)">
              Cancel
            </VBtn>
            <VBtn type="button" variant="outlined" color="info" :loading="isLoading" @click="openPreview"
              prepend-icon="tabler-eye" size="default" class="text-capitalize">
              Preview
            </VBtn>
          </VCol>
        </VRow>
      </VForm>
    </VCardText>
  </VCard>
  <!-- Preview Dialog -->
  <VDialog v-model="dialog" max-width="1200">
    <VCard class="quotation-preview">
      <VCardTitle class="d-flex justify-space-between align-center pa-6 bg-primary text-white">
        <div class="d-flex align-center gap-3">
          <VIcon icon="tabler-file-invoice" size="24" />
          <h3 class="text-h4 font-weight-bold text-white">Quotation Preview</h3>
        </div>
        <VBtn icon="tabler-x" variant="text" color="white" @click="dialog = false" />
      </VCardTitle>

      <VCardText class="pa-0">
        <!-- Document Container -->
        <div class="quotation-document pa-8">
          <!-- Header Section -->
          <div class="document-header mb-8">
            <h1 class="font-weight-bold text-primary mb-4 text-h4">DETAILS</h1>
            <VRow>
              <VCol cols="12" md="4">
                <div class="quotation-info">
                  <div class="info-grid">
                    <div class="info-item">
                      <span class="label">Quotation #:</span>
                      <span class="value">QT-{{ new Date().getTime() }}</span>
                    </div>
                    <div class="info-item">
                      <span class="label">Select Person Type:</span>
                      <span class="value">{{
                        selectQuotationType === QUOTATION_CLIENT
                          ? "Client"
                          : "Lead"
                      }}</span>
                    </div>
                    <!-- <div class="info-item">
                      <span class="label">Quotation To:</span>
                      <span class="value">{{s}}</span>
                    </div> -->
                    <div class="info-item">
                      <span class="label">Quotation Title:</span>
                      <span class="value">{{ record.title }}</span>
                    </div>
                    <!-- <div class="info-item">
                      <span class="label">Custom Header Text:</span>
                      <span class="value">{{ record.custom_header_text }}</span>
                    </div> -->
                  </div>
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <div class="quotation-info">
                  <div class="info-grid">
                    <div class="info-item">
                      <span class="label">Date:</span>
                      <span class="value">{{
                        moment().format("DD/MM/YYYY")
                      }}</span>
                    </div>
                    <div class="info-item">
                      <span class="label">Valid Until:</span>
                      <span class="value">{{
                        record.valid_uptil
                          ? moment(record.valid_uptil).format("DD/MM/YYYY")
                          : "NotSet"
                      }}</span>
                    </div>
                  </div>
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <div class="quotation-info">
                  <div class="info-grid">
                    <div class="info-item">
                      <span class="label">Quotation Type:</span>
                      <span class="value">{{ record.quotation_type }}</span>
                    </div>
                    <div class="info-item">
                      <span class="label">Status:</span>
                      <VChip :color="record.items.length > 0 ? 'success' : 'warning'" size="small"
                        class="text-capitalize">
                        {{
                          record.items.length > 0
                            ? "Quatation Created"
                            : "Draft"
                        }}
                      </VChip>
                    </div>
                  </div>
                </div>
              </VCol>
            </VRow>
          </div>

          <!-- Client/Lead Information -->
          <div class="client-section mb-8" v-if="personInfo">
            <VCard variant="outlined" class="client-card">
              <VCardTitle class="text-h5 pa-4 pb-2 bg-grey-lighten-4">
                <VIcon icon="tabler-user" class="me-2" />
                {{
                  selectQuotationType === QUOTATION_CLIENT
                    ? "Client Information"
                    : "Lead Information"
                }}
              </VCardTitle>
              <VCardText class="pa-4">
                <VRow>
                  <VCol cols="12" md="6">
                    <div class="info-row">
                      <span class="label">Name:</span>
                      <span class="value">{{ personInfo.name }}</span>
                    </div>
                    <div class="info-row">
                      <span class="label">Phone:</span>
                      <span class="value">{{ personInfo.phone || "N/A" }}</span>
                    </div>
                  </VCol>
                  <VCol cols="12" md="6">
                    <div class="info-row">
                      <span class="label">Email:</span>
                      <span class="value">{{ personInfo.email || "N/A" }}</span>
                    </div>
                    <div class="info-row">
                      <span class="label">Address:</span>
                      <span class="value">{{
                        personInfo.address || "N/A"
                      }}</span>
                    </div>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </div>

          <!-- Items Table -->
          <div class="items-section mb-8" v-if="record.items.length">
            <VCard variant="outlined">
              <VCardTitle class="text-h5 pa-4 pb-2 bg-grey-lighten-4">
                Quotation Items
              </VCardTitle>
              <VCardText class="pa-0">
                <VTable class="items-table">
                  <thead>
                    <tr>
                      <th class="text-left">Item</th>
                      <th class="text-center">Qty</th>
                      <th class="text-right">Unit Price</th>
                      <th class="text-right">GST %</th>
                      <th class="text-right">Discount %</th>
                      <th class="text-right">Subtotal</th>
                      <th class="text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in record.items" :key="index" class="item-row">
                      <td>
                        <div class="item-details">
                          <div class="item-name font-weight-medium">
                            {{ item.name }}
                          </div>
                          <div class="item-description text-caption" v-if="item.description">
                            {{ item.description }}
                          </div>
                          <!-- Attributes -->
                          <div class="item-attributes" v-if="item.attributes.length">
                            <VChip v-for="(attr, i) in item.attributes" :key="i" size="x-small" color="primary"
                              variant="tonal" class="me-1 mb-1">
                              {{ attr.key }}: {{ attr.value }}
                            </VChip>
                          </div>
                        </div>
                      </td>
                      <td class="text-center">{{ item.quantity }}</td>
                      <td class="text-right">
                        ₹{{ Number(item.unit_price || 0).toFixed(2) }}
                      </td>
                      <td class="text-right">{{ item.tax_rate || 0 }}%</td>
                      <td class="text-right">{{ item.discount_rate || 0 }}%</td>
                      <td class="text-right">
                        ₹{{ Number(item.subtotal || 0).toFixed(2) }}
                      </td>
                      <td class="text-right font-weight-bold">
                        ₹{{ Number(item.total || 0).toFixed(2) }}
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </VCardText>
            </VCard>
          </div>

          <!-- Summary Section -->
          <div class="summary-section">
            <VRow>
              <!-- Summary Card -->
              <VCol cols="12" md="12">
                <VCard class="summary-card" variant="outlined">
                  <VCardText class="pa-4">
                    <div class="summary-item">
                      <span class="label">Subtotal:</span>
                      <span class="value">₹{{ Number(calculateSubtotal()).toFixed(2) }}</span>
                    </div>
                    <div class="summary-item">
                      <span class="label">Total Discount:</span>
                      <span class="value text-error">-₹{{
                        Number(calculateTotalDiscount()).toFixed(2)
                      }}</span>
                    </div>
                    <div class="summary-item">
                      <span class="label">Total GST:</span>
                      <span class="value">₹{{ Number(calculateTotalTax()).toFixed(2) }}</span>
                    </div>
                    <VDivider class="my-3" />
                    <div class="summary-item total">
                      <span class="label">Total Amount:</span>
                      <span class="value">₹{{ Number(record.amount_due || 0).toFixed(2) }}</span>
                    </div>
                  </VCardText>
                </VCard>
              </VCol>

              <VCol cols="12" md="12">
                <!-- Payment Terms -->
                <VCard variant="outlined" class="mb-4" v-if="record.payment_terms">
                  <VCardTitle class="text-h6 bg-grey-lighten-4">
                    <VIcon icon="tabler-credit-card" class="me-2" />
                    Payment Terms
                  </VCardTitle>
                  <VCardText class="pb-1">
                    <p v-html="record.payment_terms" class="text-body-1 mb-0"></p>
                  </VCardText>
                </VCard>

                <!-- Terms & Conditions -->
                <VCard variant="outlined" v-if="record.terms_conditions">
                  <VCardTitle class="text-h6 bg-grey-lighten-4">
                    <VIcon icon="tabler-file-text" class="me-2" />
                    Terms & Conditions
                  </VCardTitle>
                  <VCardText class="pb-1">
                    <p v-html="record.terms_conditions" class="text-body-1 mb-0"></p>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </div>
        </div>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Product Drawer -->
  <VDialog v-model="productDrawer" max-width="1200" persistent class="vuexy-drawer">
    <VCard class="vuexy-card">
      <VCardTitle class="vuexy-drawer-header d-flex justify-space-between align-center">
        <div class="d-flex align-center gap-3">
          <div class="vuexy-icon-wrapper">
            <VIcon icon="tabler-plus" size="24" color="white" />
          </div>
          <div>
            <h3 class="vuexy-title mb-1">Add Product/Service</h3>
            <p class="vuexy-subtitle">
              Create a new product or service for your quotation
            </p>
          </div>
        </div>
        <VBtn icon="tabler-x" variant="text" color="white" @click="closeProductDrawer" class="vuexy-close-btn" />
      </VCardTitle>

      <VCardText class="vuexy-drawer-content">
        <VForm ref="refItemForm" v-model="valid" @submit.prevent="onItemSubmit">
          <VRow>
            <!-- Basic Information Section -->
            <VCol cols="12" variant="outlined">
              <div class="vuexy-section">
                <div class="vuexy-section-header">
                  <VIcon icon="tabler-info-circle" size="20" class="me-2" />
                  <h4 class="vuexy-section-title">Basic Information</h4>
                </div>

                <VRow class="mt-4">
                  <VCol cols="12" md="6">
                    <AppTextField v-model="itemRecord.name" label="Name*" :rules="[requiredValidator]"
                      placeholder="Enter product/service name" class="vuexy-input" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <AppTextField v-model="itemRecord.price" label="Price" type="number" min="0" step="0.01"
                      placeholder="Enter price" class="vuexy-input" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="vuexy-combobox-wrapper">
                      <label class="vuexy-label">Site Inspection Checklist*</label>
                      <VCombobox v-model="itemRecord.site_inspection_checklist" multiple :items="[]" chips
                        placeholder="Enter title and press enter" hint="Enter title and press enter"
                        :rules="[requiredValidator]" class="vuexy-combobox">
                        <template v-slot:chip="{ item, index }">
                          <VChip class="vuexy-chip" color="primary">
                            {{ item.raw }}
                            <VIcon @click="removeInspectionTag(index)" class="vuexy-chip-remove" size="16"
                              icon="tabler-circle-letter-x" />
                          </VChip>
                        </template>
                      </VCombobox>
                    </div>
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="vuexy-combobox-wrapper">
                      <label class="vuexy-label">Site Installation Checklist*</label>
                      <VCombobox v-model="itemRecord.site_installation_checklist" multiple :items="[]" chips
                        placeholder="Enter title and press enter" hint="Enter title and press enter"
                        :rules="[requiredValidator]" class="vuexy-combobox">
                        <template v-slot:chip="{ item, index }">
                          <VChip class="vuexy-chip" color="primary">
                            {{ item.raw }}
                            <VIcon @click="removeInstallationTag(index)" class="vuexy-chip-remove" size="16"
                              icon="tabler-circle-letter-x" />
                          </VChip>
                        </template>
                      </VCombobox>
                    </div>
                  </VCol>
                </VRow>
              </div>
            </VCol>

            <!-- Attributes Section -->
            <VCol cols="12">
              <div class="vuexy-section">
                <div class="vuexy-section-header">
                  <VIcon icon="tabler-tag" size="20" class="me-2" />
                  <h4 class="vuexy-section-title me-1">Attributes</h4>
                  <p class="vuexy-section-subtitle">
                    Add custom attributes to your product/service
                  </p>
                </div>

                <VCol cols="12" v-for="(attr, index) in itemRecord.attributes" :key="attr.id" class="mt-4">
                  <div class="vuexy-attribute-card">
                    <VRow>
                      <VCol cols="12" md="3">
                        <AppTextField v-model="attr.key" label="Key*" placeholder="Attribute name" :error-messages="attr.errorMessage ? [attr.errorMessage] : []
                          " @blur="validateAttribute(attr)" class="vuexy-input" />
                      </VCol>

                      <VCol cols="12" md="3">
                        <AppSelect v-model="attr.type" label="Type*" :items="attributeTypes" class="vuexy-input" />
                      </VCol>

                      <VCol cols="12" md="5">
                        <AppTextField v-model="attr.value" :label="`Value (${attr.type})`" :type="attr.type"
                          :placeholder="`Enter ${attr.type} value`" class="vuexy-input" />
                      </VCol>

                      <VCol cols="12" md="1" class="d-flex align-center justify-end">
                        <VBtn icon color="error" variant="text" @click="removeItemAttribute(index)"
                          class="vuexy-remove-btn">
                          <VIcon icon="tabler-trash" size="20" />
                        </VBtn>
                      </VCol>
                    </VRow>
                  </div>
                </VCol>

                <VCol cols="12" class="d-flex justify-end mt-4">
                  <VBtn color="primary" variant="tonal" @click="addItemAttribute" prepend-icon="tabler-plus"
                    class="vuexy-add-btn">
                    Add Attribute
                  </VBtn>
                </VCol>
              </div>
            </VCol>

            <!-- Action Buttons -->
            <VCol cols="12" class="vuexy-actions">
              <VBtn type="submit" color="primary" :loading="isItemLoading" class="vuexy-save-btn" size="large">
                <VIcon icon="tabler-device-floppy" class="me-2" />
                Save Product
              </VBtn>
              <VBtn color="error" variant="tonal" @click="closeProductDrawer" class="vuexy-cancel-btn" size="large">
                <VIcon icon="tabler-x" class="me-2" />
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { numberToWords } from "amount-to-words";
import moment from "moment";
import { v4 as uuidv4 } from "uuid";
import {
  computed,
  getCurrentInstance,
  nextTick,
  onMounted,
  reactive,
  ref,
  watch,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue3-toastify";

const instance = getCurrentInstance();
const $can = instance?.proxy?.$can;

const route = useRoute();
const today = new Date();

const dialog = ref(false);

const productDrawer = ref(false);

const openPreview = () => {
  dialog.value = true;
};

// Calculation functions for summary
const calculateSubtotal = () => {
  return record.value.items.reduce((sum, item) => {
    return (
      sum + parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)
    );
  }, 0);
};

const calculateTotalDiscount = () => {
  return record.value.items.reduce((sum, item) => {
    const subtotal =
      parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0);
    const discountRate = parseFloat(item.discount_rate || 0);
    return sum + (subtotal * discountRate) / 100;
  }, 0);
};

const calculateTotalTax = () => {
  return record.value.items.reduce((sum, item) => {
    const subtotal =
      parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0);
    const discountRate = parseFloat(item.discount_rate || 0);
    const discountAmount = (subtotal * discountRate) / 100;
    const taxableAmount = subtotal - discountAmount;
    const taxRate = parseFloat(item.tax_rate || 0);
    return sum + (taxableAmount * taxRate) / 100;
  }, 0);
};

const datePickerOptions = {
  minDate: today,
};

const personInfo = ref(null);
const selectQuotationType = ref(QUOTATION_CLIENT);
const quotationUserTypeList = ref([
  { title: "Lead", slug: QUOTATION_LEAD, action: "leads", subject: "view" },
  {
    title: "Client",
    slug: QUOTATION_CLIENT,
    action: "client",
    subject: "view",
  },
]);

const filterQuotationUserTypeList = computed(() =>
  quotationUserTypeList.value.filter(
    ({ action, subject, extraPermissions }) => {
      if (!action || !subject) return true;
      const permission = $can?.(action, subject);
      const extra = extraPermissions?.some((extra) =>
        $can?.(extra.action, extra.subject)
      );
      return permission || extra;
    }
  )
);

// Get type and id from route query
const type = ref(route.query.type || "");
const id = ref(route.query.id || null);

// const refForm = ref()
const errors = reactive({});
const refForm = ref(false);
const isSubmitting = ref(false);
const isLoading = ref(false);

const record = ref({
  valid_uptil: "",
  quotation_type: "",
  title: "",
  status: "",
  items: [],
  custom_header_text: "",
  payment_terms: "",
  terms_conditions: "",
  lead_id: "",
  client_id: "",
  contract_id: "",
  amount_due: 0,
});

const quotationTypeSearchText = ref('');
// Handle quotation type search input updates
const handleQuotationTypeSearchUpdate = (value) => {
  quotationTypeSearchText.value = value;
};

// Quotation type functionality
const uniqueQuotationTypes = ref(['Standard']);


// Add new quotation type to the list
const addNewQuotationType = () => {
  if (quotationTypeSearchText.value && !uniqueQuotationTypes.value.includes(quotationTypeSearchText.value)) {
    uniqueQuotationTypes.value.push(quotationTypeSearchText.value);
    record.value.quotation_type = quotationTypeSearchText.value;
    quotationTypeSearchText.value = '';

    // Show success message
    toast.success(`New quotation type "${record.value.quotation_type}" added successfully`);
  }
};

// Filter quotation types based on search text
const filteredQuotationTypes = computed(() => {
  if (!quotationTypeSearchText.value) return uniqueQuotationTypes.value;

  return uniqueQuotationTypes.value.filter(type =>
    type.toLowerCase().includes(quotationTypeSearchText.value.toLowerCase())
  );
});

// Fetch existing quotation types from the database
const fetchQuotationTypes = async () => {
  try {
    const response = await $api("/quotations");
    const quotations = response?.data || [];

    // Extract unique quotation types from existing quotations
    const types = [...new Set(quotations.map(quotation => quotation.quotation_type))];

    // Update the uniqueQuotationTypes array with existing types
    uniqueQuotationTypes.value = types.length > 0 ? types : ['Standard'];

  } catch (error) {
    console.error('Failed to fetch quotation types:', error);
    // Keep the default 'Standard' type if fetch fails
    uniqueQuotationTypes.value = ['Standard'];
  }
};

const newItem = () => ({
  item_id: uuidv4(),
  name: "",
  description: "",
  quantity: 1,
  unit_price: 0,
  tax_rate: 0,
  tax_amount: 0,
  discount_rate: 0,
  discount_amount: 0,
  subtotal: 0,
  total: 0,
  gst: 0,
  attributes: [],
});

const fetchPatmentTermDetails = async () => {
  try {
    const response = await $api('/settings');
    console.log('API Response:', response.data);

    if (response.data) {
      record.value.payment_terms = response.data.payment_term;
      record.value.terms_conditions = response.data.term_condition;
    }
  } catch (error) {
    console.error("API Error:", error);
    if (error.response) {
      console.error("Response Status:", error.response.status);
      console.error("Response Data:", error.response.data);
    }
  }

}

const addItem = () => record.value.items.push(newItem());
const removeItem = (index) => record.value.items.splice(index, 1);

// Validate items
const validateItems = () => {
  for (const item of record.value.items) {
    if (!item.name || item.quantity <= 0 || item.unit_price <= 0) {
      toast.error(
        "Each item must have Name, Quantity > 0, and Unit Price > 0."
      );
      return false;
    }
  }
  return true;
};

// Calculate item values
const calculateItemValues = (item) => {
  const quantity = parseFloat(item.quantity || 0);
  const unitPrice = parseFloat(item.unit_price || 0);
  const taxRate = parseFloat(item.tax_rate || 0);
  const discountRate = parseFloat(item.discount_rate || 0);

  // const subtotal = quantity * unitPrice
  // const discountAmount = (subtotal * discountRate) / 100
  // const taxAmount = ((subtotal - discountAmount) * taxRate) / 100
  // const total = subtotal - discountAmount + taxAmount

  const subtotal = quantity * unitPrice;
  const discountAmount = (subtotal * discountRate) / 100;
  const taxableAmount = subtotal - discountAmount;
  const taxAmount = (taxableAmount * taxRate) / 100;
  const total = taxableAmount + taxAmount;

  item.subtotal = parseFloat(subtotal.toFixed(2));
  item.tax_amount = parseFloat(taxAmount.toFixed(2));
  item.discount_amount = parseFloat(discountAmount.toFixed(2));
  item.total = parseFloat(total.toFixed(2));
  record.value.amount_due = parseFloat(total.toFixed(2));
};

// Watch for real-time item updates
watch(
  () => record.value.items,
  (items) => {
    items.forEach((item) => calculateItemValues(item));
  },
  { deep: true }
);

const loadingAttributes = ref(false);
const attributeItems = ref([]);
const currentSearchText = ref("");

const fetchAttributes = async (search = "") => {
  loadingAttributes.value = true;
  currentSearchText.value = search; // Store the search text
  try {
    const { data } = await $api("/product", { params: { search } });
    attributeItems.value = data;
  } catch (e) {
    console.error("Failed to load attributeItems", e);
  } finally {
    loadingAttributes.value = false;
  }
};

const onProductSelected = async (product, item) => {
  if (!product) return;
  item.name = product.name;
  item.unit_price = parseFloat(product.price);
  item.attributes = product.attributes.map((val) => ({
    key: val.key,
    value: val.value,
  }));
};

// Add or remove item attributes
const removeAttribute = (itemIndex, attributeIndex) =>
  record.value.items[itemIndex].attributes.splice(attributeIndex, 1);

const addAttribute = (itemIndex) =>
  record.value.items[itemIndex].attributes.push({ key: "", value: "" });

// Fetch lead and client lists
const leadList = ref([]);
const clientList = ref([]);

const fetchLeadList = async () => {
  try {
    const res = await $api("/option-lead-list");
    leadList.value = res.data;
  } catch (error) {
    console.error("Failed to fetch lead list", error);
  }
};

const fetchClientList = async () => {
  try {
    const res = await $api("/option-client-list");
    clientList.value = res.data;
  } catch (error) {
    console.error("Failed to fetch client list", error);
  }
};

// watch(selectQuotationType, (newVal) => {
//   if (newVal === QUOTATION_LEAD) {
//     fetchLeadList();
//   } else if (newVal === QUOTATION_CLIENT) {
//     fetchClientList();
//   }
// });

watch([record.value.client_id, record.value.lead_id], () => {
  const list =
    selectQuotationType.value === QUOTATION_CLIENT ? clientList : leadList;
  personInfo.value = list.value.find(
    (item) =>
      item.id ===
      (selectQuotationType.value === QUOTATION_CLIENT
        ? record.value.client_id
        : record.value.lead_id)
  );
});

onMounted(async () => {
  // if (type.value === QUOTATION_CLIENT) {
  //   selectQuotationType.value = QUOTATION_CLIENT;
  //   record.value.client_id = id.value;
  //   await fetchClientList();
  // } else if (type.value === QUOTATION_LEAD) {
  //   selectQuotationType.value = QUOTATION_LEAD;
  //   record.value.lead_id = id.value;
  //   await fetchLeadList();
  // }
  fetchClientList();
  fetchPatmentTermDetails();
  fetchQuotationTypes();
  addItem();
});

const resetFiledInfo = () => {
  personInfo.value = null;
  record.value = {
    valid_uptil: "",
    quotation_type: "",
    title: "",
    status: "",
    items: [],
    custom_header_text: "",
    payment_terms: "",
    terms_conditions: "",
    lead_id: "",
    client_id: "",
    contract_id: "",
    amount_due: 0,
  };
};

const formResetValidation = () => {
  resetFiledInfo();
  refForm.value?.reset();
  refForm.value?.resetValidation();
};

const clearFieldError = (field) => {
  if (errors[field]) {
    delete errors[field];
  }
};

const onSubmit = async () => {
  if (!selectQuotationType.value)
    return toast.error(
      "Please select whether the person is a Client or a Lead before proceeding."
    );

  if (isSubmitting.value) return;
  isSubmitting.value = true;

  const { valid: isValid } = await refForm.value.validate();
  if (!isValid || !validateItems()) {
    isSubmitting.value = false;
    return;
  }

  if (!record.value.client_id && !record.value.lead_id) {
    toast.warning("Please choose either a client or lead to proceed");
    isSubmitting.value = false;
    return;
  }

  record.value.status =
    record.value.items.length > 0 ? QUOTATION_CREATED : QUOTATION_DRAFT;
  record.value.valid_uptil = record.value.valid_uptil
    ? moment(record.value.valid_uptil).format("YYYY-MM-DD")
    : null;
  try {
    isLoading.value = true;
    const res = await $api("/quotations", {
      method: "POST",
      body: JSON.stringify(record.value),
    });

    toast.success(
      res?.data?.message || res?.message || "Quotation created successfully!"
    );
    formResetValidation();
    router.go(-1);
    // router.push({ name: 'quotation-list' })
  } catch (error) {
    const defaultMessage = "Request Failed!";
    const response =
      error?.response?.data || error?._data || error?.response || error || {};

    let errorMessage = "";

    if (typeof response.message === "string") {
      errorMessage = response.message;
    } else if (Array.isArray(response.errors)) {
      errorMessage = response.errors[0];
    } else if (typeof response.errors === "object") {
      const firstErrorField = Object.keys(response.errors)[0];
      errorMessage = response.errors[firstErrorField]?.[0] || defaultMessage;
    } else {
      errorMessage = defaultMessage;
    }

    toast.error(errorMessage);

    // Process validation errors
    const validationErrors = response.errors || {};
    errors.value = {};
    for (const field in validationErrors) {
      if (Object.prototype.hasOwnProperty.call(validationErrors, field)) {
        errors.value[field] = validationErrors[field][0] || "Invalid value";
      }
    }
  } finally {
    isSubmitting.value = false;
    isLoading.value = false;
  }
};

// script for product/service drawer

const router = useRouter();

const refItemForm = ref(null);
const valid = ref(true);
const isItemLoading = ref(false);
const isItemSubmitting = ref(false);

const itemRecord = ref({
  name: "",
  price: null,
  site_inspection_checklist: [
    "Verify site address and location details",
    "Check access points to the installation area",
    "Confirm availability of power/water (if needed)",
    "Inspect for any obstructions or hazards",
  ],
  site_installation_checklist: [
    "Unpack and inspect the product for damage",
    "Confirm required tools and materials are available",
    "Install product as per manufacturer's guidelines",
    "Secure all fixtures and fittings",
  ],
  attributes: [],
});

// Attribute types for the dropdown
const attributeTypes = ref([
  { title: "Text", value: "text" },
  { title: "Number", value: "number" },
  { title: "Date", value: "date" },
]);

// Generate a new empty attribute
const newAttribute = () => ({
  id: uuidv4(),
  key: "",
  type: "text",
  value: "",
});

const addItemAttribute = () => {
  const attr = newAttribute();
  itemRecord.value.attributes.push(attr);

  // Validate after DOM update
  nextTick(() => {
    validateAttribute(attr);
  });
};

const removeItemAttribute = async (index) => {
  itemRecord.value.attributes.splice(index, 1);
  await nextTick();
  refItemForm.value?.resetValidation();
};

// Validate single attribute
const validateAttribute = (attr) => {
  attr.isValid = true;
  attr.errorMessage = "";

  // Required check
  if (!attr.key?.trim()) {
    attr.isValid = false;
    attr.errorMessage = "Attribute key is required";
    return false;
  }

  // Uniqueness check
  const duplicateCount = itemRecord.value.attributes.filter(
    (a) => a.id !== attr.id && a.key === attr.key
  ).length;

  if (duplicateCount > 0) {
    attr.isValid = false;
    attr.errorMessage = "Attribute key must be unique";
    return false;
  }

  // Type-specific validation
  if (attr.type === "number" && attr.value && isNaN(attr.value)) {
    attr.isValid = false;
    attr.errorMessage = "Must be a valid number";
    return false;
  }

  return true;
};

// Validate all attributes
const validateAllAttributes = () => {
  let allValid = true;

  itemRecord.value.attributes.forEach((attr) => {
    if (!validateAttribute(attr)) {
      toast.error(
        `Attribute "${attr.key || "Untitled"}": ${attr.errorMessage}`
      );
      allValid = false;
    }
  });

  return allValid;
};

const onItemSubmit = async () => {
  if (isItemSubmitting.value) return;
  isItemSubmitting.value = true;

  // Reset validation states
  itemRecord.value.attributes.forEach((attr) => {
    attr.isValid = null;
    attr.errorMessage = "";
  });

  // Validate Vuetify form
  const { valid: formValid } = await refItemForm.value.validate();

  // Validate attributes
  const attributesValid = validateAllAttributes();

  if (!formValid || !attributesValid) {
    isItemSubmitting.value = false;
    return;
  }

  try {
    isItemLoading.value = true;
    const payload = {
      ...itemRecord.value,
      attributes: itemRecord.value.attributes.map(
        ({ id, isValid, errorMessage, ...rest }) => rest
      ),
    };

    const res = await $api("/product", {
      method: "POST",
      body: JSON.stringify(payload),
    });

    if (res?.data) {
      toast.success(res?.data?.message || "Created successfully!");

      // If this was opened from the autocomplete, automatically select the new product
      if (currentItemForDrawer.value) {
        const newProduct = res.data;
        currentItemForDrawer.value.product = newProduct;
        onProductSelected(newProduct, currentItemForDrawer.value);
        currentItemForDrawer.value = null; // Reset the reference
      }

      // Close the drawer and refresh the product list
      closeProductDrawer();
      // Refresh the attributes list for quotation items
      await fetchAttributes();
      // Reset the form
      itemRecord.value = {
        name: "",
        price: null,
        site_inspection_checklist: [
          "Verify site address and location details",
          "Check access points to the installation area",
          "Confirm availability of power/water (if needed)",
          "Inspect for any obstructions or hazards",
        ],
        site_installation_checklist: [
          "Unpack and inspect the product for damage",
          "Confirm required tools and materials are available",
          "Install product as per manufacturer's guidelines",
          "Secure all fixtures and fittings",
        ],
        attributes: [],
      };
    }
  } catch (err) {
    console.error(err);
    toast.error(err?._data?.message || "An error occurred");
  } finally {
    isItemSubmitting.value = false;
    isItemLoading.value = false;
  }
};

function removeInspectionTag(index) {
  itemRecord.value.site_inspection_checklist.splice(index, 1);
}

function removeInstallationTag(index) {
  itemRecord.value.site_installation_checklist.splice(index, 1);
}

// Store the current item that triggered the drawer
const currentItemForDrawer = ref(null);

const openProductDrawerWithSearch = (item, event) => {
  // Stop event propagation to prevent autocomplete from closing
  event?.stopPropagation();

  // Store the current item for later use
  currentItemForDrawer.value = item;

  // Clear the product selection
  item.product = null;

  // Open the product drawer
  productDrawer.value = true;

  // Pre-fill the form with search text and reset other fields
  nextTick(() => {
    refItemForm.value?.reset();
    refItemForm.value?.resetValidation();

    // Pre-fill the name with the current search text
    itemRecord.value.name = currentSearchText.value || "";
    itemRecord.value.price = null;
    itemRecord.value.site_inspection_checklist = [
      "Verify site address and location details",
      "Check access points to the installation area",
      "Confirm availability of power/water (if needed)",
      "Inspect for any obstructions or hazards",
    ];
    itemRecord.value.site_installation_checklist = [
      "Unpack and inspect the product for damage",
      "Confirm required tools and materials are available",
      "Install product as per manufacturer's guidelines",
      "Secure all fixtures and fittings",
    ];
    itemRecord.value.attributes = [];
  });
};

const closeProductDrawer = () => {
  productDrawer.value = false;
  currentItemForDrawer.value = null; // Reset the reference
  currentSearchText.value = ""; // Reset search text
};
</script>


<style scoped>
.amount-words {
  border: 1px solid #dee2e6;
  border-radius: 4px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-inline-start: 4px solid orangered;
  color: #000;
  font-size: 8pt;
  font-weight: 500;
  margin-block: 3mm;
  margin-inline: 0;
  padding-block: 3mm;
  padding-inline: 4mm;
}

.chip_clear_icon {
  block-size: 13px !important;
}

.quotation-preview {
  max-block-size: 90vh;
  overflow-y: auto;
}

.quotation-document {
  background: white;
}

.document-header {
  border-block-end: 2px solid #e0e0e0;
  padding-block-end: 2rem;
}

.company-info h2 {
  color: #1976d2;
  margin-block-end: 1rem;
}

.quotation-info h1 {
  color: #1976d2;
}

.info-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item {
  display: flex;

  /* justify-content: space-around; */
  align-items: center;
  padding-block: 0.25rem;
  padding-inline: 0;
}

.info-item .label {
  color: #666;
  font-weight: 600;
  margin-inline-end: 10px;
}

.info-item .value {
  color: #333;
  font-weight: 500;
}

.client-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
}

.info-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-block-end: 1px solid #f5f5f5;
  padding-block: 0.5rem;
  padding-inline: 0;
}

.info-row .label {
  color: #666;
  font-weight: 600;
  min-inline-size: 100px;
}

.info-row .value {
  color: #333;
  font-weight: 500;
}

.header-card {
  border: 1px solid #e9ecef;
  background: #f8f9fa;
}

.items-table {
  border-collapse: collapse;
  inline-size: 100%;
}

.items-table th {
  background: #f8f9fa;
  border-block-end: 2px solid #dee2e6;
  color: #495057;
  font-weight: 600;
  padding-block: 1rem;
  padding-inline: 0.5rem;
}

.items-table td {
  border-block-end: 1px solid #f1f3f4;
  padding-block: 1rem;
  padding-inline: 0.5rem;
  vertical-align: top;
}

.item-row:hover {
  background: #f8f9fa;
}

.item-details {
  max-inline-size: 300px;
}

.item-name {
  color: #1976d2;
  margin-block-end: 0.25rem;
}

.item-description {
  color: #666;
  font-style: italic;
  margin-block-end: 0.5rem;
}

.item-attributes {
  margin-block-start: 0.5rem;
}

.summary-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-block-end: 1px solid #f1f3f4;
  padding-block: 0.5rem;
  padding-inline: 0;
}

.summary-item:last-child {
  border-block-end: none;
}

.summary-item .label {
  color: #666;
  font-weight: 500;
}

.summary-item .value {
  color: #333;
  font-weight: 600;
}

.summary-item.total {
  color: #1976d2;
  font-size: 1.1rem;
  font-weight: 700;
}

.summary-item.total .value {
  color: #1976d2;
  font-size: 1.2rem;
}

.document-footer {
  background: #f8f9fa;
  border-block-start: 2px solid #e0e0e0;
  margin-block-start: 2rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .quotation-document {
    padding: 1rem;
  }

  .info-grid {
    gap: 0.25rem;
  }

  .items-table {
    font-size: 0.875rem;
  }

  .items-table th,
  .items-table td {
    padding-block: 0.5rem;
    padding-inline: 0.25rem;
  }
}

/* Print styles */
@media print {
  .quotation-preview {
    overflow: visible;
    max-block-size: none;
  }

  .quotation-document {
    padding: 0;
    min-block-size: auto;
  }

  .document-footer {
    page-break-inside: avoid;
  }
}

/* Vuexy Drawer Styles */
.vuexy-drawer {
  backdrop-filter: blur(8px);
}

.vuexy-card {
  overflow: hidden;
  border: none;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 15%);
}

.vuexy-drawer-header {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgb(var(--v-theme-primary-darken-1)) 100%);
  color: white;
  padding-block: 24px;
  padding-inline: 32px;
}

.vuexy-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 30%);
  border-radius: 12px;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 20%);
  block-size: 48px;
  inline-size: 48px;
}

.vuexy-title {
  margin: 0;
  color: white;
  font-size: 1.5rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 10%);
}

.vuexy-subtitle {
  margin: 0;
  color: rgba(255, 255, 255, 80%);
  font-size: 0.875rem;
  font-weight: 400;
}

.vuexy-close-btn {
  border-radius: 8px;
  background: rgba(255, 255, 255, 10%);
  transition: all 0.3s ease;
}

.vuexy-close-btn:hover {
  background: rgba(255, 255, 255, 20%);
  transform: scale(1.1);
}

.vuexy-drawer-content {
  padding: 32px;
  background: #fafbfc;
  max-block-size: 70vh;
  overflow-y: auto;
}

.vuexy-section {
  padding: 24px;
  border: 1px solid #e8eaed;
  border-radius: 12px;
  background: white;
  margin-block-end: 24px;
}

.vuexy-section-header {
  display: flex;
  align-items: center;
  border-block-end: 2px solid #f0f2f5;
  margin-block-end: 20px;
  padding-block-end: 16px;
}

.vuexy-section-title {
  margin: 0;
  color: #2c3e50;
  font-size: 1.125rem;
  font-weight: 600;
}

.vuexy-section-subtitle {
  color: #6c757d;
  font-size: 0.875rem;
  font-weight: 400;
  margin-block: 4px 0;
  margin-inline: 0;
}

.vuexy-input {
  margin-block-end: 16px;
}

.vuexy-input .v-field {
  border-radius: 8px;
  transition: all 0.3s ease;
}

.vuexy-input .v-field:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
}

.vuexy-input .v-field--focused {
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 10%);
}

.vuexy-combobox-wrapper {
  margin-block-end: 16px;
}

.vuexy-label {
  display: block;
  color: #2c3e50;
  font-size: 0.875rem;
  font-weight: 500;
  margin-block-end: 8px;
}

/* .vuexy-combobox .v-field {
  border-radius: 8px;
  transition: all 0.3s ease;
} */

.vuexy-combobox .v-field:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
}

.vuexy-chip {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.vuexy-chip:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 15%);
  transform: translateY(-1px);
}

.vuexy-chip-remove {
  cursor: pointer;
  transition: all 0.2s ease;
}

.vuexy-chip-remove:hover {
  color: #dc3545;
  transform: scale(1.2);
}

.vuexy-attribute-card {
  position: relative;
  padding: 16px;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  background: #f8f9fa;
  transition: all 0.3s ease;
}

.vuexy-attribute-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 8%);
}

.vuexy-attribute-card::before {
  position: absolute;
  border-radius: 8px 8px 0 0;
  background: linear-gradient(90deg, #667eea, #764ba2);
  block-size: 3px;
  content: "";
  inset-block-start: 0;
  inset-inline: 0;
}

.vuexy-remove-btn {
  border-radius: 6px;
  background: rgba(220, 53, 69, 10%);
  transition: all 0.3s ease;
}

.vuexy-remove-btn:hover {
  background: rgba(220, 53, 69, 20%);
  transform: scale(1.1);
}

.vuexy-add-btn {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 20%);
  font-weight: 500;
  transition: all 0.3s ease;
}

.vuexy-add-btn:hover {
  box-shadow: 0 4px 16px rgba(102, 126, 234, 30%);
  transform: translateY(-2px);
}

.vuexy-actions {
  display: flex;
  justify-content: flex-end;
  border-block-start: 1px solid #e8eaed;
  gap: 16px;
  margin-block-start: 32px;
  padding-block-start: 32px;
}

.vuexy-save-btn {
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 30%);
  font-weight: 600;
  text-transform: none;
  transition: all 0.3s ease;
}

.vuexy-save-btn:hover {
  box-shadow: 0 6px 20px rgba(102, 126, 234, 40%);
  transform: translateY(-2px);
}

.vuexy-cancel-btn {
  border-radius: 8px;
  font-weight: 500;
  text-transform: none;
  transition: all 0.3s ease;
}

.vuexy-cancel-btn:hover {
  box-shadow: 0 4px 12px rgba(220, 53, 69, 20%);
  transform: translateY(-1px);
}

/* Responsive adjustments for Vuexy drawer */
@media (max-width: 768px) {
  .vuexy-drawer-header {
    padding-block: 20px;
    padding-inline: 24px;
  }

  .vuexy-drawer-content {
    padding: 24px;
  }

  .vuexy-section {
    padding: 20px;
  }

  .vuexy-actions {
    flex-direction: column;
  }

  .vuexy-actions .v-btn {
    inline-size: 100%;
  }
}

/* Custom scrollbar for drawer content */
.vuexy-drawer-content::-webkit-scrollbar {
  inline-size: 6px;
}

.vuexy-drawer-content::-webkit-scrollbar-track {
  border-radius: 3px;
  background: #f1f1f1;
}

.vuexy-drawer-content::-webkit-scrollbar-thumb {
  border-radius: 3px;
  background: #c1c1c1;
}

.vuexy-drawer-content::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Add New Product Button Styles */
.add-new-product-btn {
  box-shadow: 0 2px 8px rgba(102, 126, 234, 20%);
  transition: all 0.3s ease;
}

.add-new-product-btn:hover {
  background: rgb(var(--v-theme-primary)) !important;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 30%);
  color: white !important;
  transform: translateY(-1px);
}
</style>
