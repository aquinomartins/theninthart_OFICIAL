<?php
declare(strict_types=1);
namespace Tna\Repository;
use PDO;
final class QuadrantVariantRepository
{
    public function __construct(private readonly PDO $pdo){}
    public function count(): int { $s=$this->pdo->prepare('SELECT COUNT(*) FROM quadrant_variants'); $s->execute(); return (int)$s->fetchColumn(); }
    public function slotCount(): int { return $this->count(); }
    public function allPublicSlots(): array
    { $s=$this->pdo->prepare('SELECT v.slot_key, v.quadrant_number, v.version_number, v.position, v.title, v.description, v.narrative_payload_json, v.tags_json, v.required_controls_json, v.excluded_controls_json, v.widget_constraints_json, v.status, v.revision, v.metadata_json, a.expected_path, a.asset_path, a.mime_type, a.width, a.height, a.aspect_ratio, a.alt_text, a.caption, a.status asset_status, a.checksum, a.revision asset_revision FROM quadrant_variants v LEFT JOIN media_assets a ON a.quadrant_variant_id=v.id ORDER BY v.position'); $s->execute(); return array_map(static fn(array $r): array => ['key'=>$r['slot_key'],'quadrantNumber'=>(int)$r['quadrant_number'],'versionNumber'=>(int)$r['version_number'],'position'=>(int)$r['position'],'title'=>$r['title'],'description'=>$r['description'],'narrativePayload'=>RepositoryJson::decode($r['narrative_payload_json']),'tags'=>RepositoryJson::decode($r['tags_json']),'requiredControls'=>RepositoryJson::decode($r['required_controls_json']),'excludedControls'=>RepositoryJson::decode($r['excluded_controls_json']),'widgetConstraints'=>RepositoryJson::decode($r['widget_constraints_json']),'status'=>$r['status'],'revision'=>(int)$r['revision'],'metadata'=>RepositoryJson::decode($r['metadata_json']),'asset'=>['path'=>$r['asset_path'],'expectedPath'=>$r['expected_path'],'mimeType'=>$r['mime_type'],'width'=>$r['width']===null?null:(int)$r['width'],'height'=>$r['height']===null?null:(int)$r['height'],'aspectRatio'=>$r['aspect_ratio'],'altText'=>$r['alt_text'],'caption'=>$r['caption'],'status'=>$r['asset_status'],'checksum'=>$r['checksum'],'revision'=>$r['asset_revision']===null?null:(int)$r['asset_revision']]], $s->fetchAll()); }
}
