import os

replacements = {
    'ðŸ‘‹': '&#128075;',
    'à§³': '&#2547;',
    'â€”': '&mdash;',
    'â ³': '&#8987;',
    'âœ…': '<i class="fas fa-check-circle"></i>',
    'â Œ': '<i class="fas fa-times-circle"></i>',
    'Â·': '&middot;',
    'ðŸ†•': 'NEW',
    'ðŸŽ“': '<i class="fas fa-graduation-cap"></i>',
    'ðŸ¤ ': '<i class="fas fa-hands-helping"></i>',
    'ðŸ” ': '<i class="fas fa-search"></i>',
    'ðŸ“…': '<i class="fas fa-calendar"></i>',
    'ðŸ“‹': '<i class="fas fa-clipboard-list"></i>',
    'ðŸ’³': '<i class="fas fa-credit-card"></i>',
    'ðŸ ·ï¸ ': '<i class="fas fa-tags"></i>',
    'âœ•': '&times;',
    'ðŸ—‘ï¸ ': '<i class="fas fa-trash"></i>',
    'â†’': '&rarr;',
    'â”€â”€': '──' # specific fix from dashboard
}

admin_dir = r"c:\Users\Ameer Hamza\Downloads\public_html\public_html\nexopay.com.bd\admin"

for root, dirs, files in os.walk(admin_dir):
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            
            original_content = content
            for old, new in replacements.items():
                content = content.replace(old, new)
                
            if content != original_content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Fixed encoding in {file}")

print("Encoding fix complete.")
