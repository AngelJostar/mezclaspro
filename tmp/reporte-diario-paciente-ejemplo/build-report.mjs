import fs from "node:fs/promises";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const outputDir = "C:/laragon/www/mezclaspro/outputs/reporte-diario-paciente-ejemplo-20260820";
const outputPath = `${outputDir}/reporte_diario_por_paciente_ejemplo.xlsx`;
const previewPath = `${outputDir}/reporte_diario_por_paciente_ejemplo.png`;

const sourceRows = [
  { deliveredAt: "2026-08-05T11:45:00", lot: "L050826004", patient: "Paciente Ejemplo Catorce", doctor: "Dra. Sofia Navarro", birthDate: "1986-12-11", cost: 2890.45 },
  { deliveredAt: "2026-08-01T08:10:00", lot: "L010826001", patient: "Paciente Ejemplo Uno", doctor: "Dra. Elena Vargas", birthDate: "1987-02-14", cost: 3520.50 },
  { deliveredAt: "2026-08-03T14:20:00", lot: "L030826006", patient: "Paciente Ejemplo Diez", doctor: "Dr. Carlos Mendez", birthDate: "1978-10-03", cost: 4210.35 },
  { deliveredAt: "2026-08-08T10:30:00", lot: "L080826002", patient: "Paciente Ejemplo Diecisiete", doctor: "Dra. Laura Medina", birthDate: "1994-04-19", cost: 2765.90 },
  { deliveredAt: "2026-08-03T07:40:00", lot: "L030826001", patient: "Paciente Ejemplo Cinco", doctor: "Dra. Monica Reyes", birthDate: "1981-06-30", cost: 3100.00 },
  { deliveredAt: "2026-08-05T07:55:00", lot: "L050826001", patient: "Paciente Ejemplo Once", doctor: "Dra. Sofia Navarro", birthDate: "1990-09-22", cost: 1985.75 },
  { deliveredAt: "2026-08-01T12:35:00", lot: "L010826003", patient: "Paciente Ejemplo Tres", doctor: "Dr. Roberto Salas", birthDate: "1974-11-08", cost: 2840.10 },
  { deliveredAt: "2026-08-03T10:05:00", lot: "L030826003", patient: "Paciente Ejemplo Siete", doctor: "Dr. Carlos Mendez", birthDate: "1992-01-17", cost: 2650.90 },
  { deliveredAt: "2026-08-08T08:15:00", lot: "L080826001", patient: "Paciente Ejemplo Dieciseis", doctor: "Dra. Laura Medina", birthDate: "1983-07-05", cost: 3340.25 },
  { deliveredAt: "2026-08-05T13:10:00", lot: "L050826005", patient: "Paciente Ejemplo Quince", doctor: "Dr. Andres Paredes", birthDate: "1976-03-27", cost: 4055.00 },
  { deliveredAt: "2026-08-01T09:25:00", lot: "L010826002", patient: "Paciente Ejemplo Dos", doctor: "Dra. Elena Vargas", birthDate: "1995-08-09", cost: 2190.80 },
  { deliveredAt: "2026-08-03T08:30:00", lot: "L030826002", patient: "Paciente Ejemplo Seis", doctor: "Dra. Monica Reyes", birthDate: "1988-05-12", cost: 3760.40 },
  { deliveredAt: "2026-08-05T09:20:00", lot: "L050826002", patient: "Paciente Ejemplo Doce", doctor: "Dr. Andres Paredes", birthDate: "1984-01-15", cost: 2310.60 },
  { deliveredAt: "2026-08-03T12:00:00", lot: "L030826004", patient: "Paciente Ejemplo Ocho", doctor: "Dra. Monica Reyes", birthDate: "1991-11-29", cost: 1890.25 },
  { deliveredAt: "2026-08-01T15:05:00", lot: "L010826004", patient: "Paciente Ejemplo Cuatro", doctor: "Dr. Roberto Salas", birthDate: "1969-04-25", cost: 3985.00 },
  { deliveredAt: "2026-08-08T13:45:00", lot: "L080826003", patient: "Paciente Ejemplo Dieciocho", doctor: "Dr. Ivan Cortes", birthDate: "1989-02-08", cost: 3480.70 },
  { deliveredAt: "2026-08-05T10:35:00", lot: "L050826003", patient: "Paciente Ejemplo Trece", doctor: "Dra. Sofia Navarro", birthDate: "1993-06-18", cost: 3675.20 },
  { deliveredAt: "2026-08-03T13:10:00", lot: "L030826005", patient: "Paciente Ejemplo Nueve", doctor: "Dr. Carlos Mendez", birthDate: "1985-09-14", cost: 2940.80 },
];

const sortedRows = [...sourceRows].sort((left, right) => {
  const dateComparison = left.deliveredAt.localeCompare(right.deliveredAt);
  return dateComparison !== 0 ? dateComparison : left.lot.localeCompare(right.lot);
});

const groups = new Map();
for (const row of sortedRows) {
  const dateKey = row.deliveredAt.slice(0, 10);
  if (!groups.has(dateKey)) groups.set(dateKey, []);
  groups.get(dateKey).push(row);
}

const excelDate = (isoDate) => {
  const [year, month, day] = isoDate.slice(0, 10).split("-").map(Number);
  return new Date(year, month - 1, day, 12, 0, 0);
};

const workbook = Workbook.create();
const sheet = workbook.worksheets.add("Reporte diario");
sheet.showGridLines = false;

sheet.getRange("A1:G1").merge();
sheet.getRange("A1").values = [["Reporte diario por paciente"]];
sheet.getRange("A2:G2").merge();
sheet.getRange("A2").values = [["Nutriciones conciliables entregadas, agrupadas por día"]];
sheet.getRange("A3:G3").merge();
sheet.getRange("A3").values = [["EJEMPLO CON DATOS FICTICIOS PARA VALIDAR EL ORDENAMIENTO"]];

sheet.getRange("A5:B7").values = [
  ["Hospital", "HOSPITAL DEMOSTRATIVO PRODIFEM"],
  ["Periodo", "01/08/2026 al 08/08/2026"],
  ["Fecha de generación", new Date(2026, 7, 20, 15, 0, 0)],
];
sheet.getRange("F5:F7").values = [["Total de mezclas"], ["Costo total"], ["Criterio de orden"]];
sheet.getRange("G5").formulas = [["=COUNT(A10:A100)"]];
sheet.getRange("G7").values = [["Fecha/hora ascendente"]];

sheet.getRange("A9:G9").values = [[
  "CANTIDAD POR DÍA",
  "FECHA",
  "LOTE",
  "PACIENTE",
  "MÉDICO",
  "FECHA DE NACIMIENTO",
  "COSTO",
]];

let currentRow = 10;
const summaryRows = [];
const detailRanges = [];
const expectedCounts = [];

for (const [dateKey, dailyRows] of groups) {
  const summaryRow = currentRow;
  const detailStart = summaryRow + 1;
  const detailEnd = detailStart + dailyRows.length - 1;
  summaryRows.push(summaryRow);
  detailRanges.push(`G${detailStart}:G${detailEnd}`);
  expectedCounts.push({ dateKey, count: dailyRows.length, summaryRow, detailStart, detailEnd });

  sheet.getRange(`B${summaryRow}`).values = [[excelDate(dateKey)]];
  sheet.getRange(`D${summaryRow}`).formulas = [[
    `=COUNTA(D${detailStart}:D${detailEnd})&IF(COUNTA(D${detailStart}:D${detailEnd})=1," mezcla"," mezclas")`,
  ]];
  sheet.getRange(`G${summaryRow}`).formulas = [[`=SUM(G${detailStart}:G${detailEnd})`]];

  const details = dailyRows.map((row, index) => [
    index + 1,
    excelDate(row.deliveredAt),
    row.lot,
    row.patient,
    row.doctor,
    excelDate(row.birthDate),
    row.cost,
  ]);
  sheet.getRange(`A${detailStart}:G${detailEnd}`).values = details;
  currentRow = detailEnd + 1;
}

const lastRow = currentRow - 1;
sheet.getRange("G6").formulas = [[`=SUM(${detailRanges.join(",")})`]];

sheet.getRange("A1:G1").format = {
  fill: "#FFFFFF",
  font: { name: "Aptos Display", size: 18, bold: true, color: "#0F172A" },
  horizontalAlignment: "left",
  verticalAlignment: "center",
};
sheet.getRange("A2:G2").format = {
  font: { name: "Aptos", size: 10, italic: true, color: "#64748B" },
};
sheet.getRange("A3:G3").format = {
  fill: "#FFF7ED",
  font: { name: "Aptos", size: 10, bold: true, color: "#C2410C" },
  horizontalAlignment: "center",
};
sheet.getRange("A5:A7").format = {
  font: { bold: true, color: "#0F172A" },
};
sheet.getRange("F5:F7").format = {
  fill: "#EFF6FF",
  font: { bold: true, color: "#1E3A8A" },
};
sheet.getRange("G5:G7").format = {
  fill: "#F8FAFC",
  font: { bold: true, color: "#0F172A" },
};
sheet.getRange("A5:B7").format.borders = { preset: "outside", style: "thin", color: "#CBD5E1" };
sheet.getRange("F5:G7").format.borders = { preset: "all", style: "thin", color: "#CBD5E1" };

sheet.getRange("A9:G9").format = {
  fill: "#1E293B",
  font: { name: "Aptos", size: 10, bold: true, color: "#FFFFFF" },
  horizontalAlignment: "center",
  verticalAlignment: "center",
  wrapText: true,
  borders: { preset: "all", style: "thin", color: "#64748B" },
};
sheet.getRange(`A10:G${lastRow}`).format = {
  font: { name: "Aptos", size: 10, color: "#0F172A" },
  verticalAlignment: "center",
  borders: { preset: "all", style: "thin", color: "#CBD5E1" },
};

for (const row of summaryRows) {
  sheet.getRange(`A${row}:G${row}`).format = {
    fill: "#D9E5F3",
    font: { name: "Aptos", size: 10, bold: true, color: "#0F172A" },
    verticalAlignment: "center",
    borders: { preset: "all", style: "thin", color: "#64748B" },
  };
  sheet.getRange(`B${row}`).format.horizontalAlignment = "left";
  sheet.getRange(`D${row}`).format.horizontalAlignment = "left";
}

sheet.getRange(`A10:A${lastRow}`).format.horizontalAlignment = "center";
sheet.getRange(`B10:B${lastRow}`).format.numberFormat = "dd/mm/yyyy";
sheet.getRange(`F10:F${lastRow}`).format.numberFormat = "dd/mm/yyyy";
sheet.getRange("B7").format.numberFormat = "dd/mm/yyyy hh:mm";
sheet.getRange(`G10:G${lastRow}`).format.numberFormat = '"$"#,##0.00';
sheet.getRange("G6").format.numberFormat = '"$"#,##0.00';
sheet.getRange("D10:E100").format.wrapText = true;

sheet.getRange("A1:G1").format.rowHeight = 28;
sheet.getRange("A3:G3").format.rowHeight = 22;
sheet.getRange("A9:G9").format.rowHeight = 32;
sheet.getRange(`A10:G${lastRow}`).format.rowHeight = 22;

const columnWidths = {
  A: 18,
  B: 15,
  C: 20,
  D: 36,
  E: 34,
  F: 22,
  G: 18,
};
for (const [column, width] of Object.entries(columnWidths)) {
  sheet.getRange(`${column}:${column}`).format.columnWidth = width;
}

sheet.freezePanes.freezeRows(9);

const check = await workbook.inspect({
  kind: "table",
  range: `Reporte diario!A1:G${lastRow}`,
  include: "values,formulas",
  tableMaxRows: 40,
  tableMaxCols: 7,
  maxChars: 12000,
});

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 100 },
  summary: "final formula error scan",
});

await fs.mkdir(outputDir, { recursive: true });
const preview = await workbook.render({
  sheetName: "Reporte diario",
  range: `A1:G${lastRow}`,
  scale: 1.25,
  format: "png",
});
await fs.writeFile(previewPath, new Uint8Array(await preview.arrayBuffer()));

const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);

console.log(JSON.stringify({
  outputPath,
  previewPath,
  detailCount: sortedRows.length,
  groups: expectedCounts,
  inspect: check.ndjson,
  errors: errors.ndjson,
}, null, 2));
