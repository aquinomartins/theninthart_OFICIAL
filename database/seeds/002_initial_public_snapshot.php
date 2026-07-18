<?php
declare(strict_types=1);


return static function (\PDO $pdo): void {
    $exists = (int) $pdo->query('SELECT COUNT(*) FROM public_snapshots')->fetchColumn();
    if ($exists > 0) { return; }
    $payload = json_encode([
        'storyRuns' => 0,
        'sessions' => 0,
        'users' => 0,
        'interactions' => 0,
        'participation' => 0,
        'catalog' => ['controls' => 0, 'widgets' => 0, 'versions' => 0, 'quadrants' => 0, 'variants' => 0, 'assets' => 0],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $stmt = $pdo->prepare('INSERT INTO public_snapshots (public_id,snapshot_key,snapshot_type,aggregate_json,schema_version,revision,generated_at,created_at) VALUES (UUID(),?,?,?,?,1,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
    $stmt->execute(['initial-public-zeroes','catalog-public-initial',$payload,'1.0.0']);
};
