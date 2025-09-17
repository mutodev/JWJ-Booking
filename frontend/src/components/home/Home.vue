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
        <tab-content title="Step 1"><Step1 /></tab-content>
        <tab-content title="Step 2"><Step2 /></tab-content>
        <tab-content title="Step 3"><Step3 /></tab-content>
        <tab-content title="Step 4"><Step4 /></tab-content>
        <tab-content title="Step 5"><Step5 /></tab-content>
        <tab-content title="Step 6"><Step6 /></tab-content>
        <tab-content title="Step 7"><Step7 /></tab-content>
        <tab-content title="Step 8"><Step8 /></tab-content>
        <tab-content title="Step 9"><Step9 /></tab-content>
        <tab-content title="Step 10"><Step10 /></tab-content>
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

function onTabChange(prevIndex, nextIndex) {
  // activeStep.value = nextIndex + 1;
}

/* ======= Prevención de navegación por tabs ======= */
const wizardRoot = ref(null);

function handleClick(e) {
  const anchor = e.target.closest(".vue-form-wizard .wizard-nav-pills > li > a");
  if (anchor) {
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
  }
}

function handleKeydown(e) {
  const anchor = e.target.closest(".vue-form-wizard .wizard-nav-pills > li > a");
  if (
    anchor &&
    (e.key === "Enter" || e.key === " " || e.key === "Spacebar")
  ) {
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
</script>

