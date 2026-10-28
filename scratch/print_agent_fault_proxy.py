"""Disposable installer fault injection; forwards only to the isolated audit server."""
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.request import Request, urlopen
from urllib.error import HTTPError


class Handler(BaseHTTPRequestHandler):
    def do_POST(self):
        body = self.rfile.read(int(self.headers.get('Content-Length', '0')))
        if self.path == '/api/print-agent/activate-enrollment':
            code, result = 401, b'{"message":"Audit: activation rejected before credential switch"}'
        elif self.path in ['/api/print-agent/preflight', '/api/print-agent/prepare']:
            request = Request('http://127.0.0.1:7110' + self.path, data=body, headers={'Content-Type': 'application/json', 'Accept': 'application/json'})
            try:
                with urlopen(request, timeout=15) as response:
                    code, result = response.status, response.read()
            except HTTPError as error:
                code, result = error.code, error.read()
        else:
            code, result = 404, b'{}'
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(result)

    def log_message(self, *_):
        pass


ThreadingHTTPServer(('127.0.0.1', 7111), Handler).serve_forever()
