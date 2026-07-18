<?php
declare(strict_types=1);

namespace Tna\Service;

use PDO;
use Tna\Database\TransactionManager;
use Tna\Http\ApiException;
use Tna\Story\BaselineStoryResolver;
use Tna\Support\Json;

final class StoryRunService
{
    private const EVENT_TYPES = ['session-created','controls-updated','widget-updated','story-run-created','story-panel-viewed','story-grid-ready','story-engine-error'];
    public function __construct(private readonly TransactionManager $tx, private readonly BaselineStoryResolver $resolver = new BaselineStoryResolver()){}

    /** @return array<string,mixed> */
    public function create(array $body, ?string $token, ?string $key): array
    { return $this->tx->run(function(PDO $pdo) use($body,$token,$key): array {
        if(!is_string($key)||trim($key)===''){throw new ApiException(400,'Idempotency-Key is required.');}
        $sessionId=$body['sessionId']??null; $revision=$body['revision']??null;
        if(!is_string($sessionId)||$sessionId===''){throw new ApiException(422,'sessionId is required.');}
        if(!is_int($revision)||$revision<1){throw new ApiException(422,'revision is required.');}
        $existing=$this->runByKey($pdo,$key); if($existing){return $this->publicRun($pdo,$existing['public_id']);}
        $session=$this->authorize($pdo,$sessionId,$token,true); if((int)$session['revision']!==$revision){throw new ApiException(409,'Revision conflict.');}
        $resolved=$this->resolver->resolve($pdo,(int)$session['seed']); $now=$this->now(); $publicId=$this->id('run');
        $input=['sessionId'=>$sessionId,'revision'=>$revision,'controls'=>$this->controls($pdo,(int)$session['id']),'widgets'=>$this->widgets($pdo,(int)$session['id'])];
        $stmt=$pdo->prepare('INSERT INTO story_runs (public_id,session_id,user_id,dominant_version_id,schema_version,mechanism_version,rules_version,resolution_mode,input_state_json,resolved_state_json,continuity_payload_json,resolution_trace_json,seed,revision,idempotency_key,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$publicId,$session['id'],$session['user_id'],$resolved['dominantVersionId'],'1.0.0','1.0.0','baseline-v1',BaselineStoryResolver::RESOLUTION_MODE,Json::encode($input),Json::encode($resolved['resolvedState']),Json::encode(['blocks'=>$resolved['blocks']]),Json::encode($resolved['trace']),(int)$session['seed'],1,$key,$now,$now]);
        $rid=(int)$pdo->lastInsertId(); $panel=$pdo->prepare('INSERT INTO story_run_panels (story_run_id,quadrant_id,quadrant_variant_id,position,selection_reason,panel_payload_json,created_at) VALUES (?,?,?,?,?,?,?)');
        foreach($resolved['selections'] as $sel){$ids=$sel['_ids']; $pub=$sel; unset($pub['_ids']); $panel->execute([$rid,$ids['quadrantId'],$ids['variantId'],$sel['position'],$sel['selectionReason'],Json::encode($pub),$now]);}
        $this->interaction($pdo,(int)$session['id'],$rid,'story-run-created',['storyRunId'=>$publicId],$key.':story-run-created',$now);
        $this->outbox($pdo,'story_run',$publicId,'story.run.created',['storyRunId'=>$publicId,'sessionId'=>$sessionId],$now);
        return $this->publicRun($pdo,$publicId);
    });}

    /** @return array<string,mixed> */
    public function get(string $runId, ?string $token): array
    { return $this->tx->run(function(PDO $pdo) use($runId,$token): array { $run=$this->run($pdo,$runId); $this->authorizeByInternalId($pdo,(int)$run['session_id'],$token); return $this->formatRun($pdo,$run); }); }

    /** @return array<string,int> */
    public function eventsBatch(array $body, ?string $token): array
    { return $this->tx->run(function(PDO $pdo) use($body,$token): array { $events=$body['events']??null; if(!is_array($events)||!array_is_list($events)){throw new ApiException(422,'events must be an array.');} if(count($events)>100){throw new ApiException(422,'A maximum of 100 events is allowed.');} $counts=['accepted'=>0,'duplicated'=>0,'rejected'=>0]; foreach($events as $e){ if(!is_array($e)){ $counts['rejected']++; continue; } $type=$e['type']??null; $key=$e['idempotencyKey']??null; $sid=$e['sessionId']??null; if(!is_string($type)||!in_array($type,self::EVENT_TYPES,true)||!is_string($key)||$key===''||!is_string($sid)){ $counts['rejected']++; continue; } try{$s=$this->authorize($pdo,$sid,$token,false);}catch(\Throwable){$counts['rejected']++; continue;} if($this->eventByKey($pdo,$key)){ $counts['duplicated']++; continue; } $this->interaction($pdo,(int)$s['id'],null,$type,is_array($e['payload']??null)?$e['payload']:[],$key,$this->now()); $counts['accepted']++; } return $counts; }); }

    private function authorize(PDO $pdo,string $id,?string $token,bool $lock): array { $s=$pdo->prepare('SELECT * FROM sessions WHERE public_id=?'.($lock?' FOR UPDATE':'')); $s->execute([$id]); $r=$s->fetch(); if(!$r){throw new ApiException(404,'Session not found.');} if(!is_string($token)||!hash_equals($r['anonymous_token_hash'],hash('sha256',$token))){throw new ApiException(401,'Invalid session token.');} return $r; }
    private function authorizeByInternalId(PDO $pdo,int $id,?string $token): void { $s=$pdo->prepare('SELECT * FROM sessions WHERE id=?'); $s->execute([$id]); $r=$s->fetch(); if(!$r||!is_string($token)||!hash_equals($r['anonymous_token_hash'],hash('sha256',$token))){throw new ApiException(401,'Invalid session token.');} }
    private function runByKey(PDO $pdo,string $key): ?array { $s=$pdo->prepare('SELECT public_id FROM story_runs WHERE idempotency_key=?'); $s->execute([$key]); $r=$s->fetch(); return $r?:null; }
    private function eventByKey(PDO $pdo,string $key): bool { $s=$pdo->prepare('SELECT id FROM interaction_events WHERE idempotency_key=?'); $s->execute([$key]); return (bool)$s->fetch(); }
    private function run(PDO $pdo,string $id): array { $s=$pdo->prepare('SELECT * FROM story_runs WHERE public_id=?'); $s->execute([$id]); $r=$s->fetch(); if(!$r){throw new ApiException(404,'Story run not found.');} return $r; }
    private function publicRun(PDO $pdo,string $id): array { return $this->formatRun($pdo,$this->run($pdo,$id)); }
    private function formatRun(PDO $pdo,array $run): array { $p=$pdo->prepare('SELECT panel_payload_json FROM story_run_panels WHERE story_run_id=? ORDER BY position'); $p->execute([$run['id']]); $sels=[]; foreach($p->fetchAll() as $row){$sels[]=json_decode($row['panel_payload_json'],true)?:[];} return ['storyRunId'=>$run['public_id'],'title'=>BaselineStoryResolver::TITLE,'resolutionMode'=>$run['resolution_mode'],'revision'=>(int)$run['revision'],'seed'=>(int)$run['seed'],'versions'=>['dominant'=>BaselineStoryResolver::DOMINANT_VERSION],'resolvedState'=>json_decode($run['resolved_state_json'],true)?:[],'trace'=>json_decode($run['resolution_trace_json'],true)?:[],'selections'=>$sels,'createdAt'=>$run['created_at'],'updatedAt'=>$run['updated_at']]; }
    private function controls(PDO $pdo,int $sid): array { $s=$pdo->prepare('SELECT c.stable_key,scs.enabled FROM session_control_states scs JOIN story_controls c ON c.id=scs.control_id WHERE scs.session_id=? ORDER BY c.position'); $s->execute([$sid]); $o=[]; foreach($s->fetchAll() as $r){$o[$r['stable_key']]=(bool)$r['enabled'];} return $o; }
    private function widgets(PDO $pdo,int $sid): array { $s=$pdo->prepare('SELECT w.stable_key,sws.state_json FROM session_widget_states sws JOIN story_widgets w ON w.id=sws.widget_id WHERE sws.session_id=? ORDER BY w.position'); $s->execute([$sid]); $o=[]; foreach($s->fetchAll() as $r){$o[$r['stable_key']]=json_decode($r['state_json'],true)?:[];} return $o; }
    private function interaction(PDO $pdo,int $sid,?int $rid,string $type,array $payload,string $key,string $now): void { $pdo->prepare('INSERT INTO interaction_events (public_id,session_id,user_id,story_run_id,event_type,payload_json,schema_version,idempotency_key,occurred_at,received_at) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([$this->id('evt'),$sid,null,$rid,$type,Json::encode($payload),'1.0.0',$key,$now,$now]); }
    private function outbox(PDO $pdo,string $aggType,string $agg,string $type,array $payload,string $now): void { $pdo->prepare('INSERT INTO outbox_events (public_id,aggregate_type,aggregate_public_id,event_type,payload_json,schema_version,status,available_at,created_at) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$this->id('evt'),$aggType,$agg,$type,Json::encode($payload),'1.0.0','pending',$now,$now]); }
    private function id(string $prefix): string { return $prefix.'_'.bin2hex(random_bytes(16)); }
    private function now(): string { return gmdate('Y-m-d H:i:s'); }
}
