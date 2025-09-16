<template>
  <div class="full-height-container">
    <div class="wizard-with-progress">
      <!-- Barra de progreso vertical -->
      <div class="vertical-progress-line">
        <div
          class="vertical-progress-fill"
          :style="{ height: (activeStep / totalSteps) * 100 + '%' }"
        ></div>
      </div>

      <!-- Wizard -->
      <form-wizard
        layout="vertical"
        class="full-height-wizard"
        @on-change="onTabChange"
      >
        <tab-content title="Step 1"><h3>1</h3></tab-content>
        <tab-content title="Step 2"><h3>2</h3></tab-content>
        <tab-content title="Step 3"><h3>3</h3></tab-content>
        <tab-content title="Step 4"><h3>4</h3></tab-content>
        <tab-content title="Step 5"><h3>5</h3></tab-content>
        <tab-content title="Step 6"><h3>6</h3></tab-content>
        <tab-content title="Step 7"><h3>7</h3></tab-content>
        <tab-content title="Step 8"><h3>8</h3></tab-content>
        <tab-content title="Step 9"><h3>9</h3></tab-content>
        <tab-content title="Step 10"><h3>10</h3></tab-content>
      </form-wizard>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { FormWizard, TabContent } from "vue3-form-wizard";
import "vue3-form-wizard/dist/style.css";

const totalSteps = 10;
const activeStep = ref(1);

function onTabChange(prevIndex, nextIndex) {
  activeStep.value = nextIndex + 1;
}
</script>

<style scoped>
/* === Contenedor principal === */
.full-height-container {
  height: 100vh;
  box-sizing: border-box;
  display: flex;
  min-height: 0;
}

/* === Wrapper para wizard + progreso === */
.wizard-with-progress {
  display: flex;
  flex-direction: row;
  height: 100%;
  width: 100%;
  position: relative;
}

/* === Línea vertical === */
.vertical-progress-line {
  position: relative;
  width: 4px;
  background: #e0e0e0;
  margin-right: 12px; /* separa la barra del wizard */
}

.vertical-progress-fill {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  background: #e74c3c;
  transition: height 0.3s ease;
}

/* === Wizard === */
.full-height-wizard {
  flex: 1;
  display: flex;
  flex-direction: row;
  min-height: 0;
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
}

/* Cada item (li) */
::v-deep(.wizard-steps.vertical li) {
  min-height: clamp(32px, 5vh, 56px) !important;
  padding: 0 !important;
  box-sizing: border-box !important;
}

/* === Link de cada paso === */
::v-deep(.vue-form-wizard .wizard-nav-pills > li > a) {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 4px !important;
  width: 100% !important;
  padding: 4px 10px !important;
  margin: 0 !important;
  color: #0003 !important;
  box-sizing: border-box !important;
  text-decoration: none !important;
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
  font-size: clamp(12px, 1.5vh, 16px) !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
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
</style>
