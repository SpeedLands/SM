import openpyxl

file_path = "Datos transformados .xlsx"
wb = openpyxl.load_workbook(file_path)

for sheet_name in wb.sheetnames:
    sheet = wb[sheet_name]
    headers = [cell.value for cell in sheet[1]]
    print(f"SHEET: {sheet_name} | HEADERS: {headers}")
