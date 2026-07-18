<?php
declare(strict_types=1);
namespace Tna\Controller;
use Tna\Http\{Request,Response}; use Tna\Service\BootstrapService; use Tna\Support\Clock;
final class BootstrapController
{ public function __construct(private readonly BootstrapService $service, private readonly Clock $clock){} public function __invoke(Request $request): Response { return HttpCache::response($this->service->data(), $request, $this->clock); } }
