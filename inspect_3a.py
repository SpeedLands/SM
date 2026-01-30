import pandas as pd

file = "DIRECTORIO ALUMNOS 3°A  T.M..xlsm"
df = pd.read_excel(file, sheet_name=0, header=None)

# Let's print the first 100 rows properly
for i, row in df.head(100).iterrows():
    print(f"Row {i:3}: {list(row)}")
