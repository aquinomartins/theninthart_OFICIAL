<?php
declare(strict_types=1);

namespace Tna\Http;

use Tna\Support\Json;

final class JsonBodyParser
{
    /** @return array<string,mixed> */
    public function parse(Request $request): array
    {
        if ($request->body() === '') {
            return [];
        }
        return Json::decodeObject($request->body());
    }
}
