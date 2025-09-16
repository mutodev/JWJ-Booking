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

/* === Línea vertical gris (base) === */
.vertical-progress-line {
  position: absolute;
  top: calc(clamp(24px, 4vh, 40px) / 2);   /* inicia en el centro de la primera esfera */
  bottom: calc(clamp(96px, 16vh, 160px) / 2);                             /* baja completo (se recorta después) */
  left: calc(clamp(24px, 4vh, 40px) / 2 + 10px);
  transform: translateX(-50%);
  width: 4px;
  background: #e0e0e0;
  z-index: 0;
}

/* Recorta la línea para que termine en el centro de la última esfera */
.wizard-steps.vertical li:last-child::after {
  content: "";
  position: absolute;
  left: calc(clamp(24px, 4vh, 40px) / 2 + 10px);
  bottom: 0;
  transform: translateX(-50%);
  width: 4px;
  height: 50%; /* tapa desde el centro hacia abajo */
  background: white; /* mismo color que el fondo */
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
}

/* === Wizard === */
.full-height-wizard {
  flex: 1;
  display: flex;
  flex-direction: row;
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
  width: 100% !important;
  padding: 4px 10px !important;
  margin: 0 !important;
  color: #0003 !important;
  box-sizing: border-box !important;
  text-decoration: none !important;
  position: relative;
  z-index: 2;
  background: transparent;
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
  font-size: clamp(12px, 1.5vh, 16px) !important;
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
</style>
