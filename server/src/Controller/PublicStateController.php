<?php
declare(strict_types=1);
namespace Tna\Controller;
use Tna\Http\{Request,Response}; use Tna\Service\PublicStateService; use Tna\Support\Clock;
final class PublicStateController
{ public function __construct(private readonly PublicStateService $service, private readonly Clock $clock){} public function __invoke(Request $request): Response { return HttpCache::response($this->service->latest(), $request, $this->clock); } }
