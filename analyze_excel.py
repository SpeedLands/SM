import openpyxl
import sys

file_path = "Datos transformados .xlsx"

try:
    # Load the workbook
    wb = openpyxl.load_workbook(file_path)
    sheet = wb.active
    
    print(f"Sheet Name: {sheet.title}")
    
    # Get headers (first row)
    headers = [cell.value for cell in sheet[1]]
    print("Columns:")
    print(headers)
    
    # Print first 5 rows of data
    print("\nFirst 5 rows:")
    for i, row in enumerate(sheet.iter_rows(min_row=2, max_row=6, values_only=True), start=1):
        print(f"Row {i}: {row}")
        
except Exception as e:
    print(f"Error reading file: {e}")
