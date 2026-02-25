<template>
  <div class="row mt-3">
    <div class="col-10 mb-3">
      <div class="input-group">
        <input
          v-model="searchValue"
          type="text"
          class="form-control"
          placeholder="Search templates..."
        />
      </div>
    </div>

    <div class="col-md-12">
      <EasyDataTable
        :headers="headers"
        :items="data"
        :search-field="searchField"
        :search-value="searchValue"
        table-class-name="table table-hover"
        header-text-direction="center"
        body-text-direction="center"
        :rows-per-page="10"
        :rows-per-page-options="[5, 10, 25]"
        show-index
        index-column-text="#"
      >
        <template #item-is_active="{ is_active }">
          <span v-if="is_active" class="badge bg-success">Active</span>
          <span v-else class="badge bg-danger">Inactive</span>
        </template>

        <template #item-actions="item">
          <div class="d-flex gap-1 justify-content-center">
            <button class="btn btn-sm btn-warning" @click="editTemplate(item)">
              <i class="bi bi-pencil-square"></i> Edit
            </button>
            <button class="btn btn-sm btn-info" @click="previewTemplate(item)">
              <i class="bi bi-eye"></i> Preview
            </button>
          </div>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <EmailTemplateEdit
    :show="editModalVisible"
    :template-id="selectedTemplateId"
    @close="editModalVisible = false"
    @saved="handleSaved"
  />

  <EmailTemplatePreview
    :show="previewModalVisible"
    :template-id="previewTemplateId"
    @close="previewModalVisible = false"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import EmailTemplateEdit from "./EmailTemplateEdit.vue";
import EmailTemplatePreview from "./EmailTemplatePreview.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Email Templates", icon: "bi bi-envelope-paper" });

const tableHelpers = inject("tableHelpers");

const data = ref([]);
const searchValue = ref("");
const editModalVisible = ref(false);
const previewModalVisible = ref(false);
const selectedTemplateId = ref(null);
const previewTemplateId = ref(null);

const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    excludeColumns: ["available_variables", "body"],
    customLabels: {
      name: "Template Name",
      slug: "Slug",
      subject: "Subject",
      is_active: "Status",
    },
  });
});

const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

const getData = async () => {
  try {
    const response = await api.get("/email-templates");
    data.value = response.data;
  } catch (error) {
    console.error("Error fetching email templates:", error);
  }
};

const editTemplate = (item) => {
  selectedTemplateId.value = item.id;
  editModalVisible.value = true;
};

const previewTemplate = (item) => {
  previewTemplateId.value = item.id;
  previewModalVisible.value = true;
};

const handleSaved = () => {
  editModalVisible.value = false;
  getData();
};

onMounted(() => {
  getData();
});
</script>

<style scoped>
.input-group-text {
  background-color: var(--bs-light);
  border-color: var(--bs-border-color);
}

:deep(.vue3-easy-data-table__main) {
  border-radius: 0.375rem;
  overflow: hidden;
}

:deep(.vue3-easy-data-table__header) {
  background-color: var(--bs-light);
}

:deep(.vue3-easy-data-table__body tr:hover) {
  background-color: var(--bs-light);
}
</style>
