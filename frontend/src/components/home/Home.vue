<template>
  <div class="full-height-container">
    <div class="wizard-with-progress" ref="wizardRoot">
      <!-- Barra de progreso vertical -->
      <div class="vertical-progress-line">
        <div
          class="vertical-progress-fill"
          :style="{ height: (activeStep / totalSteps) * 0 + '%' }"
        ></div>
      </div>

      <!-- Wizard -->
      <form-wizard
        layout="vertical"
        class="full-height-wizard"
        :navigable-tabs="false"
        @on-change="onTabChange"
      >
        <tab-content title="Step 1" :before-change="validateNext">
          <Step1 @setData="setData" />
        </tab-content>
        <tab-content title="Step 2" :before-change="validateNext">
          <Step2 @setData="setData" :city="form?.customer?.cityId" />
        </tab-content>
        <tab-content title="Step 3" :before-change="validateNext">
          <Step3
            @setData="setData"
            :county="form?.customer?.countyId"
            :active="nextIndex === 2"
          />
        </tab-content>
        <tab-content title="Step 4" :before-change="validateNext">
          <Step4
            @setData="setData"
            :service="form?.service?.id"
            :active="nextIndex === 3"
          />
        </tab-content>
        <tab-content title="Step 5" :before-change="validateNext">
          <Step5
            @setData="setData"
            :service="form?.service?.id"
            :active="nextIndex === 4"
          />
        </tab-content>
        <tab-content title="Step 6" :before-change="validateNext">
          <Step6 @setData="setData" />
        </tab-content>
        <tab-content title="Step 7" :before-change="validateNext">
          <Step7 @setData="setData" />
        </tab-content>
        <tab-content title="Step 8" :before-change="validateNext">
          <Step8 @setData="setData" />
        </tab-content>
        <tab-content title="Step 9" :before-change="validateNext">
          <Step9 @setData="setData" />
        </tab-content>
        <tab-content title="Step 10" :before-change="validateNext">
          <Step10 @setData="setData" />
        </tab-content>
      </form-wizard>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { FormWizard, TabContent } from "vue3-form-wizard";
import "vue3-form-wizard/dist/style.css";
import "../../assets/styles/wizard-home.css";

import Step1 from "./form/Step1.vue";
import Step2 from "./form/Step2.vue";
import Step3 from "./form/Step3.vue";
import Step4 from "./form/Step4.vue";
import Step5 from "./form/Step5.vue";
import Step6 from "./form/Step6.vue";
import Step7 from "./form/Step7.vue";
import Step8 from "./form/Step8.vue";
import Step9 from "./form/Step9.vue";
import Step10 from "./form/Step10.vue";

const totalSteps = 10;
const activeStep = ref(1);
const form = ref({});

// refs para saber de dónde a dónde vamos
const prevIndex = ref(null);
const nextIndex = ref(0);

function onTabChange(prev, next) {
  prevIndex.value = prev;
  nextIndex.value = next;
  activeStep.value = next + 1;
}

/* ======= Prevención de navegación por tabs ======= */
const wizardRoot = ref(null);

function handleClick(e) {
  const anchor = e.target.closest(
    ".vue-form-wizard .wizard-nav-pills > li > a"
  );
  if (anchor) {
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
  }
}

function handleKeydown(e) {
  const anchor = e.target.closest(
    ".vue-form-wizard .wizard-nav-pills > li > a"
  );
  if (anchor && (e.key === "Enter" || e.key === " " || e.key === "Spacebar")) {
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
  }
}

onMounted(() => {
  if (wizardRoot.value) {
    wizardRoot.value.addEventListener("click", handleClick, true);
    wizardRoot.value.addEventListener("keydown", handleKeydown, true);
  }
});

onBeforeUnmount(() => {
  if (wizardRoot.value) {
    wizardRoot.value.removeEventListener("click", handleClick, true);
    wizardRoot.value.removeEventListener("keydown", handleKeydown, true);
  }
});

/**
 * Arma datos del formulario
 */
const setData = (data) => {
  for (const key in data) {
    if (data[key] === null) {
      delete form.value[key];
    } else {
      form.value[key] = data[key];
    }
  }
  console.log(form.value);
};

/**
 * Validación antes de cambiar de tab
 */
function validateNext() {
  console.log(nextIndex.value);

  if (nextIndex.value === 0 && !form.value.customer) return false;
  if (nextIndex.value === 1 && !form.value.zipcode) return false;
  if (nextIndex.value === 2 && !form.value.service) return false;
  if (nextIndex.value === 3 && !form.value.kids) return false;

  return true;
}
</script>
