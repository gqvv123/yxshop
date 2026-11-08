/**
 * 盈锐商贸 官网 + H5 本地服务（Node 原生，零依赖）
 *
 *  - 静态资源：public/
 *  - /api/v1/* 同源代理到现有 Webman 后端，浏览器无需跨域
 *  - 后端地址可用 YXSHOP_API_ORIGIN 覆盖，默认 http://127.0.0.1:8777
 *
 * 启动：node server.js   然后访问 http://127.0.0.1:8080/  （H5：/h5.html）
 */
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const { URL } = require('node:url');

const PORT = Number(process.env.PORT || 8080);
const API_ORIGIN = (process.env.YXSHOP_API_ORIGIN || 'http://127.0.0.1:8777').replace(/\/$/, '');
const ROOT = path.join(__dirname, 'public');

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.ico': 'image/x-icon',
  '.json': 'application/json; charset=utf-8',
  '.woff2': 'font/woff2',
};

function send(res, status, body, headers = {}) {
  res.writeHead(status, { 'Cache-Control': 'no-cache', ...headers });
  res.end(body);
}

function serveStatic(req, res, pathname) {
  let rel = decodeURIComponent(pathname);
  if (rel === '/' || rel === '') rel = '/index.html';
  const full = path.join(ROOT, path.normalize(rel).replace(/^(\.\.[/\\])+/, ''));
  if (!full.startsWith(ROOT)) return send(res, 403, 'Forbidden');

  fs.stat(full, (err, stat) => {
    if (err || !stat.isFile()) {
      // 单页入口回退
      const fallback = path.join(ROOT, 'index.html');
      return fs.readFile(fallback, (e2, buf) => {
        if (e2) return send(res, 404, 'Not Found');
        send(res, 200, buf, { 'Content-Type': MIME['.html'] });
      });
    }
    fs.readFile(full, (e2, buf) => {
      if (e2) return send(res, 500, 'Read error');
      send(res, 200, buf, { 'Content-Type': MIME[path.extname(full).toLowerCase()] || 'application/octet-stream' });
    });
  });
}

function proxyApi(req, res, pathname, search) {
  const target = new URL(API_ORIGIN + pathname + (search || ''));
  const headers = { ...req.headers };
  delete headers.host;
  delete headers['accept-encoding'];
  delete headers.connection;
  delete headers['content-length'];

  const chunks = [];
  req.on('data', (c) => chunks.push(c));
  req.on('end', () => {
    const body = Buffer.concat(chunks);
    if (body.length) headers['content-length'] = String(body.length);
    else delete headers['content-length'];

    const upstream = http.request(
      {
        hostname: target.hostname,
        port: target.port || 80,
        path: target.pathname + target.search,
        method: req.method,
        headers,
        timeout: 20000,
      },
      (up) => {
        res.writeHead(up.statusCode || 502, { ...up.headers, 'Cache-Control': 'no-store' });
        up.pipe(res);
      }
    );
    upstream.on('timeout', () => upstream.destroy(new Error('upstream timeout')));
    upstream.on('error', (e) => {
      if (res.headersSent) return res.end();
      send(res, 502, JSON.stringify({
        code: 502,
        message: '后端服务未连接：' + e.message,
        data: null,
      }), { 'Content-Type': 'application/json; charset=utf-8' });
    });
    if (body.length) upstream.write(body);
    upstream.end();
  });
}

const server = http.createServer((req, res) => {
  const u = new URL(req.url, 'http://' + (req.headers.host || 'localhost'));
  const p = u.pathname;

  if (req.method === 'OPTIONS' && p.startsWith('/api/')) {
    return send(res, 204, '', {
      'Access-Control-Allow-Origin': req.headers.origin || '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization',
    });
  }
  if (p.startsWith('/api/')) return proxyApi(req, res, p, u.search);
  return serveStatic(req, res, p);
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`盈锐商贸站点已启动: http://127.0.0.1:${PORT}/`);
  console.log(`H5 商城:            http://127.0.0.1:${PORT}/h5.html`);
  console.log(`API 代理目标:       ${API_ORIGIN}`);
});
