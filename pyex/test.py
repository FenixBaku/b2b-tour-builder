import pandas as pd

# ... (oxuma hissəsi eynidir)

df = pd.read_excel('TRANSFER.xlsx', sheet_name='Лист1', header=None)
df = df.iloc[5:]  # data başlayan sətir

columns = [
    'duty_code', 'a', 'b', 'service', 'distance', 'start_time', 'duration_mins', 'day_schedule',
    'sedan_4_local', 'sedan_4_english', 'vito_8_local', 'vito_8_english',
    'sprinter_18', 'isuzu_27', 'merc_403_45', 'trivago_54', 'neoplan_48',
    'guides_a', 'guides_o'
]

df.columns = columns

# duty_code sütununu təmizlə və int-ə çevir (boş/NaN olarsa sonra dolduracağıq)
df['duty_code'] = pd.to_numeric(df['duty_code'], errors='coerce')

# Boş və ya NaN olanları avtomatik doldur
if df['duty_code'].isna().any() or (df['duty_code'] <= 0).any():
    print("Bəzi duty_code-lar boşdur və ya 0-dır → avtomatik 1-dən başlayaraq doldurulur.")
    df['duty_code'] = range(1, len(df) + 1)
else:
    # Əgər hamısı dolu və məntiqli id-dirsə, olduğu kimi qalsın
    df['duty_code'] = df['duty_code'].astype(int)

df = df.dropna(subset=['service', 'day_schedule'])  # daha ciddi filtrləmə (istəyə görə dəyiş)

# NaN-ları None-a çevir
df = df.where(pd.notnull(df), None)

# Qalan hissə eynidir...
batch_size = 500
col_str = ', '.join(columns)
table_name = 'transfers'

for start in range(0, len(df), batch_size):
    batch_df = df.iloc[start:start + batch_size]
    file_name = f'insert_{start}.sql'
    
    with open(file_name, 'w', encoding='utf-8') as f:
        for _, row in batch_df.iterrows():
            values = []
            for val in row:
                if val is None:
                    values.append('NULL')
                elif isinstance(val, str):
                    escaped = val.replace("'", "''").replace('\n', '\\n')
                    values.append(f"'{escaped}'")
                else:
                    values.append(str(val))
            val_str = ', '.join(values)
            f.write(f"INSERT INTO {table_name} ({col_str}) VALUES ({val_str});\n")
    
    print(f"{file_name} yaradıldı ({len(batch_df)} sətir).")

print("Bütün SQL insert faylları hazırdır.")