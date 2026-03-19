import sys
import codecs

with codecs.open("c:/Users/USER/Desktop/book-app/public/css/premium_new.css", "r", "utf-8") as f:
    lines = f.readlines()

# The error starts exactly after `.nav-icon-link:hover` block.
# Let's find the `transform: translateY(-1px);` space followed by `}`.
target_idx = -1
for i, line in enumerate(lines):
    if "transform: translateY(-1px);" in line:
        target_idx = i
        break

if target_idx == -1:
    print("Could not find start index")
    sys.exit(1)

# The `}` is at `target_idx + 1`
start_idx = target_idx + 2

# We need to find where the correct dropdown CSS begins, or just the end of the error chunk.
# The error chunk is duplicated till line 427 which is `/* Dark mode adjustments */` or similar.
# Let's find the original start of the dropdown part:
# Wait, let's just use the `lines` index directly if we know it.
end_idx = start_idx
for i in range(start_idx, len(lines)):
    if "/* Align User Profile/Icon sections dropdowns to the right */" in lines[i]:
        end_idx = i
        break

if end_idx == start_idx:
    print("Could not find end index")
    sys.exit(1)

new_lines = lines[:start_idx] + [
'/* Fallback / ensure no clipping */\n',
'.nav-item.dropdown, .dropdown { position: relative !important; overflow: visible !important; }\n',
'/* Redesigned Dropdown Menus - Optimized For Stability */\n',
'.navbar-premium .dropdown-menu {\n',
'    position: absolute !important;\n',
'    top: 100%;\n',
'    left: 0;\n',
'    border-radius: 18px !important;\n',
'    padding: 12px !important;\n',
'    margin-top: 12px !important;\n',
'    background: var(--nav-bg) !important;\n',
'    backdrop-filter: blur(20px);\n',
'    border: 1px solid rgba(0, 0, 0, 0.08) !important;\n',
'    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12) !important;\n',
'    z-index: 10000 !important;\n',
'    min-width: 220px !important;\n',
'    display: block;\n',
'    visibility: hidden;\n',
'    opacity: 0;\n',
'    pointer-events: none;\n',
'    transform: translateY(10px) scale(0.98);\n',
'    transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), visibility 0.3s;\n',
'}\n',
'/* Premium pop animation when shown */\n',
'.navbar-premium .dropdown-menu.show {\n',
'    visibility: visible !important;\n',
'    opacity: 1 !important;\n',
'    pointer-events: auto !important;\n',
'    transform: translateY(0) scale(1) !important;\n',
'}\n',
'/* Dark mode adjustments */\n',
'[data-bs-theme=\'dark\'] .navbar-premium .dropdown-menu {\n',
'    background: rgba(15, 23, 42, 0.98) !important;\n',
'    border: 1px solid rgba(255, 255, 255, 0.08) !important;\n',
'}\n',
] + lines[end_idx:]

with codecs.open("c:/Users/USER/Desktop/book-app/public/css/premium_new.css", "w", "utf-8") as f:
    f.writelines(new_lines)

print("Fix applied successfully!")
