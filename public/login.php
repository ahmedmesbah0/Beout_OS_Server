<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beout_OS Management Server Login</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: var(--card-radius);
            padding: 2.5rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .login-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(135deg, #fff 0%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
            font-size: 0.95rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px var(--accent-glow);
        }

        .error-message {
            color: #ff453a;
            font-size: 0.85rem;
            text-align: center;
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Beout_OS Portal Login</h2>
        <p style="color: var(--text-secondary); font-size: 0.85rem; text-align: center; margin-top: -0.5rem;">Access the central licensing authority</p>
        
        <div id="errorBox" class="error-message"></div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="password">Administrator Password</label>
                <input type="password" id="password" class="input-field" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Sign In</button>
        </form>
    </div>

    <script>
        async function handleLogin(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            const errorBox = document.getElementById('errorBox');
            errorBox.style.display = 'none';

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
                    errorBox.style.display = 'block';
                }
            } catch (err) {
                errorBox.textContent = 'Server connection error.';
                errorBox.style.display = 'block';
            }
        }
    </script>
</body>
</html>
