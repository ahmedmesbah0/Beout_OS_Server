# Beout OS Server — Deploy Guide

## ✅ How to Deploy on a New Server

Run these commands on your **server** (as `root`):

```bash
cd /root
git clone https://github.com/yourusername/Beout_OS.git Beout_OS_Server
cd Beout_OS_Server
sudo ./install-server.sh
```

> ✅ That’s it. The server will auto-start on boot and restart if it crashes.

## 🔧 Why This Works
- The service file uses **absolute path**: `/root/Beout_OS_Server`
- You install it **once** — then just `git pull` for updates
- No auto-detection magic — clean, predictable, and reliable
- Runs as root, which matches your server environment

## 🔄 Update

To update on the server:

```bash
cd /root/Beout_OS_Server
git pull
sudo systemctl restart beout-server.service
```

## 🔐 Security Notes
- The server listens on port `80` — consider setting up Nginx + SSL for public use
- Only trusted machines should access this endpoint

## 📌 Tip

Add this alias to your server’s `~/.bashrc`:

```bash
alias beout-restart='sudo systemctl restart beout-server.service'
```

Now you can just type `beout-restart` after `git pull`.

---

© 2026 Beout OS — Simple. Portable. Reliable.