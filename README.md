# Eruda Toolkit

A small toolkit of site-building modules. Each one can be switched off from
**Settings → Eruda Toolkit**; they are on by default.

| Module | What it does | Needs |
| --- | --- | --- |
| Numbered Accordion | A numbered, lightly animated accordion widget for Elementor | Elementor 3.5+ |
| Impact Grid | A grid of numbered figure and checklist cards that animate into view | Elementor 3.5+ |
| Duplicate Pages | A Duplicate row action and bulk action on Pages and Posts | Nothing beyond core |

![Widget](docs/preview.png)

## Install

Download the latest zip from [Releases](../../releases), then in WordPress:
**Plugins → Add New → Upload Plugin**.

Once installed, the plugin checks this repository for new releases and shows a
normal update notice in wp-admin. Auto-updates are deliberately disabled —
installing an update is always a manual click.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor 3.5+, for the accordion and impact grid modules only

## Layout

```
numbered-accordion-elementor.php   bootstrap, update channel
includes/                          module registry and settings screen
modules/accordion/                 the accordion widget and its assets
modules/impact/                    the impact grid widget and its assets
modules/duplicator/                the Duplicate action
tests/                             php tests/run.php
docs/QA.md                         manual checklist for what the tests cannot cover
```

A module implements `ErudaToolkit\Module`: static `id()`, `label()`,
`description()` and `requirements_met()`, plus a `boot()` that adds its hooks.
Register it in `Toolkit::$registry` and it appears on the settings screen. A
module id must never change once shipped — it is the settings option key.

`Toolkit::is_enabled()` treats a missing key as enabled, so a new module ships
switched on without an upgrade routine to write its key into the stored option.

## Notes for future maintenance

### The plugin is named Eruda Toolkit; its folder is not

The directory and main file are still `numbered-accordion-elementor`. That is
deliberate, and should stay that way while any install remains on 1.0.x.

WordPress identifies a plugin by its path, and the bundled
plugin-update-checker keys its update channel on the same path. Renaming either
orphans every existing install — PUC cannot carry an install across a slug
change, so each site would need a manual install-the-new-then-remove-the-old, in
that order, with the accordion missing from live pages in between.

The text domain stays `numbered-accordion` for the same reason.

### Strings that must never change

Live client pages carry these inside saved Elementor JSON. Change one and
existing pages render empty:

- the widget names `nacc-numbered-accordion` and `eimp-impact-grid`
- every `.nacc*` and `.eimp*` CSS class
- every Elementor control ID

PHP namespaces, class names and asset handles are runtime-only and safe to rename.

### The accordion widget

This is a classic (V3) `Widget_Base` widget, chosen deliberately: Elementor has
not published a public API for V4 atomic widgets, and its
`Elementor\Modules\AtomicWidgets\*` internals carry no deprecation policy. A V3
widget renders correctly on V4 Atomic pages and can sit alongside atomic
elements.

Do **not** test for `\Elementor\Widget_Base` at `plugins_loaded` — Elementor's
autoloader cannot resolve it (it derives `ELEMENTOR_PATH/widget-base.php`, while
the file lives at `includes/base/widget-base.php`), so the class does not exist
until the widgets manager requires it immediately before the
`elementor/widgets/register` hook fires.

### The impact grid

Two decisions here are worth keeping.

**Reveals are CSS animations, not transitions.** Each card's delay comes from a
`--eimp-i` custom property, and the animation uses `animation-fill-mode:
backwards` so it holds its opening frame during that delay and then hands the
element back to its declared style. A transition with a `transition-delay` would
have done the same reveal, but the delay would then also apply to the hover
lift, which would stall by up to half a second on the last card. Animations do
not have that problem.

**`--eimp-i` is rewritten per batch.** The markup ships an index matching each
card's position in the grid, which is the right cascade when the whole grid
arrives at once. `IntersectionObserver` rewrites it for each batch of cards that
crosses the threshold together, so a card scrolling in alone later does not sit
waiting out five other cards' worth of delay before appearing.

Whether a figure counts up is decided once, in PHP, by
`Impact_Content::is_countable()`, and handed to the browser as a data attribute.
Anything that is not a plain number — a range, a trailing plus, a percentage —
is left exactly as typed rather than counted to a value the widget guessed at.
The count-up derives its thousands separators and decimal places from the string
the editor typed, not from the visitor's locale.

Stacking is one `top` value and nothing else. A card is `position: sticky` at
all times -- a sticky box with `auto` insets lays out exactly like a relative
one, so grid mode costs nothing -- and the Card layout control switches `top`
between `auto` and a calc of the card's index times the ledge. That declaration
has to land on the card, not the grid: the calc multiplies `--eimp-i`, which
only exists per card, so routing it through a custom property on the container
resolves the index once, to its fallback of zero, and pins every card at the
same height. It looks like it works until you scroll.

The icon width control sets `width`, not `max-width`. That is not a style
preference: `max-width` on a replaced element with `width: auto` can only shrink
it below its intrinsic size, so the control silently stopped responding above
the width of the file WordPress served, and the icon is requested at full size
for the same reason. `max-width: 100%` remains, but only to stop a large icon
overflowing a narrow card.

Elementor has no nested repeater, so a checklist card's nesting comes from its
text: one item per line, and a line opening with a dash, asterisk or bullet
nests under the item above it. `Impact_Content::parse_list()` owns that, and
promotes an orphan sub-bullet to a top-level item rather than dropping it — a
list that lost text would look like a bug in the widget.

### The duplicator

Two things in `Duplicator` are load-bearing and easy to undo by accident:

**The meta denylist.** `_elementor_css`, `_elementor_page_assets` and
`_elementor_element_cache` are caches keyed to the *source* post id. Copy them
and the duplicate serves the original's stale CSS. Elementor regenerates all
three on first render.

**The re-slashing.** `_elementor_data` is slashed JSON in the database.
`get_post_meta()` unslashes on read and `add_post_meta()` unslashes again on
write, so a plain get-then-write round trip strips a level of escaping and
corrupts every escaped quote in the layout. `filter_meta()` passes values back
through `wp_slash()` to keep the round trip lossless.

Both are covered by `tests/run.php`.

## Tests

```sh
php tests/run.php
```

No install step and no Composer: the suite stubs the handful of WordPress
functions the pure logic touches. It covers the meta denylist, the slashing
round trip, the insert payload, the module-enabled default, and the impact
grid's list parsing, figure detection and numbering. Everything it
cannot reach — hook wiring, capability checks, the Elementor round trip — is in
[`docs/QA.md`](docs/QA.md).

## Releasing

```sh
bin/release.sh 2.0.0
```

Lints and tests, bumps the version in the plugin header, `ERUDA_VERSION` and
`readme.txt`, builds the zip, tags, and publishes a GitHub release with the zip
attached. Client sites see the update within 12 hours, or immediately via
**Dashboard → Updates → Check again**.

To regenerate the translation template after changing any user-facing string:

```sh
find . -path ./vendor -prune -o -path ./tests -prune -o -name '*.php' -print \
  | xgettext --files-from=- --language=PHP --from-code=UTF-8 --force-po \
      -k__ -k_e -k_n:1,2 -kesc_html__ -kesc_html_e -kesc_attr__ -kesc_attr_e \
      --add-comments=translators -o languages/numbered-accordion.pot
```

## Licence

GPL-2.0-or-later. Bundles
[plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker)
(MIT).
