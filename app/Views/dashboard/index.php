<div class="page-header">
    <h2>Dashboard</h2>
    <span class="sub">Scal-e Customer Data Platform</span>
</div>

<div class="card">
    <p class="card-title">Quick Start — API Endpoints</p>
    <table>
        <thead>
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="badge badge-green">POST</span></td>
                <td><code>/api/events</code></td>
                <td>Ingest a customer event</td>
            </tr>
            <tr>
                <td><span class="badge badge-blue">GET</span></td>
                <td><code>/api/customers</code></td>
                <td>List customers (paginated)</td>
            </tr>
            <tr>
                <td><span class="badge badge-blue">GET</span></td>
                <td><code>/api/customers/{id}</code></td>
                <td>Customer profile + stats</td>
            </tr>
            <tr>
                <td><span class="badge badge-green">POST</span></td>
                <td><code>/api/segments/query</code></td>
                <td>Segmentation query</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="card">
    <p class="card-title">Example — Ingest an event</p>
    <pre>curl -X POST http://localhost/api/events \
  -H "Content-Type: application/json" \
  -d '{
    "customer": { "email": "john@example.com", "name": "John Doe" },
    "event": "purchase",
    "properties": { "amount": 120, "product": "Shoes" },
    "timestamp": "2026-04-10T12:00:00Z"
  }'</pre>
</div>

<div class="card">
    <p class="card-title">Example — Segment query</p>
    <pre>curl -X POST http://localhost/api/segments/query \
  -H "Content-Type: application/json" \
  -d '{
    "conditions": [
      { "event": "purchase", "property": "amount", "operator": ">", "value": 100 }
    ]
  }'</pre>
</div>
