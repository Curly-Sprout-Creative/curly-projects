# Curly Projects

Custom **`projects`** post type for Curly Sprout sites — a portfolio/works
entry with service + type taxonomies, a links repeater, and shortcodes built
for Oxygen 6 Post Loops. Pairs with the WordPress archive at `/projects/`.

Repo: `Curly-Sprout-Creative/curly-projects` (public). Updates flow
GitHub Release → plugin-update-checker → wp-admin, the same pipeline as
`curly-events` / `curly-site-tools`.

## Post type

- `projects` — `has_archive` (`/projects/`), supports title, editor, featured
  image, excerpt, and **page attributes** (order with `menu_order`).
  `show_in_rest` on, so the Oxygen 6 agent can create/read projects.
- `project_service` — the "what we did" line (Graphic Design, Copywriting,
  Websites, …).
- `project_type` — **Website / Email / Branding**, so non-website work
  (e.g. email campaigns) can be looped in or excluded.
- Both taxonomies have rewrite disabled (admin tagging + loop queries only).

## Meta

| Key | Type | Notes |
|---|---|---|
| `_project_links` | array of `{label, url, target}` | The links shown under the card. Repeater UI in the post edit screen. |
| `_project_url` | string | Optional primary URL, used as a fallback when `_project_links` is empty. |
| `_project_preview_desktop` | attachment id | Full-height desktop screenshot (1440px wide) shown in the browser frame. |
| `_project_preview_mobile` | attachment id | Full-height mobile screenshot (390px wide) shown in the phone frame. |
| `_project_preview_bg` | hex colour | Card background — usually the site's dominant brand colour. |
| `_project_preview_frame` | hex colour | Border colour of both device frames. |

Both are registered with `show_in_rest`, so they work with Oxygen 6 dynamic
data and the MCP agent.

## Shortcodes

Both default to the current loop post, so drop them into an Oxygen 6 Post Loop
item with no `id`.

### `[project_preview]`

Renders the two-screen scrolling mockup: full-page screenshots inside a desktop
and a phone frame, panning as the card crosses the viewport, on a brand-coloured
card. Reads the four `_project_preview_*` fields above (all editable in the
project edit screen, no ACF needed). Falls back to the featured image when no
desktop screenshot is set.

Attributes: `id` (defaults to the current post), `ratio` (overrides the default
`3 / 2` card ratio, e.g. `ratio="4 / 3"`).

### `[project_links]`

Renders the repeater as a row of mini links.

```
[project_links]
[project_links class="mini-link-row" link_class="mini-link hover-slide-up"]
```

| Attribute | Default | Notes |
|---|---|---|
| `id` | current post | |
| `class` | `mini-link-row` | Container class. |
| `link_class` | `mini-link hover-slide-up` | Anchor class. |
| `fallback_label` | `Visit website` | Used when a row has no label. |

### `[project_services]`

Renders terms joined by a separator (the "Copywriting • Website" label).

```
[project_services]
[project_services taxonomy="project_type"]
[project_services taxonomy="project_service" sep=" • "]
```

| Attribute | Default | Notes |
|---|---|---|
| `id` | current post | |
| `taxonomy` | `project_service` | Or `project_type`. |
| `sep` | ` • ` | |
| `link` | `0` | `1` links each term to its term archive. |
| `class` | `project-services` | Wrapper class. |

## Oxygen 6 wiring

- **Projects section (home)** — a `PostsList` querying `post_type=projects`,
  `orderby=menu_order`, `order=ASC`. Each item is the **Project Card**
  component: featured image, `[project_services]` label, title, excerpt/content,
  `[project_links]`.
- **Archive** — `/projects/` is the CPT archive. Assign the O6 template to it
  (or render the same card in a `PostsList`).

## Development

- `php -l` every PHP file, `node --check` every JS asset.
- Version header in `curly-projects.php` must match the git tag (`1.0.0` ↔
  `v1.0.0`); tagging triggers `.github/workflows/release.yml`, which builds and
  uploads the zip as a GitHub Release.
- Never hand-edit plugin files on a server — ship a release and let the site
  updater pull it.
