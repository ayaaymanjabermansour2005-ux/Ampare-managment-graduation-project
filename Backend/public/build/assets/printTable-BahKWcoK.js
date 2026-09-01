function n(t){return t==null?"":String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function s({title:t,locale:l,columns:r,rows:i}){const a=l==="ar",e=window.open("","_blank");if(!e)return;const d=r.map(o=>`<th>${n(o.label)}</th>`).join(""),p=i.map(o=>`<tr>${r.map(c=>`<td>${n(c.value(o))}</td>`).join("")}</tr>`).join("");e.document.write(`<!doctype html>
<html dir="${a?"rtl":"ltr"}" lang="${a?"ar":"en"}">
<head>
<meta charset="utf-8" />
<title>${n(t)}</title>
<style>
  body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; padding: 24px; color: #222; }
  h1 { color: #3E582E; font-size: 18px; margin: 0 0 4px; }
  p.meta { color: #666; font-size: 12px; margin: 0 0 16px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 8px; text-align: ${a?"right":"left"}; font-size: 12.5px; }
  th { background: #EBF1E7; }
  tr:nth-child(even) { background: #fafafa; }
</style>
</head>
<body>
  <h1>${n(t)}</h1>
  <p class="meta">${new Date().toLocaleString(a?"ar-EG":"en-GB")}</p>
  <table>
    <thead><tr>${d}</tr></thead>
    <tbody>${p}</tbody>
  </table>
</body>
</html>`),e.document.close(),e.focus(),setTimeout(()=>e.print(),400)}export{s as p};
