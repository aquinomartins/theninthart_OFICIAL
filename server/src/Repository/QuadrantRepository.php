<?php
declare(strict_types=1);
namespace Tna\Repository;
use PDO;
final class QuadrantRepository
{
    public function __construct(private readonly PDO $pdo){}
    public function count(): int { $s=$this->pdo->prepare('SELECT COUNT(*) FROM quadrants'); $s->execute(); return (int)$s->fetchColumn(); }
    public function allPublic(): array
    { $s=$this->pdo->prepare('SELECT stable_key, number, position, block_key, block_label, fixed_function, narrative_purpose, aspect_class, grid_area, continuity_group, narrative_weight, previous_quadrant_key, next_quadrant_key, gutter_before_key, gutter_after_key, affected_controls_json, affected_widgets_json, allowed_version_ids_json, image_required, caption_allowed, dialogue_allowed, metadata_json, manifest_version FROM quadrants ORDER BY position'); $s->execute(); return array_map(static fn(array $r): array => ['key'=>$r['stable_key'],'number'=>(int)$r['number'],'position'=>(int)$r['position'],'block'=>['key'=>$r['block_key'],'label'=>$r['block_label']],'fixedFunction'=>$r['fixed_function'],'narrativePurpose'=>$r['narrative_purpose'],'aspectClass'=>$r['aspect_class'],'gridArea'=>$r['grid_area'],'continuityGroup'=>$r['continuity_group'],'narrativeWeight'=>(float)$r['narrative_weight'],'previousQuadrantKey'=>$r['previous_quadrant_key'],'nextQuadrantKey'=>$r['next_quadrant_key'],'gutterBeforeKey'=>$r['gutter_before_key'],'gutterAfterKey'=>$r['gutter_after_key'],'affectedControls'=>RepositoryJson::decode($r['affected_controls_json']),'affectedWidgets'=>RepositoryJson::decode($r['affected_widgets_json']),'allowedVersionIds'=>RepositoryJson::decode($r['allowed_version_ids_json']),'imageRequired'=>(bool)$r['image_required'],'captionAllowed'=>(bool)$r['caption_allowed'],'dialogueAllowed'=>(bool)$r['dialogue_allowed'],'metadata'=>RepositoryJson::decode($r['metadata_json']),'manifestVersion'=>$r['manifest_version']], $s->fetchAll()); }
}
