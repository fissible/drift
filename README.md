# fissible/drift

OpenAPI drift detection and version analysis for PHP. Compares your live API routes against their spec, recommends semver bumps, and generates changelogs.

Part of the [Fissible](https://github.com/fissible) suite. New to Fissible? [Start with fissible/accord](https://github.com/fissible/accord) — it explains how the three packages work together.

---

## Why drift detection matters

As an API evolves, its spec and its actual routes can quietly fall out of sync. A route gets added but never documented. A route gets removed but the spec still describes it. Clients that rely on the spec to understand your API get a false picture of what's actually available.

**drift** surfaces that gap before it causes problems. It compares the routes your application actually serves against the routes your OpenAPI spec describes, tells you exactly what has been added or removed, and recommends how to version the change — so clients always know what to expect.

---

## Requirements

- PHP ^8.2
- OpenAPI 3.0.x spec files (YAML or JSON)
- fissible/accord ^1.0

## Installation

```bash
composer require fissible/drift
```

### Laravel auto-discovery

The service provider registers automatically. Console commands are registered with Artisan:

```bash
php artisan accord:validate
php artisan accord:version
php artisan drift:coverage
```

---

## How it works

Drift enumerates your application's routes via a `RouteInspectorInterface` implementation and compares them against the paths defined in your OpenAPI spec. Each route is normalised to a canonical form (`GET /v1/users/{param}`) so that parameter names don't cause false positives.

The result is a `DriftReport` describing:
- **Matched** routes — present in both the app and the spec
- **Added** routes — live in the app but not yet documented
- **Removed** routes — in the spec but no longer served by the app

From that report, drift recommends a semver bump and generates a changelog entry.

---

## Console commands

### `accord:validate`

Checks for drift between your live routes and your OpenAPI spec:

```bash
php artisan accord:validate
php artisan accord:validate --api-version=v2
```

Output is a table showing each route's status. Exits with a non-zero code if any drift is detected — useful in CI pipelines to catch undocumented or removed routes before they ship.

```
 Version  Method  Path                  Status
 v1       GET     /v1/users             PASS
 v1       POST    /v1/users             PASS
 v1       GET     /v1/users/{id}        WARN  (undocumented — not in spec)
 v1       DELETE  /v1/orders/{id}       FAIL  (removed — in spec but not routed)
```

### `accord:version`

Runs the full drift-analyse-changelog pipeline:

```bash
php artisan accord:version
php artisan accord:version --api-version=v1 --dry-run
php artisan accord:version --yes          # skip confirmation prompt
```

1. Detects drift for the given version
2. Reads the current `info.version` from the spec
3. Recommends a semver bump (major / minor / patch / none)
4. Confirms with you before writing any changes
5. Updates the spec's `info.version` in place
6. Prepends a changelog entry to `CHANGELOG.md`

When drift introduces breaking changes (removed routes), the command also notes that a new URI version (`/v2/`) should be considered.

### `drift:coverage`

Checks that every registered route resolves to an existing controller class and method:

```bash
php artisan drift:coverage
php artisan drift:coverage --api-version=v1
```

Output is a table showing each route's implementation status. Exits with a non-zero code if any routes are unimplemented, making it suitable for CI.

```
 Coverage       Method  Path               Action
 IMPLEMENTED    GET     /api/v1/posts      App\Http\Controllers\V1\PostController@index
 IMPLEMENTED    POST    /api/v1/posts      App\Http\Controllers\V1\PostController@store
 MISSING        DELETE  /api/v1/posts/{id} App\Http\Controllers\V1\PostController@destroy
 UNKNOWN        GET     /api/v1/ping       (closure)
```

- **IMPLEMENTED** — controller class and method both exist
- **MISSING** — class or method cannot be found; the route would throw a server error if called
- **UNKNOWN** — route uses a closure or has no resolvable action string

---

## Laravel

### Route inspector

The bundled `LaravelRouteInspector` enumerates routes in your application's `api` middleware group, skipping HEAD routes:

```php
// Registered automatically by DriftServiceProvider
use Fissible\Drift\Drivers\Laravel\Inspectors\LaravelRouteInspector;
```

To filter routes differently, bind your own `RouteInspectorInterface` implementation in a service provider:

```php
$this->app->singleton(RouteInspectorInterface::class, function () {
    return new LaravelRouteInspector(
        router: $this->app['router'],
        filter: fn($route) => str_starts_with($route->uri, 'api/'),
    );
});
```

---

## Core API

### DriftDetector

```php
use Fissible\Drift\DriftDetector;
use Fissible\Accord\FileSpecSource;

$source   = new FileSpecSource('/var/www/app');
$detector = new DriftDetector($source);
$report   = $detector->detect($routes, 'v1');

$report->isClean();            // true if no drift
$report->hasBreakingChanges(); // true if routes were removed
$report->hasAdditiveChanges(); // true if routes were added
$report->summary();            // human-readable string
```

### VersionAnalyser

```php
use Fissible\Drift\VersionAnalyser;

$analyser       = new VersionAnalyser($source);
$recommendation = $analyser->analyse($report);

$recommendation->bumpType;             // 'major' | 'minor' | 'patch' | 'none'
$recommendation->recommendedVersion;   // '1.2.0'
$recommendation->requiresNewUriVersion; // true when breaking changes are present
$recommendation->label();             // '1.1.0 → 1.2.0 (minor)'
```

### ChangelogGenerator

```php
use Fissible\Drift\ChangelogGenerator;

$generator = new ChangelogGenerator();
$entry     = $generator->generate($report, $recommendation);

// Prepend the entry to CHANGELOG.md (creates the file if missing)
$generator->prepend($entry, base_path('CHANGELOG.md'));
```

---

## Custom route inspectors

Implement `RouteInspectorInterface` to enumerate routes from any framework:

```php
use Fissible\Drift\RouteInspectorInterface;
use Fissible\Drift\RouteDefinition;

class MyFrameworkInspector implements RouteInspectorInterface
{
    public function getRoutes(): array
    {
        return array_map(
            fn($route) => new RouteDefinition($route->method, $route->path),
            $this->router->getRoutes(),
        );
    }
}
```

`RouteDefinition` normalises parameter syntax automatically — `:id`, `{id}`, and `<id>` all resolve to the same canonical path for comparison.

---

## CI integration

Add both commands to your CI pipeline:

```yaml
# .github/workflows/ci.yml
- name: Check API drift
  run: php artisan accord:validate

- name: Check for unimplemented routes
  run: php artisan drift:coverage
```

`accord:validate` fails the build if any routes are undocumented or have been removed from the spec without a version bump. `drift:coverage` fails the build if any route's controller or method is missing — catching spec-first development gaps before they reach production.

---

## License

MIT
