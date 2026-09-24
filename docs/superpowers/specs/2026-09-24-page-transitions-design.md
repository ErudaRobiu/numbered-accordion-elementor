# Page Transitions module — design

Date: 2026-09-24
Status: approved, implementing

## What this is

One Eruda Toolkit module that owns a single curtain of coloured columns and uses
it for two jobs:

1. **A preloader** on the first page view of a browser session — the site logo
   wipes in over the curtain, a bar tracks real readiness, then the curtain
   sweeps up to reveal the page.
2. **A page transition** on every internal link click after that — the same
   columns rise to cover, a real navigation happens underneath, and the columns
   continue upward to reveal the new page.

The columns always travel upward. Nothing else moves. The logo appears only in
job 1.

References studied before designing this: the Hatamex preloader
(GSAP timeline, tile grid, fake 2s counter) and the Williams GP Tech transition
(pure CSS columns, sessionStorage handoff across a real navigation). We take the
Williams navigation choreography and the Hatamex logo/progress staging, and
discard the tile grid and the fake percentage.

## Naming — locked on first release

Module id `transitions`. CSS prefix `etrn-`. State classes on `<html>`:
`etrn-preloading`, `etrn-covering`, `etrn-entering`, `etrn-leaving`.

These follow the same rule as `nacc-` and `eimp-`: once a client site has run
this, renaming any of them changes behaviour on pages we cannot see. They do not
change.

## Files

```
includes/interface-configurable.php    NEW  the settings contract
includes/class-settings.php            EDIT render + sanitise declared fields
includes/class-toolkit.php             EDIT one registry line
modules/transitions/
  class-transitions-module.php              boot, options, markup
  class-transitions-fields.php              settings_fields() declarations
  assets/css/transitions.css
  assets/js/transitions.js
  index.php
```

`Configurable` is a **separate interface**, not a new method on `Module`.
Adding a method to `Module` would break all fourteen existing implementers.

## The settings contract

```php
interface Configurable {
    /** @return array<int, array{id:string,label:string,type:string,default:mixed,...}> */
    public static function settings_fields();
}
```

Field descriptors are declarative: `id`, `label`, `type`
(`color|number|checkbox|text`), `default`, and for numbers `min`, `max`, `step`,
plus an optional `help` line. The settings screen renders whatever a module
declares, indented under that module's existing on/off checkbox. Values live in
a **second option**, `eruda_toolkit_settings`, keyed by module id — the existing
`eruda_toolkit_modules` option stays a flat map of booleans and its `sanitize()`
is untouched.

Sanitising is driven by the declarations, not written by hand per module:
colours validated, numbers clamped to their declared range, checkboxes cast to
bool, unknown keys dropped.

## Runtime

### The critical structural fact

The covering CSS and the state-restoring script **must be inline in `wp_head`**.
Not a linked stylesheet, not a deferred script. If either arrives late there is
a flash of the new page before the curtain re-establishes, which is the entire
thing this prevents. That inline block reads `sessionStorage` and sets
`etrn-covering` or `etrn-preloading` on `<html>` before first paint.

Everything else — full stylesheet, main script — loads normally in the footer.

Curtain markup renders at `wp_body_open`, with a `wp_footer` fallback guarded by
a render-once flag, because not every theme fires `wp_body_open`.

### First visit of a session

```
head sets html.etrn-preloading   columns already covering, animation: none
  logo clip-wipe in                        0.6s
  bar eases to 90%, snaps to 100% on ready [min 600ms, max 4000ms]
  logo + bar fade                          0.3s
  columns sweep UP, staggered              0.18s each, 0.05s apart = 0.43s
  sessionStorage etrn:visited = 1
```

### Every click after

```
click -> prefetch target, columns rise UP to cover     0.43s
      -> sessionStorage etrn:covering = 1, navigate
new document -> head script covers it before paint
             -> DOMContentLoaded: clear, reflow, sweep UP   0.43s
```

The reflow (`void el.offsetWidth`) between removing the covering class and
adding the leaving class is required — without it the browser coalesces the two
and the columns never animate.

### Readiness

Ready means all of:

- `document.fonts.ready` resolved
- every image intersecting the initial viewport decoded
- `window` `load` fired

whichever completes first against a 3s internal cap, then clamped by the min and
max duration settings. Below min it waits so the preloader does not flash; above
max it gives up and reveals. A page ready in 300ms costs the visitor 600ms, not
the 6.5 seconds Hatamex costs.

## Settings

| Field | Type | Default |
|---|---|---|
| Curtain colour | color | Elementor kit primary when set, else `#111111` |
| Columns | number 1–12 | 6 |
| Travel | number, seconds | 0.18 |
| Stagger | number, seconds | 0.05 |
| Show preloader on first visit | checkbox | on |
| Minimum preloader duration | number, ms | 600 |
| Maximum preloader duration | number, ms | 4000 |
| Show percentage | checkbox | on |

Mobile column count derives as `min(4, columns)` at ≤478px, matching the
reference. The logo comes from `get_custom_logo()`; with no logo set the
preloader runs with bar and counter only.

## Never trap a visitor

The failure mode that matters is a client's site showing a solid coloured
rectangle and nothing else. Against that:

- Every wait has a timeout ending in "reveal anyway": cover→navigate capped at
  600ms, readiness at max duration, reveal at 1s.
- A top-level `try`/`catch` that strips the curtain and clears all
  `sessionStorage` state on any throw.
- `<noscript>` hides the curtain entirely.
- `pointer-events: none` except while actually covering.
- `aria-hidden="true"` on the curtain, `aria-busy` on `<body>` during preload —
  not an `aria-live` region, which would announce percentages.
- `pageshow` with `e.persisted` clears state and reveals immediately, rather
  than Williams's `location.reload()` which costs a round trip on every Back.
- `prefers-reduced-motion: reduce` skips the curtain and the preloader
  completely. Both reference sites ignore this; Hatamex still holds such a
  visitor for 6.5 seconds.

Links deliberately not intercepted: external hosts, hash-only, `target="_blank"`,
`download`, `rel="external"`, non-http schemes, `/wp-admin/`, `.no-transition`
and `[data-no-transition]`, and ctrl/cmd/shift/middle clicks.

Module is off in wp-admin and inside the Elementor editor, as Smooth Scrolling
already is.

## Testing

PHP, dependency-free via `php tests/run.php`:

- declared fields sanitise — colour validated, numbers clamped, unknown keys
  dropped, checkboxes cast
- markup renders the configured column count
- module skipped in admin and in the editor
- the settings screen picks up `Configurable`, and the fourteen modules without
  it are unaffected

Browser, following the `spatial.html` / `steps.html` pattern: a working sheet at
`tests/browser/transitions.html` with live controls, and a puppeteer probe that

- measures the real column stagger against the configured values
- proves there is no flash on arrival
- forces every timeout fallback by suppressing `animationend`
- confirms reduced motion skips everything

`tests/` is excluded from the release zip.
