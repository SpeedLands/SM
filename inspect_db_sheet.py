import pandas as pd

file = "DIRECTORIO ALUMNOS 3°A  T.M..xlsm"
df = pd.read_excel(file, sheet_name='BASE DE DATOS')

print("Columns:", df.columns.tolist())
print("\nFirst 5 rows:")
print(df.head(5))
