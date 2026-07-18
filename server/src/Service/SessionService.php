<?php
declare(strict_types=1);

namespace Tna\Service;

use PDO;
use Tna\Database\TransactionManager;
use Tna\Http\ApiException;
use Tna\Support\Clock;
use Tna\Support\Json;

final class SessionService
{
    private const WIDGETS = ['timeline','public-kitchen','machine-state','dramatic-climate'];

    public function __construct(private readonly TransactionManager $tx, private readonly Clock $clock){}

    public function create(): array
    {
        return $this->tx->run(function(PDO $pdo): array {
            $controls=$this->controls($pdo); $widgets=$this->widgets($pdo); $this->assertCatalog($controls,$widgets);
            $now=$this->nowDb(); $publicId=$this->id('ses'); $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token); $seed=random_int(1, PHP_INT_MAX);
            $pdo->prepare('INSERT INTO sessions (public_id,user_id,anonymous_token_hash,schema_version,mechanism_version,status,seed,revision,metadata_json,created_at,updated_at,last_seen_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$publicId,null,$hash,'1.0.0','1.0.0','active',$seed,1,'{}',$now,$now,$now]);
            $sessionId=(int)$pdo->lastInsertId();
            $ins=$pdo->prepare('INSERT INTO session_control_states (session_id,control_id,enabled,value_json,revision,updated_at) VALUES (?,?,?,?,?,?)');
            foreach($controls as $c){ $ins->execute([$sessionId,$c['id'],0,'{"enabled":false}',1,$now]); }
            $wins=$pdo->prepare('INSERT INTO session_widget_states (session_id,widget_id,state_json,revision,updated_at) VALUES (?,?,?,?,?)');
            foreach($widgets as $w){ $wins->execute([$sessionId,$w['id'],Json::encode(['parameters'=>$this->defaults($w['parameters'])]),1,$now]); }
            $this->outbox($pdo,$publicId,'session.created',['sessionId'=>$publicId,'revision'=>1]);
            $state=$this->readState($pdo,$publicId);
            $state['token']=$token;
            return $state;
        });
    }

    public function get(string $publicId, ?string $token): array { return $this->tx->run(fn(PDO $pdo): array => $this->authorizedState($pdo,$publicId,$token)); }

    public function updateControls(string $publicId, ?string $token, array $body): array
    {
        return $this->tx->run(function(PDO $pdo) use ($publicId,$token,$body): array {
            $session=$this->authorize($pdo,$publicId,$token,true); $revision=$this->revision($body); $updates=$body['controls']??null;
            if(!is_array($updates)){ throw new ApiException(422,'controls must be an object.'); }
            foreach($body as $k=>$_){ if(!in_array($k,['revision','controls'],true)){ throw new ApiException(422,'Unexpected property.'); }}
            $controls=$this->controls($pdo); $byKey=[]; foreach($controls as $c){$byKey[$c['stable_key']]=$c;}
            foreach($updates as $key=>$value){ if(!isset($byKey[$key]) || !is_bool($value)){ throw new ApiException(422,'Invalid control update.'); }}
            if((int)$session['revision']!==$revision){ return $this->conflict($pdo,$publicId); }
            $now=$this->nowDb(); $stmt=$pdo->prepare('UPDATE session_control_states SET enabled=?, value_json=?, revision=revision+1, updated_at=? WHERE session_id=? AND control_id=?');
            foreach($updates as $key=>$value){$stmt->execute([$value?1:0,Json::encode(['enabled'=>$value]),$now,$session['id'],$byKey[$key]['id']]);}
            $pdo->prepare('UPDATE sessions SET revision=revision+1, updated_at=?, last_seen_at=? WHERE id=? AND revision=?')->execute([$now,$now,$session['id'],$revision]);
            $this->outbox($pdo,$publicId,'session.controls.updated',['sessionId'=>$publicId,'revision'=>$revision+1,'controls'=>$updates]);
            return $this->readState($pdo,$publicId);
        });
    }

    public function updateWidget(string $publicId, string $widgetKey, ?string $token, array $body): array
    {
        return $this->tx->run(function(PDO $pdo) use ($publicId,$widgetKey,$token,$body): array {
            if(!in_array($widgetKey,self::WIDGETS,true)){ throw new ApiException(404,'Widget not found.'); }
            $session=$this->authorize($pdo,$publicId,$token,true); $revision=$this->revision($body);
            foreach($body as $k=>$_){ if(!in_array($k,['revision','parameters'],true)){ throw new ApiException(422,'Unexpected property.'); }}
            $params=$body['parameters']??null; if(!is_array($params)){ throw new ApiException(422,'parameters must be an object.'); }
            if((int)$session['revision']!==$revision){ return $this->conflict($pdo,$publicId); }
            $widget=$this->widget($pdo,$widgetKey); $current=$this->widgetState($pdo,(int)$session['id'],(int)$widget['id']); $merged=$current['parameters']??[];
            $defs=[]; foreach(RepositoryJsonShim::decode($widget['parameters_json']) as $p){$defs[$p['id']]=$p;}
            foreach($params as $k=>$v){ if(!isset($defs[$k]) || !$this->validParam($defs[$k],$v)){ throw new ApiException(422,'Invalid widget parameter.'); } $merged[$k]=$v; }
            $now=$this->nowDb(); $pdo->prepare('UPDATE session_widget_states SET state_json=?, revision=revision+1, updated_at=? WHERE session_id=? AND widget_id=?')->execute([Json::encode(['parameters'=>$merged]),$now,$session['id'],$widget['id']]);
            $pdo->prepare('UPDATE sessions SET revision=revision+1, updated_at=?, last_seen_at=? WHERE id=? AND revision=?')->execute([$now,$now,$session['id'],$revision]);
            $this->outbox($pdo,$publicId,'session.widget.updated',['sessionId'=>$publicId,'revision'=>$revision+1,'widgetId'=>$widgetKey,'parameters'=>$params]);
            return $this->readState($pdo,$publicId);
        });
    }

    private function revision(array $b): int { if(!array_key_exists('revision',$b)||!is_int($b['revision'])||$b['revision']<1){throw new ApiException(422,'revision is required.');} return $b['revision']; }
    private function authorize(PDO $pdo,string $id,?string $token,bool $lock=false): array { $s=$pdo->prepare('SELECT * FROM sessions WHERE public_id=?'.($lock?' FOR UPDATE':'')); $s->execute([$id]); $r=$s->fetch(); if(!$r){throw new ApiException(404,'Session not found.');} if(!is_string($token)||!hash_equals($r['anonymous_token_hash'],hash('sha256',$token))){throw new ApiException(401,'Invalid session token.');} return $r; }
    private function authorizedState(PDO $pdo,string $id,?string $token): array { $this->authorize($pdo,$id,$token); return $this->readState($pdo,$id); }
    private function readState(PDO $pdo,string $id): array { $s=$pdo->prepare('SELECT id,public_id,revision,status,created_at,updated_at,last_seen_at FROM sessions WHERE public_id=?'); $s->execute([$id]); $session=$s->fetch(); if(!$session){throw new ApiException(404,'Session not found.');} return ['sessionId'=>$session['public_id'],'revision'=>(int)$session['revision'],'status'=>$session['status'],'controls'=>$this->controlState($pdo,(int)$session['id']),'widgets'=>$this->widgetStates($pdo,(int)$session['id']),'createdAt'=>$session['created_at'],'updatedAt'=>$session['updated_at'],'lastSeenAt'=>$session['last_seen_at']]; }
    private function conflict(PDO $pdo,string $id): array { $state=$this->readState($pdo,$id); throw new RevisionConflictException($state); }
    private function controls(PDO $pdo): array { $s=$pdo->query('SELECT id,stable_key FROM story_controls ORDER BY position'); return $s->fetchAll(); }
    private function widgets(PDO $pdo): array { $s=$pdo->query('SELECT id,stable_key,parameters_json FROM story_widgets ORDER BY position'); return $s->fetchAll(); }
    private function widget(PDO $pdo,string $key): array { $s=$pdo->prepare('SELECT id,stable_key,parameters_json FROM story_widgets WHERE stable_key=?'); $s->execute([$key]); $w=$s->fetch(); if(!$w){throw new ApiException(404,'Widget not found.');} return $w; }
    private function widgetState(PDO $pdo,int $sid,int $wid): array { $s=$pdo->prepare('SELECT state_json FROM session_widget_states WHERE session_id=? AND widget_id=?'); $s->execute([$sid,$wid]); return RepositoryJsonShim::decode((string)$s->fetchColumn()); }
    private function controlState(PDO $pdo,int $sid): array { $s=$pdo->prepare('SELECT c.stable_key, scs.enabled FROM session_control_states scs JOIN story_controls c ON c.id=scs.control_id WHERE scs.session_id=? ORDER BY c.position'); $s->execute([$sid]); $out=[]; foreach($s->fetchAll() as $r){$out[$r['stable_key']]=(bool)$r['enabled'];} return $out; }
    private function widgetStates(PDO $pdo,int $sid): array { $s=$pdo->prepare('SELECT w.stable_key, sws.state_json FROM session_widget_states sws JOIN story_widgets w ON w.id=sws.widget_id WHERE sws.session_id=? ORDER BY w.position'); $s->execute([$sid]); $out=[]; foreach($s->fetchAll() as $r){$out[$r['stable_key']]=RepositoryJsonShim::decode($r['state_json']);} return $out; }
    private function defaults(string $json): array { $out=[]; foreach(RepositoryJsonShim::decode($json) as $p){$out[$p['id']]=$p['defaultValue']??null;} return $out; }
    private function validParam(array $d,mixed $v): bool { if(($d['type']??'')==='select'){return is_string($v)&&in_array($v,$d['options']??[],true);} if(($d['type']??'')==='range'){ if(!is_int($v)&&!is_float($v)){return false;} if($v<$d['min']||$v>$d['max']){return false;} $step=$d['step']??null; return !$step || fmod((float)($v-$d['min']),(float)$step)==0.0; } if(($d['type']??'')==='boolean'){return is_bool($v);} return false; }
    private function assertCatalog(array $c,array $w): void { if(count($c)!==32||count($w)!==4){throw new ApiException(503,'Session catalog is not ready.');} }
    private function outbox(PDO $pdo,string $agg,string $type,array $payload): void { $now=$this->nowDb(); $pdo->prepare('INSERT INTO outbox_events (public_id,aggregate_type,aggregate_public_id,event_type,payload_json,schema_version,status,available_at,created_at) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$this->id('evt'),'session',$agg,$type,Json::encode($payload),'1.0.0','pending',$now,$now]); }
    private function id(string $prefix): string { return $prefix.'_'.bin2hex(random_bytes(16)); }
    private function nowDb(): string { return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'); }
}
final class RevisionConflictException extends ApiException { public function __construct(private readonly array $state){parent::__construct(409,'Revision conflict.');} public function state(): array{return $this->state;} }
final class RepositoryJsonShim { public static function decode(string $json): array { $d=json_decode($json,true); return is_array($d)?$d:[]; } }
