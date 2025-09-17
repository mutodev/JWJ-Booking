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

<style scoped>
/* === Contenedor principal === */
.full-height-container {
  height: 100vh;
  box-sizing: border-box;
  display: flex;
  min-height: 0;
  padding: 2%;
}

/* === Wrapper para wizard + progreso === */
.wizard-with-progress {
  display: flex;
  flex-direction: row;
  height: 100%;
  width: 100%;
  position: relative;
  box-sizing: border-box;
}

/* === Línea vertical gris (base) === */
.vertical-progress-line {
  position: absolute;
  top: calc(clamp(24px, 4vh, 40px) / 2);
  left: calc(clamp(24px, 4vh, 40px) / 2 + 10px);
  transform: translateX(-50%);
  width: 4px;
  background: #e0e0e0;
  z-index: 0;
  height: 100%;
}

/* Recorta la línea para que termine en el centro de la última esfera */
.wizard-steps.vertical li:last-child::after {
  content: "";
  position: absolute;
  left: calc(clamp(24px, 4vh, 40px) / 2 + 10px);
  bottom: 0;
  transform: translateX(-50%);
  width: 4px;
  height: 50%;
  background: white;
  z-index: 2;
}

/* === Línea roja de progreso === */
.vertical-progress-fill {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  background: #e74c3c;
  transition: height 0.3s ease;
  z-index: 1;
  height: 100%;
}

/* Navigation ajustes */
::v-deep(.wizard-navigation) {
  display: flex;
  flex-direction: row;
  height: 100% !important;
}

/* === Wizard === */
.full-height-wizard {
  flex: 1;
  display: flex;
  flex-direction: column !important;
  min-height: 0;
  position: relative;
  z-index: 2;
}

/* === Columna de pasos (vertical) === */
::v-deep(.wizard-steps.vertical) {
  height: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  overflow-y: auto !important;
  padding: 0 !important;
  margin: 0 !important;
  box-sizing: border-box !important;
  position: relative;
  z-index: 2;
}

/* Cada item (li) */
::v-deep(.wizard-steps.vertical li) {
  min-height: clamp(32px, 5vh, 56px) !important;
  padding: 0 !important;
  box-sizing: border-box !important;
  position: relative;
  z-index: 2;
}

/* === Link de cada paso === */
::v-deep(.vue-form-wizard .wizard-nav-pills > li > a) {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 4px !important;
  width: 106% !important;
  padding: 4px 10px !important;
  margin: 0 !important;
  color: #0003 !important;
  box-sizing: border-box !important;
  text-decoration: none !important;
  position: relative;
  z-index: 2;
  background: transparent;
  cursor: default !important;
}

/* === Círculo indicador dinámico === */
::v-deep(.vue-form-wizard.md .wizard-icon-circle) {
  width: clamp(24px, 4vh, 40px) !important;
  height: clamp(24px, 4vh, 40px) !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 50% !important;
  box-sizing: border-box !important;
  flex-shrink: 0 !important;
  margin: 0 !important;
  font-size: clamp(12px, 1.5vh, 16px) !important;
  position: relative;
  z-index: 3;
  background: #fff;
}

/* Contenedor interno del círculo */
::v-deep(.vue-form-wizard .wizard-icon-circle .wizard-icon-container) {
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  width: 100% !important;
  height: 100% !important;
  border-radius: 50% !important;
  margin: 0 !important;
  box-sizing: border-box !important;
}

/* Icono activo */
::v-deep(.vue-form-wizard.md .wizard-nav-pills > li.active > a .wizard-icon) {
  font-size: clamp(12px, 1.5vh, 16px) !important;
}

/* === Título del paso === */
::v-deep(.vue-form-wizard .wizard-nav-pills > li > a .stepTitle),
::v-deep(.vue-form-wizard .wizard-nav-pills > li > a .wizard-title) {
  flex: 0 1 auto !important;
  text-align: left !important;
  margin: 0 !important;
  padding: 0 !important;
  font-size: clamp(12px, 1vh, 16px) !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  position: relative;
  z-index: 2;
}

/* === Columna de contenido === */
::v-deep(.wizard-content) {
  flex: 1 !important;
  display: flex !important;
  flex-direction: column !important;
  min-height: 0 !important;
  height: 100% !important;
  padding: 0 !important;
  box-sizing: border-box !important;
}

::v-deep(.wizard-tab) {
  flex: 1 !important;
  min-height: 0 !important;
  height: 100% !important;
  overflow: auto !important;
  box-sizing: border-box !important;
}

/* Tab content ocupa todo el espacio */
::v-deep(.wizard-tab-content) {
  flex: 1 1 auto !important;
  width: 100% !important;
  height: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  overflow: auto !important;
  box-sizing: border-box !important;
  position: relative !important;
}

::v-deep(.wizard-card-footer) {
  position: fixed !important;
  bottom: 2vh;
  right: 2vw;
  padding: 1.5rem 2rem;
  max-width: 90%;
  border-radius: 8px;
  z-index: 1000;
}
</style>
