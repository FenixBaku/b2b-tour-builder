<?php
/** Shared BB head: fonts + global.css + optional Tailwind CDN */
$bb_page_title = $bb_page_title ?? 'B2B Tour Builder';
$bb_use_tailwind = $bb_use_tailwind ?? true;
?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars($bb_page_title, ENT_QUOTES, 'UTF-8') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="/bb/global.css" />
<?php if ($bb_use_tailwind): ?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        bb: {
          primary: '#0c4a6e',
          secondary: '#0369a1',
          muted: '#64748b'
        }
      },
      fontFamily: {
        sans: ['Montserrat', 'Segoe UI', 'sans-serif']
      },
      borderRadius: {
        bb: '12px'
      }
    }
  }
};
</script>
<?php endif; ?>
