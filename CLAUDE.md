# CLAUDE.md — fissible/drift

## What this is

Drift detection and version analysis for PHP APIs. Compares live routes against an OpenAPI spec, identifies added/removed routes, recommends a semver bump, and generates changelog entries. Provides three Artisan commands: `accord:validate`, `accord:version`, `drift:coverage`.

Depends on **fissible/accord** for spec loading (`SpecSourceInterface`, `FileSpecSource`).

## Running tests

```bash
vendor/bin/phpunit
```

Two suites: `Unit` and `Feature`. Unit tests are in `tests/Unit/`; the Feature suite exists but is currently empty.

## Key files

| File | Purpose |
|---|---|
| `src/DriftDetector.php` | Compares `RouteDefinition[]` against a spec version; returns `DriftReport` |
| `src/DriftReport.php` | Result: `matched`, `added`, `removed` route arrays; `isClean()`, `hasBreakingChanges()` |
| `src/RouteDefinition.php` | Normalised route (method + path); normalises `:id`, `{id}`, `<id>` to `{id}` |
| `src/VersionAnalyser.php` | Reads current semver from spec, recommends bump from `DriftReport` |
| `src/VersionRecommendation.php` | Bump result: `bumpType`, `currentVersion`, `recommendedVersion`, `requiresNewUriVersion` |
| `src/ChangelogGenerator.php` | Generates a Markdown changelog entry; `prepend()` writes to `CHANGELOG.md` |
| `src/CoverageAnalyser.php` | Checks each route for an existing controller class + method |
| `src/RouteInspectorInterface.php` | Contract — implement to enumerate routes from any framework |
| `src/Console/ValidateCommand.php` | `accord:validate` — exits non-zero on drift |
| `src/Console/VersionCommand.php` | `accord:version` — full detect→bump→changelog pipeline |
| `src/Console/CoverageCommand.php` | `drift:coverage` — exits non-zero on missing implementations |
| `src/Drivers/Laravel/Inspectors/LaravelRouteInspector.php` | Enumerates routes from the Laravel router (`api` middleware group) |

## Architecture rules

**`RouteDefinition` normalises paths — always use it for comparisons.** Never compare raw URI strings between routes and spec paths. The normalisation handles `:param`, `{param}`, and `<param>` variants.

**`DriftReport` is the canonical intermediate representation.** Everything downstream (version analysis, changelog, UI display) consumes a `DriftReport`. Keep that boundary clean.

**Commands are thin shells.** Business logic lives in `DriftDetector`, `VersionAnalyser`, and `ChangelogGenerator` — not in the console commands themselves.

## Conventions

- `declare(strict_types=1)` on every file
- No framework dependencies in `src/` outside `src/Drivers/`
- Console command output uses Symfony Console table/section helpers — keep it consistent with the existing command style
- `VersionRecommendation` is a value object — no setters

## Semver bump logic

| Change type | Bump |
|---|---|
| Routes removed (breaking) | `major` |
| Routes added (additive) | `minor` |
| Route moved / renamed | `major` |
| No drift | `none` |

A `major` bump also sets `requiresNewUriVersion = true` — callers (including Pilot's UI) use this to suggest creating a new URI version file rather than modifying the existing one.

## Relationship to other packages

- **fissible/accord** is a direct dependency — uses its `SpecSourceInterface` and `FileSpecSource`
- **fissible/forge** is a sibling — not a dependency of drift; they're both consumers of accord's spec abstractions
- **fissible/pilot** installs all three and exposes `DriftDetector`, `VersionAnalyser`, and `ChangelogGenerator` through its web UI
