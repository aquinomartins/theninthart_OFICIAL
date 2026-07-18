<?php
declare(strict_types=1);
namespace Tna\Repository;
use PDO;
final class StoryWidgetRepository
{
    public function __construct(private readonly PDO $pdo){}
    public function count(): int { $s=$this->pdo->prepare('SELECT COUNT(*) FROM story_widgets'); $s->execute(); return (int)$s->fetchColumn(); }
    public function allPublic(): array
    { $s=$this->pdo->prepare('SELECT stable_key, position, title, short_title, description, activation_button_id, panel_id, exclusive_panel, parameters_json, affected_quadrants_json, narrative_axes_json, future_capabilities_json, metadata_json, enabled, manifest_version FROM story_widgets ORDER BY position'); $s->execute(); return array_map(static fn(array $r): array => ['key'=>$r['stable_key'],'position'=>(int)$r['position'],'title'=>$r['title'],'shortTitle'=>$r['short_title'],'description'=>$r['description'],'activationButtonId'=>$r['activation_button_id'],'panelId'=>$r['panel_id'],'exclusivePanel'=>(bool)$r['exclusive_panel'],'parameters'=>RepositoryJson::decode($r['parameters_json']),'affectedQuadrants'=>RepositoryJson::decode($r['affected_quadrants_json']),'narrativeAxes'=>RepositoryJson::decode($r['narrative_axes_json']),'futureCapabilities'=>RepositoryJson::decode($r['future_capabilities_json']),'metadata'=>RepositoryJson::decode($r['metadata_json']),'enabled'=>(bool)$r['enabled'],'manifestVersion'=>$r['manifest_version']], $s->fetchAll()); }
}
