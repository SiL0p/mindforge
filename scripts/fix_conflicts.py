from pathlib import Path

files = [
    'src/Controller/AdminController.php',
    'src/Controller/Carriere/DemandeController.php',
    '.env',
    'templates/user/workspace.html.twig',
    'templates/user/guardian/hub.html.twig',
    'templates/admin/partials/sidebar.html.twig',
    'src/Repository/Architect/EmotionLogRepository.php',
    'src/JobAlertBundle/Twig/JobAlertExtension.php',
    'src/JobAlertBundle/Controller/JobAlertNotificationController.php',
    'composer.lock',
]
root = Path(__file__).resolve().parent.parent
edited = []
for f in files:
    p = root / f
    if not p.exists():
        print(f"SKIP (missing): {f}")
        continue
    s = p.read_text(encoding='utf-8')
    if '<<<<<<< HEAD' not in s:
        print(f"OK (no markers): {f}")
        continue
    out = ''
    i = 0
    while True:
        idx = s.find('<<<<<<< HEAD', i)
        if idx == -1:
            out += s[i:]
            break
        out += s[i:idx]
        idx_eq = s.find('=======', idx)
        if idx_eq == -1:
            print(f"Malformed conflict in {f}: no =======")
            out += s[idx:]
            break
        idx_end = s.find('>>>>>>>', idx_eq)
        if idx_end == -1:
            print(f"Malformed conflict in {f}: no >>>>>>>")
            out += s[idx:]
            break
        head_start = idx + len('<<<<<<< HEAD')
        head = s[head_start:idx_eq]
        out += head
        i = idx_end + len('>>>>>>>')
        nl = s.find('\n', i)
        if nl != -1:
            i = nl+1
    p.write_text(out, encoding='utf-8')
    edited.append(f)

print('Edited files:')
for e in edited:
    print(' -', e)
