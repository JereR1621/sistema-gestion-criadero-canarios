import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(fileURLToPath(new URL('./public/', import.meta.url)));
const mime = { '.html': 'text/html; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.css': 'text/css; charset=utf-8', '.png': 'image/png', '.jpg': 'image/jpeg', '.svg': 'image/svg+xml', '.woff2': 'font/woff2', '.txt': 'text/plain; charset=utf-8' };
http.createServer((req, res) => {
    try {
        const requested = decodeURIComponent(new URL(req.url, 'http://localhost').pathname);
        const file = path.resolve(root, `.${requested.endsWith('/') ? `${requested}index.html` : requested}`);
        if (!file.startsWith(root + path.sep)) { res.writeHead(403).end(); return; }
        if (!fs.existsSync(file) || !fs.statSync(file).isFile()) { res.writeHead(404).end('No encontrado'); return; }
        res.writeHead(200, { 'Content-Type': mime[path.extname(file)] ?? 'application/octet-stream' });
        fs.createReadStream(file).pipe(res);
    } catch { res.writeHead(400).end(); }
}).listen(4173, '127.0.0.1', () => console.log('Maqueta local: http://127.0.0.1:4173'));
