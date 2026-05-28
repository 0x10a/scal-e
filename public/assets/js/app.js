/* Scal-e CDP — minimal ES6+ vanilla JS */

'use strict';

/* ──────────────────────────────────────────────────────────
   Segment Query Playground
   ────────────────────────────────────────────────────────── */
(function initSegmentForm() {
    const form      = document.getElementById('segment-form');
    const resultBox = document.getElementById('result-box');

    if (!form || !resultBox) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const rawInput = document.getElementById('segment-json').value.trim();
        resultBox.innerHTML = '<em>Running query…</em>';

        let payload;
        try {
            payload = JSON.parse(rawInput);
        } catch {
            resultBox.innerHTML = '<div class="alert alert-danger">Invalid JSON — please check your input.</div>';
            return;
        }

        try {
            const apiKey = document.querySelector('meta[name="api-key"]')?.content ?? '';
            const res  = await fetch('/api/segments/query', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Api-Key': apiKey },
                body:    JSON.stringify(payload),
            });
            const data = await res.json();
            resultBox.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
        } catch (err) {
            resultBox.innerHTML = `<div class="alert alert-danger">Request failed: ${err.message}</div>`;
        }
    });
})();

/* ──────────────────────────────────────────────────────────
   Mark active nav link
   ────────────────────────────────────────────────────────── */
(function markActiveNav() {
    const path  = window.location.pathname;
    document.querySelectorAll('nav a').forEach((a) => {
        const href = a.getAttribute('href');
        if (href === path || (href !== '/' && path.startsWith(href))) {
            a.classList.add('active');
        }
    });
})();
