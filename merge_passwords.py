import openpyxl
import json
import sys

excel_path = "Datos transformados .xlsx"
json_path = "extracted_data.json"

try:
    # 1. Read Excel Data
    print(f"Reading {excel_path}...")
    wb = openpyxl.load_workbook(excel_path)
    sheet = wb.active
    
    # Headers are in row 1: 'Nombre ', 'Correo', 'Teléfono ', 'Contraseña '
    # We need to map headers to column indices
    headers = {cell.value: i for i, cell in enumerate(sheet[1])}
    
    email_col_idx = headers.get('Correo')
    password_col_idx = headers.get('Contraseña ')
    
    if email_col_idx is None or password_col_idx is None:
        print(f"Error: Could not find required columns 'Correo' and 'Contraseña ' in headers: {list(headers.keys())}")
        sys.exit(1)

    # Create a dictionary of email -> password from Excel
    excel_passwords = {}
    row_count = 0
    for row in sheet.iter_rows(min_row=2, values_only=True):
        email = row[email_col_idx]
        password = row[password_col_idx]
        
        if email and password: # Only include if both exist
            excel_passwords[str(email).strip()] = str(password).strip()
            row_count += 1
            
    print(f"Found {len(excel_passwords)} user credentials in Excel.")

    # 2. Read JSON Data
    print(f"Reading {json_path}...")
    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    if 'users' not in data:
        print("Error: JSON file does not contain a 'users' key.")
        sys.exit(1)
        
    # 3. Update Passwords
    updated_count = 0
    users = data['users']
    
    for user in users:
        email = user.get('email')
        if email and email in excel_passwords:
            new_password = excel_passwords[email]
            if user.get('password') != new_password:
                user['password'] = new_password
                updated_count += 1
                # print(f"Updated password for {email}")

    print(f"Updated passwords for {updated_count} users.")

    # 4. Save JSON Data
    print(f"Saving updated data to {json_path}...")
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4)
        
    print("Done.")

except Exception as e:
    print(f"An error occurred: {e}")
    sys.exit(1)
