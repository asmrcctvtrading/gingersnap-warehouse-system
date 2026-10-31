import pandas as pd
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from datetime import datetime

# Read CSV
csv_file = r"C:\Users\Odyssey Jan Pascual\Downloads\Inventory Snapshot_02092026065145.csv"
df = pd.read_csv(csv_file)

print(f"CSV loaded: {len(df)} rows")

# Extract department from UPC column (contains dept codes like GIRLS, BINFANT BOYS, BOYS, etc.)
df['Department'] = df['UPC'].str.strip()

# Group by Facility and Department, sum inventory
agg = df.groupby(['Facility', 'Department'])['Inventory'].sum().reset_index()
agg.columns = ['Facility', 'Department', 'Inventory']
agg = agg.sort_values(['Facility', 'Department'])

print(f"Aggregated data: {len(agg)} department groups")
print(f"Facilities: {sorted(agg['Facility'].unique().tolist())}")

# Create Excel workbook
output_file = r"D:\Inventory 10-26-26\Warehouse_Inventory_Sept_2026.xlsx"
wb = openpyxl.Workbook()
ws = wb.active
ws.title = "09 02 2026"

# Styles
title_font = Font(name='Arial', size=14, bold=True)
section_font = Font(name='Arial', size=11, bold=True)
header_fill = PatternFill(start_color="CCCCCC", end_color="CCCCCC", fill_type="solid")
header_font = Font(name='Arial', size=10, bold=True)
center_align = Alignment(horizontal='center', vertical='center', wrap_text=True)
left_align = Alignment(horizontal='left', vertical='center')
number_align = Alignment(horizontal='right', vertical='center')
thin_border = Border(
    left=Side(style='thin'),
    right=Side(style='thin'),
    top=Side(style='thin'),
    bottom=Side(style='thin')
)

# Title
ws['A1'] = "Warehouse Onhand Inventory"
ws['A1'].font = title_font

# Location
ws['A2'] = "Local"

# Date
ws['A3'] = "As of September 02, 2026"

# Start writing data
current_row = 5

# Header row for column structure
header_row = current_row
ws[f'A{header_row}'] = "Department"
ws[f'B{header_row}'] = "REGULAR"
ws[f'C{header_row}'] = "REGULAR"
ws[f'D{header_row}'] = "SALE"
ws[f'E{header_row}'] = "SALE"
ws[f'F{header_row}'] = "SMART BUYS"
ws[f'G{header_row}'] = "SMART BUYS"
ws[f'H{header_row}'] = "Total"
ws[f'I{header_row}'] = "Total"

for col in range(1, 10):
    ws.cell(header_row, col).font = header_font
    ws.cell(header_row, col).fill = header_fill
    ws.cell(header_row, col).border = thin_border
    ws.cell(header_row, col).alignment = center_align

# Sub-header row (Qty, Amount)
current_row += 1
sub_headers = ['', 'Qty', 'Amount', 'Qty', 'Amount', 'Qty', 'Amount', 'Qty', 'Amount']
for col_idx, sub_h in enumerate(sub_headers, 1):
    if sub_h:
        cell = ws.cell(current_row, col_idx)
        cell.value = sub_h
        cell.font = header_font
        cell.fill = header_fill
        cell.border = thin_border
        cell.alignment = center_align

current_row += 1

# Data rows - for each unique facility group
facilities = sorted(agg['Facility'].unique())
for facility_idx, facility in enumerate(facilities):
    # Facility section header
    ws[f'A{current_row}'] = f"{facility}:"
    ws[f'A{current_row}'].font = section_font
    current_row += 2  # Skip blank row

    # Department rows for this facility
    facility_data = agg[agg['Facility'] == facility].sort_values('Department')
    for _, row in facility_data.iterrows():
        # Department name in column A
        ws[f'A{current_row}'] = row['Department']
        ws[f'A{current_row}'].border = thin_border
        ws[f'A{current_row}'].alignment = left_align

        # Put inventory in REGULAR Qty (B) and Total Qty (H)
        inventory_qty = int(row['Inventory']) if pd.notna(row['Inventory']) else 0

        ws[f'B{current_row}'] = inventory_qty  # REGULAR Qty
        ws[f'C{current_row}'] = 0              # REGULAR Amount
        ws[f'D{current_row}'] = 0              # SALE Qty
        ws[f'E{current_row}'] = 0              # SALE Amount
        ws[f'F{current_row}'] = 0              # SMART BUYS Qty
        ws[f'G{current_row}'] = 0              # SMART BUYS Amount
        ws[f'H{current_row}'] = inventory_qty  # Total Qty
        ws[f'I{current_row}'] = 0              # Total Amount

        # Format cells
        for col in range(1, 10):
            cell = ws.cell(current_row, col)
            cell.border = thin_border
            if col > 1:
                cell.alignment = number_align
                cell.number_format = '0'

        current_row += 1

    # Blank row between facilities
    current_row += 1

# Set column widths
ws.column_dimensions['A'].width = 25
for col in ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I']:
    ws.column_dimensions[col].width = 15

# Save file
wb.save(output_file)
print(f"Excel created: {output_file}")
print(f"Sheet: 09 02 2026")
print(f"Total rows: {current_row}")
