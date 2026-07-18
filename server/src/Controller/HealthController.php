<?php
declare(strict_types=1);
namespace Tna\Controller;
use Tna\Http\{ApiException,Request,Response}; use Tna\Service\HealthService; use Tna\Support\Clock;
final class HealthController
{
    public function __construct(private readonly HealthService $service, private readonly Clock $clock){}
    public function __invoke(Request $request): Response
    {
        try {
            return Response::envelope($this->service->check(), $request->requestId()??'unavailable', $this->clock, 200, null, ['Cache-Control'=>'no-store']);
        } catch (ApiException $exception) {
            return Response::envelope([], $request->requestId()??'unavailable', $this->clock, $exception->statusCode(), ['code'=>$exception->statusCode(),'message'=>'Service unavailable.'], ['Cache-Control'=>'no-store']);
        }
    }
}
