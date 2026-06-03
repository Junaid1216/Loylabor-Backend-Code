import os
import re

directory = r"c:\xampp\htdocs\homeservices-12Mar2026"
colors_to_replace = [r'#6777ef', r'#6777EF', r'#2046da', r'#2046DA', r'rgb\(8, 124, 192\)', r'#2a9cf5']
new_color = '#FE7701'

for root, dirs, files in os.walk(directory):
    if 'node_modules' in root or 'vendor' in root or '.git' in root:
        continue
    for file in files:
        if file.endswith('.css') or file.endswith('.scss') or file.endswith('.js') or file.endswith('.php'):
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                changed = False
                for color in colors_to_replace:
                    if re.search(color, content):
                        content = re.sub(color, new_color, content)
                        changed = True
                
                if changed:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Updated {filepath}")
            except Exception as e:
                print(f"Could not process {filepath}: {e}")

print("Done")
