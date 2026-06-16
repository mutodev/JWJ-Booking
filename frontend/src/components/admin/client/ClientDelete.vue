<template>
  <ConfirmModal
    :show="show"
    title="Delete Customer"
    :message="`You are about to permanently delete <strong>${data?.full_name}</strong> (${data?.email}). This will remove the customer from the system.`"
    confirmLabel="Delete Customer"
    @confirm="submitDelete"
    @cancel="$emit('close')"
  />
</template>

<script setup>
import { ref } from "vue";
import api from "@/services/axios";
import ConfirmModal from "@/components/admin/shared/ConfirmModal.vue";

const props = defineProps({
  show: { type: Boolean, default: false },
  data: { type: Object, default: null },
});

const emit = defineEmits(["close", "deleted"]);

const loading = ref(false);

async function submitDelete() {
  if (!props.data?.id) return;
  try {
    loading.value = true;
    await api.delete(`/customers/${props.data.id}`);
    emit("deleted");
  } catch (error) {
    console.error("Error deleting customer:", error);
  } finally {
    loading.value = false;
  }
}
</script>
