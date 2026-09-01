function escapeHtml(value) {
  if (value === null || value === undefined) return "";
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

/**
 * Opens a new window and prints only the given table's data — never the
 * whole admin page (sidebar/topbar/KPI cards/maps are never included).
 *
 * @param {Object} opts
 * @param {string} opts.title - Printed document title / heading.
 * @param {string} opts.locale - "ar" | "en", controls dir/lang and text alignment.
 * @param {Array<{label: string, value: (row) => (string|number)}>} opts.columns
 * @param {Array<object>} opts.rows
 */
export function printTable({ title, locale, columns, rows }) {
  const isAr = locale === "ar";
  const printWin = window.open("", "_blank");
  if (!printWin) return;

  const headHtml = columns.map((c) => `<th>${escapeHtml(c.label)}</th>`).join("");
  const bodyHtml = rows
    .map(
      (row) =>
        `<tr>${columns.map((c) => `<td>${escapeHtml(c.value(row))}</td>`).join("")}</tr>`
    )
    .join("");

  printWin.document.write(`<!doctype html>
<html dir="${isAr ? "rtl" : "ltr"}" lang="${isAr ? "ar" : "en"}">
<head>
<meta charset="utf-8" />
<title>${escapeHtml(title)}</title>
<style>
  body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; padding: 24px; color: #222; }
  h1 { color: #3E582E; font-size: 18px; margin: 0 0 4px; }
  p.meta { color: #666; font-size: 12px; margin: 0 0 16px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 8px; text-align: ${isAr ? "right" : "left"}; font-size: 12.5px; }
  th { background: #EBF1E7; }
  tr:nth-child(even) { background: #fafafa; }
</style>
</head>
<body>
  <h1>${escapeHtml(title)}</h1>
  <p class="meta">${new Date().toLocaleString(isAr ? "ar-EG" : "en-GB")}</p>
  <table>
    <thead><tr>${headHtml}</tr></thead>
    <tbody>${bodyHtml}</tbody>
  </table>
</body>
</html>`);
  printWin.document.close();
  printWin.focus();
  setTimeout(() => printWin.print(), 400);
}
