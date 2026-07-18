<?php
declare(strict_types=1);

namespace Tna\Story;

use PDO;
use RuntimeException;
use Tna\Support\Json;

final class BaselineStoryResolver
{
    public const RESOLUTION_MODE = 'baseline-v1';
    public const DOMINANT_VERSION = 'v01';
    public const TITLE = 'A Tapioca do T-Rex';

    /** @return array<string,mixed> */
    public function resolve(PDO $pdo, int $seed): array
    {
        $version = $this->version($pdo);
        $variants = $this->variants($pdo);
        if (count($variants) !== 29) {
            throw new RuntimeException('Baseline catalog must provide exactly 29 v01 quadrant variants.');
        }
        $blocks = [];
        $selections = [];
        foreach ($variants as $row) {
            $slot = sprintf('q%02d-v01', (int) $row['quadrant_number']);
            if ($row['slot_key'] !== $slot) {
                throw new RuntimeException('Baseline catalog slot order is inconsistent.');
            }
            $blocks[$row['block_key']] = ['id' => $row['block_key'], 'label' => $row['block_label'], 'version' => self::DOMINANT_VERSION];
            $payload = JsonLocal::decode((string) $row['narrative_payload_json']);
            $selections[] = [
                'position' => (int) $row['position'],
                'quadrant' => ['id' => $row['quadrant_key'], 'number' => (int) $row['quadrant_number'], 'blockId' => $row['block_key'], 'blockLabel' => $row['block_label']],
                'slotId' => $row['slot_key'],
                'version' => self::DOMINANT_VERSION,
                'title' => $row['title'],
                'selectionReason' => 'baseline-v1-fixed-slot',
                'payload' => $payload,
                '_ids' => ['quadrantId' => (int) $row['quadrant_id'], 'variantId' => (int) $row['variant_id']],
            ];
        }
        if (count($blocks) !== 6) {
            throw new RuntimeException('Baseline catalog must resolve exactly six v01 blocks.');
        }
        return [
            'title' => self::TITLE,
            'resolutionMode' => self::RESOLUTION_MODE,
            'dominantVersion' => self::DOMINANT_VERSION,
            'dominantVersionId' => (int) $version['id'],
            'seed' => $seed,
            'versions' => ['dominant' => self::DOMINANT_VERSION, 'blocks' => array_values($blocks)],
            'blocks' => array_values($blocks),
            'selections' => $selections,
            'resolvedState' => ['title' => self::TITLE, 'resolutionMode' => self::RESOLUTION_MODE, 'dominantVersion' => self::DOMINANT_VERSION, 'selectionCount' => 29, 'blockCount' => 6],
            'trace' => ['resolver' => 'BaselineStoryResolver', 'mode' => self::RESOLUTION_MODE, 'weightsApplied' => false, 'steps' => ['selected dominant version v01', 'selected fixed slots q01-v01 through q29-v01', 'validated 29 selections and six blocks']],
        ];
    }

    /** @return array<string,mixed> */
    private function version(PDO $pdo): array
    {
        $s = $pdo->prepare('SELECT id, stable_key FROM story_versions WHERE stable_key=? AND enabled=1');
        $s->execute([self::DOMINANT_VERSION]);
        $row = $s->fetch();
        if (!$row) { throw new RuntimeException('Baseline story version v01 is unavailable.'); }
        return $row;
    }

    /** @return list<array<string,mixed>> */
    private function variants(PDO $pdo): array
    {
        $sql = "SELECT qv.id AS variant_id,qv.slot_key,qv.quadrant_id,qv.quadrant_number,qv.position,qv.title,qv.narrative_payload_json,q.stable_key AS quadrant_key,q.block_key,q.block_label FROM quadrant_variants qv JOIN story_versions sv ON sv.id=qv.story_version_id JOIN quadrants q ON q.id=qv.quadrant_id WHERE sv.stable_key='v01' AND qv.slot_key REGEXP '^q[0-9][0-9]-v01$' ORDER BY qv.quadrant_number";
        $rows = $pdo->query($sql)->fetchAll();
        return is_array($rows) ? $rows : [];
    }
}

final class JsonLocal { public static function decode(string $json): array { $d=json_decode($json,true); return is_array($d)?$d:[]; } }
