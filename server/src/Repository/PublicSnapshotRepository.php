<?php
declare(strict_types=1);
namespace Tna\Repository;
use PDO;
final class PublicSnapshotRepository
{
    public function __construct(private readonly PDO $pdo){}
    public function latestPublic(): ?array
    { $s=$this->pdo->prepare('SELECT public_id, snapshot_key, snapshot_type, aggregate_json, schema_version, revision, generated_at, created_at FROM public_snapshots ORDER BY generated_at DESC, id DESC LIMIT 1'); $s->execute(); $r=$s->fetch(); if(!$r){return null;} return ['publicId'=>$r['public_id'],'snapshotKey'=>$r['snapshot_key'],'snapshotType'=>$r['snapshot_type'],'aggregate'=>RepositoryJson::decode($r['aggregate_json']),'schemaVersion'=>$r['schema_version'],'revision'=>(int)$r['revision'],'generatedAt'=>$r['generated_at'],'createdAt'=>$r['created_at']]; }
}
