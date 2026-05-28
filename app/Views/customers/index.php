<div class="page-header">
    <h2>Customers</h2>
    <span class="sub">Total: <?= (int) ($total ?? 0) ?> &nbsp;·&nbsp; Page <?= (int) ($page ?? 1) ?> / <?= (int) ($last_page ?? 1) ?></span>
</div>

<?php $apiKey = (string) (\App\Core\Env::get('API_KEY') ?? ''); ?>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Profile</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;color:var(--color-muted);">
                        No customers yet. Ingest an event to create one.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($data as $customer): ?>
                <tr>
                    <td><?= (int) $customer['id'] ?></td>
                    <td><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($customer['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php
                            $profileUrl = '/api/customers/' . (int) $customer['id'];
                            if ($apiKey !== '') {
                                $profileUrl .= '?api_key=' . rawurlencode($apiKey);
                            }
                        ?>
                        <a class="btn btn-primary btn-sm"
                           href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>"
                           target="_blank">JSON ↗</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (($last_page ?? 1) > 1): ?>
    <div class="pagination">
        <?php if (($page ?? 1) > 1): ?>
            <a href="/customers?page=<?= (($page ?? 1) - 1) ?>">← Prev</a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= ($last_page ?? 1); $p++): ?>
            <?php if ($p === ($page ?? 1)): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="/customers?page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if (($page ?? 1) < ($last_page ?? 1)): ?>
            <a href="/customers?page=<?= (($page ?? 1) + 1) ?>">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
