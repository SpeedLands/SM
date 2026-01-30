import pandas as pd
import sys

file_path = "DIRECTORIO ALUMNOS 3°A  T.M..xlsm"

try:
    df = pd.read_excel(file_path, sheet_name=0)
    print("Columns:", df.columns.tolist())
    # Display rows without truncation
    pd.set_option('display.max_columns', None)
    pd.set_option('display.max_rows', None)
    pd.set_option('display.width', None)
    print(df.head(20))
except Exception as e:
    print(f"Error: {e}")
