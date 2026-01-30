import pandas as pd
import os

files = [
    "DIRECTORIO ALUMNOS 3°A  T.M..xlsm",
    "DIRECTORIO ALUMNOS 3°B  T.M..xlsm",
    "DIRECTORIO ALUMNOS 3°C T.M..xlsm",
    "DIRECTORIO ALUMNOS 3°D T.M..xlsm"
]

for file in files:
    print(f"\n--- FILE: {file} ---")
    try:
        # Load the whole sheet
        df = pd.read_excel(file, sheet_name=0, header=None)
        
        # Look for rows that have more than 1 non-NaN value
        # This helps skip the "title" rows
        data_found = False
        for i, row in df.iterrows():
            if row.count() > 5: # Assuming at least 6 columns of data
                print(f"Potential Header at row {i}:")
                print(row.values)
                # Print next 5 rows
                print("Sample data:")
                print(df.iloc[i+1:i+6].values)
                data_found = True
                break
        
        if not data_found:
            print("No dense data found in first 100 rows.")
            # Print first 20 rows anyway to see what's there
            print("First 20 rows:")
            print(df.head(20).values)

    except Exception as e:
        print(f"Error reading {file}: {e}")
