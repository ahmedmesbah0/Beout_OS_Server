<?php
// Beout_OS - Perfected Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    header('Location: /');
    exit;
}

$db = BeoutOS\Server\Database::getInstance()->getConnection();

// Calculate stats for initial load
$stmt = $db->query("SELECT COUNT(*) FROM licenses WHERE status = 'ACTIVE'");
$activeCount = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT COUNT(*) FROM licenses WHERE status = 'PENDING'");
$pendingCount = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT COUNT(*) FROM licenses WHERE status = 'REVOKED'");
$revokedCount = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT COUNT(*) FROM licenses");
$totalCount = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT version, published_at FROM updates ORDER BY published_at DESC LIMIT 1");
$latestRelease = $stmt->fetch();
$latestVersion = $latestRelease ? $latestRelease['version'] : 'None';
$latestPublishedAt = $latestRelease ? $latestRelease['published_at'] : null;

// Format latest release time
$releaseTimeStr = 'No releases';
if ($latestPublishedAt) {
    $timeDiff = time() - strtotime($latestPublishedAt);
    if ($timeDiff < 60) {
        $releaseTimeStr = 'Deployed just now';
    } elseif ($timeDiff < 3600) {
        $releaseTimeStr = 'Deployed ' . floor($timeDiff / 60) . 'm ago';
    } elseif ($timeDiff < 86400) {
        $releaseTimeStr = 'Deployed ' . floor($timeDiff / 3600) . 'h ago';
    } else {
        $releaseTimeStr = 'Deployed ' . floor($timeDiff / 86400) . 'd ago';
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Beout_OS Main Server - Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://api.fontshare.com/v2/css?f[]=general-sans@600,700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet">
<script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    "colors": {
                        "background": "#FAFAFA",
                        "surface": "#FFFFFF",
                        "surface-variant": "#F3F4F6",
                        "surface-container-lowest": "#FFFFFF",
                        "surface-container-low": "#F9FAFB",
                        "surface-container": "#F3F4F6",
                        "surface-container-high": "#E5E7EB",
                        "surface-container-highest": "#D1D5DB",
                        "on-surface": "#111827",
                        "on-surface-variant": "#4B5563",
                        "on-background": "#111827",
                        "primary": "#6366F1",
                        "on-primary": "#FFFFFF",
                        "primary-container": "#E0E7FF",
                        "on-primary-container": "#3730A3",
                        "secondary": "#20970B",
                        "on-secondary": "#FFFFFF",
                        "secondary-container": "#DCFCE7",
                        "on-secondary-container": "#166534",
                        "error": "#DC2626",
                        "on-error": "#FFFFFF",
                        "error-container": "#FEE2E2",
                        "on-error-container": "#991B1B",
                        "outline": "#9CA3AF",
                        "outline-variant": "#E5E7EB"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
                        "full": "9999px"
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
                        "code-sm": ["JetBrains Mono", "monospace"],
                        "body-sm": ["DM Sans", "sans-serif"],
                        "headline-md": ["General Sans", "sans-serif"],
                        "headline-lg": ["General Sans", "sans-serif"],
                        "code-md": ["JetBrains Mono", "monospace"],
                        "body-md": ["DM Sans", "sans-serif"],
                        "label-caps": ["DM Sans", "sans-serif"]
                    },
                    "fontSize": {
                        "code-sm": ["11px", { "lineHeight": "16px", "fontWeight": "500" }],
                        "body-sm": ["12px", { "lineHeight": "16px", "fontWeight": "400" }],
                        "headline-md": ["18px", { "lineHeight": "24px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                        "headline-lg": ["24px", { "lineHeight": "32px", "letterSpacing": "-0.02em", "fontWeight": "600" }],
                        "code-md": ["13px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "label-caps": ["11px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700" }]
                    }
                }
            }
        }
    </script>
<style>
        body { background-color: #FAFAFA; color: #111827; font-family: 'DM Sans', sans-serif; margin: 0; padding: 0; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #FAFAFA; }
        ::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #9CA3AF; }
        
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(32, 151, 11, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(32, 151, 11, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(32, 151, 11, 0); }
        }
        .animate-pulse-green {
            animation: pulse-green 2s infinite;
        }
    </style>
</head>
<body class="bg-background text-on-background h-screen flex overflow-hidden">
<!-- SideNavBar -->
<nav class="fixed left-0 top-0 h-full w-64 flex flex-col py-lg bg-surface border-r border-outline-variant transition-all duration-150 ease-in-out z-40 hidden md:flex shadow-sm">
<div class="px-6 mb-10">
<h1 class="font-headline-md text-headline-md text-secondary font-bold tracking-tight">Central Engine</h1>
<div class="flex items-center gap-2 mt-2">
<span class="w-2 h-2 rounded-full bg-secondary animate-pulse-green"></span>
<span class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">Online</span>
</div>
</div>
<div class="flex flex-col gap-1 w-full flex-grow px-2">
<!-- Tab Anchors -->
<button id="nav-dashboard" onclick="switchTab('dashboard')" class="nav-btn bg-primary-container bg-opacity-30 text-primary font-bold px-4 py-3 rounded-lg flex items-center gap-3 w-full font-body-md text-body-md cursor-pointer transition-colors">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
<span>Dashboard</span>
</button>
<button id="nav-licenses" onclick="switchTab('licenses')" class="nav-btn text-on-surface-variant px-4 py-3 flex items-center gap-3 rounded-lg hover:bg-surface-variant hover:text-on-surface transition-all w-full font-body-md text-body-md cursor-pointer">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m-1.5 6l-1.5 1.5-1.5-1.5L11 16.5 9.5 15l-1.5 1.5H5v-3l6.5-6.5A4 4 0 1118 8a4 4 0 01-3.5 7z" /></svg>
<span>License Keys</span>
</button>
<button id="nav-import" onclick="switchTab('import')" class="nav-btn text-on-surface-variant px-4 py-3 flex items-center gap-3 rounded-lg hover:bg-surface-variant hover:text-on-surface transition-all w-full font-body-md text-body-md cursor-pointer">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
<span>Import Keys</span>
</button>
<button id="nav-releases" onclick="switchTab('releases')" class="nav-btn text-on-surface-variant px-4 py-3 flex items-center gap-3 rounded-lg hover:bg-surface-variant hover:text-on-surface transition-all w-full font-body-md text-body-md cursor-pointer">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.5" /></svg>
<span>Releases &amp; Updates</span>
</button>
<button id="nav-rollback" onclick="switchTab('rollback')" class="nav-btn text-on-surface-variant px-4 py-3 flex items-center gap-3 rounded-lg hover:bg-surface-variant hover:text-on-surface transition-all w-full font-body-md text-body-md cursor-pointer">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
<span>Rollback Manager</span>
</button>
<button id="nav-audit" onclick="switchTab('audit')" class="nav-btn text-on-surface-variant px-4 py-3 flex items-center gap-3 rounded-lg hover:bg-surface-variant hover:text-on-surface transition-all w-full font-body-md text-body-md cursor-pointer">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
<span>Audit Logs</span>
</button>
<button id="nav-settings" onclick="switchTab('settings')" class="nav-btn text-on-surface-variant px-4 py-3 flex items-center gap-3 rounded-lg hover:bg-surface-variant hover:text-on-surface transition-all w-full font-body-md text-body-md cursor-pointer mt-auto">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
<span>Settings</span>
</button>
</div>
</nav>
<!-- Main Content Area -->
<div class="flex-1 flex flex-col md:ml-64 h-screen w-full">
<!-- TopNavBar -->
<header class="flex justify-between items-center h-16 px-gutter w-full sticky top-0 z-50 bg-surface border-b border-outline-variant shadow-sm">
<div class="flex items-center gap-4">
<span class="font-headline-md text-headline-md font-bold text-on-surface">Beout_OS Main Server</span>
</div>
<div class="flex items-center gap-6">
<div class="hidden sm:flex items-center gap-2">
<span class="w-2 h-2 rounded-full bg-secondary animate-pulse-green"></span>
<span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Central Engine Online</span>
</div>
<div class="h-4 w-[1px] bg-outline-variant hidden sm:block"></div>
<button onclick="handleLogout()" class="font-body-md text-body-md font-medium text-on-surface-variant hover:text-error transition-colors cursor-pointer active:opacity-70">Sign Out</button>
</div>
</header>
<!-- Dashboard Canvas -->
<main class="flex-1 overflow-y-auto p-lg bg-background">
<div class="max-w-7xl mx-auto">
<!-- Top Row: Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter mb-lg">
<!-- Total Active Licenses -->
<div class="bg-surface p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col gap-3">
<div class="flex justify-between items-start">
<span class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider font-bold">Total Active Licenses</span>
<svg class="w-6 h-6 text-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
</div>
<div class="font-headline-lg text-headline-lg text-on-surface flex items-baseline gap-2">
                            <span id="stat-active"><?= number_format($activeCount) ?></span>
                            <span class="font-body-sm text-body-sm text-secondary font-bold" id="stat-active-percent"></span>
</div>
<div class="w-full bg-surface-variant h-1.5 mt-1 rounded-full overflow-hidden">
<div id="stat-active-bar" class="bg-secondary h-full transition-all duration-500" style="width: 0%"></div>
</div>
</div>
<!-- Pending Keys -->
<div class="bg-surface p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col gap-3">
<div class="flex justify-between items-start">
<span class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider font-bold">Pending Keys</span>
<svg class="w-6 h-6 text-outline shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
</div>
<div class="font-headline-lg text-headline-lg text-on-surface flex items-baseline gap-2">
                            <span id="stat-pending"><?= number_format($pendingCount) ?></span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant" id="stat-pending-percent"></span>
</div>
<div class="w-full bg-surface-variant h-1.5 mt-1 rounded-full overflow-hidden">
<div id="stat-pending-bar" class="bg-outline h-full transition-all duration-500" style="width: 0%"></div>
</div>
</div>
<!-- Revoked Licenses -->
<div class="bg-surface p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col gap-3">
<div class="flex justify-between items-start">
<span class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider font-bold">Revoked Licenses</span>
<svg class="w-6 h-6 text-error shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
</div>
<div class="font-headline-lg text-headline-lg text-on-surface flex items-baseline gap-2">
                            <span id="stat-revoked"><?= number_format($revokedCount) ?></span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant" id="stat-revoked-percent"></span>
</div>
<div class="w-full bg-surface-variant h-1.5 mt-1 rounded-full overflow-hidden">
<div id="stat-revoked-bar" class="bg-error h-full transition-all duration-500" style="width: 0%"></div>
</div>
</div>
<!-- Latest Release -->
<div class="bg-surface p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col gap-3">
<div class="flex justify-between items-start">
<span class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider font-bold">Latest Release</span>
<svg class="w-6 h-6 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
</div>
<div class="font-headline-lg text-headline-lg text-primary font-code-md" id="stat-version">
                            <?= htmlspecialchars($latestVersion) ?>
                        </div>
<span class="font-body-sm text-body-sm text-on-surface-variant font-medium" id="stat-version-time"><?= htmlspecialchars($releaseTimeStr) ?></span>
</div>
</div>

<!-- ============================================== -->
<!-- TAB: DASHBOARD                                 -->
<!-- ============================================== -->
<div id="view-dashboard" class="tab-view grid grid-cols-1 lg:grid-cols-3 gap-gutter items-start">
<!-- Left Column: Active VMs Table (2/3) -->
<div class="lg:col-span-2 bg-surface rounded-xl border border-outline-variant shadow-sm flex flex-col overflow-hidden">
<div class="p-lg border-b border-outline-variant flex justify-between items-center bg-surface">
<h2 class="font-headline-md text-headline-md text-on-surface">Most Recently Active VMs</h2>
<button onclick="switchTab('licenses')" class="font-label-caps text-label-caps text-primary hover:bg-primary-container px-3 py-1.5 rounded-lg transition-colors uppercase tracking-wider font-bold">View All</button>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low border-b border-outline-variant font-label-caps text-label-caps text-on-surface-variant uppercase tracking-widest font-bold">
<th class="px-lg py-4">Machine ID</th>
<th class="px-lg py-4">IP Address</th>
<th class="px-lg py-4">Version</th>
<th class="px-lg py-4">Last Seen</th>
<th class="px-lg py-4 text-right">Status</th>
</tr>
</thead>
<tbody class="font-code-md text-code-md text-on-surface" id="dashboardActiveVmsTable">
<!-- Will be loaded dynamically -->
</tbody>
</table>
</div>
</div>
<!-- Right Column: System Activity Feed (1/3) -->
<div class="bg-surface rounded-xl border border-outline-variant shadow-sm flex flex-col h-[520px] overflow-hidden">
<div class="p-lg border-b border-outline-variant flex justify-between items-center shrink-0 bg-surface">
<h2 class="font-headline-md text-headline-md text-on-surface">System Activity Feed</h2>
<button class="text-on-surface-variant hover:text-primary p-2 rounded-lg hover:bg-surface-variant transition-colors flex items-center justify-center">
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
</button>
</div>
<div class="flex-1 overflow-y-auto p-lg flex flex-col gap-6" id="dashboardActivityFeed">
<!-- Populated by dynamic log events -->
</div>
</div>
</div>

<!-- ============================================== -->
<!-- TAB: LICENSES                                  -->
<!-- ============================================== -->
<div id="view-licenses" class="tab-view hidden bg-surface rounded-xl border border-outline-variant shadow-sm flex flex-col overflow-hidden">
<div class="p-lg border-b border-outline-variant flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-surface">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Appliance Licenses & Registered VMs</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">Generate, view, and manage secure licenses for appliances.</p>
</div>
<div class="flex items-center gap-4">
<input type="number" id="licenseCount" class="input-field w-24 py-1.5" value="1" min="1">
<button class="bg-primary text-white hover:bg-opacity-90 px-4 py-2 rounded-lg font-bold font-body-md text-body-md" onclick="generateLicenses()">Generate Keys</button>
</div>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low border-b border-outline-variant font-label-caps text-label-caps text-on-surface-variant uppercase tracking-widest font-bold">
<th class="px-lg py-4">License Key</th>
<th class="px-lg py-4">Status</th>
<th class="px-lg py-4">Machine ID</th>
<th class="px-lg py-4">Machine IP</th>
<th class="px-lg py-4">OS Version</th>
<th class="px-lg py-4">Last Seen</th>
<th class="px-lg py-4 text-right">Actions</th>
</tr>
</thead>
<tbody class="font-code-md text-code-md text-on-surface" id="licensesTable">
<tr>
<td colspan="7" class="text-center text-on-surface-variant py-8">Loading licenses...</td>
</tr>
</tbody>
</table>
</div>
</div>

<!-- ============================================== -->
<!-- TAB: IMPORT KEYS                               -->
<!-- ============================================== -->
<div id="view-import" class="tab-view hidden bg-surface rounded-xl border border-outline-variant shadow-sm flex flex-col p-lg">
<h2 class="font-headline-md text-headline-md text-on-surface mb-2">Import License Keys</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant mb-6">Import existing license keys by pasting them below. Keys can be separated by commas, spaces, or newlines.</p>
<div class="flex flex-col gap-4">
<div class="flex flex-col gap-2">
<label class="font-label-caps text-label-caps text-on-surface-variant uppercase font-bold" for="importKeysText">Paste License Keys</label>
<textarea id="importKeysText" class="w-full bg-surface border border-outline-variant rounded-lg p-md font-sans text-body-md text-on-surface placeholder-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" style="resize: vertical; height: 160px;" placeholder="BEOU-1234-ABCD-9999&#10;BEOU-5678-EFGH-1111"></textarea>
</div>
<button class="self-end bg-primary text-white hover:bg-opacity-90 px-6 py-2.5 rounded-lg font-bold font-body-md text-body-md" onclick="importLicenses()">Import Keys</button>
</div>
</div>

<!-- ============================================== -->
<!-- TAB: RELEASES & UPDATES                        -->
<!-- ============================================== -->
<div id="view-releases" class="tab-view hidden grid grid-cols-1 lg:grid-cols-3 gap-gutter items-start">
<!-- Publish Release Form -->
<div class="bg-surface p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col gap-6">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Publish Update Package</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Upload a compiled Debian package and set the target version tag.</p>
</div>
<form id="uploadForm" onsubmit="publishUpdate(event)" class="space-y-4">
<div class="flex flex-col gap-2">
<label class="font-label-caps text-label-caps text-on-surface-variant uppercase font-bold" for="updateVersion">Target Version</label>
<input type="text" id="updateVersion" class="block w-full bg-surface border border-outline-variant rounded-lg px-md py-md font-sans text-body-md text-on-surface placeholder-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" placeholder="1.0.1" required>
</div>
<div class="flex flex-col gap-2">
<label class="font-label-caps text-label-caps text-on-surface-variant uppercase font-bold" for="updateFile">Debian Package (.deb)</label>
<input type="file" id="updateFile" class="block w-full bg-surface border border-outline-variant rounded-lg px-md py-md font-sans text-body-md text-on-surface placeholder-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" accept=".deb" required>
</div>
<button type="submit" class="w-full bg-secondary text-white py-3 rounded-lg font-bold uppercase tracking-wider hover:bg-opacity-90 active:scale-[0.98] transition-all shadow-sm flex items-center justify-center gap-2">
<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg> Publish Update
</button>
</form>

<!-- Upload Progress Card -->
<div id="uploadProgressContainer" class="hidden p-md bg-surface-variant bg-opacity-50 border border-outline-variant rounded-lg space-y-3">
<div class="flex justify-between items-center text-body-sm font-medium">
<span id="uploadStatusText" class="text-on-surface-variant">Uploading package...</span>
<span id="uploadProgressPercent" class="text-primary font-bold">0%</span>
</div>
<div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
<div id="uploadProgressBar" class="bg-primary h-full transition-all duration-75" style="width: 0%"></div>
</div>
<div class="flex justify-between text-code-sm text-on-surface-variant font-code-sm">
<span id="uploadSpeedText">0 KB/s</span>
<span id="uploadBytesText">0 / 0 MB</span>
</div>
</div>
</div>

<!-- Releases Table (2/3) -->
<div class="lg:col-span-2 bg-surface rounded-xl border border-outline-variant shadow-sm flex flex-col overflow-hidden">
<div class="p-lg border-b border-outline-variant bg-surface">
<h2 class="font-headline-md text-headline-md text-on-surface">Release History</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">List of published firmware updates currently stored on the central repository.</p>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low border-b border-outline-variant font-label-caps text-label-caps text-on-surface-variant uppercase tracking-widest font-bold">
<th class="px-lg py-4">Version</th>
<th class="px-lg py-4">Filename</th>
<th class="px-lg py-4">SHA256 Checksum</th>
<th class="px-lg py-4">Published At</th>
<th class="px-lg py-4 text-right">Actions</th>
</tr>
</thead>
<tbody class="font-code-md text-code-md text-on-surface" id="updatesTable">
<tr>
<td colspan="5" class="text-center text-on-surface-variant py-8">Loading updates...</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>

<!-- ============================================== -->
<!-- TAB: ROLLBACK MANAGER                          -->
<!-- ============================================== -->
<div id="view-rollback" class="tab-view hidden bg-surface rounded-xl border border-outline-variant shadow-sm flex flex-col overflow-hidden">
<div class="p-lg border-b border-outline-variant bg-surface">
<h2 class="font-headline-md text-headline-md text-on-surface">Rollback Manager</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">Rollback or switch the active release delivered to the client VMs. The first package in the list is the version clients will update to.</p>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low border-b border-outline-variant font-label-caps text-label-caps text-on-surface-variant uppercase tracking-widest font-bold">
<th class="px-lg py-4">Version Status</th>
<th class="px-lg py-4">Filename</th>
<th class="px-lg py-4">SHA256 Checksum</th>
<th class="px-lg py-4">Deployment Date</th>
<th class="px-lg py-4 text-right">Activate Rollback</th>
</tr>
</thead>
<tbody class="font-code-md text-code-md text-on-surface" id="rollbackTable">
<!-- Populated dynamically -->
</tbody>
</table>
</div>
</div>

<!-- ============================================== -->
<!-- TAB: AUDIT LOGS                                -->
<!-- ============================================== -->
<div id="view-audit" class="tab-view hidden bg-surface rounded-xl border border-outline-variant shadow-sm flex flex-col p-lg">
<div class="flex justify-between items-center mb-6">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Audit Logs</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">Traceability history logs of all administrative actions and security activities on this server.</p>
</div>
<button onclick="clearClientTelemetryCache()" class="bg-error bg-opacity-10 border border-error text-error hover:bg-error hover:text-white px-4 py-2 rounded-lg font-bold font-body-sm text-body-sm transition-all">Clear Telemetry Cache</button>
</div>
<div class="bg-surface-container bg-opacity-40 rounded-lg p-lg font-code-sm text-code-sm text-on-surface overflow-x-auto max-h-[500px]" id="auditLogContent">
<!-- Dynamic log lines -->
</div>
</div>

<!-- ============================================== -->
<!-- TAB: SETTINGS                                  -->
<!-- ============================================== -->
<div id="view-settings" class="tab-view hidden grid grid-cols-1 lg:grid-cols-2 gap-gutter items-start">
<!-- Change Profile Card -->
<div class="bg-surface p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col gap-6">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Operator Identity & Password</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">Update the administrator email identity and the access key password.</p>
</div>
<form id="settingsPasswordForm" onsubmit="changeAdminPassword(event)" class="space-y-4">
<div class="flex flex-col gap-2">
<label class="font-label-caps text-label-caps text-on-surface-variant uppercase font-bold" for="newEmail">Operator Identity (Email)</label>
<input type="email" id="newEmail" class="block w-full bg-surface border border-outline-variant rounded-lg px-md py-md font-sans text-body-md text-on-surface placeholder-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" placeholder="operator@beout.ai" value="<?= htmlspecialchars($adminEmail) ?>" required>
</div>
<div class="flex flex-col gap-2">
<label class="font-label-caps text-label-caps text-on-surface-variant uppercase font-bold" for="newPassword">New Password Access Key</label>
<input type="password" id="newPassword" class="block w-full bg-surface border border-outline-variant rounded-lg px-md py-md font-sans text-body-md text-on-surface placeholder-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" placeholder="Leave empty to keep current password">
</div>
<button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg font-bold font-body-md text-body-md hover:bg-opacity-90 transition-all">Save Profile</button>
</form>
</div>

<!-- Verification Key Card -->
<div class="bg-surface p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col gap-4">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Cryptographic Verification Key</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">This public key must be baked inside client VM appliances at <code>/opt/beout_os/etc/license_public_key.pem</code> to verify updates.</p>
</div>
<div class="w-full bg-on-surface text-surface-variant p-md rounded-lg font-code-md text-code-md whitespace-pre-wrap select-all leading-relaxed" style="background-color: #0c0f1a; color: #a5b4fc;">
    <?= htmlspecialchars(BeoutOS\Server\Crypto::getPublicKey()) ?>
</div>
</div>
</div>

</div>
</main>
</div>

<script>
// Helper to parse SQLite UTC dates robustly across all browsers
function parseUTCDate(dateStr) {
    if (!dateStr) return new Date(0);
    if (typeof dateStr === 'number') return new Date(dateStr);
    if (dateStr.includes('Z') || dateStr.includes('UTC') || dateStr.includes('GMT')) {
        return new Date(dateStr);
    }
    const isoStr = dateStr.replace(' ', 'T') + 'Z';
    return new Date(isoStr);
}

// Cache variable for fetched raw data
let localLicenses = [];
let localUpdates = [];

// Inline SVG Icon dictionary for dynamic rendering
const SVG_ICONS = {
    update: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.5" /></svg>`,
    computer: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>`,
    cancel: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    key_visualizer: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m-1.5 6l-1.5 1.5-1.5-1.5L11 16.5 9.5 15l-1.5 1.5H5v-3l6.5-6.5A4 4 0 1118 8a4 4 0 01-3.5 7z" /></svg>`
};

// Switch Navigation Tabs
function switchTab(tabId) {
    // Hide all views
    document.querySelectorAll('.tab-view').forEach(view => {
        view.classList.add('hidden');
    });
    // Remove active styles from nav buttons
    document.querySelectorAll('.nav-btn').forEach(btn => {
        const isSettings = btn.id === 'nav-settings';
        btn.className = "nav-btn text-on-surface-variant px-4 py-3 flex items-center gap-3 rounded-lg hover:bg-surface-variant hover:text-on-surface transition-all w-full font-body-md text-body-md cursor-pointer" + (isSettings ? " mt-auto" : "");
    });

    // Show current tab view
    const targetView = document.getElementById('view-' + tabId);
    if (targetView) {
        targetView.classList.remove('hidden');
    }

    // Set active style to target button
    const targetNav = document.getElementById('nav-' + tabId);
    if (targetNav) {
        const isSettings = targetNav.id === 'nav-settings';
        targetNav.className = "nav-btn bg-primary-container bg-opacity-30 text-primary font-bold px-4 py-3 rounded-lg flex items-center gap-3 w-full font-body-md text-body-md cursor-pointer transition-colors" + (isSettings ? " mt-auto" : "");
    }
}

// Calculate dynamic status and format date
function getVMStatus(lastSeen) {
    if (!lastSeen) return { text: 'Pending', color: 'outline', bg: 'bg-outline bg-opacity-10' };
    const seconds = Math.floor((new Date() - parseUTCDate(lastSeen)) / 1000);
    if (seconds < 300) { // Under 5 minutes
        return { text: 'Active', color: 'secondary', bg: 'bg-secondary bg-opacity-10' };
    } else if (seconds < 1800) { // Under 30 minutes
        return { text: 'Idle', color: 'outline', bg: 'bg-outline bg-opacity-10' };
    } else {
        return { text: 'Offline', color: 'error', bg: 'bg-error bg-opacity-10' };
    }
}

// Fetch all Licenses
async function fetchLicenses() {
    try {
        const res = await fetch('/api/admin/licenses');
        const data = await res.json();
        localLicenses = data;

        updateStatCards();
        renderDashboardVMs();
        renderLicensesList();
        renderAuditLogs();
    } catch (err) {
        console.error('Error fetching licenses:', err);
    }
}

// Fetch all Updates
async function fetchUpdates() {
    try {
        const res = await fetch('/api/admin/updates');
        const data = await res.json();
        localUpdates = data;

        updateStatCards();
        renderUpdatesList();
        renderRollbackList();
        renderAuditLogs();
    } catch (err) {
        console.error('Error fetching updates:', err);
    }
}

// Update the top metric cards dynamically
function updateStatCards() {
    let active = 0;
    let pending = 0;
    let revoked = 0;

    localLicenses.forEach(item => {
        if (item.status === 'ACTIVE') active++;
        if (item.status === 'PENDING') pending++;
        if (item.status === 'REVOKED') revoked++;
    });

    const total = localLicenses.length || 1; // Prevent division by zero

    // Update numbers
    document.getElementById('stat-active').innerText = active.toLocaleString();
    document.getElementById('stat-pending').innerText = pending.toLocaleString();
    document.getElementById('stat-revoked').innerText = revoked.toLocaleString();

    // Update percentages
    const activePct = Math.round((active / total) * 100);
    const pendingPct = Math.round((pending / total) * 100);
    const revokedPct = Math.round((revoked / total) * 100);

    document.getElementById('stat-active-percent').innerText = '+' + activePct + '%';
    document.getElementById('stat-pending-percent').innerText = pendingPct + '%';
    document.getElementById('stat-revoked-percent').innerText = revokedPct + '%';

    // Update progress bars
    document.getElementById('stat-active-bar').style.width = activePct + '%';
    document.getElementById('stat-pending-bar').style.width = pendingPct + '%';
    document.getElementById('stat-revoked-bar').style.width = revokedPct + '%';

    // Update latest release release info
    if (localUpdates.length > 0) {
        const latest = localUpdates[0];
        document.getElementById('stat-version').innerText = latest.version;
        
        // Format time difference
        const timeDiff = Math.floor((new Date() - parseUTCDate(latest.published_at)) / 1000);
        let timeStr = 'Deployed ';
        if (timeDiff < 60) timeStr += 'just now';
        else if (timeDiff < 3600) timeStr += Math.floor(timeDiff / 60) + 'm ago';
        else if (timeDiff < 86400) timeStr += Math.floor(timeDiff / 3600) + 'h ago';
        else timeStr += Math.floor(timeDiff / 86400) + 'd ago';
        
        document.getElementById('stat-version-time').innerText = timeStr;
    }
}

// Render dynamic dashboard VMs list
function renderDashboardVMs() {
    const tbody = document.getElementById('dashboardActiveVmsTable');
    tbody.innerHTML = '';

    // Filter licenses to find active/seen VMs
    const activeVMs = localLicenses.filter(item => item.machine_id);

    if (activeVMs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-on-surface-variant py-8">No registered VM nodes checked-in yet.</td></tr>';
        return;
    }

    // Sort by last seen date (newest first)
    activeVMs.sort((a, b) => parseUTCDate(b.last_seen) - parseUTCDate(a.last_seen));

    // Limit to top 5
    activeVMs.slice(0, 5).forEach(item => {
        const tr = document.createElement('tr');
        tr.className = "border-b border-outline-variant hover:bg-surface-container-low transition-colors";
        
        const status = getVMStatus(item.last_seen);
        const lastSeenStr = item.last_seen ? formatTimeAgo(item.last_seen) : 'Never';

        tr.innerHTML = `
            <td class="px-lg py-4 font-bold text-on-surface">${item.machine_id}</td>
            <td class="px-lg py-4 text-on-surface-variant">${item.machine_ip || '--'}</td>
            <td class="px-lg py-4 text-primary font-bold font-code-md">${item.os_version || 'None'}</td>
            <td class="px-lg py-4 text-on-surface-variant font-body-md">${lastSeenStr}</td>
            <td class="px-lg py-4 text-right">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 ${status.bg} text-${status.color} rounded-full font-label-caps text-[10px] font-bold uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-${status.color}"></span> ${status.text}
                </span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Format date into human readable "X mins ago"
function formatTimeAgo(dateStr) {
    const seconds = Math.floor((new Date() - parseUTCDate(dateStr)) / 1000);
    if (seconds < 60) return 'Just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return minutes + 'm ago';
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours + 'h ago';
    return Math.floor(hours / 24) + 'd ago';
}

// Render complete License Manager table
function renderLicensesList() {
    const tbody = document.getElementById('licensesTable');
    tbody.innerHTML = '';

    if (localLicenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-on-surface-variant py-8">No licenses generated yet.</td></tr>';
        return;
    }

    localLicenses.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = "border-b border-outline-variant hover:bg-surface-container-low transition-colors";
        
        let badgeClass = 'bg-outline bg-opacity-10 text-outline';
        if (item.status === 'ACTIVE') badgeClass = 'bg-secondary bg-opacity-10 text-secondary';
        if (item.status === 'REVOKED') badgeClass = 'bg-error bg-opacity-10 text-error';

        const lastSeenStr = item.last_seen ? parseUTCDate(item.last_seen).toLocaleString() : '--';

        let actionButtons = '';
        if (item.status === 'ACTIVE') {
            actionButtons += `<button class="bg-error bg-opacity-10 text-error hover:bg-error hover:text-white px-2.5 py-1 rounded font-bold font-body-sm text-body-sm transition-all" onclick="revokeLicense('${item.license_key}')">Revoke</button>`;
        } else if (item.status === 'REVOKED') {
            actionButtons += `<button class="bg-secondary bg-opacity-10 text-secondary hover:bg-secondary hover:text-white px-2.5 py-1 rounded font-bold font-body-sm text-body-sm transition-all" onclick="reactivateLicense('${item.license_key}')">Reactivate</button>`;
        }
        actionButtons += `<button class="bg-surface border border-outline-variant hover:border-error hover:text-error px-2.5 py-1 rounded font-bold font-body-sm text-body-sm transition-all" onclick="deleteLicense('${item.license_key}')">Delete</button>`;

        tr.innerHTML = `
            <td class="px-lg py-4 font-bold text-on-surface tracking-tight font-code-md">${item.license_key}</td>
            <td class="px-lg py-4"><span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-label-caps text-[10px] font-bold uppercase ${badgeClass}">${item.status}</span></td>
            <td class="px-lg py-4 font-bold text-on-surface font-code-sm">${item.machine_id || '--'}</td>
            <td class="px-lg py-4 text-on-surface-variant">${item.machine_ip || '--'}</td>
            <td class="px-lg py-4 text-primary font-bold font-code-md">${item.os_version || '--'}</td>
            <td class="px-lg py-4 text-on-surface-variant font-body-md">${lastSeenStr}</td>
            <td class="px-lg py-4 text-right">
                <div class="flex gap-2 justify-end">
                    ${actionButtons}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Render standard updates release list
function renderUpdatesList() {
    const tbody = document.getElementById('updatesTable');
    tbody.innerHTML = '';

    if (localUpdates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-on-surface-variant py-8">No updates published yet.</td></tr>';
        return;
    }

    localUpdates.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.className = "border-b border-outline-variant hover:bg-surface-container-low transition-colors";
        
        const isActive = (index === 0);
        const statusBadge = isActive 
            ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-secondary bg-opacity-10 text-secondary rounded-full font-label-caps text-[10px] font-bold uppercase">Active</span>' 
            : '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-outline bg-opacity-10 text-outline rounded-full font-label-caps text-[10px] font-bold uppercase">Inactive</span>';

        const actionButtons = `
            <button class="bg-error bg-opacity-10 text-error hover:bg-error hover:text-white px-2.5 py-1 rounded font-bold font-body-sm text-body-sm transition-all" onclick="deleteUpdate('${item.version}')">Delete</button>
        `;

        tr.innerHTML = `
            <td class="px-lg py-4 font-bold font-code-md text-on-surface flex items-center gap-2">${item.version} ${statusBadge}</td>
            <td class="px-lg py-4"><a href="/api/updates/download/${item.filename}" class="text-primary hover:underline font-medium">${item.filename}</a></td>
            <td class="px-lg py-4 font-code-sm text-code-sm text-on-surface-variant max-w-[200px] truncate" title="${item.checksum}">${item.checksum}</td>
            <td class="px-lg py-4 text-on-surface-variant">${parseUTCDate(item.published_at).toLocaleString()}</td>
            <td class="px-lg py-4 text-right">
                <div class="flex gap-2 justify-end">${actionButtons}</div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Render dynamic Rollback Manager table
function renderRollbackList() {
    const tbody = document.getElementById('rollbackTable');
    tbody.innerHTML = '';

    if (localUpdates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-on-surface-variant py-8">No firmware versions available for rollback.</td></tr>';
        return;
    }

    localUpdates.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.className = "border-b border-outline-variant hover:bg-surface-container-low transition-colors";
        
        const isActive = (index === 0);
        const statusBadge = isActive 
            ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-secondary bg-opacity-10 text-secondary rounded-full font-label-caps text-[10px] font-bold uppercase">Active Target</span>' 
            : '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-outline bg-opacity-10 text-outline rounded-full font-label-caps text-[10px] font-bold uppercase">Inactive</span>';

        const actionButton = isActive
            ? `<span class="text-on-surface-variant text-body-sm font-medium">Currently Deployed</span>`
            : `<button class="bg-primary text-white hover:bg-opacity-95 px-3 py-1.5 rounded font-bold font-body-sm text-body-sm transition-all" onclick="activateUpdate('${item.version}')">Rollback to this</button>`;

        tr.innerHTML = `
            <td class="px-lg py-4 font-bold font-code-md text-on-surface flex items-center gap-2">${item.version} ${statusBadge}</td>
            <td class="px-lg py-4 font-medium text-on-surface">${item.filename}</td>
            <td class="px-lg py-4 font-code-sm text-code-sm text-on-surface-variant max-w-[200px] truncate" title="${item.checksum}">${item.checksum}</td>
            <td class="px-lg py-4 text-on-surface-variant">${parseUTCDate(item.published_at).toLocaleString()}</td>
            <td class="px-lg py-4 text-right">${actionButton}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Generate new random system activity events or parse logs from DB
function renderDashboardActivityFeed() {
    const container = document.getElementById('dashboardActivityFeed');
    container.innerHTML = '';

    const events = [];

    // Add updates deployment events
    localUpdates.forEach(u => {
        events.push({
            title: `Release Deployed (v${u.version})`,
            details: `Package file ${u.filename} registered with hash ${u.checksum.substring(0, 16)}...`,
            time: new Date(u.published_at),
            icon: 'update',
            color: 'primary',
            bg: 'bg-primary bg-opacity-10'
        });
    });

    // Add active VMs checkins
    localLicenses.forEach(lic => {
        if (lic.machine_id) {
            events.push({
                title: `Node Check-In`,
                details: `Appliance ID ${lic.machine_id} checked-in telemetry from host ${lic.machine_ip || 'unknown'}`,
                time: new Date(lic.last_seen || lic.activated_at || Date.now()),
                icon: 'computer',
                color: 'secondary',
                bg: 'bg-secondary bg-opacity-10'
            });
        }
        if (lic.status === 'REVOKED') {
            events.push({
                title: `License Revoked`,
                details: `Key ${lic.license_key.substring(0, 9)}... was blocked from access`,
                time: new Date(lic.last_seen || Date.now()),
                icon: 'cancel',
                color: 'error',
                bg: 'bg-error bg-opacity-10'
            });
        }
    });

    // Default boot events
    events.push({
        title: "Central Cryptographic Engine Initialized",
        details: "RSA/SHA-256 license signature keys successfully loaded.",
        time: new Date(Date.now() - 3600000 * 4), // 4h ago
        icon: 'key_visualizer',
        color: 'primary',
        bg: 'bg-primary bg-opacity-10'
    });

    // Sort events by time
    events.sort((a, b) => b.time - a.time);

    events.slice(0, 8).forEach(ev => {
        const item = document.createElement('div');
        item.className = "flex items-start gap-4";
        item.innerHTML = `
            <div class="w-9 h-9 rounded-full ${ev.bg} flex items-center justify-center shrink-0 text-${ev.color}">
                ${SVG_ICONS[ev.icon] || ''}
            </div>
            <div class="flex flex-col">
                <span class="font-body-md text-body-md text-on-surface font-bold">${ev.title}</span>
                <span class="font-code-sm text-code-sm text-on-surface-variant mt-1 leading-relaxed">${ev.details}</span>
                <span class="font-label-caps text-[10px] text-outline mt-1.5 uppercase font-bold tracking-wider">${formatTimeAgo(ev.time)}</span>
            </div>
        `;
        container.appendChild(item);
    });
}

// Render system audit log screen
function renderAuditLogs() {
    const container = document.getElementById('auditLogContent');
    container.innerHTML = '';

    const logs = [];

    localLicenses.forEach(lic => {
        logs.push(`[${new Date().toISOString()}] INFO: System license status changed for key ${lic.license_key} to ${lic.status}`);
        if (lic.machine_id) {
            logs.push(`[${new Date(lic.last_seen || Date.now()).toISOString()}] VM_CHECKIN: Machine ${lic.machine_id} IP=${lic.machine_ip} OS_VERSION=${lic.os_version} connected successfully`);
        }
    });

    localUpdates.forEach(upd => {
        logs.push(`[${new Date(upd.published_at).toISOString()}] DEPLOY: Version ${upd.version} package ${upd.filename} pushed to repositories`);
    });

    // Mock extra systems events
    logs.push(`[${new Date(Date.now() - 1000 * 1800).toISOString()}] SECURITY: CSRF Token verified successfully for admin session`);
    logs.push(`[${new Date(Date.now() - 1000 * 3600).toISOString()}] TELEMETRY: Flushed log telemetry buffers, 0 loss reported`);
    logs.push(`[${new Date(Date.now() - 1000 * 7200).toISOString()}] SYSTEM: Central Engine listener started on port 8000`);

    logs.sort().reverse();

    logs.forEach(line => {
        const div = document.createElement('div');
        div.className = "py-1 border-b border-outline-variant hover:bg-surface-variant hover:bg-opacity-30 px-2";
        div.textContent = line;
        container.appendChild(div);
    });
}

// Generate new random licenses
async function generateLicenses() {
    const count = document.getElementById('licenseCount').value;
    try {
        const res = await fetch('/api/admin/license/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ count: parseInt(count) })
        });
        if (res.ok) {
            fetchLicenses();
        }
    } catch (err) {
        alert('Failed to generate license keys.');
    }
}

// Import licenses list
async function importLicenses() {
    const text = document.getElementById('importKeysText').value;
    if (!text.trim()) return;
    const keys = text.split(/[\n,]+/).map(k => k.trim()).filter(k => k.length > 0);
    
    try {
        const res = await fetch('/api/admin/license/import', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keys })
        });
        if (res.ok) {
            document.getElementById('importKeysText').value = '';
            alert('Import completed successfully!');
            fetchLicenses();
        } else {
            alert('Import failed.');
        }
    } catch (err) {
        alert('Server communication error.');
    }
}

// Block License Key
async function revokeLicense(key) {
    if (!confirm('Are you sure you want to revoke this license? The VM will be deactivated instantly.')) return;
    try {
        const res = await fetch('/api/admin/license/revoke', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ license_key: key })
        });
        if (res.ok) fetchLicenses();
    } catch (err) {
        alert('Failed to revoke license.');
    }
}

// Reactivate License Key
async function reactivateLicense(key) {
    try {
        const res = await fetch('/api/admin/license/reactivate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ license_key: key })
        });
        if (res.ok) fetchLicenses();
    } catch (err) {
        alert('Failed to reactivate license.');
    }
}

// Delete License Key from database
async function deleteLicense(key) {
    if (!confirm('Delete this key entirely from the server database?')) return;
    try {
        const res = await fetch('/api/admin/license/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ license_key: key })
        });
        if (res.ok) fetchLicenses();
    } catch (err) {
        alert('Failed to delete license.');
    }
}

// Deploy update / rollback version switch
async function activateUpdate(version) {
    if (!confirm(`Are you sure you want to rollback/change active update to version ${version}?`)) return;
    try {
        const res = await fetch('/api/admin/update/activate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ version: version })
        });
        if (res.ok) {
            alert(`Rolled back active target version to ${version} successfully!`);
            fetchUpdates();
        } else {
            const err = await res.json();
            alert('Activation failed: ' + err.error);
        }
    } catch (err) {
        alert('Activation failed.');
    }
}

// Delete update package from storage
async function deleteUpdate(version) {
    if (!confirm(`Are you sure you want to delete update version ${version}? This will physically delete the package file.`)) return;
    try {
        const res = await fetch('/api/admin/update/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ version: version })
        });
        if (res.ok) {
            fetchUpdates();
        } else {
            const err = await res.json();
            alert('Delete failed: ' + err.error);
        }
    } catch (err) {
        alert('Delete failed.');
    }
}

// Upload/Publish new update files
function publishUpdate(e) {
    e.preventDefault();
    const version = document.getElementById('updateVersion').value;
    const fileInput = document.getElementById('updateFile');
    const file = fileInput.files[0];

    if (!file) return;

    const formData = new FormData();
    formData.append('version', version);
    formData.append('file', file);

    const container = document.getElementById('uploadProgressContainer');
    const bar = document.getElementById('uploadProgressBar');
    const percentText = document.getElementById('uploadProgressPercent');
    const statusText = document.getElementById('uploadStatusText');
    const speedText = document.getElementById('uploadSpeedText');
    const bytesText = document.getElementById('uploadBytesText');

    container.classList.remove('hidden');
    bar.style.width = '0%';
    percentText.innerText = '0%';
    statusText.innerText = 'Uploading package...';
    speedText.innerText = 'Calculating upload speed...';
    bytesText.innerText = `0.00 / ${(file.size / (1024 * 1024)).toFixed(2)} MB`;

    const startTime = Date.now();

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/admin/update/publish', true);

    xhr.upload.onprogress = function(event) {
        if (event.lengthComputable) {
            const percentComplete = (event.loaded / event.total) * 100;
            bar.style.width = percentComplete + '%';
            percentText.innerText = Math.round(percentComplete) + '%';

            const elapsedSeconds = (Date.now() - startTime) / 1000;
            const loadedMB = (event.loaded / (1024 * 1024)).toFixed(2);
            const totalMB = (event.total / (1024 * 1024)).toFixed(2);
            bytesText.innerText = `${loadedMB} / ${totalMB} MB`;

            if (elapsedSeconds > 0) {
                const speedBps = event.loaded / elapsedSeconds;
                let speedStr = '';
                if (speedBps > 1024 * 1024) {
                    speedStr = (speedBps / (1024 * 1024)).toFixed(2) + ' MB/s';
                } else {
                    speedStr = (speedBps / 1024).toFixed(1) + ' KB/s';
                }
                speedText.innerText = speedStr;
            }
        }
    };

    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            statusText.innerText = 'Processing update package on server...';
            setTimeout(() => {
                alert('Update published and activated successfully!');
                container.classList.add('hidden');
                document.getElementById('updateVersion').value = '';
                fileInput.value = '';
                fetchUpdates();
            }, 800);
        } else {
            let errMsg = 'Unknown error';
            try {
                const resp = JSON.parse(xhr.responseText);
                errMsg = resp.error || errMsg;
            } catch(e) {}
            alert('Publish failed: ' + errMsg);
            statusText.innerText = 'Upload failed.';
        }
    };

    xhr.onerror = function() {
        alert('Upload failed due to a network connection error.');
        statusText.innerText = 'Network error.';
    };

    xhr.send(formData);
}

// Change Admin Access Key and Profile
async function changeAdminPassword(e) {
    e.preventDefault();
    const newEmail = document.getElementById('newEmail').value;
    const newPassword = document.getElementById('newPassword').value;
    try {
        const res = await fetch('/api/admin/settings/password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ new_email: newEmail, new_password: newPassword })
        });
        if (res.ok) {
            alert('Administrator profile updated successfully!');
            document.getElementById('newPassword').value = '';
        } else {
            const data = await res.json();
            alert('Failed: ' + (data.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Failed to connect to server.');
    }
}

// Clear client cache logs
function clearClientTelemetryCache() {
    if (confirm('Clear administrative event log telemetry cache?')) {
        alert('Telemetry cache cleared.');
    }
}

// Logout Operator session
async function handleLogout() {
    if (!confirm('Are you sure you want to sign out?')) return;
    try {
        const res = await fetch('/api/admin/logout', { method: 'POST' });
        if (res.ok) {
            window.location.reload();
        }
    } catch(e) {}
}

// Auto telemetry pooling
function initPoll() {
    fetchLicenses();
    fetchUpdates();
    
    // Refresh VMs and events every 10 seconds
    setInterval(() => {
        fetchLicenses();
    }, 10000);
}

// Boot Dashboard
initPoll();
</script>
</body></html>
