<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix='Tna\\'; if(!str_starts_with($class,$prefix)){return;} $file=__DIR__.'/../server/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($file)){require $file;}
});

use Tna\Controller\HttpCache;
use Tna\Database\ConnectionFactory;
use Tna\Http\{ApiException,Request,Router};
use Tna\Service\HealthService;
use Tna\Support\Clock;
use Tna\Config\DatabaseConfig;

function assert_true(bool $ok, string $msg): void { if(!$ok){throw new RuntimeException($msg);} }

$router = new Router();
$router->get('/v1/health', static fn(Request $r) => Tna\Http\Response::envelope(['ok'=>true], 'req_test', new Clock()));
assert_true($router->dispatch(new Request('GET','/v1/health',[],[],''))->status()===200, 'GET route should succeed.');
try { $router->dispatch(new Request('GET','/v1/missing',[],[],'留')); throw new RuntimeException('Missing route did not throw.'); } catch (ApiException $e) { assert_true($e->statusCode()===404, 'Missing route should be 404.'); }
try { $router->dispatch(new Request('POST','/v1/health',[],[],'{}')); throw new RuntimeException('Invalid method did not throw.'); } catch (ApiException $e) { assert_true($e->statusCode()===405, 'Invalid method should be 405.'); }

$data=['controls'=>array_fill(0,32,['key'=>'c']),'widgets'=>array_fill(0,4,['key'=>'w']),'versions'=>array_fill(0,7,['key'=>'v']),'quadrants'=>array_fill(0,29,['key'=>'q']),'slots'=>array_fill(0,203,['key'=>'s']),'publicSnapshot'=>null,'capabilities'=>['anonymousSessions'=>false,'googleAuth'=>false,'websocket'=>false,'eventBatch'=>false,'storyResolutionMode'=>'not-available']];
$etag=HttpCache::etagFor($data);
$res=HttpCache::response($data, new Request('GET','/v1/bootstrap',[],[],'','req_test'), new Clock());
assert_true($res->status()===200, 'ETag response should be 200 without If-None-Match.');
assert_true(($res->headers()['ETag']??'')===$etag, 'ETag header should match payload.');
$res304=HttpCache::response($data, new Request('GET','/v1/bootstrap',['if-none-match'=>$etag],[],'','req_test'), new Clock());
assert_true($res304->status()===304, 'Matching If-None-Match should return 304.');

$bad = new ConnectionFactory(new DatabaseConfig('127.0.0.1', 1, 'missing', 'missing', 'missing', 'utf8mb4'));
try { (new HealthService($bad, __DIR__.'/../database/migrations'))->check(); throw new RuntimeException('Unavailable database did not throw.'); } catch (ApiException $e) { assert_true($e->statusCode()===503, 'Unavailable database should map to 503.'); }

assert_true(count($data['controls'])===32 && count($data['widgets'])===4 && count($data['versions'])===7 && count($data['quadrants'])===29 && count($data['slots'])===203, 'Expected public catalog counts should be represented.');
echo "read endpoint checks passed\n";
