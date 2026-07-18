<?php
declare(strict_types=1);
namespace Tna\Controller;
use Tna\Http\{JsonBodyParser,Request,Response}; use Tna\Service\{RevisionConflictException,SessionService}; use Tna\Support\Clock;
final class SessionController
{ public function __construct(private readonly SessionService $service, private readonly Clock $clock, private readonly JsonBodyParser $parser=new JsonBodyParser()){}
  public function create(Request $r): Response { return Response::envelope($this->service->create(), $r->requestId()??'', $this->clock, 201); }
  public function get(Request $r,string $sessionId): Response { return Response::envelope($this->service->get($sessionId,$r->header('X-TNA-Session-Token')), $r->requestId()??'', $this->clock); }
  public function controls(Request $r,string $sessionId): Response { try{return Response::envelope($this->service->updateControls($sessionId,$r->header('X-TNA-Session-Token'),$this->parser->parse($r)), $r->requestId()??'', $this->clock);}catch(RevisionConflictException $e){return Response::envelope($e->state(), $r->requestId()??'', $this->clock, 409, ['code'=>'REVISION_CONFLICT','message'=>$e->getMessage()]);} }
  public function widget(Request $r,string $sessionId,string $widgetId): Response { try{return Response::envelope($this->service->updateWidget($sessionId,$widgetId,$r->header('X-TNA-Session-Token'),$this->parser->parse($r)), $r->requestId()??'', $this->clock);}catch(RevisionConflictException $e){return Response::envelope($e->state(), $r->requestId()??'', $this->clock, 409, ['code'=>'REVISION_CONFLICT','message'=>$e->getMessage()]);} }
}
