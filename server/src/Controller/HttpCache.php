<?php
declare(strict_types=1);
namespace Tna\Controller;
use Tna\Http\Request; use Tna\Http\Response; use Tna\Support\Clock; use Tna\Support\Json;
final class HttpCache
{
    public static function etagFor(array $data): string { return '"' . hash('sha256', Json::encode($data)) . '"'; }
    public static function response(array $data, Request $request, Clock $clock, string $cacheControl='private, max-age=0, must-revalidate'): Response
    { $etag=self::etagFor($data); if(trim((string)$request->header('if-none-match'))===$etag){ return new Response(304, [], ['ETag'=>$etag,'Cache-Control'=>$cacheControl]); } return Response::envelope($data,$request->requestId()??'unavailable',$clock,200,null,['ETag'=>$etag,'Cache-Control'=>$cacheControl]); }
}
