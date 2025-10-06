<template>
  <div class="gst-challan-container">
    <v-card class="pa-6">
      <v-card-title class="text-h5 mb-4">
        GST Challan Generation
      </v-card-title>

      <v-form ref="form" v-model="isFormValid" @submit.prevent="submitChallan">
        <!-- Basic Details Section -->
        <v-row>
          <v-col cols="12" md="6">
            <v-text-field v-model="challanData.gstin" label="GSTIN" :rules="[rules.required, rules.gstinFormat]"
              placeholder="Enter 15-digit GSTIN" outlined dense></v-text-field>
          </v-col>
          <v-col cols="12" md="6">
            <v-menu v-model="dateMenu" :close-on-content-click="false" transition="scale-transition" offset-y
              max-width="290px" min-width="290px">
              <template v-slot:activator="{ on, attrs }">
                <v-text-field v-model="challanData.challanDate" label="Challan Date" readonly outlined dense
                  v-bind="attrs" v-on="on"></v-text-field>
              </template>
              <v-date-picker v-model="challanData.challanDate" no-title @input="dateMenu = false"></v-date-picker>
            </v-menu>
          </v-col>
          <!-- <v-col cols="12" md="6">
            <v-select v-model="challanData.paymentType" :items="paymentTypes" item-title="text" item-value="value"
              label="Payment Type" :rules="[rules.required]" outlined dense></v-select>
          </v-col> -->
        </v-row>

        <!-- Tax Period Section -->
        <v-row>
          <v-col cols="12" md="6">
            <v-select v-model="challanData.financialYear" :items="financialYears" item-title="text" item-value="value"
              label="Financial Year" :rules="[rules.required]" outlined dense></v-select>
          </v-col>
          <v-col cols="12" md="6">
            <v-select v-model="challanData.taxPeriod" :items="taxPeriods" item-title="text" item-value="value"
              label="GST Period" :rules="[rules.required]" outlined dense></v-select>
          </v-col>
        </v-row>



        <!-- Additional Details -->
        <v-row class="mt-4">
          <!-- <v-col cols="12" md="6">
            <v-text-field
              :value="generatedChallanNumber || 'Will be generated on submit'"
              label="Quotation Number"
              readonly
              outlined
              dense
            ></v-text-field>
          </v-col> -->

        </v-row>

        <!-- Total Section -->
        <v-card class="mt-4 pa-4" outlined>
          <v-row>
            <v-col cols="12" md="6">
              <div class="text-h6"></div>
            </v-col>
            <v-col cols="12" md="6" class="d-flex justify-end">
              <v-btn color="error" class="mr-4" text @click="resetForm">
                Reset
              </v-btn>
              <v-btn color="primary" type="submit" :loading="loading" :disabled="!isFormValid">
                Checklist
              </v-btn>
            </v-col>
          </v-row>
        </v-card>
      </v-form>
    </v-card>

    <!-- Success Dialog -->
    <v-dialog v-model="successDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h5 green--text">
          Challan Generated Successfully
        </v-card-title>
        <v-card-text class="pt-4">
          <p>Your GST challan has been generated successfully.</p>
          <p class="mb-0"><strong>Challan Identification No. (CPIN):</strong> {{ generatedChallanNumber }}</p>
          <p class="mb-0"><strong>Total Amount:</strong> ₹{{ totalAmount }}</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" text @click="downloadChallan">
            Download PDF
          </v-btn>
          <v-btn color="green" text @click="successDialog = false">
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
export default {
  name: 'GstChallan',
  data() {
    return {
      isFormValid: false,
      loading: false,
      dateMenu: false,
      successDialog: false,
      generatedChallanNumber: '',
      challanData: {
        gstin: '22AAAAA0000A1Z5',
        paymentType: 'regular',
        financialYear: '',
        taxPeriod: '04',
        taxItems: [
          { type: '', value: 0 }
        ],
        challanDate: new Date().toISOString().substr(0, 10)
      },
      paymentTypes: [
        { text: 'Regular', value: 'regular' },
        { text: 'Composition', value: 'composition' },
        { text: 'Unregistered', value: 'unregistered' }
      ],
      financialYears: [],
      taxPeriods: [
        { text: 'April', value: '04' },
        { text: 'May', value: '05' },
        { text: 'June', value: '06' },
        { text: 'July', value: '07' },
        { text: 'August', value: '08' },
        { text: 'September', value: '09' },
        { text: 'October', value: '10' },
        { text: 'November', value: '11' },
        { text: 'December', value: '12' },
        { text: 'January', value: '01' },
        { text: 'February', value: '02' },
        { text: 'March', value: '03' }
      ],
      gstTypes: [
        { text: 'CGST', value: 'cgst' },
        { text: 'SGST', value: 'sgst' },
        { text: 'IGST', value: 'igst' },
        { text: 'CESS', value: 'cess' },
        { text: 'Interest', value: 'interest' },
        { text: 'Penalty', value: 'penalty' }
      ],
      rules: {
        required: v => !!v || 'This field is required',
        nonNegative: v => v >= 0 || 'Amount cannot be negative',
        gstinFormat: v => /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(v) || 'Invalid GSTIN format'
      }
    }
  },
  created() {
    // Generate financial years for the last 10 years
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = 0; i < 10; i++) {
      const startYear = currentYear - i;
      const endYear = startYear + 1;
      const financialYear = `${startYear}-${endYear.toString().slice(-2)}`;
      years.push({
        text: financialYear,
        value: financialYear
      });
    }
    this.financialYears = years;

    // Set default financial year to current year
    const defaultFinancialYear = `${currentYear}-${(currentYear + 1).toString().slice(-2)}`;
    this.challanData.financialYear = defaultFinancialYear;
  },
  computed: {
    totalAmount() {
      return this.challanData.taxItems.reduce((sum, item) => sum + Number(item.value || 0), 0).toFixed(2);
    },
    allGstTypesUsed() {
      // Returns true if all GST types are already used
      return this.challanData.taxItems.length >= this.gstTypes.length;
    }
  },
  methods: {
    resetForm() {
      this.$refs.form.reset()
      Object.keys(this.challanData).forEach(key => {
        if (typeof this.challanData[key] === 'number') {
          this.challanData[key] = 0
        } else if (key === 'challanDate') {
          this.challanData[key] = new Date().toISOString().substr(0, 10)
        } else {
          this.challanData[key] = ''
        }
      })
    },
    async submitChallan() {
      if (!this.$refs.form.validate()) return

      this.loading = true
      try {
        // Prepare challan data
        const challanPayload = {
          ...this.challanData,
          challanNumber: 'GST' + Date.now().toString().slice(-8),
          totalAmount: this.totalAmount
        };

        // Make API call to store challan
        const response = await fetch('/api/quotations/gst-challan', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify(challanPayload),
        });

        if (!response.ok) throw new Error('Failed to store challan');
        const data = await response.json();

        this.generatedChallanNumber = data.challan.challanNumber;
        this.$emit('challan-generated', {
          ...data.challan
        });
        this.successDialog = true;
      } catch (error) {
        this.$toast.error('Failed to generate challan');
        console.error('Error generating challan:', error);
      } finally {
        this.loading = false;
      }
    },
    async downloadChallan() {
      try {
        // Prepare challan data for backend
        const challanPayload = {
          ...this.challanData,
          challanNumber: this.generatedChallanNumber,
          totalAmount: this.totalAmount,
        };
        const response = await fetch('/api/quotations/gst-challan-pdf', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/pdf',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify(challanPayload),
        });
        if (!response.ok) throw new Error('Failed to generate PDF');
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `GST_Challan_${this.generatedChallanNumber}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        this.$toast.success('Challan PDF downloaded successfully');
      } catch (error) {
        this.$toast.error('Failed to download challan PDF');
        console.error('Error downloading challan:', error);
      }
    },
    addTaxItem() {
      if (!this.allGstTypesUsed) {
        this.challanData.taxItems.push({ type: '', value: 0 });
      }
    },
    removeTaxItem(idx) {
      if (this.challanData.taxItems.length > 1) {
        this.challanData.taxItems.splice(idx, 1);
      }
    },
    availableGstTypes(currentIdx, currentValue) {
      // Show all GST types not already selected in other rows, but always include the current value
      const selectedTypes = this.challanData.taxItems
        .map((item, idx) => idx !== currentIdx ? item.type : null)
        .filter(Boolean);
      return this.gstTypes.filter(
        type => !selectedTypes.includes(type.value) || type.value === currentValue
      );
    },
    gstTypeUsed(type, currentIdx) {
      // Returns true if this GST type is already used in another row
      return this.challanData.taxItems.some((item, idx) => idx !== currentIdx && item.type === type);
    }
  }
}
</script>

<style scoped>
.gst-challan-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
</style>
