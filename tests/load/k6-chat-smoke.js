/**
 * k6 load smoke for POST /api/chat (requires SANCTUM_TOKEN and BASE_URL).
 * Install: https://k6.io/docs/getting-started/installation/
 * Run: k6 run --env BASE_URL=https://example.com --env SANCTUM_TOKEN=xxx tests/load/k6-chat-smoke.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 5,
    duration: '30s',
    thresholds: {
        http_req_failed: ['rate<0.05'],
        http_req_duration: ['p(95)<8000'],
    },
};

export default function () {
    const base = __ENV.BASE_URL || 'http://127.0.0.1:8000';
    const token = __ENV.SANCTUM_TOKEN || '';
    if (!token) {
        console.warn('Set SANCTUM_TOKEN for authenticated chat load test.');
    }
    const res = http.post(
        `${base}/api/chat`,
        JSON.stringify({ message: 'Say hello in one short sentence.', stream: false }),
        {
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
        }
    );
    check(res, { 'status is 200 or 422': (r) => r.status === 200 || r.status === 422 });
    sleep(1);
}
