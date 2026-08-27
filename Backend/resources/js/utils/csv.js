/**
 *
 * @param {Array<Array<string|number|null|undefined>>} rows
 * @returns {string}
 */
function toCsvString(rows) {
  const escapeCell = (cell) => {
    const value = cell === null || cell === undefined ? "" : String(cell);
    if (/[",\n\r]/.test(value)) {
      return `"${value.replace(/"/g, '""')}"`;
    }
    return value;
  };

  return rows.map((row) => row.map(escapeCell).join(",")).join("\r\n");
}

/**
 *
 * @param {string} filename - اسم الملف (بدون الحاجة لإضافة .csv يدويًا)
 * @param {Array<Array<string|number|null|undefined>>} rows - صفوف البيانات
 */
export function downloadCsv(filename, rows) {
  const csvContent = toCsvString(rows);
  const BOM = "\uFEFF";
  const blob = new Blob([BOM + csvContent], {
    type: "text/csv;charset=utf-8;",
  });

  const safeFilename = filename.endsWith(".csv") ? filename : `${filename}.csv`;
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.setAttribute("download", safeFilename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}