import pandas as pd

file = "DIRECTORIO ALUMNOS 3°A  T.M..xlsm"
df = pd.read_excel(file, sheet_name='BASE DE DATOS', header=None)

# Find where the headers are
for i, row in df.head(10).iterrows():
    print(f"Row {i}: {list(row)}")
