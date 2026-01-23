import openpyxl
import json
import sys

excel_path = "Datos transformados .xlsx"
json_path = "extracted_data.json"

try:
    # 1. Read Excel Emails
    print(f"Reading {excel_path}...")
    wb = openpyxl.load_workbook(excel_path)
    sheet = wb.active
    
    headers = {cell.value: i for i, cell in enumerate(sheet[1])}
    email_col_idx = headers.get('Correo')
    
    if email_col_idx is None:
        print("Error: 'Correo' column not found.")
        sys.exit(1)

    excel_emails = set()
    for row in sheet.iter_rows(min_row=2, values_only=True):
        email = row[email_col_idx]
        if email:
            excel_emails.add(str(email).strip())
            
    print(f"Total emails in Excel: {len(excel_emails)}")

    # 2. Read JSON Emails
    print(f"Reading {json_path}...")
    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    json_emails = set()
    for user in data.get('users', []):
        if user.get('email'):
            json_emails.add(user.get('email').strip())
            
    print(f"Total users in JSON: {len(json_emails)}")

    # 3. Find Missing
    missing_in_json = excel_emails - json_emails
    
    print("\n--- Emails in Excel BUT NOT in JSON (Missing) ---")
    print(f"Count: {len(missing_in_json)}")
    for email in missing_in_json:
        print(f"MISSING: {email}")

except Exception as e:
    print(f"An error occurred: {e}")
