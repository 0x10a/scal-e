<div class="page-header">
    <h2>Segment Query Playground</h2>
    <span class="sub">Build conditions and run against live data</span>
</div>

<div class="card">
    <p class="card-title">Query Builder</p>

    <form id="segment-form">
        <div class="form-group">
            <label for="segment-json">Conditions JSON</label>
            <textarea id="segment-json" class="form-control" rows="14">{
  "conditions": [
    {
      "event": "purchase",
      "property": "amount",
      "operator": ">",
      "value": 100
    }
  ]
}</textarea>
        </div>

        <div class="form-group">
            <p style="font-size:.85rem;color:var(--color-muted);margin-bottom:.5rem;">
                Supported operators:
                <code>=</code> <code>!=</code> <code>&gt;</code> <code>&gt;=</code>
                <code>&lt;</code> <code>&lt;=</code> <code>contains</code>
                &nbsp;·&nbsp; Multiple conditions = AND logic.
            </p>
            <button type="submit" class="btn btn-primary">Run Query</button>
        </div>
    </form>
</div>

<div class="card">
    <p class="card-title">Result</p>
    <div id="result-box">
        <pre style="color:var(--color-muted);background:transparent;padding:0;">Run a query to see results here.</pre>
    </div>
</div>
