<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$target = $root . '/data/quadrant-slots.json';
$check = in_array('--check', $argv, true);
$force = in_array('--force', $argv, true);
function loadJson(string $path): array {
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) { throw new RuntimeException("JSON inválido: {$path}"); }
    return $data;
}
try {
    $quadrants = loadJson($root . '/data/quadrants.json')['items'];
    $versions = loadJson($root . '/data/story-versions.json')['items'];
    usort($quadrants, fn($a, $b) => $a['number'] <=> $b['number']);
    usort($versions, fn($a, $b) => $a['versionNumber'] <=> $b['versionNumber']);
    $existing = is_file($target) ? loadJson($target) : ['items' => []];
    $byId = [];
    foreach ($existing['items'] ?? [] as $slot) { $byId[$slot['id']] = $slot; }
    $items = [];
    foreach ($quadrants as $q) foreach ($versions as $v) {
        $id = $q['id'] . '-' . $v['id'];
        $position = (($q['number'] - 1) * count($versions)) + $v['versionNumber'];
        $slot = ['id'=>$id,'slotId'=>$id,'quadrantId'=>$q['id'],'quadrantNumber'=>$q['number'],'versionId'=>$v['id'],'versionNumber'=>$v['versionNumber'],'position'=>$position,'asset'=>['path'=>null,'expectedPath'=>"/assets/story/quadrants/{$q['id']}/{$v['id']}/{$id}.png",'mimeType'=>null],'status'=>'empty','altText'=>sprintf('Quadrante %02d, versão %02d, imagem pendente.', $q['number'], $v['versionNumber']),'caption'=>null,'revision'=>0,'checksum'=>null,'dimensions'=>['width'=>null,'height'=>null,'aspectRatio'=>null],'metadata'=>[]];
        if (!$force && isset($byId[$id])) {
            $old = $byId[$id];
            foreach (['status','caption','revision','checksum','dimensions','metadata'] as $key) { $slot[$key] = $old[$key] ?? $slot[$key]; }
            $slot['asset']['path'] = $old['asset']['path'] ?? null;
            $slot['asset']['mimeType'] = $old['asset']['mimeType'] ?? null;
        }
        $items[] = $slot;
    }
    if (count($items) !== count($quadrants) * count($versions) || count(array_unique(array_column($items, 'id'))) !== count($items)) { throw new RuntimeException('Produto cartesiano interno inválido.'); }
    $manifest = ['schemaVersion'=>'1.0.0','manifestVersion'=>'1.0.0','locale'=>'pt-BR','generatedAt'=>null,'items'=>$items];
    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    if ($check) {
        if (!is_file($target) || file_get_contents($target) !== $json) { fwrite(STDERR, "[ERRO] quadrant-slots.json está desatualizado.\n"); exit(1); }
        echo "[OK] quadrant-slots.json está determinístico e atualizado (" . count($items) . " slots).\n"; exit(0);
    }
    $published = array_filter($byId, fn($s) => ($s['status'] ?? '') === 'published');
    if ($force && $published) { fwrite(STDERR, "[ERRO] --force recusado: há slots publicados. Preserve-os no modo padrão.\n"); exit(1); }
    $tmp = $target . '.tmp'; file_put_contents($tmp, $json, LOCK_EX); rename($tmp, $target);
    echo "[OK] Gerados " . count($items) . " slots em data/quadrant-slots.json.\n";
} catch (Throwable $e) { fwrite(STDERR, '[ERRO] ' . $e->getMessage() . "\n"); exit(1); }
