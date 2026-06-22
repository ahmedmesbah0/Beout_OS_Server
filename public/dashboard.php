<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beout_OS Main Management Server</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0e17;
            --bg-glass: rgba(16, 24, 48, 0.4);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-primary: #ffffff;
            --text-secondary: #8e9bb3;
            --accent: #5e5ce6;
            --accent-glow: rgba(94, 92, 230, 0.35);
            --success: #30d158;
            --success-glow: rgba(48, 209, 88, 0.2);
            --danger: #ff453a;
            --danger-glow: rgba(255, 69, 58, 0.25);
            --warning: #ff9f0a;
            --card-radius: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
        }

        body {
            background-color: var(--bg-primary);
            background-image: radial-gradient(circle at 10% 20%, rgba(94, 92, 230, 0.08) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(48, 209, 88, 0.05) 0%, transparent 40%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: var(--card-radius);
            backdrop-filter: blur(20px);
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .panel {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: var(--card-radius);
            padding: 2rem;
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .panel h2 {
            font-size: 1.25rem;
            font-weight: 600;
            border-bottom: 1px solid var(--border-glass);
            padding-bottom: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
        }

        .input-field:focus {
            border-color: var(--accent);
            box-shadow: 0 0 10px var(--accent-glow);
        }

        .btn {
            background: var(--accent);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px var(--accent-glow);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            box-shadow: 0 5px 15px var(--success-glow);
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-danger:hover {
            box-shadow: 0 5px 15px var(--danger-glow);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-glass);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        th, td {
            text-align: left;
            padding: 0.65rem;
            border-bottom: 1px solid var(--border-glass);
            vertical-align: middle;
        }

        th {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-active {
            background: rgba(48, 209, 88, 0.15);
            color: var(--success);
            border: 1px solid rgba(48, 209, 88, 0.3);
        }

        .badge-pending {
            background: rgba(255, 159, 10, 0.15);
            color: var(--warning);
            border: 1px solid rgba(255, 159, 10, 0.3);
        }

        .badge-revoked {
            background: rgba(255, 69, 58, 0.15);
            color: var(--danger);
            border: 1px solid rgba(255, 69, 58, 0.3);
        }
        
        .code-box {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
            word-break: break-all;
            user-select: all;
        }

        .actions-cell {
            display: flex;
            gap: 6px;
        }

        .import-box {
            background: rgba(255,255,255,0.02);
            border: 1px dashed var(--border-glass);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Beout_OS Main Server</h1>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem;">Central Licensing and Auto-Update Authority (PHP)</p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="badge badge-active">Central Engine Online</span>
                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.75rem;" onclick="handleLogout()">Sign Out</button>
            </div>
        </div>

        <div class="grid">
            <!-- Licensing Card -->
            <div class="panel" style="grid-column: span 2;">
                <h2>Appliance Licenses & Registered VMs</h2>
                
                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="display: flex; gap: 1rem; align-items: flex-end; flex: 1; min-width: 250px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Generate License Keys</label>
                            <input type="number" id="licenseCount" class="input-field" value="1" min="1">
                        </div>
                        <button class="btn" onclick="generateLicenses()">Generate Keys</button>
                    </div>

                    <div class="import-box" style="flex: 1.5; min-width: 350px;">
                        <div class="form-group">
                            <label>Import License Keys (Paste keys, comma or newline separated)</label>
                            <textarea id="importKeysText" class="input-field" style="resize: vertical; height: 60px;" placeholder="BEOU-1234-ABCD-9999&#10;BEOU-5678-EFGH-1111"></textarea>
                        </div>
                        <button class="btn btn-outline" style="align-self: flex-end;" onclick="importLicenses()">Import Keys</button>
                    </div>
                </div>

                <div style="overflow-x: auto; max-height: 400px; border: 1px solid var(--border-glass); border-radius: 8px;">
                    <table>
                        <thead>
                            <tr>
                                <th>License Key</th>
                                <th>Status</th>
                                <th>Machine ID</th>
                                <th>Machine IP</th>
                                <th>OS Version</th>
                                <th>Last Seen</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="licensesTable">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">Loading appliances data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h3>Embedded Verification Key</h3>
                    <p style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem;">Bake this public key into /opt/beout_os/etc/license_public_key.pem inside the VM images.</p>
                    <div class="code-box"><?php echo htmlspecialchars($publicKey); ?></div>
                </div>
            </div>

            <!-- Auto-Updates Card -->
            <div class="panel" style="grid-column: span 2;">
                <h2>Release & Auto-Updates</h2>
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                    <form id="uploadForm" onsubmit="publishUpdate(event)" style="display: flex; flex-direction: column; gap: 1rem;">
                        <div class="form-group">
                            <label>Target Version (e.g., 1.0.1)</label>
                            <input type="text" id="updateVersion" class="input-field" placeholder="1.0.1" required>
                        </div>
                        <div class="form-group">
                            <label>Upload Debian Package (.deb)</label>
                            <input type="file" id="updateFile" class="input-field" accept=".deb" required>
                        </div>
                        <button type="submit" class="btn btn-success">Publish Update</button>
                    </form>

                    <div style="overflow-x: auto; max-height: 300px; border: 1px solid var(--border-glass); border-radius: 8px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Filename</th>
                                    <th>SHA256 Checksum</th>
                                    <th>Published At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="updatesTable">
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">Loading updates list...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function fetchLicenses() {
            const res = await fetch('/api/admin/licenses');
            const data = await res.json();
            const tbody = document.getElementById('licensesTable');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No licenses generated yet.</td></tr>';
                return;
            }

            data.forEach(item => {
                const tr = document.createElement('tr');
                let badgeClass = 'badge-pending';
                if (item.status === 'ACTIVE') badgeClass = 'badge-active';
                if (item.status === 'REVOKED') badgeClass = 'badge-revoked';

                const lastSeenStr = item.last_seen ? new Date(item.last_seen).toLocaleString() : '--';

                let actionButtons = '';
                if (item.status === 'ACTIVE') {
                    actionButtons += `<button class="btn btn-danger" style="padding: 4px 8px; font-size: 0.75rem;" onclick="revokeLicense('${item.license_key}')">Revoke</button>`;
                } else if (item.status === 'REVOKED') {
                    actionButtons += `<button class="btn btn-success" style="padding: 4px 8px; font-size: 0.75rem;" onclick="reactivateLicense('${item.license_key}')">Reactivate</button>`;
                }
                actionButtons += `<button class="btn btn-outline" style="padding: 4px 8px; font-size: 0.75rem; border-color: rgba(255,69,58,0.4);" onclick="deleteLicense('${item.license_key}')">Delete</button>`;

                tr.innerHTML = `
                    <td style="font-family: monospace; font-size: 0.9rem; font-weight: 600;">${item.license_key}</td>
                    <td><span class="badge ${badgeClass}">${item.status}</span></td>
                    <td style="font-family: monospace; font-size: 0.8rem; color: ${item.machine_id ? 'var(--text-primary)' : 'var(--text-secondary)'}">
                        ${item.machine_id || '--'}
                    </td>
                    <td>${item.machine_ip || '--'}</td>
                    <td>${item.os_version || '--'}</td>
                    <td style="font-size: 0.8rem; color: var(--text-secondary);">${lastSeenStr}</td>
                    <td>
                        <div class="actions-cell">
                            ${actionButtons}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function fetchUpdates() {
            const res = await fetch('/api/admin/updates');
            const data = await res.json();
            const tbody = document.getElementById('updatesTable');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">No updates published yet.</td></tr>';
                return;
            }

            data.forEach((item, index) => {
                const tr = document.createElement('tr');
                const pubDate = new Date(item.published_at).toLocaleString();
                
                const isActive = (index === 0);
                const statusBadge = isActive 
                    ? '<span style="display: inline-block; padding: 2px 6px; font-size: 0.75rem; border-radius: 4px; background: rgba(52, 211, 153, 0.2); color: #34d399; margin-left: 0.5rem; font-weight: 500;">Active</span>' 
                    : '<span style="display: inline-block; padding: 2px 6px; font-size: 0.75rem; border-radius: 4px; background: rgba(255,255,255,0.05); color: var(--text-secondary); margin-left: 0.5rem; font-weight: 500;">Inactive</span>';

                const actionButtons = isActive
                    ? `<button class="btn btn-danger" onclick="deleteUpdate('${item.version}')" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 4px;">Delete</button>`
                    : `
                        <button class="btn btn-success" onclick="activateUpdate('${item.version}')" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 4px; margin-right: 0.5rem;">Activate</button>
                        <button class="btn btn-danger" onclick="deleteUpdate('${item.version}')" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 4px;">Delete</button>
                      `;

                tr.innerHTML = `
                    <td style="font-weight: 600; display: flex; align-items: center;">${item.version} ${statusBadge}</td>
                    <td><a href="/api/updates/download/${item.filename}" style="color: var(--accent); text-decoration: none;">${item.filename}</a></td>
                    <td style="font-family: monospace; font-size: 0.75rem; color: var(--text-secondary); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${item.checksum}
                    </td>
                    <td style="font-size: 0.8rem; color: var(--text-secondary);">${pubDate}</td>
                    <td>
                        <div style="display: flex; align-items: center;">
                            ${actionButtons}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function activateUpdate(version) {
            if (!confirm(`Are you sure you want to rollback/change active update to version ${version}?`)) return;
            const res = await fetch('/api/admin/update/activate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ version: version })
            });
            if (res.ok) {
                fetchUpdates();
            } else {
                const err = await res.json();
                alert('Activation failed: ' + err.error);
            }
        }

        async function deleteUpdate(version) {
            if (!confirm(`Are you sure you want to delete update version ${version}? This will physically delete the package file.`)) return;
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
        }

        async function generateLicenses() {
            const count = document.getElementById('licenseCount').value;
            await fetch('/api/admin/license/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ count: parseInt(count) })
            });
            fetchLicenses();
        }

        async function importLicenses() {
            const text = document.getElementById('importKeysText').value;
            if (!text.trim()) return;
            const keys = text.split(/[\n,]+/).map(k => k.trim()).filter(k => k.length > 0);
            
            const res = await fetch('/api/admin/license/import', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ keys })
            });
            if (res.ok) {
                document.getElementById('importKeysText').value = '';
                fetchLicenses();
            } else {
                alert('Import failed.');
            }
        }

        async function revokeLicense(key) {
            if (!confirm('Are you sure you want to revoke this license? The VM will be deactivated instantly.')) return;
            const res = await fetch('/api/admin/license/revoke', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ license_key: key })
            });
            if (res.ok) fetchLicenses();
        }

        async function reactivateLicense(key) {
            const res = await fetch('/api/admin/license/reactivate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ license_key: key })
            });
            if (res.ok) fetchLicenses();
        }

        async function deleteLicense(key) {
            if (!confirm('Delete this key entirely from the server database?')) return;
            const res = await fetch('/api/admin/license/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ license_key: key })
            });
            if (res.ok) fetchLicenses();
        }

        async function publishUpdate(e) {
            e.preventDefault();
            const version = document.getElementById('updateVersion').value;
            const fileInput = document.getElementById('updateFile');
            const file = fileInput.files[0];

            const formData = new FormData();
            formData.append('version', version);
            formData.append('file', file);

            const res = await fetch('/api/admin/update/publish', {
                method: 'POST',
                body: formData
            });

            if (res.ok) {
                alert('Update published successfully!');
                document.getElementById('updateVersion').value = '';
                fileInput.value = '';
                fetchUpdates();
            } else {
                const err = await res.json();
                alert('Publish failed: ' + err.error);
            }
        }

        async function handleLogout() {
            if (!confirm('Are you sure you want to sign out?')) return;
            const res = await fetch('/api/admin/logout', { method: 'POST' });
            if (res.ok) {
                window.location.reload();
            }
        }

        // Init
        fetchLicenses();
        fetchUpdates();
    </script>
</body>
</html>
