import openpyxl
import csv
import sys
import os

def convert_xlsx_to_csv(xlsx_path, csv_path):
    if not os.path.exists(xlsx_path):
        print(f"Error: File {xlsx_path} not found.")
        sys.exit(1)

    try:
        wb = openpyxl.load_workbook(xlsx_path, read_only=True, data_only=True)
        sheet = wb.active
        
        with open(csv_path, 'w', newline='', encoding='utf-8') as f:
            c = csv.writer(f)
            for r in sheet.iter_rows(values_only=True):
                # Filter out completely empty rows
                if any(cell is not None for cell in r):
                    c.writerow(r)
        
        print(f"Successfully converted {xlsx_path} to {csv_path}")
    except Exception as e:
        print(f"Error converting file: {e}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python convert_xlsx_to_csv.py <input_xlsx> <output_csv>")
        sys.exit(1)
    
    convert_xlsx_to_csv(sys.argv[1], sys.argv[2])
