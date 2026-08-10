# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress 7.0 multisite (subdirectory-based) for the municipality of Marche-en-Famenne, Belgium. The custom `marchebe` theme in `wp-content/themes/marchebe/` contains all custom code. PHP 8.4+ required, Symfony 8.0 components.

## Development Commands

### CSS Build (TailwindCSS v4)
```bash
# Watch mode
npx @tailwindcss/cli -i wp-content/themes/marchebe/assets/css/tailwind.css -o wp-content/themes/marchebe/assets/css/marchebe.css --watch

# Single build
npx @tailwindcss/cli -i wp-content/themes/marchebe/assets/css/tailwind.css -o wp-content/themes/marchebe/assets/css/marchebe.css
```

**The queue board has no CSS build.** `api/guichet/guichet.css` is hand-written
and is *not* generated output. Commit `53dec11` deliberately dropped the Tailwind
dependency there. Edit that file directly, and do not point a Tailwind build at
it: the output would overwrite the board's stylesheet.

### Console Commands
```bash
php console pivot:cache          # Fetch & cache Pivot tourism API data (--all, --parse, --purge)
php console pivot:query          # Query single Pivot event (--codeCgt=<code>, --level=<2|4>)
php console meili:server         # Manage MeiliSearch index (--key, --tasks, --reset, --update, --dump)
php console fix                  # Data integrity fixes
php console integrity            # Data integrity checks
```

### Composer
```bash
composer install
composer dump-autoload --optimize  # Production
```

## Architecture

### Namespaces (PSR-4 via Composer)
- `AcMarche\Theme\` → `wp-content/themes/marchebe/`
- `AcMarche\Issep\` → `src/AcMarche/Issep/src/` (air quality sensors)

### Theme Bootstrap (`functions.php`)
Instantiates all theme classes in sequence: error handling → `SetupTheme` → `Assets` → `Ajax` → routers (`RouterEvent`, `RouterBottin`, `RouterEnquete`) → `SecurityConfig` → `Seo` → `OpenGraph` → `ShortCode` → `WpEventsSubscriber` → `RestApi` → `AcSort`.

### Key Directories (under `wp-content/themes/marchebe/`)
- `Inc/` — WordPress integration: theme setup, asset enqueuing, AJAX handlers, custom routing, REST API, shortcodes, security hardening
- `Lib/` — Business logic: Twig engine, Pivot API client/parser/entities (`Lib/Pivot/`), MeiliSearch (`Lib/Search/`), Bottin directory, sorting, helpers
- `Repository/` — Data access: `WpRepository` (WordPress), `PivotRepository` (tourism API), `BottinRepository` (external DB via PDO), `AdlRepository`, `MenuRepository`
- `Command/` — Symfony Console commands run via `php console`
- `Data/` — Static data (shortcuts, widgets, partners, menu items)
- `templates/` — Twig templates (namespace `@AcMarche`), organized by content type (agenda, article, bottin, category, enquete, etc.)
- `assets/css/` — `tailwind.css` (source) → `marchebe.css` (compiled output); `admin.css`
- `assets/js/` — Three Alpine.js components: `header-nav.js`, `category-show.js`, `image-gallery.js`

### Multisite Blog IDs (`Inc/Theme.php`)
Each blog has an associated color class (e.g., `color-cat-cit`) and path. Key constants:
- `CITOYEN=1`, `ADMINISTRATION=2`, `ECONOMIE=3`, `TOURISME=4`, `SPORT=5`, `SANTE=6`, `SOCIAL=7`, `CULTURE=11`, `ROMAN=12`, `ENFANCE=14`

### Template System
WordPress `.php` templates (homepage, archive, single, search, agenda, etc.) call `Twig::rendPage()` or `Twig::loadTwig()->render()` to render Twig templates. Twig is configured with `strict_variables` and `DebugExtension` when `WP_DEBUG` is on; caching disabled in debug mode. Global variables: `locale` (fr), `WP_DEBUG`, `template_directory`.

### Custom Routing
Routers register WordPress rewrite rules for clean URLs on specific blogs:
- `RouterEvent` — `/agenda-des-manifestations/manifestation/{codeCgt}` (blog TOURISME only)
- `RouterBottin` — Directory entries
- `RouterEnquete` — Public surveys

### Frontend Stack
- **TailwindCSS 4** with `@tailwindcss/typography` and `@tailwindcss/forms`. Source scans `templates/` directory.
- **Alpine.js 3** (CDN) with three deferred components loaded before it.
- **Leaflet 1.9.4** for maps (conditional on cookie consent).
- **Font Awesome 7.0.1** (CDN).

### Caching
Redis via `Symfony\Component\Cache\Adapter\RedisTagAwareAdapter`, namespace `marcheWp`, 8-hour TTL. JSON file fallback at `/var/data/pivot.json` for Pivot data. Twig templates cached in `var/cache/twig/` (production only).

### External Integrations
- **Pivot Tourism API** — Events and attractions. Client in `Lib/Pivot/PivotApi.php`, entities in `Lib/Pivot/Entity/`, repository in `Repository/PivotRepository.php`.
- **MeiliSearch** — Full-text search across all blogs. Index management in `Lib/Search/MeiliServer.php`, search in `Lib/Search/MeiliSearch.php`.
- **Bottin** — Separate MySQL database for business directory. Direct PDO in `Repository/BottinRepository.php`.
- **ISSEP** — Air quality sensors (`src/AcMarche/Issep/`).
- **ADL** — Economic development content.

### REST API
Custom endpoint: `GET /wp-json/pivot/events` (returns all Pivot events).

### Queue Board (`api/guichet/`)
Standalone page, outside WordPress and outside the theme. Reads the separate
`guichet` MySQL database (`offices`, `tickets`) by direct PDO and renders the
waiting-room board shown on a screen mounted above the entrance door, read from
the plaza at several metres. `index.php` holds both the queries and the markup;
`guichet.css` is hand-written (see the build-command warning above).

`?partial=1` returns the board fragment only; the page polls it every 10s and
swaps the fragment in, so the screen updates without the full-reload flash.
`<meta http-equiv="refresh">` remains as a `<noscript>` fallback.

Constraints that drive the design: nothing below ~30px (distance reading), a
bright surface (the panel catches a specular reflection, so a dark board acts as
a mirror), and the board must fit `100vh` with no scrollbar at any ticket count.

### Environment (`.env`)
Key variables: `PIVOT_BASE_URI`, `PIVOT_WS_KEY`, `PIVOT_CODE`, `MEILI_INDEX_NAME`, `MEILI_MASTER_KEY`, `MEILI_API_KEY`, `WP_URL_HOME`, `DB_BOTTIN_USER`, `DB_BOTTIN_PASS`, `DB_GUICHET_USER`, `DB_GUICHET_PASS`, `APP_CACHE_DIR`.

### Asset URL Handling
`Assets::getThemeUri()` builds theme URLs from `$_ENV['WP_URL_HOME']` instead of `get_template_directory_uri()` to fix multisite subdirectory path issues. A filter also strips subsite paths from `wp-includes`/`wp-content` URLs.