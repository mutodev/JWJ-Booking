/**
 * Helper para generar headers dinámicos para EasyDataTable
 * @param {Array} data - Array de datos de la tabla
 * @param {Object} options - Opciones de configuración
 * @param {Array} options.excludeColumns - Columnas específicas a omitir
 * @param {Boolean} options.excludeIdColumns - Si omitir columnas que terminan en '_id' (default: true)
 * @param {Boolean} options.excludeTimestamps - Si omitir columnas de timestamp (default: true)
 * @param {Boolean} options.addActionsColumn - Si agregar columna de acciones (default: true)
 * @param {Object} options.customLabels - Mapeo personalizado de nombres de columnas
 * @param {Boolean} options.sortable - Si las columnas son ordenables (default: true)
 * @returns {Array} Array de headers para EasyDataTable
 */
export const generateTableHeaders = (data, options = {}) => {
  const {
    excludeColumns = [],
    excludeIdColumns = true,
    excludeTimestamps = true,
    addActionsColumn = true,
    customLabels = {},
    sortable = true,
  } = options;

  // Si no hay datos, retornar array vacío
  if (!data || data.length === 0) return [];

  const firstItem = data[0];

  // Columnas por defecto a omitir
  const defaultExcludedColumns = [];

  if (excludeTimestamps) {
    defaultExcludedColumns.push("created_at", "updated_at", "deleted_at");
  }

  // Combinar todas las columnas a excluir
  const allExcludedColumns = [
    ...defaultExcludedColumns,
    ...excludeColumns,
    "id", // Siempre omitir id
  ];

  // Generar headers
  const headers = Object.keys(firstItem)
    .filter((key) => {
      // Omitir columnas específicas
      if (allExcludedColumns.includes(key)) return false;

      // Omitir columnas que terminan en '_id' si está habilitado
      if (excludeIdColumns && key.endsWith("_id")) return false;

      return true;
    })
    .map((key) => {
      let text;

      // Usar label personalizado si existe
      if (customLabels[key]) {
        text = customLabels[key];
      } else {
        // Convertir snake_case a Title Case
        text = key
          .replace(/_/g, " ")
          .replace(/\b\w/g, (letter) => letter.toUpperCase());
      }

      return {
        text,
        value: key,
        sortable,
      };
    });

  // Agregar columna de acciones si está habilitado
  if (addActionsColumn) {
    headers.push({
      text: "Actions",
      value: "actions",
      sortable: false,
    });
  }

  return headers;
};

/**
 * Helper específico para generar campos de búsqueda
 * @param {Array} headers - Headers generados por generateTableHeaders
 * @returns {Array} Array de campos para búsqueda
 */
export const generateSearchFields = (headers) => {
  return headers
    .filter((header) => header.value !== "actions")
    .map((header) => header.value);
};

/**
 * Mapeo de labels comunes para reutilizar
 */
export const commonLabels = {
  name: "Name",
  email: "Email",
  phone: "Phone",
  address: "Address",
  is_active: "State",
  is_enabled: "Enabled",
  is_verified: "Verified",
  status: "Status",
  description: "Description",
  title: "Title",
  first_name: "First Name",
  last_name: "Last Name",
  user_name: "Username",
  role_name: "Role Name",
  category: "Category",
  price: "Price",
  quantity: "Quantity",
  total: "Total",
};

// Exportación por defecto
export default {
  generateTableHeaders,
  generateSearchFields,
  commonLabels,
};
