# Eruda Toolkit 2.0.0 — modular rebrand and page duplicator

Date: 2026-09-08
Status: approved

## Summary

Rebrand "Numbered Accordion for Elementor" as "Eruda Toolkit", a modular plugin,
and add a second module that duplicates pages and posts.

## Constraints

### Frozen strings

Live client pages carry these in saved Elementor JSON. Changing any of them
renders existing pages empty:

- The widget name `nacc-numbered-accordion` (`Widget_Base::get_name()`)
- Every `.nacc*` CSS class
- Every Elementor control ID

PHP namespaces, class names and `wp_register_style`/`wp_register_script` handles
are runtime-only and safe to rename.

### The update channel

`plugin-update-checker` keys an install on its plugin file path
(`numbered-accordion-elementor/numbered-accordion-elementor.php`). Renaming the
folder or the main file orphans every existing install: PUC cannot migrate an
install across a slug change, so each site would need a manual
install-new-then-remove-old.

Therefore the rebrand is display-only. The directory name, the main file name
and the GitHub repository URL do not change. Clients receive 2.0.0 as an
ordinary update and the entry in their plugin list renames itself.

The mismatch between the folder name and the product name is accepted. It is
visible only over FTP. The repository may be renamed later, once no install
remains on 1.0.x.

## Architecture

```
numbered-accordion-elementor.php   bootstrap: constants, Toolkit::boot(), update checker
includes/
  interface-module.php             the module contract
  class-toolkit.php                module registry, enable/disable, boot
  class-settings.php               Settings -> Eruda Toolkit
modules/
  accordion/
    class-accordion-module.php     was includes/class-plugin.php
    widgets/class-numbered-accordion-widget.php
    assets/css/numbered-accordion.css
    assets/js/numbered-accordion.js
  duplicator/
    class-duplicator-module.php    hooks: row actions, bulk actions, notices
    class-duplicator.php           the copy itself
```

Constants move from the `NACC_` prefix to `ERUDA_`. The namespace root moves from
`NumberedAccordion\` to `ErudaToolkit\`.

### Module contract

```php
interface Module {
    public static function id(): string;
    public static function label(): string;
    public static function description(): string;
    public static function requirements_met(): array; // [] when satisfied
    public function boot(): void;
}
```

`requirements_met()` returns a list of human-readable failure reasons.

The Elementor dependency moves out of the plugin bootstrap and into the
accordion module's `requirements_met()`. Today the entire plugin returns early
when Elementor is absent. Under the toolkit a site without Elementor still gets
a working duplicator, and only the accordion module reports itself unavailable.

Consequently the plugin must NOT declare a `Requires Plugins: elementor` header.
Elementor is a module requirement, not a plugin requirement.

### Settings

- Page: `add_options_page`, capability `manage_options`, built on the Settings API.
- Option: `eruda_toolkit_modules`, an `id => bool` map.
- `is_enabled( $id )` treats a missing key as enabled. Future modules therefore
  ship on by default and need no upgrade routine.
- The sanitize callback casts known module ids to bool and drops unknown keys.
- A module whose `requirements_met()` is non-empty renders disabled, with the
  reasons shown inline.

## Duplicator

### Surfaces

- Row action, via `page_row_actions` and `post_row_actions`.
- Bulk action, via `bulk_actions-edit-{type}` and `handle_bulk_actions-edit-{type}`.
- Single duplication posts to `admin-post.php?action=eruda_duplicate`, nonced
  per post id (`eruda_duplicate_{id}`).

Supported types: `page` and `post`.

After either action the user stays on the list table and sees an admin notice.
This is predictable for bulk and consistent for single.

### Authorization

Two capability checks, not one:

1. `current_user_can( 'edit_post', $source_id )` — may read the source.
2. `current_user_can( $post_type_object->cap->create_posts )` — may create the copy.

Checking only the first is a common defect in duplicator plugins; it lets a
Contributor mint posts of a type they could not otherwise create.

The module must also be enabled and the post type must be supported.

### What is copied

Copied: `post_content`, `post_excerpt`, `post_content_filtered`, `post_parent`,
`menu_order`, `post_type`, `comment_status`, `ping_status`, `post_password`,
all taxonomy terms for the post type, and all meta except the denylist below.

Transformed:

- `post_title` becomes `sprintf( __( '%s (Copy)' ), $title )`
- `post_status` is always `draft`
- `post_author` becomes the current user, not the original author
- `post_name` and `post_date` are regenerated

Not copied: comments.

The featured image needs no special case; it is `_thumbnail_id` meta. The post
format needs no special case; it is the `post_format` taxonomy.

### Meta denylist

```
_edit_lock, _edit_last          stale editor locks
_wp_old_slug, _wp_old_date      redirect history
_elementor_css                  keyed to the source post id
_elementor_page_assets          keyed to the source post id
_elementor_element_cache        keyed to the source post id
```

The three Elementor entries are caches keyed to the source post id. Copying them
makes the duplicate serve the original's stale CSS. Elementor regenerates all
three on first render.

`_elementor_data`, `_elementor_page_settings`, `_elementor_edit_mode`,
`_elementor_template_type`, `_elementor_version` and `_elementor_controls_usage`
are copied.

### Slashing

`_elementor_data` is stored as slashed JSON. `get_post_meta()` unslashes on read
and `update_post_meta()` slashes on write, so a naive get-then-update round trip
strips one level of escaping and corrupts every escaped quote in the layout.

All copied meta values pass through `wp_slash()` before being written. This is
the correct general form for a meta round trip, not an Elementor special case.

### Errors

- `wp_insert_post( $args, true )`; a `WP_Error` redirects back with an error notice.
- An invalid or missing source post calls `wp_die()` with a clean message.
- `error_log()` only under `WP_DEBUG`, matching the existing bootstrap.
- Every hook in this module is admin-only. Nothing here can reach a front end.

## Accordion fixes folded into 2.0.0

1. **Multi-open lock.** `toggle()` returns early whenever an item is open and
   "allow closing every item" is off. Correct in single-open mode, wrong in
   multi-open mode: once several rows are open none can ever be closed. Bail
   only when this is the last open item.

2. **Collapsed eyebrow stays in the accessibility tree.** The eyebrow sits
   inside the `<button>` wrapped in `.nacc-collapse`. In animated mode a closed
   row is `grid-template-rows: 0fr` with `overflow: hidden` — clipped, not
   `display: none` — so a screen reader announces a closed row as "Recover,
   Lepido Heat Recovery Unit". `setState()` applies `inert` only to
   `.nacc-item__panel`. Apply it to every `.nacc-collapse` in the item.

3. **Numbering skips blank-titled items.** `foreach ( array_values( $items ) as
   $index => $item )` advances `$index` for items skipped by `continue`, so rows
   render 01, 03, 04. The same variable drives `$force_first_open && 0 ===
   $index`, so a blank first item means "keep one open" opens nothing. Use a
   separate render counter.

## Testing

The machine has no PHP toolchain, so the toolchain is installed as part of this
work (`brew install php`) and PHP is syntax-checked and unit-tested locally.

Pure logic is extracted so it can be tested without WordPress:

- `Duplicator::filter_meta()` — denylist filtering and slashing
- `Duplicator::build_args()` — the insert payload
- `Duplicator::copy_title()` — the title suffix
- `Toolkit::is_enabled()` — missing key means enabled

These are covered by a PHPUnit suite with stubbed WordPress functions. No
`wp-env` or WordPress test suite: the harness cost is disproportionate for a
two-module plugin, and the hook wiring it would cover is carried instead by a
written manual QA checklist in `docs/QA.md`.

## Release

- Version 2.0.0.
- `bin/release.sh` updates its sed pattern for the renamed version constant.
- `readme.txt`: changelog entry, `Tested up to` bump.
- A `.pot` file ships in `languages/`, which is currently empty of one.
- No tag and no GitHub release are cut as part of this work. Client sites update
  from tagged releases, so nothing reaches them until `bin/release.sh` runs.
