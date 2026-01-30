import openpyxl

try:
    wb = openpyxl.load_workbook("DIRECTORIO ALUMNOS 3°A  T.M..xlsm")
    print("Success load!")
except Exception as e:
    print(f"Error: {e}")
