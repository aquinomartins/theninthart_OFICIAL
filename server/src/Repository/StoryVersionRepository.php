<?php
declare(strict_types=1);
namespace Tna\Repository;
use PDO;
final class StoryVersionRepository
{
    public function __construct(private readonly PDO $pdo){}
    public function count(): int { $s=$this->pdo->prepare('SELECT COUNT(*) FROM story_versions'); $s->execute(); return (int)$s->fetchColumn(); }
    public function allPublic(): array
    { $s=$this->pdo->prepare('SELECT stable_key, version_number, position, title, short_title, description, temporal_profile_json, world_profile_json, machine_profile_json, kitchen_profile_json, threat_profile_json, emotional_profile_json, residue_profile_json, title_patterns_json, abuela_line_themes_json, required_tags_json, preferred_tags_json, excluded_tags_json, default_widget_configuration_json, metadata_json, enabled, manifest_version FROM story_versions ORDER BY position'); $s->execute(); return array_map(static fn(array $r): array => ['key'=>$r['stable_key'],'versionNumber'=>(int)$r['version_number'],'position'=>(int)$r['position'],'title'=>$r['title'],'shortTitle'=>$r['short_title'],'description'=>$r['description'],'profiles'=>['temporal'=>RepositoryJson::decode($r['temporal_profile_json']),'world'=>RepositoryJson::decode($r['world_profile_json']),'machine'=>RepositoryJson::decode($r['machine_profile_json']),'kitchen'=>RepositoryJson::decode($r['kitchen_profile_json']),'threat'=>RepositoryJson::decode($r['threat_profile_json']),'emotional'=>RepositoryJson::decode($r['emotional_profile_json']),'residue'=>RepositoryJson::decode($r['residue_profile_json'])],'titlePatterns'=>RepositoryJson::decode($r['title_patterns_json']),'abuelaLineThemes'=>RepositoryJson::decode($r['abuela_line_themes_json']),'requiredTags'=>RepositoryJson::decode($r['required_tags_json']),'preferredTags'=>RepositoryJson::decode($r['preferred_tags_json']),'excludedTags'=>RepositoryJson::decode($r['excluded_tags_json']),'defaultWidgetConfiguration'=>RepositoryJson::decode($r['default_widget_configuration_json']),'metadata'=>RepositoryJson::decode($r['metadata_json']),'enabled'=>(bool)$r['enabled'],'manifestVersion'=>$r['manifest_version']], $s->fetchAll()); }
}
