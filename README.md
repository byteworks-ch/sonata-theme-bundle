# byteworks/sonata-theme-bundle

A complete theme for **SonataAdminBundle 4.x** on **AdminLTE 4** (Bootstrap 5.3), without jQuery.

Sonata Admin renders its interface with AdminLTE 2.4, Bootstrap 3 and jQuery plus plugins (Select2, iCheck,
X-editable, jQuery UI, slimScroll). This bundle replaces templates, form themes and assets with an implementation on
AdminLTE 4: layout, menu, all CRUD pages, forms, blocks and the whole frontend. Sonata's PHP code, a project's admin
classes, block names, routes and AJAX contracts stay as they are.

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Skins and colors](#skins-and-colors)
- [The look and custom Sass builds](#the-look-and-custom-sass-builds)
- [Customizing in a project](#customizing-in-a-project)
- [JavaScript](#javascript)
- [Migrating an existing project](#migrating-an-existing-project)
- [Limitations](#limitations)
- [Development](#development)

---

## Features

- **AdminLTE 4 and Bootstrap 5.3**: light and dark color mode with a toggle (light, dark, automatic from the operating
  system), fullscreen toggle, collapsible sidebar with an icon bar.
- **No jQuery, no inline scripts, no inline event handlers.** All behavior hangs on data attributes and Stimulus
  controllers; a strict Content Security Policy is possible.
- **All templates of Sonata 4.x** ported, plus those of the companion bundles whose parts Sonata's interface renders:
  `doctrine-orm-admin-bundle`, `form-extensions` (date picker), `twig-extensions` (flash messages), `block-bundle`
  (dashboard).
- **All features of Sonata Admin:** association widgets with modals (list, create, edit), autocomplete, sortable
  collections, inline tables and tabs, inline editing in lists, batch actions, filters, export, mosaic view, ACL,
  history with revision compare, preview mode, global search.
- **Tom Select instead of Select2:** reads Sonata's `data-sonata-select2-*` attributes; the dropdown is attached to
  the body, so scrolling tables do not cut it off.
- **Skins:** the color sets of AdminLTE's documentation (29 presets) on AdminLTE's color palette, custom skins and
  custom colors, also with a value per color mode, without a Sass build of your own.
- **A calm look** on Bootstrap's and AdminLTE's variables: neutral grays, anthracite in the dark color mode, tables
  without stripes, bold form labels; adjustable with your own build.
- **Login layout** for a project's login and two-factor pages, **error pages** in place of Symfony's.
- **Translations** of the theme's texts in English and German (domain `ByteworksSonataThemeBundle`).

## Requirements

| | Version |
|---|---|
| PHP | `^8.4` |
| Symfony | `^7.4` or `^8.1` (the form theme builds on the block `form_row_render` of Symfony 7.4) |
| `sonata-project/admin-bundle` | `^4.45` |
| Twig | `^3.15` |
| `sonata-project/doctrine-orm-admin-bundle` | optional; the theme covers its templates as well |

The built assets ship with the bundle (`public/`); a project needs neither Node nor a build of its own for the theme.

## Installation

```bash
composer require byteworks/sonata-theme-bundle
```

1. Register the bundle unless Symfony Flex does it (`config/bundles.php`):

   ```php
   Byteworks\SonataThemeBundle\ByteworksSonataThemeBundle::class => ['all' => true],
   ```

2. Install the assets:

   ```bash
   php bin/console assets:install --symlink --relative
   ```

3. Only for custom colors (option `colors`, see [Skins and colors](#skins-and-colors)): import the bundle's route, e.g.
   `config/routes/byteworks_sonata_theme.yaml`:

   ```yaml
   byteworks_sonata_theme:
       resource: '@ByteworksSonataThemeBundle/config/routes.php'
   ```

   The route serves the stylesheet at `/bundles/byteworkssonatatheme/custom-colors.css`, next to the theme's other
   files, but from a controller: the web server must hand a missing file under `/bundles/` to the front controller
   (Symfony's usual `try_files $uri /index.php…`), not answer it with 404 itself. The stylesheet must be reachable
   **without login**, since the login page loads it too. To put it elsewhere, define a route named
   `byteworks_sonata_theme_colors` with the controller `Byteworks\SonataThemeBundle\Controller\ColorsController`
   instead of importing the bundle's routes; the templates link it by that name.

4. In the prod environment, clear the cache (`php bin/console cache:clear`).

The bundle sets Sonata's `assets.stylesheets` and `assets.javascripts` to its own files and removes the stylesheet of
the configured AdminLTE 2 skin. With `extra_stylesheets` and `extra_javascripts` a project adds its own files as
usual.

## Configuration

All options with their defaults (`config/packages/byteworks_sonata_theme.yaml`):

```yaml
byteworks_sonata_theme:
    # colors of header, sidebar and primary: a preset of AdminLTE's documentation or a custom skin
    skin: default
    # AdminLTE color palette: default (AdminLTE 4) | v3 (the colors of AdminLTE 3) | none (your own Sass build)
    palette: default
    # custom colors without a build: name: '#rrggbb' or { light: '#rrggbb', dark: '#rrggbb' }
    colors: {}
    # custom skins
    skins: {}

    color_mode:
        default: auto            # light | dark | auto (follows the operating system), until the user switches
        toggle: true             # color mode toggle in the header

    sidebar:
        mini: true               # collapsed as an icon bar instead of hidden
        collapsed: false         # collapsed on the first visit

    brand:
        url: ~                   # link of the logo; default: the dashboard
        text: ~                  # text next to the logo; default: sonata_admin.title
        logo:
            light: ~             # logo on a light background (asset path)
            dark: ~              # logo on a dark background
            mini_light: ~        # symbol of the collapsed sidebar, light background
            mini_dark: ~         # symbol of the collapsed sidebar, dark background

    layout:
        fixed_header: true
        fixed_footer: false
        fullscreen: true         # fullscreen toggle in the header, left of the color mode toggle
        compact: false           # AdminLTE's compact mode: lower header, tighter menu entries
        breadcrumb: content      # content (AdminLTE: right of the page title) | header (like Sonata: in the header bar)

    select:
        enabled: ~               # Tom Select for select boxes; null follows sonata_admin.options.use_select2
```

Invalid values (an unknown skin or color, an invalid hex value, a skin from the other palette) stop the application
from starting, with a message that lists the valid values.

### Logo and brand

If a logo variant is missing, `dark` falls back to `light`, `mini_*` to the corresponding full variant, all of them to
Sonata's `title_logo`. Sonata's `logo_content` (`text`, `icon`, `all`) still decides whether text, image or both are
shown. The variant follows the color mode of the sidebar, not of the page: a dark sidebar shows the dark logo in the
light color mode as well.

### Breadcrumb

- `content`: AdminLTE's place, right of the page title; below it on narrow screens.
- `header`: like Sonata, in the header bar after the sidebar toggle, in the colors of the bar, on one line with an
  ellipsis at a long last entry, hidden on narrow screens. The page title, the tab menu and the actions then share one
  line, which saves a line of height.

### Sonata's options

The theme honors `title`, `title_logo`, `logo_content`, `confirm_exit`, `use_stickyforms` (form actions sticky via
CSS `position: sticky`), `html5_validate`, `js_debug`, `form_type` (`horizontal` as well), `list_action_button_content`,
`dropdown_number_groups_per_colums`, `pager_links`, `lock_protection`, `mosaic_background` and `search`.
`use_select2` is the default of `select.enabled`. Without effect: `skin` (its stylesheet is removed), `use_icheck` and
`use_bootlint`.

## Skins and colors

A skin defines three things, the way AdminLTE describes them in its color documentation:

- the **header**: background and color mode (`data-bs-theme`),
- the **sidebar**: background and color mode,
- the **primary color** (`data-lte-primary` on `<html>`): buttons, links, the active menu entry, focus, checkboxes,
  card borders.

The color mode of the page (light, dark) is the user's choice, independent of the skin.

### Presets

The names are slugs of the names in AdminLTE's documentation ("Navy & sky" is `navy-sky`).

| Group | Skins | Palette |
|---|---|---|
| Light | `light`, `light-indigo`, `light-sky` | `default` (`light` works with any) |
| Semi-dark | `default`, `graphite`, `navy`, `steel`, `midnight`, `indigo` | `default` (`default` works with any) |
| Full dark | `navy-sky`, `midnight-mono`, `steel-mono`, `slate-teal`, `graphite-orange` | `default` |
| Colored | `violet-mono`, `gradient-indigo`, `gradient-teal` | `default` |
| AdminLTE 2/3 | `blue`, `black`, `purple`, `green`, `red`, `yellow`, and the same with `-light` | `v3` |

`default` is AdminLTE's own layout: white header, dark neutral sidebar, Bootstrap's blue. `blue` (palette `v3`) is
the classic look of AdminLTE 2.

```yaml
byteworks_sonata_theme:
    skin: blue
    palette: v3
```

### Palettes

| `palette` | Colors | Contrast |
|---|---|---|
| `default` | orange, amber, olive, teal, sky, indigo, violet, fuchsia, pink, navy, steel, slate, graphite, midnight | all at least 4.5:1 with white text (WCAG AA) |
| `v3` | lightblue, navy, olive, lime, fuchsia, maroon, blue, indigo, purple, pink, red, orange, yellow, green, teal, cyan, gray, gray-dark | eight colors miss AA with white text |
| `none` | none; the project loads its own palette (see below) | |

Bootstrap's colors `primary`, `secondary`, `success`, `info`, `warning`, `danger`, `light` and `dark` are always
available. The palette brings the classes `.bg-*`, `.text-bg-*`, `.text-*`, `.border-*`, `.link-*`, `.bg-gradient-*`,
`.card-*` and `.callout-*` for each color; they are available in your own templates as well.

### Custom colors and skins

```yaml
byteworks_sonata_theme:
    skin: brand
    colors:
        # one value for both color modes
        accent: '#0f766e'
        # or a value per color mode: a little lighter in the light mode, the stronger green in the dark one
        logo: { light: '#168666', dark: '#0f6e56' }
    skins:
        brand:
            header:  { background: body }                     # follows the color mode of the page
            sidebar: { background: body-secondary, theme: dark }
            primary: logo
```

- `background`: `body`, `body-secondary`, `body-tertiary` (Bootstrap's surfaces), a color name (Bootstrap, palette,
  custom color) or `gradient-<color>`.
- `theme`: `light`, `dark` or `page` (follows the page). Without it: `page` on a surface, on a color derived from its
  contrast (white text gives `dark`).
- `primary`: `primary` (Bootstrap's blue) or a color name.
- The bundle generates custom colors the way AdminLTE's palette does: variables for both color modes, all classes, the
  tones for hover, active, links and subtle backgrounds (computed like Bootstrap's `tint-color` and `shade-color`).
  They are served by the bundle's route (see [Installation](#installation)), not as an inline style.
- With a value per color mode, both values must carry the same text color (both white or both black), so a colored
  region is right in both modes.
- White text on the primary color needs at least 4.5:1; otherwise Bootstrap puts black text on the buttons. Any WCAG
  contrast checker tells.

### Palette from your own Sass build

With `palette: none` the bundle loads no palette. The project builds it with AdminLTE's mechanism and adds it through
`sonata_admin.assets.extra_stylesheets`:

```scss
$lte-palette-custom: ("brand": #0f766e);
@import "admin-lte/src/scss/adminlte-colors";
```

Colors the bundle does not know then need an explicit `theme` in a custom skin.

## The look and custom Sass builds

The bundle's built CSS sets a calm look, almost entirely through Bootstrap's and AdminLTE's variables in
`assets/scss/app.scss` (all with `!default`):

| Area | Variables |
|---|---|
| Neutral grays, anthracite in the dark color mode | `$body-tertiary-bg`, `$body-secondary-bg`, `$border-color`, `$border-color-translucent`, `$body-bg-dark`, `$body-tertiary-bg-dark`, `$body-secondary-bg-dark`, `$border-color-dark`, `$border-color-translucent-dark`, `$body-color-dark` |
| A dark sidebar a shade darker than the page | `$adminlte4-sidebar-bg-dark` (colored skin sidebars keep their color) |
| Cards | `$card-cap-bg`, `$lte-card-shadow` |
| Tables without stripes, fine lines | `$table-striped-bg-factor`, `$table-border-color`; header row: `$adminlte4-table-head-font-size`; row height of lists: `$adminlte4-list-row-height` |
| Bold form labels | `$form-label-font-weight` |
| `<code>` in the primary's text color instead of pink | `$code-color`, `$code-color-dark` |
| A narrow icon bar (3.6rem), icons that stay in place when the sidebar opens | `$adminlte4-sidebar-link-padding-x`, `$lte-sidebar-mini-width` |

A project with its own build sets other values before importing the theme's stylesheet and loads the result instead
of `app.css` (through `sonata_admin.assets.stylesheets`). Without a build of its own, a project changes colors through
skins and `colors`, not through CSS overrides.

## Customizing in a project

### Extending the layout

A project layout extends Sonata's layout as before (`sonata_admin.templates.layout`) and overrides blocks:

```twig
{% extends '@SonataAdmin/standard_layout.html.twig' %}

{% block adminlte4_navbar_items %}
    {# own entries in the header, left of the fullscreen and color mode toggles #}
    <li class="nav-item">
        <a class="nav-link" href="{{ path('help') }}" title="Help"><i class="fas fa-circle-question"></i></a>
    </li>
{% endblock %}

{% block sonata_top_nav_menu_user_block %}
    {# the user menu, Bootstrap 5 markup: ul.navbar-nav > li.nav-item > a.nav-link #}
{% endblock %}
```

Sonata's block names stay (`sonata_nav`, `sonata_top_nav_menu`, `sonata_top_nav_menu_add_block`,
`sonata_top_nav_menu_user_block`, `sonata_left_side`, `sonata_side_nav`, `side_bar_before_nav`, `side_bar_nav`,
`side_bar_after_nav`, `side_bar_after_nav_content`, `sonata_sidebar_search`, `sonata_page_content`,
`sonata_page_content_header`, `sonata_page_content_nav`, `sonata_breadcrumb`, `sonata_admin_content`, `notice`,
`footer`, `stylesheets`, `javascripts`, `meta_tags` …). The theme adds `adminlte4_navbar_items`,
`adminlte4_fullscreen_toggle` and `adminlte4_color_mode_toggle`.

A project's templates in `templates/bundles/SonataAdminBundle/` still take precedence over the theme's. That is
intended, but means: each such override must be moved to Bootstrap 5 or removed.

### Icons of menu items

Sonata sets the icons of the menu: a group's with `sonata_admin.dashboard.groups.<group>.icon`, an item's with the
attribute `item_icon` of the admin's tag or the option `icon` of an item of a dashboard group. The theme shows the
circle of AdminLTE for an item without an icon.

```yaml
services:
    admin.blog.post:
        class: App\Admin\PostAdmin
        tags:
            - name: sonata.admin
              model_class: App\Entity\Post
              manager_type: orm
              group: blog
              label: Posts
              item_icon: 'fas fa-pen-to-square'   # the icon of the menu item; "icon" is the one of the group
```

- The values are those of the group icons: CSS classes or HTML (`'<i class="fas fa-photo-film"></i>'`).
- Up to version 1.0.6 the theme had the option `sidebar.icons` for this. It is gone; move its icons to `item_icon`.

### Sidebar footer

The block `side_bar_after_nav_content` fills the footer at the bottom of the sidebar; a line separates its sections.
Links in the menu's markup look like menu entries and keep their icon in the icon bar. Text that has no room in the
icon bar gets a stand-in on the same spot with `.adminlte4-sidebar-swap`, so nothing moves when the sidebar opens or
closes:

```twig
{% block side_bar_after_nav_content %}
    <ul class="nav sidebar-menu flex-column">
        <li class="nav-item">
            <a class="nav-link" href="{{ path('api_doc') }}" target="_blank" rel="noopener">
                <i class="nav-icon fas fa-book"></i>
                <p>API documentation</p>
            </a>
        </li>
    </ul>
    <div class="small adminlte4-sidebar-swap">
        <div class="adminlte4-sidebar-full">Version 1.2.0<br>&copy; 2026 Example Inc.</div>
        <span class="adminlte4-sidebar-mini" title="Version 1.2.0 · © 2026 Example Inc.">
            <i class="nav-icon fa-regular fa-copyright"></i>
        </span>
    </div>
{% endblock %}
```

### Login and two-factor pages

```twig
{% extends '@ByteworksSonataTheme/security/layout.html.twig' %}

{% block title %}Sign in{% endblock %}

{% block login_content %}
    <h1 class="login-box-msg h5">Sign in</h1>
    <form method="post">…</form>
{% endblock %}
```

The layout shows the logo from `brand` matching the color mode, loads Sonata's stylesheets and scripts, sets the color
mode before the first paint and the skin's primary color. Blocks: `title`, `meta_tags`, `stylesheets`,
`javascripts`, `login_logo`, `login_content`, `login_footer`.

Other pages outside the admin can build on the same base, `@ByteworksSonataTheme/standalone_layout.html.twig` (blocks
`title`, `meta_tags`, `stylesheets`, `javascripts`, `body_class`, `body`);
`@ByteworksSonataTheme/standalone_logo.html.twig` renders the logo.

### Error pages

The theme's error page replaces Symfony's (404, 403, 500 and all other codes) when the project adds
`templates/bundles/TwigBundle/Exception/error.html.twig`:

```twig
{% extends '@ByteworksSonataTheme/error.html.twig' %}
```

As AdminLTE's error pages: the logo, the status code, a title and a sentence for it. "Go to the start page" leads where
the logo leads (`brand.url`, else the dashboard; a visitor who is not signed in gets to the login). "Back" shows only
when the previous page is one of the application (a referrer of the same origin; Stimulus controller `adminlte4-back`),
not for a page opened from a bookmark, a mail or another site. Which user is signed in the page cannot tell: on a 404
Symfony does not know the user yet (routing comes before the firewall). The texts are translated for 400, 401, 403, 404,
405, 410, 429, 500 and 503; other server errors get those of 500, other client errors general ones. The page shows no
details of the error. Blocks as in the login layout, plus `error_logo`, `error_content`, `error_actions`,
`error_footer`.

Symfony shows these pages only without debug; in the `dev` environment it shows its exception page, the error pages
are at `/_error/404`, `/_error/500` etc. (route `_errors` of FrameworkBundle, part of the Flex recipe). Errors that
occur before Twig can render (e.g. while the container is built) still get Symfony's plain page.

### Forms, show pages and lists

- **Group frames:** `box_class` may still contain Sonata's classes (`box box-primary` becomes a card with a colored
  border, `box box-solid box-danger` a card with a colored header); card classes (`card card-success card-outline`)
  work directly. Sonata's mapper gives every group `box box-primary` (`BaseGroupedMapper::with()`), a default a theme
  cannot change; so the theme's templates translate the value with the filter `adminlte4_card_class`.
- **Column widths in inline tables:** `attr: {'data-col-width': '22%'}` on a field of a collection sets the width of
  its column; the table then gets a fixed layout.
- **Select boxes:** Tom Select reads Sonata's attributes `data-sonata-select2-allow-clear`, `-allow-tags`,
  `-maximum-selection-length`, `-minimum-results-for-search` (from this number of options on, a search shows in the
  dropdown; default 10) and `data-placeholder`; `data-sonata-select2="false"` turns it off for a field. With fixed
  values nobody types into the field; the search sits in the dropdown.
- **Sortable multiple choice** (`sortable: true`): the field needs `by_reference: false`, otherwise the order is lost
  on saving (Symfony's MergeCollectionListener).
- **Autocomplete:** Sonata's options apply; `dropdown_auto_width: true` makes the dropdown as wide as its longest
  option.
- **Show pages:** the label column has the same width in every card, also in cards of different widths (a half and a
  whole one), so the values line up: 14rem, adjustable with the CSS variable `--adminlte4-show-label-width`; on phones,
  where the cards stack, 40% of the card. Long values wrap. A field with `'label' => false` still gets an empty label cell from Sonata's template; a field
  template of your own can render `<td colspan="2">` instead and use the whole width (see the pitfalls below).
- **Empty lists:** the table keeps its header, and one row over all columns says why it is empty and what to do:
  reset the filters when they leave nothing, create an entry (Sonata's `button_create` template) when there is none, go
  to the first page after the last one. The row's content is Sonata's block `no_result_content`.
- **Long list cells** (e.g. many badges): cut to one line; on hover and keyboard focus the full content shows in the
  same design over the cell:

  ```twig
  {% extends '@SonataAdmin/CRUD/base_list_field.html.twig' %}
  {% block field %}
      <div class="adminlte4-clamp" {{ stimulus_controller('adminlte4-clamp') }}>
          <div class="adminlte4-clamp-content">
              {%- for role in value %}<span class="badge text-bg-primary">{{ role }}</span> {% endfor -%}
          </div>
      </div>
  {% endblock %}
  ```

  The cell takes a share of the table's width and shows what fits into it: CSS variable `--adminlte4-clamp-width`
  (default 40%), set on the table or a parent.

### Twig

- Global `adminlte4`: the options, plus the resolved skin (`adminlte4.skin.header.class`,
  `adminlte4.skin.sidebar.theme`, `adminlte4.skin.primary` …).
- Filters `adminlte4_card_class` (Sonata's `box_class` to card classes) and `adminlte4_color` (an AdminLTE 2 color name
  to a Bootstrap 5 color, e.g. `aqua` to `info`).

## JavaScript

The theme ships Bootstrap 5, AdminLTE 4, Stimulus, Tom Select and SortableJS as one built script. It provides:

- `window.bootstrap`: Bootstrap's JavaScript (Modal, Tab, Dropdown, Tooltip …). Do not bundle Bootstrap a second time
  in a project, or there are two instances with separate state.
- `window.sonataApplication` and `window.stimulus`: the theme's Stimulus application; projects can register their own
  controllers there or start an application of their own.

Besides `app.js`, the theme loads `head.js`, a few lines that run blocking in `<head>` before the first paint: they set
the color mode (a dark page does not flash white) and keep the collapsed sidebar open when one of its links was clicked
while it was open on hover (otherwise the next page shows it collapsed and opens it again a moment later; the first
pointer movement hands over to hovering again). A project that replaces the layout templates loads it the same way; up
to version 1.0.3 it was called `color-mode.js`.

Registered controllers: `sonata-association`, `sonata-autocomplete`, `sonata-batch`, `sonata-choice-field-mask`,
`sonata-collection`, `sonata-confirm-exit`, `sonata-edit`, `sonata-filter`, `sonata-filter-list`,
`sonata-inline-edit`, `sonata-per-page`, `sonata-readmore`, `sonata-revision`, `sonata-select`, `sonata-sortable`,
`adminlte4-back`, `adminlte4-color-mode`, `adminlte4-sidebar`, `adminlte4-sidebar-menu`, `adminlte4-clamp`.

`assets/package.json` and `assets/controllers.json` describe these controllers for tools, such as the Symfony language
tools (`symfony lsp:check`) and IDEs, so `stimulus_controller('adminlte4-clamp')` in a project's template counts as
known. They only describe: the theme's script registers the controllers, not the project. The bundle is no Symfony UX
package (no Composer keyword `symfony-ux`), so Symfony Flex does not add the controllers to the project's
`package.json` or `controllers.json`. A test compares both files with the registrations in `assets/js/app.js`.

Events (as DOM events, not jQuery events): `sonata-admin-append-form-element` (a collection or inline form was
reloaded), `sonata-collection-item-added`, `sonata-collection-item-deleted`, `sonata-autocomplete:created`.
AdminLTE's own events (e.g. `expand.lte.treeview`) contain dots; Stimulus cannot use them as action names, they need
`addEventListener`.

## Migrating an existing project

This list sums up what had to be done to move a Sonata project in production (admins with their own templates, form
themes, Stimulus controllers, login with two factors) to the theme, completed by further known places. The quickest
order: install, click through all pages, search for Bootstrap 3 markup, JavaScript, styles, tests.

### 1. Installation and configuration

- Install and register the bundle, `assets:install` (see [Installation](#installation)).
- `sonata_admin.options.skin` can go: the theme sets it to `null`, an AdminLTE 2 skin of the project's own would only
  load its stylesheet for nothing. `use_icheck` has no effect and can go as well.
- Remove logo blocks of your own from the layout; the logos belong to `brand.logo` (light and dark variant, symbol
  for the icon bar). `sonata_admin.options.logo_content` still decides about text and image.
- Choose a skin or create one; with custom colors, import the route and check that it is reachable without login.

### 2. The project's templates

- **Layout:** the blocks stay the same, their content needs Bootstrap 5 markup. Typical places:
  - the user menu in the header (`sonata_top_nav_menu`): `ul.navbar-nav > li.nav-item > a.nav-link`;
  - the sidebar footer: in `side_bar_after_nav_content`, links in the menu's markup (see above);
  - blocks for the AdminLTE 2 skin class (`admin_lte_skin_class` with `skin-black` etc.): remove them; the icon bar
    is the option `sidebar.mini`.
- **Overrides in `templates/bundles/SonataAdminBundle/`:** they win against the theme. Many become unnecessary with
  the theme (e.g. column widths in inline tables, now `data-col-width`). Overrides in a wrong directory (e.g. one level
  too deep) never applied anyway; `bin/console debug:twig` shows which file is used.
- **Login and two-factor pages:** move them to the theme's login layout; glyphicons, `has-feedback`, `col-xs-*`,
  `btn-block` and `div.checkbox` go away.
- **Field templates, form themes and pages of your own:** replace Bootstrap 3 and AdminLTE 2 classes:

  | Old | New |
  |---|---|
  | `btn-default` | `btn-outline-secondary` |
  | `span.input-group-btn` around buttons | buttons directly in `.input-group` |
  | `input-group-addon` | `input-group-text` |
  | `form-group` | `mb-3` |
  | `control-group`, `has-error` | Sonata's `sonata-ba-field-error`, or `is-invalid` on the field |
  | `help-block` | `form-text`; errors: `invalid-feedback` |
  | `div.checkbox > label > input` | `div.form-check > input.form-check-input` |
  | `select.form-control` | `select.form-select` |
  | `label label-primary` | `badge text-bg-primary` |
  | `bg-yellow`, `bg-red`, `bg-green` | `text-bg-warning`, `text-bg-danger`, `text-bg-success` |
  | `text-muted` | `text-body-secondary` |
  | `sr-only` | `visually-hidden` |
  | `pull-right`, `pull-left` | `float-end`, `float-start` |
  | `col-xs-*` | `col-*` |
  | `btn-block` | `w-100` (or `d-grid` around the buttons) |
  | `table-condensed` | `table-sm` |
  | `data-toggle`, `data-target`, `data-dismiss` | `data-bs-toggle`, `data-bs-target`, `data-bs-dismiss` |
  | `glyphicon glyphicon-*` | Font Awesome (`fas fa-*`) |
  | `box`, `box-header`, `box-title`, `box-body`, `box-tools`, `btn-box-tool` | `card`, `card-header`, `card-title`, `card-body`, `card-tools`, `btn-tool` |
  | modal: `h4.modal-title`, `button.close` with `&times;` | `h5.modal-title`, `button.btn-close` |
  | `main-sidebar`, `content-wrapper`, `main-header` | `app-sidebar`, `app-main`, `app-header` |

- **`<code>`** shows in the primary's text color; corrections of your own against Bootstrap's pink are no longer
  needed.
- **Translation keys** in templates you touch anyway: a wrong key shows as the key itself in the page, which is easy
  to overlook in rarely visited views.

### 3. Admin classes

- `box_class` with AdminLTE 2 classes keeps working. For one look everywhere, remove values of your own such as
  `box box-info` and let Sonata's default apply.
- Fields whose label only repeats the card title (e.g. a table of rules in a card of the same name): `'label' => false`
  and a field template that uses the whole width.
- Sortable multiple choice: add `by_reference: false`.

### 4. JavaScript

- **There is no jQuery any more.** `$`, `jQuery`, `window.Admin` (`Admin.setup_select2`, `Admin.get_select2_width` …),
  Select2, iCheck, X-editable and jQuery UI are gone. Code of your own that uses them must be moved, preferably to
  Stimulus.
- **Bootstrap's API:** `$(modal).modal('show')` becomes `window.bootstrap.Modal.getOrCreateInstance(modal).show()`,
  `$(tab).tab('show')` becomes `window.bootstrap.Tab.getOrCreateInstance(tab).show()`.
- **Select boxes:** instead of calling `select2()` on new selects, set `data-controller="sonata-select"`; Tom Select
  starts by itself. Changes arrive as native `change` events.
- **Selectors against Sonata's markup:** "Add new" in collections is a button with
  `data-action="sonata-association#append"` (formerly `a[onclick*="start_field_retrieve"]`), its URL is in
  `data-sonata-association-append-url-value` on the field container. Tabs: `[data-bs-toggle="tab"]`. Field containers:
  `[id^="sonata-ba-field-container-"]` instead of `.form-group`.
- **Tom Select's dropdowns** are attached to the body, not to the field; code or tests that look for `.ts-dropdown`
  inside the field do not find them there.
- **Your own build** (e.g. Webpack Encore): do not bundle Bootstrap or jQuery; use `window.bootstrap`.

### 5. Styles

- Remove project CSS for AdminLTE 2 and Bootstrap 3: login box, header logo, `skin-*`, `main-sidebar`, sidebar footer,
  hacks for grid gutters and inline tables. In practice this is a large part of an admin stylesheet of your own.
- **Fixed colors:** replace them with Bootstrap's CSS variables (`var(--bs-body-bg)`, `var(--bs-border-color)`,
  `var(--bs-primary)`, `var(--bs-tertiary-bg)`, `var(--bs-secondary-color)`, `var(--bs-danger)` …); only then is the
  dark color mode right.
- **Striped tables of your own:** adapt them to the look (the theme does not stripe).
- **Standalone pages** outside Sonata's layout (e.g. a Swagger UI) with built-in AdminLTE 2 colors: move them to the
  new values.

### 6. Tests

- Adapt selectors: `.box-title` to `.card-title`, `.nav-tabs a[data-toggle="tab"]` to
  `.nav-tabs [data-bs-toggle="tab"]`, `.label-primary` to `.badge.text-bg-primary`, the markup of the sidebar footer.
- Tests that imitate adding a row to a collection read the append URL from the data attribute instead of an inline
  script.
- JavaScript tests without a real Bootstrap need a stand-in for `window.bootstrap.Modal` and `Tab`.
- A test that fetches the stylesheet of custom colors without login guards the firewall configuration.

### 7. Operation

- After configuration changes in the prod environment: `cache:clear`.
- `assets:install` belongs to the deployment (the theme serves its files from `bundles/byteworkssonatatheme/`).

### Pitfalls

- **`^/admin` in `access_control`** matches every path that starts with `/admin`; do not call routes outside the
  admin area `/admin…`.
- **Tables in card bodies without padding:** AdminLTE gives the first and last cell of a table 1.5rem; its descendant
  selector reaches the cells of a nested table as well, so the indentation doubles. Nested tables should reset the
  padding of their own first and last cells with a more specific rule.
- **Scrolling containers** (`.table-responsive`, which Sonata puts around lists and show pages) cut off anything of
  your own that leaves its cell, e.g. CSS tooltips. The theme places its dropdowns and layers in the window for that
  reason; solutions of your own need the same or `overflow: visible` on the container.
- **Browser cache:** after an update of the bundle the browser loads the theme's files again as soon as Sonata's asset
  version changes; while testing on a running system, a hard reload helps.

## Limitations

- **Sonata's sibling bundles** (SonataUserBundle, MediaBundle, PageBundle, ClassificationBundle) require jQuery and
  `window.Admin` and do not work with the theme. `doctrine-mongodb-admin-bundle` is not covered. There is deliberately
  no jQuery compatibility layer.
- **New Sonata versions** may change templates that the theme replaces; the bundle's tests check every replaced file
  against Sonata's original (`tests/Reference`).
- **AdminLTE 4.10:** `card-tabs` shows inactive tabs on a colored header unreadable, `card-outline-tabs` lacks the
  documented accent on the active tab; the theme therefore puts tabs into the body of an outline card, as AdminLTE's
  demo does.
- **Palette `v3`:** eight colors miss WCAG AA with white text; AdminLTE's `data-lte-contrast="aa"` is no option of the
  theme.

## Development

```bash
composer install
npm install
npm run build            # builds public/ (app.css, app.js, colors.css, colors-v3.css, head.js)
vendor/bin/phpunit       # unit and functional tests against the test app
vendor/bin/phpstan analyse  # static analysis of src/ at level 10
bin/sync-reference --check  # did Sonata change a template the theme replaces? (see below)
npx playwright test      # browser tests against the test app (starts its own servers)
node bin/screenshots.mjs # screenshots: Sonata as it ships and with the theme, variants of the options
```

The test app (`tests/App`) is a Sonata admin with Doctrine ORM on SQLite and admins for every feature the theme
covers. Environments: `dev` for looking around, `test` and `e2e` for the tests (own databases; the tests load the
fixtures themselves), `original` without the theme for comparison, `acl` with security and ACL. Theme options for a
run come as JSON from the environment variable `THEME_OPTIONS`.

```bash
php tests/App/bin/console assets:install tests/App/public --symlink --relative
php tests/App/bin/console app:fixtures           # schema and the fixed data set of the tests
php tests/App/bin/console app:fixtures --demo    # or demo content: an outdoor magazine (posts, comments, pages, media)
php -S 127.0.0.1:8000 -t tests/App/public tests/App/public/index.php
```

`APP_ENV` selects the environment and so the database (`dev` without it). The dates of the demo content count back
from the day of loading.

The built assets are committed, so `composer require` works without Node.

`reference/` holds copies of the templates and scripts of Sonata the theme replaces or takes over, from the versions in
`reference/VERSIONS`. When Sonata changes one of them, `bin/sync-reference --check` and the tests of the group
`reference` name it: carry the change over into the theme, then run `bin/sync-reference` and commit `reference/`.

The GitLab pipeline checks composer.json, the code style (PHP CS Fixer) and the Twig, YAML and container of the test
app, analyses `src/` with PHPStan, runs the tests with the newest and with the lowest versions composer.json allows,
compares Sonata's templates with `reference/` (blocking), checks that `public/` is the build of `assets/` and scans
the JavaScript libraries shipped there with Trivy. A daily scheduled pipeline runs the same against new upstream releases. The Composer
package is published for tags only.

## License

MIT, see [LICENSE](LICENSE). Parts of the theme are derived from the Sonata Project (MIT, Thomas Rabaix): the
license file names them and contains Sonata's license.
