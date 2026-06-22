<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Beout_OS Admin Login</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
      tailwind.config = {
        theme: {
          extend: {
            "colors": {
                "primary": "#6366F1",
                "background": "#FAFAFA",
                "surface": "#FFFFFF",
                "on-surface": "#111827",
                "on-surface-variant": "#6B7280",
                "outline-variant": "#E5E7EB",
                "primary-container": "#6366F1",
                "on-primary-container": "#FFFFFF",
                "error": "#DC2626"
            },
            "borderRadius": {
                    "DEFAULT": "0.125rem",
                    "lg": "12px",
                    "xl": "0.5rem",
                    "full": "0.75rem"
            },
            "spacing": {
                    "xl": "40px",
                    "md": "16px",
                    "margin-safe": "24px",
                    "lg": "24px",
                    "gutter": "16px",
                    "unit": "4px",
                    "xs": "4px",
                    "sm": "8px"
            },
            "fontFamily": {
                    "sans": [
                            "DM Sans",
                            "sans-serif"
                    ],
                    "display": [
                            "General Sans",
                            "sans-serif"
                    ],
                    "code-sm": [
                            "DM Sans"
                    ],
                    "body-sm": [
                            "DM Sans"
                    ],
                    "headline-md": [
                            "General Sans"
                    ],
                    "headline-lg": [
                            "General Sans"
                    ],
                    "code-md": [
                            "DM Sans"
                    ],
                    "body-md": [
                            "DM Sans"
                    ],
                    "label-caps": [
                            "DM Sans"
                    ]
            },
            "fontSize": {
                    "code-sm": [
                            "11px",
                            {
                                    "lineHeight": "16px",
                                    "fontWeight": "500"
                            }
                    ],
                    "body-sm": [
                            "12px",
                            {
                                    "lineHeight": "16px",
                                    "fontWeight": "400"
                            }
                    ],
                    "headline-md": [
                            "18px",
                            {
                                    "lineHeight": "24px",
                                    "letterSpacing": "-0.01em",
                                    "fontWeight": "600"
                            }
                    ],
                    "headline-lg": [
                            "32px",
                            {
                                    "lineHeight": "40px",
                                    "letterSpacing": "-0.02em",
                                    "fontWeight": "700"
                            }
                    ],
                    "code-md": [
                            "13px",
                            {
                                    "lineHeight": "20px",
                                    "fontWeight": "400"
                            }
                    ],
                    "body-md": [
                            "14px",
                            {
                                    "lineHeight": "20px",
                                    "fontWeight": "400"
                            }
                    ],
                    "label-caps": [
                            "11px",
                            {
                                    "lineHeight": "16px",
                                    "letterSpacing": "0.05em",
                                    "fontWeight": "700"
                            }
                    ]
            }
          },
        },
      }
    </script>
<link href="https://api.fontshare.com/v2/css?f[]=general-sans@600,700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&amp;display=swap" rel="stylesheet">
</head>
<body class="bg-background text-on-surface min-h-screen flex items-center justify-center p-gutter selection:bg-primary selection:text-white font-sans">
<div class="w-full max-w-[440px]">
<!-- Header Section -->
<div class="text-center mb-xl">
<h1 class="font-display text-headline-lg text-on-surface tracking-tight mb-1">BeOut</h1>
<p class="font-display text-[12px] text-on-surface-variant uppercase tracking-[0.2em] font-semibold mb-lg">OS Admin</p>
<p class="font-sans text-body-md text-on-surface-variant/80">Central Licensing &amp; Update Authority</p>
</div>
<!-- Login Card -->
<div class="bg-surface border border-outline-variant rounded-lg p-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)]">
<!-- Error Alert Box -->
<div id="errorBox" class="hidden bg-red-50 border border-red-200 text-error rounded-lg p-md text-body-md mb-lg font-medium text-center"></div>

<form id="loginForm" onsubmit="handleLogin(event)" class="space-y-lg">
<!-- Email Field -->
<div>
<label class="block font-sans text-label-caps text-on-surface-variant mb-sm uppercase" for="email">Operator Identity</label>
<div class="relative">
<span class="absolute inset-y-0 left-0 flex items-center pl-md">
<svg class="w-5 h-5 text-on-surface-variant shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
</span>
<input class="block w-full bg-surface border border-outline-variant rounded-lg pl-[48px] pr-md py-md font-sans text-body-md text-on-surface placeholder-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" id="email" name="email" placeholder="admin@beout.os" required="" type="email" value="admin@beout.os">
</div>
</div>
<!-- Password Field -->
<div>
<label class="block font-sans text-label-caps text-on-surface-variant mb-sm uppercase" for="password">Access Key</label>
<div class="relative">
<span class="absolute inset-y-0 left-0 flex items-center pl-md">
<svg class="w-5 h-5 text-on-surface-variant shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m-1.5 6l-1.5 1.5-1.5-1.5L11 16.5 9.5 15l-1.5 1.5H5v-3l6.5-6.5A4 4 0 1118 8a4 4 0 01-3.5 7z" /></svg>
</span>
<input class="block w-full bg-surface border border-outline-variant rounded-lg pl-[48px] pr-md py-md font-sans text-body-md text-on-surface placeholder-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" id="password" name="password" placeholder="••••••••••••" required="" type="password">
</div>
</div>
<!-- Submit Button -->
<button class="w-full flex justify-center items-center gap-sm bg-[#6366F1] text-white py-[14px] rounded-lg font-sans text-body-md font-bold uppercase tracking-wider hover:bg-[#5558E3] active:scale-[0.98] transition-all shadow-sm" type="submit">
<span>Authenticate</span>
<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
</button>
</form>
<!-- Telemetry Footer -->
<div class="mt-xl pt-lg border-t border-outline-variant flex justify-between items-center">
<span class="font-sans text-code-sm text-on-surface-variant flex items-center gap-xs">
<span class="w-2 h-2 rounded-full bg-primary block animate-pulse"></span>
                    Node Connected
                </span>
<span class="font-sans text-code-sm text-on-surface-variant/60 font-medium">v1.0.0</span>
</div>
</div>
</div>

<script>
async function handleLogin(e) {
    e.preventDefault();
    const password = document.getElementById('password').value;
    const errorBox = document.getElementById('errorBox');
    errorBox.classList.add('hidden');

    try {
        const res = await fetch('/api/admin/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password })
        });
        
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            window.location.reload();
        } else {
            errorBox.textContent = data.error || 'Authentication failed.';
            errorBox.classList.remove('hidden');
        }
    } catch (err) {
        errorBox.textContent = 'Server connection error.';
        errorBox.classList.remove('hidden');
    }
}
</script>
</body></html>
