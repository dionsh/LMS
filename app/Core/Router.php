<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Middleware;
use InvalidArgumentException;
use LogicException;

/**
 * Maps METHOD + path to a controller method.
 *
 *   $router->get('/lajme/{slug}', [NewsController::class, 'show'], 'news.show');
 *   $router->get('/nxenesi/detyrat/{id:\d+}', [...]);   // {id:\d+} is passed as int
 *
 *   $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'role:admin']], function (Router $r) {
 *       $r->get('/', [DashboardController::class, 'index'], 'admin.dashboard');
 *   });
 *
 * Every POST route is automatically protected by the CSRF middleware.
 * Route constraints must not contain braces (use \d+ rather than \d{1,5}).
 */
final class Router
{
    private static ?Router $current = null;

    /** @var list<array{methods: list<string>, pattern: string, regex: string, casts: array<string, string>, handler: array{0: class-string, 1: string}, middleware: list<string>}> */
    private array $routes = [];

    /** @var array<string, string> route name => pattern */
    private array $names = [];

    private string $prefix = '';

    /** @var list<string> */
    private array $middleware = [];

    /** @param array<string, class-string<Middleware>> $aliases e.g. ['csrf' => VerifyCsrf::class] */
    public function __construct(private readonly array $aliases)
    {
        self::$current = $this;
    }

    public static function current(): self
    {
        return self::$current ?? throw new LogicException('The router has not been created yet.');
    }

    public function get(string $path, array $handler, ?string $name = null): void
    {
        $this->add(['GET', 'HEAD'], $path, $handler, $name);
    }

    public function post(string $path, array $handler, ?string $name = null): void
    {
        $this->add(['POST'], $path, $handler, $name);
    }

    /** @param array{prefix?: string, middleware?: list<string>} $attributes */
    public function group(array $attributes, callable $routes): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->middleware;

        $this->prefix .= '/' . trim($attributes['prefix'] ?? '', '/');
        $this->prefix = rtrim($this->prefix, '/');
        $this->middleware = array_merge($this->middleware, $attributes['middleware'] ?? []);

        $routes($this);

        $this->prefix = $previousPrefix;
        $this->middleware = $previousMiddleware;
    }

    /** Build the URL of a named route: route('news.show', ['slug' => 'hackathoni']). */
    public function urlFor(string $name, array $params = [], array $query = []): string
    {
        $pattern = $this->names[$name] ?? throw new InvalidArgumentException("Unknown route [{$name}].");

        $path = preg_replace_callback(
            '#\{([a-zA-Z_]\w*)(?::[^}]+)?\}#',
            static function (array $m) use ($params, $name): string {
                if (!array_key_exists($m[1], $params)) {
                    throw new InvalidArgumentException("Missing parameter [{$m[1]}] for route [{$name}].");
                }
                return rawurlencode((string) $params[$m[1]]);
            },
            $pattern,
        );

        return url($path, $query);
    }

    public function dispatch(Request $request): Response
    {
        // One canonical URL per page: /lajme/ → /lajme
        if ($request->isGet() && $request->hasTrailingSlash()) {
            $query = $request->queryString();
            return Response::redirect(url($request->path) . ($query !== '' ? '?' . $query : ''), 301);
        }

        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }

            if (!in_array($request->method, $route['methods'], true)) {
                array_push($allowed, ...$route['methods']);
                continue;
            }

            return $this->run($route, $request, $this->parameters($route, $matches));
        }

        if ($allowed !== []) {
            throw new HttpException(405, headers: ['Allow' => implode(', ', array_unique($allowed))]);
        }

        throw new HttpException(404);
    }

    private function add(array $methods, string $path, array $handler, ?string $name): void
    {
        $pattern = '/' . trim($this->prefix . '/' . trim($path, '/'), '/');
        [$regex, $casts] = $this->compile($pattern);

        $this->routes[] = [
            'methods'    => $methods,
            'pattern'    => $pattern,
            'regex'      => $regex,
            'casts'      => $casts,
            'handler'    => $handler,
            'middleware' => $this->middleware,
        ];

        if ($name !== null) {
            if (isset($this->names[$name])) {
                throw new LogicException("Route name [{$name}] is already used.");
            }
            $this->names[$name] = $pattern;
        }
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function compile(string $pattern): array
    {
        $casts = [];

        $regex = preg_replace_callback(
            '#\{([a-zA-Z_]\w*)(?::([^}]+))?\}|[^{]+#',
            static function (array $m) use (&$casts): string {
                if (!isset($m[1]) || $m[1] === '') {
                    return preg_quote($m[0], '#');       // literal part of the path
                }
                $constraint = $m[2] ?? '[^/]+';
                if ($constraint === '\d+') {
                    $casts[$m[1]] = 'int';
                }
                return '(?P<' . $m[1] . '>' . $constraint . ')';
            },
            $pattern,
        );

        return ['#^' . $regex . '$#u', $casts];
    }

    private function parameters(array $route, array $matches): array
    {
        $params = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = ($route['casts'][$key] ?? null) === 'int' ? (int) $value : $value;
            }
        }

        return $params;
    }

    private function run(array $route, Request $request, array $params): Response
    {
        $middleware = $route['middleware'];

        if (!$request->isGet()) {
            array_unshift($middleware, 'csrf');
        }

        foreach ($middleware as $spec) {
            [$alias, $arguments] = array_pad(explode(':', $spec, 2), 2, '');
            $class = $this->aliases[$alias] ?? throw new LogicException("Unknown middleware [{$alias}].");

            /** @var Middleware $instance */
            $instance = new $class();
            $result = $instance->handle($request, ...($arguments === '' ? [] : explode(',', $arguments)));

            if ($result instanceof Response) {
                return $result;
            }
        }

        [$class, $method] = $route['handler'];
        $result = (new $class($request))->{$method}(...$params);

        return $result instanceof Response ? $result : Response::html((string) $result);
    }
}
