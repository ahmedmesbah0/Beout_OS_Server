#!/usr/bin/env python3
import socket
import ssl
import threading
import sys
import os

def handle_client(client_socket, target_host, target_port):
    try:
        # Connect to the target HTTP server
        server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server_socket.connect((target_host, target_port))
    except Exception as e:
        print(f"Failed to connect to backend {target_host}:{target_port}: {e}")
        client_socket.close()
        return

    # Bidirectional forwarding
    def forward(src, dst):
        try:
            while True:
                data = src.recv(8192)
                if not data:
                    break
                dst.sendall(data)
        except Exception:
            pass
        finally:
            try:
                src.shutdown(socket.SHUT_RDWR)
            except Exception:
                pass
            try:
                dst.shutdown(socket.SHUT_RDWR)
            except Exception:
                pass
            try:
                src.close()
            except Exception:
                pass
            try:
                dst.close()
            except Exception:
                pass

    threading.Thread(target=forward, args=(client_socket, server_socket), daemon=True).start()
    threading.Thread(target=forward, args=(server_socket, client_socket), daemon=True).start()

def main():
    # Change directory to the script's parent's parent to ensure relative paths work
    script_dir = os.path.dirname(os.path.abspath(__file__))
    project_dir = os.path.dirname(script_dir)
    os.chdir(project_dir)

    bind_ip = "0.0.0.0"
    bind_port = 8443
    target_host = "127.0.0.1"
    target_port = 8000
    cert_file = "certs/server.crt"
    key_file = "certs/server.key"

    if not os.path.exists(cert_file) or not os.path.exists(key_file):
        print(f"Error: Certificate files not found at {cert_file} or {key_file}.")
        sys.exit(1)

    context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    context.load_cert_chain(certfile=cert_file, keyfile=key_file)

    bind_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    bind_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    bind_socket.bind((bind_ip, bind_port))
    bind_socket.listen(128)

    print(f"SSL Proxy listening on https://{bind_ip}:{bind_port} -> http://{target_host}:{target_port}")

    try:
        while True:
            client_sock, addr = bind_socket.accept()
            try:
                secure_client_sock = context.wrap_socket(client_sock, server_side=True)
                threading.Thread(target=handle_client, args=(secure_client_sock, target_host, target_port), daemon=True).start()
            except Exception as e:
                # Handshake failure (e.g. client doesn't trust cert, or scanning)
                # We close the socket and continue
                client_sock.close()
    except KeyboardInterrupt:
        print("SSL Proxy shutting down...")
    finally:
        bind_socket.close()

if __name__ == "__main__":
    main()
