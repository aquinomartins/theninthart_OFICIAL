<?php
declare(strict_types=1);

$base = rtrim($argv[1] ?? getenv('TNA_API_BASE_URL') ?: '', '/');
if ($base === '') {
    fwrite(STDERR, "Usage: php scripts-build/api-smoke-test.php https://host/api/v1\n");
    exit(2);
}

$token = null;
$sessionId = null;
$revision = null;
$storyRunId = null;
$results = [];

function masked(?string $value): string { return $value === null ? '[none]' : substr($value, 0, 6) . '…' . substr($value, -4); }
function callApi(string $method, string $url, ?array $body = null, array $headers = []): array
{
    $ch = curl_init($url);
    $requestHeaders = array_merge(['Accept: application/json', 'X-Request-ID: smoke-' . bin2hex(random_bytes(4))], $headers);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 20]);
    if ($body !== null) {
        $payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $requestHeaders[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
    $raw = curl_exec($ch);
    if ($raw === false) { throw new RuntimeException(curl_error($ch)); }
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $responseBody = substr($raw, $headerSize);
    $json = json_decode($responseBody, true);
    return ['status' => $status, 'json' => $json, 'body' => $responseBody];
}
function expectResult(array &$results, string $name, bool $ok, string $message = ''): void
{
    $results[] = $ok;
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . ($message ? ' - ' . $message : '') . PHP_EOL;
}
function validateEnvelope(array &$results, string $name, array $response, int $status): void
{
    expectResult($results, "$name HTTP", $response['status'] === $status, 'expected ' . $status . ', got ' . $response['status']);
    expectResult($results, "$name JSON", is_array($response['json']), 'valid JSON body');
    expectResult($results, "$name request ID", is_string($response['json']['meta']['requestId'] ?? null) && $response['json']['meta']['requestId'] !== '', 'meta.requestId');
}

try {
    $r = callApi('GET', "$base/health"); validateEnvelope($results, 'GET /health', $r, 200);
    expectResult($results, 'catalog controls count', ($r['json']['data']['counts']['controls'] ?? null) === 32);
    expectResult($results, 'catalog widgets count', ($r['json']['data']['counts']['widgets'] ?? null) === 4);
    expectResult($results, 'catalog versions count', ($r['json']['data']['counts']['versions'] ?? null) === 7);
    expectResult($results, 'catalog quadrants count', ($r['json']['data']['counts']['quadrants'] ?? null) === 29);
    expectResult($results, 'catalog variants count', ($r['json']['data']['counts']['variants'] ?? null) === 203);

    $r = callApi('GET', "$base/bootstrap"); validateEnvelope($results, 'GET /bootstrap', $r, 200);
    $r = callApi('POST', "$base/sessions", []); validateEnvelope($results, 'POST /sessions', $r, 201);
    $sessionId = $r['json']['data']['sessionId'] ?? null; $token = $r['json']['data']['token'] ?? null; $revision = $r['json']['data']['revision'] ?? null;
    echo 'Session token: ' . masked(is_string($token) ? $token : null) . PHP_EOL;
    expectResult($results, 'session public ID', is_string($sessionId) && str_starts_with($sessionId, 'ses_'));

    $auth = ['X-TNA-Session-Token: ' . $token];
    $r = callApi('GET', "$base/sessions/$sessionId", null, $auth); validateEnvelope($results, 'GET /sessions/{id}', $r, 200);
    expectResult($results, 'revision readable', ($r['json']['data']['revision'] ?? null) === $revision);

    $r = callApi('PUT', "$base/sessions/$sessionId/controls", ['revision' => $revision, 'controls' => ['engrenagem-vegetal' => true]], $auth); validateEnvelope($results, 'PUT /sessions/{id}/controls', $r, 200);
    $revision = $r['json']['data']['revision'] ?? null;
    $conflict = callApi('PUT', "$base/sessions/$sessionId/controls", ['revision' => 1, 'controls' => ['engrenagem-vegetal' => false]], $auth); validateEnvelope($results, 'revision conflict', $conflict, 409);

    $r = callApi('PUT', "$base/sessions/$sessionId/widgets/timeline", ['revision' => $revision, 'parameters' => ['dominant-era' => 'present']], $auth); validateEnvelope($results, 'PUT /sessions/{id}/widgets/timeline', $r, 200);
    $revision = $r['json']['data']['revision'] ?? null;

    $idem = 'smoke-run-' . bin2hex(random_bytes(6));
    $r = callApi('POST', "$base/story-runs", ['sessionId' => $sessionId, 'revision' => $revision], array_merge($auth, ['Idempotency-Key: ' . $idem])); validateEnvelope($results, 'POST /story-runs', $r, 201);
    $storyRunId = $r['json']['data']['storyRunId'] ?? null;
    expectResult($results, '29 panels per story run', count($r['json']['data']['selections'] ?? []) === 29);
    $again = callApi('POST', "$base/story-runs", ['sessionId' => $sessionId, 'revision' => $revision], array_merge($auth, ['Idempotency-Key: ' . $idem])); validateEnvelope($results, 'story run idempotency replay', $again, 201);
    expectResult($results, 'idempotent storyRunId', ($again['json']['data']['storyRunId'] ?? null) === $storyRunId);

    $r = callApi('GET', "$base/story-runs/$storyRunId", null, $auth); validateEnvelope($results, 'GET /story-runs/{id}', $r, 200);
    $r = callApi('GET', "$base/public-state"); validateEnvelope($results, 'GET /public-state', $r, 200);
    $r = callApi('POST', "$base/events/batch", ['events' => [['type' => 'story-grid-ready', 'sessionId' => $sessionId, 'idempotencyKey' => 'smoke-event-' . bin2hex(random_bytes(6)), 'payload' => ['source' => 'smoke']]]], $auth); validateEnvelope($results, 'POST /events/batch', $r, 200);
} catch (Throwable $e) {
    expectResult($results, 'smoke exception', false, $e->getMessage());
}

$failures = count(array_filter($results, static fn(bool $ok): bool => !$ok));
echo $failures === 0 ? "Smoke test passed.\n" : "Smoke test failed: $failures checks failed.\n";
exit($failures === 0 ? 0 : 1);
