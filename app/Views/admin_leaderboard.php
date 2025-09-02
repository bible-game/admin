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
                        <th>User Id</th>
                        <th>Name</th>
                        <th>Game Stars</th>
                        <th>Review Stars</th>
                        <th>Total Stars</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $user): ?>
                        <tr>
                            <td><?= esc($user['id']) ?></td>
                            <td><?= esc($user['firstname']) . " " . esc($user['lastname']) ?></td>
                            <td><?= esc($user['gameStars']) ?></td>
                            <td><?= esc($user['reviewStars']) ?></td>
                            <td><?= esc($user['gameStars']) + esc($user['reviewStars']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
