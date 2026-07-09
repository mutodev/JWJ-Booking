import { computed } from "vue";

const emptyPermissions = {
  view: false,
  create: false,
  update: false,
  delete: false,
};

const normalizeUri = (uri) => (uri || "").replace(/\/+$/, "");

const toBoolean = (value) => value === true || value === 1 || value === "1";

const findMenuByUri = (items, uri) => {
  for (const item of items) {
    if (normalizeUri(item.uri) === uri) {
      return item;
    }

    const child = findMenuByUri(item.children || [], uri);
    if (child) {
      return child;
    }
  }

  return null;
};

export function useMenuPermissions(uri = window.location.pathname) {
  const permissions = computed(() => {
    const access = JSON.parse(localStorage.getItem("access") || "[]");
    const menu = findMenuByUri(access, normalizeUri(uri));
    const rawPermissions = menu?.permissions || emptyPermissions;

    return {
      view: toBoolean(rawPermissions.view),
      create: toBoolean(rawPermissions.create),
      update: toBoolean(rawPermissions.update),
      delete: toBoolean(rawPermissions.delete),
    };
  });

  return {
    permissions,
    canView: computed(() => permissions.value.view),
    canCreate: computed(() => permissions.value.create),
    canUpdate: computed(() => permissions.value.update),
    canDelete: computed(() => permissions.value.delete),
  };
}
