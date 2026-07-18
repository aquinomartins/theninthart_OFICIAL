<?php
declare(strict_types=1);
namespace Tna\Repository;
use PDO;
final class StoryControlRepository
{
    public function __construct(private readonly PDO $pdo){}
    public function count(): int { $s=$this->pdo->prepare('SELECT COUNT(*) FROM story_controls'); $s->execute(); return (int)$s->fetchColumn(); }
    public function allPublic(): array
    {
        $stmt=$this->pdo->prepare('SELECT stable_key, position, family_key, family_label, title, short_title, description, control_type, default_value, enabled, affected_quadrants_json, tags_json, narrative_axes_json, conflicts_json, synergies_json, metadata_json, manifest_version FROM story_controls ORDER BY position');
        $stmt->execute();
        return array_map(static fn(array $r): array => ['key'=>$r['stable_key'],'position'=>(int)$r['position'],'family'=>['key'=>$r['family_key'],'label'=>$r['family_label']],'title'=>$r['title'],'shortTitle'=>$r['short_title'],'description'=>$r['description'],'controlType'=>$r['control_type'],'defaultValue'=>(bool)$r['default_value'],'enabled'=>(bool)$r['enabled'],'affectedQuadrants'=>RepositoryJson::decode($r['affected_quadrants_json']),'tags'=>RepositoryJson::decode($r['tags_json']),'narrativeAxes'=>RepositoryJson::decode($r['narrative_axes_json']),'conflicts'=>RepositoryJson::decode($r['conflicts_json']),'synergies'=>RepositoryJson::decode($r['synergies_json']),'metadata'=>RepositoryJson::decode($r['metadata_json']),'manifestVersion'=>$r['manifest_version']], $stmt->fetchAll());
    }
}
