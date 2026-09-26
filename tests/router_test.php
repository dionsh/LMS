<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\VerifyCsrf;

final class ProbeController extends App\Controllers\Controller
{
    public function show(int $id): Response { return Response::html('show:' . var_export($id, true)); }
    public function slug(string $slug): Response { return Response::html('slug:' . $slug); }
    public function plain(): string { return 'plain'; }
}

$_SESSION = [];
$router = new Router(['csrf' => VerifyCsrf::class]);
$router->get('/', [ProbeController::class, 'plain'], 'home');
$router->get('/lajme/{slug}', [ProbeController::class, 'slug'], 'news.show');
$router->group(['prefix' => '/nxenesi'], function (Router $r) {
    $r->get('/detyrat/{id:\d+}', [ProbeController::class, 'show'], 'student.assignment');
    $r->post('/detyrat/{id:\d+}/dorezo', [ProbeController::class, 'show'], 'student.submit');
});

$pass = 0; $fail = 0;
function check(string $label, callable $fn, string $expected): void {
    global $pass, $fail;
    try { $r = $fn(); $got = $r instanceof Response ? $r->status() . ' ' . ($r->header('Location') ?? $r->body()) : (string) $r; }
    catch (HttpException $e) { $got = 'HTTP ' . $e->status . ($e->headers ? ' ' . json_encode($e->headers) : ''); }
    $ok = $got === $expected; $ok ? $pass++ : $fail++;
    printf("%s  %-44s => %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $got, $ok ? '' : "   (expected: $expected)");
}

check('GET /',                         fn() => $router->dispatch(Request::create('GET', '/')), '200 plain');
check('GET /lajme/hackathoni-kombetar', fn() => $router->dispatch(Request::create('GET', '/lajme/hackathoni-kombetar')), '200 slug:hackathoni-kombetar');
check('GET /lajme/n%C3%AB-fokus (UTF-8)', fn() => $router->dispatch(Request::create('GET', '/lajme/n%C3%AB-fokus')), '200 slug:në-fokus');
check('group + int param',             fn() => $router->dispatch(Request::create('GET', '/nxenesi/detyrat/42')), '200 show:42');
check('non-numeric id → 404',          fn() => $router->dispatch(Request::create('GET', '/nxenesi/detyrat/abc')), 'HTTP 404');
check('unknown path → 404',            fn() => $router->dispatch(Request::create('GET', '/nuk-ekziston')), 'HTTP 404');
check('POST on GET route → 405',       fn() => $router->dispatch(Request::create('POST', '/lajme/x')), 'HTTP 405 {"Allow":"GET, HEAD"}');
check('trailing slash → 301',          fn() => $router->dispatch(Request::create('GET', '/lajme/x/', ['faqja' => '2'])), '301 /lajme/x?faqja=2');
check('POST without token → 419',      fn() => $router->dispatch(Request::create('POST', '/nxenesi/detyrat/7/dorezo')), 'HTTP 419');
check('POST with wrong token → 419',   fn() => $router->dispatch(Request::create('POST', '/nxenesi/detyrat/7/dorezo', [], ['_token' => 'forged'])), 'HTTP 419');
check('POST with valid token → 200',   fn() => $router->dispatch(Request::create('POST', '/nxenesi/detyrat/7/dorezo', [], ['_token' => csrf_token()])), '200 show:7');
check('route() builds URL',            fn() => route('student.assignment', ['id' => 42]), '/nxenesi/detyrat/42');
check('route() encodes params',        fn() => route('news.show', ['slug' => 'a b/c']), '/lajme/a%20b%2Fc');

echo "\n$pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
