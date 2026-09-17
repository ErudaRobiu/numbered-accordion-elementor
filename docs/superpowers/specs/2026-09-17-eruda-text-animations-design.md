# Eruda Toolkit 2.3.0 — text animations

Date: 2026-09-17
Status: approved

## Summary

Add a fourth module, `motion`, that animates text as it enters the viewport:
words rising out of a mask, characters cascading, lines revealing behind a clip.

The animations apply to text that already exists on the page. Selecting a
Heading or a Text Editor widget and opening its Advanced tab reveals an "Eruda
Text Animation" section. No widget is added, and no page needs rebuilding to use
this.

## Constraints

### Frozen strings

Saved Elementor JSON carries these. Changing one silently drops the animation
from every page already using it:

- Every control ID (`eanm_preset`, `eanm_trigger`, `eanm_duration`, ...)
- Every preset *value* (`words-up`, `chars-cascade`, ...)

The generated CSS classes (`.eanm-preset-words-up`) are derived from the values
at render time and are not themselves stored, but since they are derived from a
frozen value they are frozen in practice.

### The content must survive the script

The rule the accordion and the impact grid already follow: if the JavaScript
never runs, the page is still complete and correct.

So the stylesheet must never hide text on its own. Words become invisible only
once the script has run and marked the target `data-eanm-ready`. That mark is
set for every preset, including `blur-in`, which animates the block whole and
splits nothing -- the attribute means "the script reached this element", not
"this element was split". An old
browser, a blocked script, a JS error in an unrelated plugin — the text renders
plainly, unsplit, fully readable.

This also removes the usual flash-of-unstyled-content worry about enqueuing a
stylesheet late: arriving after first paint costs nothing, because the
pre-animation state is "visible".

### No dependencies

GSAP was considered and rejected: ~70KB on every client page, and the first hard
dependency in a plugin that has none. A vanilla splitter plus CSS transitions
reaches the same visual result in roughly 4KB. Transitions rather than
keyframes throughout: every preset here is two-state, an overshoot is just a
cubic-bezier, and a transition that is interrupted mid-flight resolves cleanly. The one thing given up is
scrubbed, scroll-linked timelines, which no preset here needs.

## Architecture

```
modules/motion/
  class-motion-module.php      registry entry, asset registration, hook wiring
  class-motion-controls.php    the injected control section
  class-motion-presets.php     preset definitions (pure, testable)
  assets/css/text-animation.css  every preset
  assets/js/text-animation.js    splitter, observer, line grouping
```

Registered in `Toolkit::$registry` as:

```php
'motion' => array(
    'file'  => 'modules/motion/class-motion-module.php',
    'class' => '\ErudaToolkit\Modules\Motion\Motion_Module',
),
```

`Motion_Module` implements `Module` exactly as `Impact_Module` does, including
the rule that `is_available()` must not touch `\Elementor\Widget_Base` (it does
not exist at `plugins_loaded`) and must not translate. Requirements are the same
as the other Elementor modules: Elementor loaded, version >= `ERUDA_MIN_ELEMENTOR`.

Handle prefix: `eanm-`. Alongside `nacc-` and `eimp-`.

## Controls

### Where they appear

```php
add_action(
    'elementor/element/common/_section_style/after_section_end',
    array( $this, 'inject' ),
    10,
    2
);
```

`common` fires for every widget, so the callback returns early unless the widget
is one whose content is plain text:

```php
public function inject( $element, $args ) {
    if ( ! $element instanceof \Elementor\Widget_Base ) {
        return;
    }

    if ( ! in_array( $element->get_name(), self::supported_widgets(), true ) ) {
        return;
    }

    // start_controls_section( 'eanm_section', [ 'tab' => TAB_ADVANCED ] ) ...
}

public static function supported_widgets() {
    return apply_filters(
        'eruda_motion_supported_widgets',
        array( 'heading', 'text-editor' )
    );
}
```

The `common` hook is kept in preference to per-widget hooks
(`elementor/element/heading/section_title/after_section_end` and friends)
because those attach relative to a named section of that specific widget, which
would land the controls on the Content tab and break if Elementor ever renames
the section. The `common` hook puts the section on the Advanced tab of every
supported widget, from one code path, with one list to extend.

The list is the whole extension point: a site that wants the controls on a
Button, or on a theme-builder Post Title, adds the widget name through the
filter and nothing else changes. Everything downstream — splitting, triggering,
the presets — is widget-agnostic already.

Buttons, Icon Boxes and containers are out of scope by default. A widget that
holds several distinct pieces of text raises a question this feature has no good
answer to: whether the title and the description are one staggered run or two.

### How values reach the DOM

Not through `add_render_attribute()`. That runs only during PHP rendering, and
widgets with a `content_template()` — the native Heading among them — are
rendered client-side by Backbone in the editor. The animation would work on the
live page and be invisible while editing.

Instead the controls use Elementor's own mechanisms, both of which Elementor
applies live in the editor and on the front end:

- `'prefix_class' => 'eanm-preset-'` on the preset control, so choosing
  `words-up` puts `eanm-preset-words-up` on the element wrapper.
- `'prefix_class' => 'eanm-trigger-'` on the trigger control.
- `'selectors' => array( '{{WRAPPER}}' => '--eanm-duration: {{SIZE}}ms;' )` on
  every numeric control, so the values land in Elementor's generated CSS.

The module therefore writes no markup and no inline styles at all.

### The section

| Control | ID | Type | Default |
|---|---|---|---|
| Animation | `eanm_preset` | select, 9 options incl. None | `none` |
| Trigger | `eanm_trigger` | select: scroll / load | `scroll` |
| Duration | `eanm_duration` | slider, 100–3000ms | 800 |
| Stagger | `eanm_stagger` | slider, 0–300ms | 60 |
| Delay | `eanm_delay` | slider, 0–3000ms | 0 |
| Easing | `eanm_ease` | select, 5 named curves | `out-expo` |
| Start at | `eanm_threshold` | slider, 0–100% | 20 |
| Replay on re-entry | `eanm_replay` | switcher | off |

Every control except `eanm_preset` declares
`'condition' => array( 'eanm_preset!' => 'none' )`, so the section stays a single
dropdown until it is actually in use.

There is deliberately no target-selector control. It existed to disambiguate
widgets holding several pieces of text, and those are no longer supported.

## Presets

| Value | Splits | Motion |
|---|---|---|
| `words-up` | words | rise out of an overflow mask, staggered |
| `words-fade` | words | opacity with a small lift |
| `chars-cascade` | chars | tight per-character rise |
| `chars-flip` | chars | `rotateX(-90deg)` to flat, per character |
| `lines-mask` | lines | per-line clip reveal |
| `blur-in` | none | blur + opacity on the whole block |
| `scale-pop` | words | `scale(0.8)` to 1 on an overshoot curve |
| `slide-left` | words | translate in from the left behind a mask |

`class-motion-presets.php` holds this as a pure array: value, label, split mode,
and whether the preset needs a mask wrapper. Both the control's option list and
the test suite read from it, so the list cannot drift out of sync with itself.

Stagger is expressed as `transition-delay: calc(var(--eanm-stagger) * var(--i))`,
where `--i` is the index the splitter writes onto each span. No per-element
inline styles beyond that one custom property.

## The splitter

### Preserving markup

`innerHTML`-based splitting flattens `<strong>`, `<a>` and `<br>`. Instead, walk
the target's child nodes recursively:

- Element node: recurse into it, leave the element itself untouched
- Text node: split on whitespace, wrap each word in `<span class="eanm-w">`, and
  re-insert the original whitespace between them as plain text nodes

Whitespace is preserved as text rather than being absorbed into the spans, so
inline-block word spans still break across lines normally.

For character presets, each word span is then split again into
`<span class="eanm-c">`, and the word span keeps its role as the mask wrapper.

Indices (`--i`) are assigned in document order across the whole target, so a
stagger sweeps the full heading rather than restarting inside every `<strong>`.

### Choosing targets

`h1, h2, h3, h4, h5, h6, p, li, blockquote` within the widget wrapper, falling
back to the wrapper's container element when none match.

The fallback is what covers a Heading, whose title is rendered as an `<a>` when
linked rather than as a heading tag, and a Text Editor holding a bare string
with no `<p>` around it.

A Text Editor with several paragraphs animates them as one continuous run:
indices carry on across blocks rather than restarting, so the stagger sweeps the
whole widget top to bottom. On a long passage with a per-character preset that
is slow by construction, and the fix is a smaller stagger.

### Idempotency

Before splitting, the original `innerHTML` is stashed on the element. Splitting
restores from the stash first. Re-running the module against already-split DOM
is therefore safe, which matters because the editor re-renders a widget on every
keystroke.

### Lines

`lines-mask` splits to words first, then groups the word spans by their
`offsetTop` and wraps each group in `<span class="eanm-l">`. The whitespace text
nodes between words move into the group alongside them; the trailing one at a
line break is dropped, since the break itself now supplies the gap. Line breaks depend
on the rendered box, so a debounced `ResizeObserver` (150ms) re-groups on width
change. This is the only preset that measures layout.

## Triggers and failure modes

`scroll` uses one shared `IntersectionObserver` per threshold value, built from
`eanm_threshold`. With `eanm_replay` off, the element is unobserved after firing.

`load` fires on the next frame after splitting.

Without `IntersectionObserver` — the only API here that is not universal — every
matched element is revealed immediately, unsplit and unanimated. Degraded, never
broken.

`prefers-reduced-motion: reduce` skips the split entirely. Not "animate faster",
not "fade instead": the DOM is left exactly as authored.

## Assets

Registered on `elementor/frontend/after_register_styles` and
`after_register_scripts`, like every other module.

`get_style_depends()` cannot be used, because the controls live on widgets this
plugin does not own. So:

- Front end: `elementor/frontend/before_render` reads `eanm_preset` from the
  element's settings and enqueues on the first element that has one. Pages with
  no animation load nothing.
- Editor preview: `elementor/preview/enqueue_styles` and
  `elementor/preview/enqueue_scripts` enqueue unconditionally. It is the editor;
  the 4KB does not matter, and it means a preset animates the moment it is
  picked.

Re-initialisation in the editor hangs off
`elementor/frontend/element_ready/global`, which Elementor fires on every widget
re-render.

## Testing

### `php tests/run.php`

- The preset registry: every entry has a value, a label and a valid split mode;
  values are unique; `none` is present
- The control option list is built from the registry and matches it exactly
- Every numeric control declares a `selectors` entry, and each one names a CSS
  custom property the stylesheet actually reads
- Every control but `eanm_preset` carries the `eanm_preset!` condition
- `supported_widgets()` returns `heading` and `text-editor`, and survives a
  filter that adds to it or empties it entirely

The class on the wrapper is Elementor's to write, not this module's, so there is
nothing of ours to unit-test there. It is covered in QA instead.

### `docs/QA.md`

- Settings lists four modules; unchecking Text Animations removes the Advanced
  section from the Heading and Text Editor widgets
- The section appears on Heading and Text Editor, and on nothing else: Button,
  Icon Box, Image Box and a container all show an unchanged Advanced tab
- A native Heading with Words Up animates on the live page and in the editor
- A Text Editor of three paragraphs staggers continuously across all three
- A heading containing `<strong>` and `<a>` keeps both, and the link still works
- A heading containing `<br>` still breaks in the same place
- Text stays selectable and copyable after splitting
- Lines Mask re-groups correctly after a browser resize from desktop to mobile
- With JS disabled, every animated heading renders plainly and completely
- With Reduce Motion on in the OS, nothing splits and nothing moves
- A page with no animated widget loads neither `text-animation.css` nor `.js`
- A 1.0.x-era accordion page is unaffected

## Release

Version 2.3.0 in the plugin header and `ERUDA_VERSION`. README.md, readme.txt
and the changelog updated. `bin/release.sh` as usual.

## Out of scope

- A dedicated "Animated Text" widget. The controls work on the native Heading;
  a second way to do the same thing would need its own typography controls, its
  own QA, and could not be applied to text that already exists.
- Any widget beyond Heading and Text Editor. Composite widgets need an answer
  for how their several pieces of text relate, and the `eruda_motion_supported_widgets`
  filter is there for a site that has one.
- Scroll-scrubbed animation, which is what would have justified GSAP.
- Animating containers and sections.
- Exit animations.
