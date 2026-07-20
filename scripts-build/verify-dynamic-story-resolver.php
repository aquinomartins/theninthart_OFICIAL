#!/usr/bin/env php
<?php
declare(strict_types=1);

spl_autoload_register(static function(string $class): void { $prefix='Tna\\'; if(!str_starts_with($class,$prefix))return; $file=__DIR__.'/../server/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($file)) require $file; });
use Tna\Story\WeightedNarrativeResolver;

function j(string $f): array { return json_decode((string)file_get_contents(__DIR__.'/../data/'.$f), true, flags: JSON_THROW_ON_ERROR); }
function db(): PDO { $pdo=new PDO('sqlite::memory:'); $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC); $pdo->exec('CREATE TABLE story_controls(id INTEGER PRIMARY KEY, stable_key TEXT, enabled INT);CREATE TABLE story_versions(id INTEGER PRIMARY KEY, stable_key TEXT, title TEXT, enabled INT);CREATE TABLE quadrants(id INTEGER PRIMARY KEY, stable_key TEXT, number INT, block_key TEXT, block_label TEXT);CREATE TABLE quadrant_variants(id INTEGER PRIMARY KEY, slot_key TEXT, quadrant_id INT, story_version_id INT, quadrant_number INT, position INT, title TEXT, narrative_payload_json TEXT);'); return $pdo; }
function seed(PDO $pdo): void { $i=1; $s=$pdo->prepare('INSERT INTO story_controls VALUES(?,?,1)'); foreach(j('story-controls.json')['items'] as $r)$s->execute([$i++,$r['id']]); $i=1; $vid=[]; $s=$pdo->prepare('INSERT INTO story_versions VALUES(?,?,?,1)'); foreach(j('story-versions.json')['items'] as $r){$vid[$r['id']]=$i; $s->execute([$i++,$r['id'],$r['title']]);} $i=1; $qid=[]; $s=$pdo->prepare('INSERT INTO quadrants VALUES(?,?,?,?,?)'); foreach(j('quadrants.json')['items'] as $r){$qid[$r['stableKey']]=$i; $s->execute([$i++,$r['stableKey'],$r['number'],$r['blockId'],$r['blockLabel']]);} $i=1; $s=$pdo->prepare('INSERT INTO quadrant_variants VALUES(?,?,?,?,?,?,?,?)'); foreach(j('quadrant-slots.json')['items'] as $r)$s->execute([$i++,$r['id'],$qid[$r['quadrantId']],$vid[$r['versionId']],$r['quadrantNumber'],$r['position'],$r['id'],'{}']); }
function assert_ok(bool $ok,string $m): void { if(!$ok){fwrite(STDERR,"FAIL: $m\n"); exit(1);} }
function summarize(string $name,array $r): void { echo "[$name] dominant={$r['dominantVersion']} secondary=".implode(',', $r['resolvedState']['secondaryVersions'])."\n"; foreach($r['blocks'] as $b) echo "  {$b['id']}: {$b['version']}\n"; }
$pdo=db(); seed($pdo); $resolver=new WeightedNarrativeResolver(__DIR__.'/../data/story-resolution-rules.json');
$states=[
 'default'=>[[],[]],
 'past'=>[['prehistoria-terrestre'=>true,'mandioca'=>true,'tapioca'=>true],['timeline'=>['dominant-era'=>'past','specific-period'=>'deep-prehistory','temporal-direction'=>'backward']]],
 'future'=>[['futuro-urbano'=>true,'bobina-eletrica'=>true,'sensor-brasas'=>true],['timeline'=>['dominant-era'=>'future','specific-period'=>'distant-future','temporal-direction'=>'forward']]],
 'melancholy'=>[['melancolia'=>true,'morte'=>true,'futuro-distopico'=>true],['dramatic-climate'=>['melancholy'=>95,'danger'=>70,'life-death-balance'=>10]]],
 'paradox'=>[['paradoxo-temporal'=>true,'imaginacao'=>true,'presente-expandido'=>true],['timeline'=>['dominant-era'=>'mixed','paradox-level'=>95,'temporal-direction'=>'circular'],'machine-state'=>['error-rate'=>90,'rupture-probability'=>85]]],
];
$results=[]; foreach($states as $n=>[$c,$w]){$r=$resolver->resolve($pdo,12345,$c,$w,7); summarize($n,$r); $results[$n]=$r; assert_ok(count($r['selections'])===29,"$n has 29 selections"); assert_ok(count(array_unique(array_map(fn($s)=>$s['quadrant']['id'],$r['selections'])))===29,"$n no duplicate quadrants"); assert_ok($r['trace']['weightsApplied']===true,"$n weights applied"); foreach($r['selections'] as $s){assert_ok((bool)preg_match('/^q[0-9]{2}-v0[1-7]$/',$s['slotId']),"valid slot {$s['slotId']}");}}
$again=$resolver->resolve($pdo,12345,$states['future'][0],$states['future'][1],7); assert_ok(json_encode($again['selections'])===json_encode($results['future']['selections']),'determinism');
assert_ok($results['past']['dominantVersion']==='v01' || $results['past']['dominantVersion']==='v02' || $results['past']['dominantVersion']==='v04' || $results['past']['dominantVersion']==='v05','past favors past');
assert_ok($results['future']['dominantVersion']==='v03' || $results['future']['dominantVersion']==='v07','future favors future');
assert_ok($results['melancholy']['dominantVersion']==='v07','melancholy favors v07');
assert_ok(count($results['paradox']['resolvedState']['secondaryVersions'])>=1,'paradox allows controlled mix');
foreach(['world'=>range(13,18),'return'=>range(21,23),'consequences'=>range(24,29)] as $label=>$range){$vs=[]; foreach($results['paradox']['selections'] as $s) if(in_array($s['quadrant']['number'],$range,true)) $vs[$s['version']]=1; assert_ok(count($vs)===1,"$label coherent");}
$fallback=(new WeightedNarrativeResolver(__DIR__.'/../data/missing-rules.json'))->resolve($pdo,12345,[],[],1); assert_ok(($fallback['trace']['fallbackUsed']??false)===true,'baseline fallback works');
echo "OK dynamic story resolver verification passed. Fallback used only in explicit missing-rules check: ".($fallback['trace']['fallbackReason']??'')."\n";
