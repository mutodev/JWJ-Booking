<template>
  <div class="row justify-content-center">
    <div class="col-5">
      <div class="form-group">
        <label for="metropolitan-area">Metropolitan Area</label>
        <Multiselect
          id="metropolitan-area"
          v-model="selectedArea"
          :options="listAreas"
          label="name"
          track-by="id"
          placeholder="Select a metropolitan area"
          @select="onSelectArea"
        />
      </div>
    </div>
    <div class="col-5">
      <div class="form-group">
        <label for="county">County</label>
        <Multiselect
          id="county"
          v-model="selectedCounty"
          :options="listCounties"
          label="name"
          track-by="id"
          placeholder="Select a county"
          @select="onSelectCounty"
        />
      </div>
    </div>
    <div class="col-5">
      <div class="form-group">
        <label for="city">City</label>
        <Multiselect
          id="city"
          v-model="selectedCity"
          :options="listCities"
          label="name"
          track-by="id"
          placeholder="Select a city"
          @select="onSelectCity"
        />
      </div>
    </div>
    <div class="col-5">
      <div class="form-group">
        <label for="zipcode">Zip Code</label>
        <Multiselect
          id="zipcode"
          v-model="selectedZipCode"
          :options="listZipCodes"
          label="zipcode"
          track-by="id"
          placeholder="Select a zip code"
          @select="onSelectZipCode"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";

const emit = defineEmits(["setData"]);
const props = defineProps({
  areas: { type: Array, default: () => [] },
});

// Selected states
const selectedArea = ref(null);
const selectedCounty = ref(null);
const selectedCity = ref(null);
const selectedZipCode = ref(null);

// Listas
const listAreas = ref([]);
const listCounties = ref([]);
const listCities = ref([]);
const listZipCodes = ref([]);

// Cargar areas desde props
watch(
  () => props.areas,
  (newData) => {
    listAreas.value = [...newData];
  },
  { immediate: true }
);

// Evento al seleccionar un área metropolitana
const onSelectArea = async (selected) => {
  listCounties.value = [];
  listCities.value = [];
  listZipCodes.value = [];

  selectedCounty.value = null;
  selectedCity.value = null;
  selectedZipCode.value = null;

  const response = await api.get(
    `/counties/get-by-metropolitan/${selected.id}`
  );
  listCounties.value = response.data;
  emit("setData", { areas: null, service: null, addons: null, form: null });
};

// Evento al seleccionar un county
const onSelectCounty = async (selected) => {
  listCities.value = [];
  listZipCodes.value = [];

  selectedCity.value = null;
  selectedZipCode.value = null;

  const response = await api.get(`/cities/get-by-county/${selected.id}`);
  listCities.value = response.data;
  emit("setData", { areas: null, service: null, addons: null, form: null });
};

const onSelectCity = async (selected) => {
  listZipCodes.value = [];
  selectedZipCode.value = null;

  const response = await api.get(`/zipcodes/get-by-city/${selected.id}`);
  listZipCodes.value = response.data;
  emit("setData", { areas: null, service: null, addons: null, form: null });
};

const onSelectZipCode = async (selected) => {
  const areas = {
    area: selectedArea.value,
    county: selectedCounty.value,
    city: selectedCity.value,
    zipcode: selectedZipCode.value,
  };

  emit("setData", { areas });
};
</script>
