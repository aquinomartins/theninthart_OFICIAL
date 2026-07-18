<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix='Tna\\'; if(!str_starts_with($class,$prefix)){return;} $file=__DIR__.'/../server/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($file)){require $file;}
});

use Tna\Story\BaselineStoryResolver;

function assert_true(bool $ok, string $msg): void { if(!$ok){throw new RuntimeException($msg);} }

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->sqliteCreateFunction('REGEXP', static fn($pattern, $value): int => preg_match('/'.$pattern.'/', (string)$value) === 1 ? 1 : 0, 2);
$pdo->exec('CREATE TABLE story_versions (id INTEGER PRIMARY KEY, stable_key TEXT, enabled INTEGER)');
$pdo->exec('CREATE TABLE quadrants (id INTEGER PRIMARY KEY, stable_key TEXT, block_key TEXT, block_label TEXT)');
$pdo->exec('CREATE TABLE quadrant_variants (id INTEGER PRIMARY KEY, slot_key TEXT, quadrant_id INTEGER, story_version_id INTEGER, quadrant_number INTEGER, position INTEGER, title TEXT, narrative_payload_json TEXT)');
$pdo->exec("INSERT INTO story_versions (id,stable_key,enabled) VALUES (1,'v01',1)");
$qi=$pdo->prepare('INSERT INTO quadrants (id,stable_key,block_key,block_label) VALUES (?,?,?,?)');
$vi=$pdo->prepare('INSERT INTO quadrant_variants (id,slot_key,quadrant_id,story_version_id,quadrant_number,position,title,narrative_payload_json) VALUES (?,?,?,?,?,?,?,?)');
for($i=1;$i<=29;$i++){ $q=sprintf('q%02d',$i); $block='block-'.(int)ceil($i/5); if($i>25){$block='block-6';} $qi->execute([$i,$q,$block,'Bloco '.$block]); $vi->execute([$i,$q.'-v01',$i,1,$i,$i,$q.'-v01','{}']); }
$r=(new BaselineStoryResolver())->resolve($pdo,123);
assert_true($r['title']==='A Tapioca do T-Rex','title should be canonical.');
assert_true($r['resolutionMode']==='baseline-v1','resolution mode should be baseline-v1.');
assert_true($r['dominantVersion']==='v01','dominant version should be v01.');
assert_true(count($r['blocks'])===6,'baseline should resolve six blocks.');
assert_true(count($r['selections'])===29,'baseline should resolve 29 selections.');
assert_true($r['selections'][0]['slotId']==='q01-v01','first slot should be q01-v01.');
assert_true($r['selections'][28]['slotId']==='q29-v01','last slot should be q29-v01.');
assert_true($r['trace']['weightsApplied']===false,'controls weights should not be applied.');
echo "baseline story resolver checks passed\n";
