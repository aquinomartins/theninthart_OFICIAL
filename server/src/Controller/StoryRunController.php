<?php
declare(strict_types=1);

namespace Tna\Controller;

use Tna\Http\{JsonBodyParser,Request,Response};
use Tna\Service\StoryRunService;
use Tna\Support\Clock;

final class StoryRunController
{
    public function __construct(private readonly StoryRunService $service, private readonly Clock $clock, private readonly JsonBodyParser $parser = new JsonBodyParser()){}
    public function create(Request $request): Response { return Response::envelope($this->service->create($this->parser->parse($request), $request->header('X-TNA-Session-Token'), $request->header('Idempotency-Key')), $request->requestId() ?? '', $this->clock, 201); }
    public function get(Request $request, string $storyRunId): Response { return Response::envelope($this->service->get($storyRunId, $request->header('X-TNA-Session-Token')), $request->requestId() ?? '', $this->clock); }
    public function eventsBatch(Request $request): Response { return Response::envelope($this->service->eventsBatch($this->parser->parse($request), $request->header('X-TNA-Session-Token')), $request->requestId() ?? '', $this->clock); }
}
