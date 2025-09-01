<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Leaderboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Admin Leaderboard</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= esc($error) ?>
            </div>
        <?php elseif (empty($leaderboard)): ?>
            <div class="alert alert-info">
                No leaderboard data available.
            </div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Contributions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $user): ?>
                        <tr>
                            <td>
                                <img src="<?= esc($user['avatar_url'], 'attr') ?>" alt="<?= esc($user['login']) ?>" width="32" height="32" class="rounded-circle me-2">
                                <a href="<?= esc($user['html_url'], 'attr') ?>" target="_blank"><?= esc($user['login']) ?></a>
                            </td>
                            <td><?= esc($user['contributions']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
