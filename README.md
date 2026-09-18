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
modules/motion/                    the text animation controls and assets
modules/story/                     the Scroll Story widget and its assets
modules/rail/                      the Scroll Rail widget and its assets
modules/smoothscroll/              eases the whole page's scrolling
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

### Widgets register through a silent guard

`register_widgets()` is wrapped in `class_exists()` so a malformed widget
cannot take the editor down. The cost is that a widget class named wrongly
simply never appears, with no error anywhere. That has now shipped twice:
2.3.0, where the controls hook never matched, and 2.6.0, where a search and
replace left the module looking for `Story_Grid_Widget` while the file
declared `Scroll_Story_Widget`.

`tests/run.php` now reads the names out of the module sources and checks that
some file in that module's `widgets/` directory declares each one. The widget
files cannot be loaded in the test suite -- they extend Elementor's
`Widget_Base` -- so a static check is the most that is available, and it is
enough to catch a typo or a rename.

### Everything lives in one panel category

`includes/class-panel-category.php` registers an "Eruda Toolkit" section and
every widget returns its slug from `get_categories()`. Registered once from
`Toolkit::boot()` rather than per module, so three modules do not race to
create the same category. The slug is panel-only: Elementor saves a widget's
name in a layout, never its category, so changing it cannot affect a live page.

### Strings that must never change

Live client pages carry these inside saved Elementor JSON. Change one and
existing pages render empty:

- the widget names `nacc-numbered-accordion` and `eimp-impact-grid`
- every `.nacc*` and `.eimp*` CSS class
- every Elementor control ID
- every text animation preset value (`words-up`, `chars-cascade`, and the rest)

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

### Text animations

Adds an **Eruda Text Animation** section to the **Style** tab of the Heading
and Text Editor widgets. Pick a preset and the widget's existing text animates
as it scrolls into view — nothing is retyped, and a page built before this
module shipped can use it without being rebuilt.

Eleven presets: fade in, words up, words fade, words build, scroll highlight,
characters cascade, characters flip, lines reveal, blur in, scale pop and slide
in.

Scroll Highlight is the only one tied to scroll *position* rather than played
on a trigger. The words sit dim and light up as the paragraph crosses the
middle of the screen, following the scrollbar in both directions. It is the
CSS-side equivalent of:

```js
gsap.to( split.words, {
  color: '#fff', stagger: 0.1,
  scrollTrigger: { trigger: '.text', start: 'top center', end: 'bottom center', scrub: true }
} );
```

Two differences worth knowing. It animates opacity rather than colour, so it
works against any text colour the widget already has instead of needing a
hard-coded one; "Dimmed to" sets how faint the unlit words are. And the scrub
band is the element's height *or 45% of the viewport, whichever is larger* — a
one-line heading is about forty pixels tall, and sweeping a whole sentence
across forty pixels of scroll is a flicker rather than a sweep. Tall paragraphs
are unaffected.

Because the scrollbar is its timeline, it ignores Duration, Stagger, Delay,
Easing and the trigger controls, and those are hidden rather than left sitting
there doing nothing.

Words Build is the standard word reveal, matching the GSAP recipe it is named
after:

```js
gsap.from( split.words, {
  opacity: 0, y: 15, stagger: 0.06, duration: 0.5, ease: 'power2.out'
} );
```

Every number there is a default here: Duration 500ms, Stagger 60ms, easing
"Natural" (which is `power2.out`, easeOutCubic), and a fixed 15px lift rather
than an em-relative one so it reads the same on a paragraph as on a headline.
Verified in Chrome: per-word delays come out `0s, 0.06s, 0.12s, 0.18s, 0.24s`
at `0.5s` each on `cubic-bezier(0.215, 0.61, 0.355, 1)`, from
`matrix(1, 0, 0, 1, 0, 15)`.

Those defaults now apply to every preset, not just this one. 800ms was slower
than the convention for text. Alongside them: duration,
stagger, delay, easing, how far into view it starts, and whether it replays
every time.

There is a **Replay animation** button in the editor. Elementor's button
control fires `elementor.channels.editor.trigger( 'eanm:replay', controlView )`
in the panel; `assets/js/editor.js` listens there and reaches into the preview
iframe to call `erudaMotion.replay()`, which text-animation.js exposes on the
preview window. Replaying disarms, resets, commits and re-arms before playing,
for the same reason the initial arming does: taking the finished state away
from an armed element animates it backwards.

Fade In and Words Fade also take a direction and a travel distance. Directions
are named for where the text comes **from** — "From the left" rather than "Fade
left", which never says which end it starts at. The presets read `--eanm-dx`
and `--eanm-dy` rather than hard-coding an axis, so one pair of rules serves
every direction.

Three things are worth knowing before changing any of it.

**Use the generic section hook, never the common one.** This is what 2.3.0 got
wrong, and it made the whole feature invisible. `elementor/element/common/…`
does not fire per widget: Elementor registers the common controls once, on a
shared `Widget_Common` stack whose `get_name()` is `'common'`, then merges them
into every widget. A widget-name guard on that hook matches nothing, and the
section is silently never added. `elementor/element/after_section_end` fires on
the real widget, so the guard works there; the section is added the first time
a permitted widget closes any section, and a per-element flag stops it being
added again.

**Never declare a transition in the same rule as the start state.** This is the
one that broke Fade In and Blur In through three releases. Those presets
animate the target element itself, which is already on the page and already
visible, so with a transition already live, "become invisible" *was* a
transition. The element animated into hiding and then reversed. The script now
applies the start state under `data-eanm-ready`, forces a reflow, and only then
sets `data-eanm-armed`, which is what the transition rules key off.

`tests/browser/` measures this. Reading the CSS will not catch it; sampling
opacity over time will.

**Commit the start state before flipping it.** A preset that splits nothing —
Fade In, Blur In — puts the transition on the target element itself, which
already exists in the HTML. Setting `data-eanm-ready` and `data-eanm-in` inside
one style recalculation means the browser only ever computes the finished
state: no transition runs and the text just appears. The script reads
`offsetWidth` between the two to force the recalculation. The split presets
were partly shielded by their spans being freshly inserted; that is luck, not
design, so the fix applies to all of them.

**Every `var()` needs a fallback.** A custom property that fails to resolve
makes the entire declaration invalid, so `transition-duration: var(--eanm-duration)`
silently becomes `0s` and the animation looks broken rather than mistuned.

**A scrubbed preset must switch the reveal transition off.** Every split word
carries a transition once armed, and on a scrubbed preset that transition eases
towards each scrolled value instead of tracking the scrollbar. It looks like the
scrub is lagging and stuttering. Measured while getting this wrong: `--eanm-p`
read `1.000` on every word while their opacities were still crawling through
`0.92, 0.86, 0.75, 0.59, 0.35`. `tests/browser/highlight.html` is the case that
shows it.

**It writes no markup.** Every value reaches the DOM through Elementor's own
`prefix_class` and `selectors`, which Elementor applies live in the editor as
well as on the front end. A render-time attribute would not work: the native
Heading has a `content_template()`, so in the editor it is rendered client-side
by Backbone and never goes through PHP. The animation would work on the live
page and be invisible exactly where you need to see it.

**The text survives the script.** The stylesheet hides nothing on its own —
every rule that makes something invisible is scoped to `[data-eanm-ready]`, and
only the script sets that. Script blocked, browser too old, Reduce Motion on:
the heading renders plainly and completely. Keep it that way. It is also why
enqueuing the stylesheet late costs nothing, and why the scroll threshold
clamps below 1.0 — an element taller than the viewport can never be fully
visible, and an animation that never fires would be the one way this module
could make content disappear.

**Two widgets only, by design.** A composite widget like an Icon Box raises a
question the feature has no good answer to: whether its title and description
are one staggered run or two. Everything downstream is widget-agnostic, and the
hook needs no per-widget anchor, so a site that wants the controls elsewhere
adds a name to the list and nothing else changes:

```php
// Offer the animation controls on the Button widget too.
add_filter(
	'eruda_motion_supported_widgets',
	function ( $widgets ) {
		$widgets[] = 'button';
		return $widgets;
	}
);
```

One fact is deliberately duplicated: which preset splits by word, character or
line is stated in `Motion_Presets::all()` and again as a `--eanm-split` custom
property in the stylesheet. The script reads the CSS copy; the PHP copy is for
the tests and for the lazy-enqueue check. Add a preset and you must add it in
both places.

### Scroll Story

A column of text items on the left, a pinned panel on the right that follows
whichever item you are reading. Each item carries its own eyebrow, heading,
description and media — an image or a video.

Modelled on the "Why Terminal" section of terminal-industries.com, rebuilt with
no libraries, and measured against it rather than eyeballed.

**The highlight is scrubbed, not triggered.** How far an item has travelled up
the screen decides how many of its characters are lit. Scroll down and the
sweep runs forward; scroll back up and it retreats. On the reference this is a
class added and removed per character by scroll position, and the asymmetry is
the point:

```css
.estry-c        { transition: color 400ms ease; }          /* going out: plain */
.estry-c.is-on  { animation: estry-sweep 500ms ease forwards; }  /* coming in */

@keyframes estry-sweep {
  0%   { color: <waiting>; }   /* light grey */
  30%  { color: <flash>;   }   /* the accent, for a fraction of a second */
  100% { color: <read>;    }   /* near-black */
}
```

The flash at 30% is the whole trick, and it belongs to arriving only. A
character going out simply transitions back, so reversing reads as an undo
rather than as a second animation. There is no fixed stagger between one
character and the next any more — your scroll speed *is* the stagger.

The band is two lines across the viewport: the sweep starts when an item's top
crosses the lower line and finishes when its bottom crosses the upper one, so a
tall item takes proportionally longer and both ends land on screen. Both lines
are controls.

Only the characters between the old lit count and the new one are touched, so a
scroll of a few pixels costs a handful of class changes rather than one per
character in the section.

**The travelling notch.** The panel's left edge is flush top and bottom and
cuts *inwards* across a band, with rounded corners and a diagonal run between
them. The band travels down as you scroll the section.

The proportions come from reading the reference's own clip path at a 30px
depth, then expressing them as ratios of the depth so any depth keeps the
shape:

```
arc radius    28.17 / 30 = 0.939
arc rise      14.80 / 30 = 0.4933    arc run   4.21 / 30 = 0.1405
diagonal rise 34.90 / 30 = 1.1633    diag run 21.58 / 30 = 0.7190
```

The two arc runs and the diagonal run add to exactly the depth; the rises add
to 2.15 x depth, which is one transition.

**The band does not sweep the whole edge.** On the reference it travels 40% of
the panel's height and is centred in what is left over — 66px of clearance at
each end of a 985px panel, measured at both extremes of its scroll. Letting it
run into the corners, which is what the first version did, turns a detail
travelling along an edge into a bite taken out of one. The travel is a control;
the clearance follows from it.

The path is generated in JavaScript rather than written as CSS, because a curve
in a clip path is in user units: `clip-path: path()` and an SVG `clipPath` with
`userSpaceOnUse` both need rebuilding whenever the panel resizes. A polygon in
percentages would scale on its own but cannot hold a fixed-radius curve.

`clip-path` and `border-radius` clip the same box, so a notched panel cannot
take a radius from CSS. It takes one from the path instead: all four corners
are arcs in the same `d`, and the band is kept between them rather than between
the corners, so a panel can have a notch and a 96px radius at once and the two
can never meet. A CSS border is clipped away for the same reason, so the
panel's border is a stroked copy of that path, drawn at twice the asked-for
width and clipped by it — which leaves exactly the asked-for width on the
inside of the edge, corners and notch included.

The band also never consumes all the room it has: one shoulder's worth is held
back so there is always a straight stretch of edge between the notch and each
corner. Without that, a generous radius or a long band puts the notch's first
contact exactly where the corner curve begins and the two read as one dent. On
the reference's own proportions the reserve changes nothing — it has room to
spare, which is how `tests/browser/story-probe.js` can still reproduce its
66.25 and 460.25 to the decimal.

**The notch's three numbers are declared on `.estry`, not on the frame.** Every
control writes to `{{WRAPPER}} .estry`, and a custom property declared on the
frame itself beats one inherited from an ancestor however specific that
ancestor's selector is. Declaring the defaults on `.estry__frame` silently
killed the notch depth, length and travel controls: they wrote a value the
frame then shadowed.

**The panel is centred on the screen, always.** Its sticky offset is derived
from its own height rather than set directly, so changing the panel height, the
column gap or the spacing between items cannot push it off centre. The old
"pin below" control is now a nudge on top of that, for a tall sticky header.

**Nothing ever fades out.** Slides stack, and a change raises the incoming one
above the rest with a rising z-index, so you are always looking at something
arriving over a solid picture. The first version cross-faded both ways at once,
which meant the panel's own background showed through the middle of every
change as a black flash. The outgoing slide keeps its picture and its place.

Four entrances, all direction-aware, so scrolling back up is the mirror of
scrolling down rather than a repeat of it:

| | |
|---|---|
| Reveal | a soft-edged sweep, both pictures visible through the feather — the default |
| Wipe | a hard edge travels with the scroll, over a picture already oversized and relaxing into place |
| Zoom | arrives oversized and out of focus, resolves as it lands |
| Push | slides in over the one below |
| Dissolve | a plain cross-fade |

Reveal is a mask three times the panel's height sliding across it, so only
`mask-position` animates — cheap, and unlike a gradient whose stops move, it
interpolates everywhere. Its stops are not free choices. The panel is one third
of the mask, so the settled window is the mask's last third and the hidden
window is its first: anything but solid across 66.7–100% leaves a permanent
veil over part of a picture that has finished arriving, and anything but clear
across 0–33.3% means the entrance starts already half visible. The first
attempt had the feather ending at 70% and left a band across the top of every
settled panel, subtle enough to pass a visual check and be caught only by
sampling the pixels — which the probe now does.

Because that edge is soft you see both pictures through it, which is what makes
the counter-move worth having: the picture being replaced eases back and away
while the new one arrives over it, so for the length of a change the panel has
two things moving at different speeds rather than one moving and one sitting
still. The drift is held at its end value during that, because dropping a
finished animation snaps the transform back and the snap is what it is there to
avoid.

Three layers do this, because one element cannot hold two transforms or two
clip paths: `.estry__slide` owns the entrance edge, `.estry__inner` owns the
parallax and the blur, and the media itself owns the slow drift. Putting the
drift on the same element as the parallax does not merely look wrong — a
running keyframe animation wins outright over a transitioned transform on the
same element, and swallows the parallax whole.

Media always fills the panel: `object-fit: cover`, with the focal point as a
control. `cover` scales a small picture up as happily as it crops a large one,
so no shape or size of image can leave the panel's background showing — being
overruled is the only real risk, and nearly every theme ships
`img { height: auto }` at a specificity a plugin stylesheet cannot beat. Width,
height, `object-fit` and `object-position` are therefore forced, and
`tests/browser/story.html` carries a deliberately hostile theme block so the
probe proves it: a 60×40 picture fills a 576×792 panel under
`width: auto; height: auto; object-fit: contain; max-height: 60%`.

**What was not copied:** their panel is a `<canvas>` playing a scroll-scrubbed
image sequence, which needs hundreds of exported frames and a decode pipeline.
This changes between one image or video per item instead — the same effect at
reading pace, from media a client can actually swap in the media library. Their
own change between media is a plain 0.5s cross-fade; ours is not.

Safe without the script, like everything else here: the text is its read colour
and the first slide is simply the picture. Dimming only happens under
`[data-estry-ready]`, which only the script sets. A stylesheet that dimmed on
its own would leave light grey text on white for anyone whose JavaScript
failed.

### Scroll Rail

A row of linked cards that travels sideways while the section is pinned, so
reading the row left to right is the same gesture as reading the page.

**Two ways of doing that, and the difference is what it costs the page.**
Sideways travel has to be spent against *something*, and the obvious something
is scrolling. Where that scrolling comes from is the whole choice:

| | |
|---|---|
| Flow | the section's own journey across the screen. Adds no height — the default |
| Pinned | extra page height, bought so the section can stand still while the row crosses |

Pinning is the more familiar effect and it reads more deliberately, but the
height it buys is real: everything after the rail sits further down the page by
exactly the width of the row. On a 1440px window with six 420px cards that is
1360px — a screen and a half of push, which is what it looks like when a
carousel "moves the rest of the page down".

Flow spends the section's passage across the screen instead. The row starts as
the section arrives from the bottom and finishes as it leaves at the top, and
the section is exactly as tall as its own cards. `tests/browser/rail-probe.js`
measures this rather than taking it on trust: it loads the page in both modes
and compares where the content *after* the rail begins.

**In pinned mode the runway is measured, not guessed.** The section is given
the stage plus however far the row overflows its viewport, times a pace
control. A row of three cards is therefore short and a row of twelve is long,
and neither leaves you scrolling past a rail that stopped moving several
screens ago. It is re-measured on resize and after pictures load, because a
card's width is a responsive custom property and the row's width follows its
content.

A hold at each end keeps the row still for a moment as it arrives and as it
leaves, instead of snapping into motion the instant the section is in play.

**`overflow: hidden` stops a person scrolling an element; it does not stop the
browser.** Focusing a child scrolls its nearest scrollable ancestor to reveal
it — and while the script is driving, that ancestor is the viewport whose
scroll is meant to stay at zero because the row is being moved by transform
instead.
A tabbed-to card was therefore shifted twice, once by the transform and once by
a scroll nobody asked for, and landed off the far side of the screen. The
viewport's scroll is now held at zero whenever the rail is pinned, and tabbing
to a card scrolls the *page* to the position that brings it into view.

**Nothing pins on a phone.** A pinned rail takes a gesture people already know
— swipe the row — and replaces it with one they have to discover, on the axis
their thumb is worst at. Below 1024px, and under reduced motion, the row is a
plain horizontally scrollable strip with snap points. That is also exactly what
it is before the script runs, which is the no-JavaScript state: the markup is a
real scroller and the stylesheet only stops being one once the script has set
`data-erail-ready`.

**The card says it is clickable before you touch it.** A hover-only affordance
arrives after you have already guessed, so the cue — an arrow out of the
corner, an arrow straight on, or a plus — sits on the picture at a low opacity
from the start, and blooms on hover or keyboard focus: it fills in, grows to
full size, and the arrow travels the way it is pointing, while the card lifts,
its border brightens and its picture returns from desaturated to full colour.

A card with no link is rendered as a `div` rather than an `a`, gets no cue, and
keeps the default cursor. It is still a card; it just does not pretend.

Pictures are forced to cover for the same reason as the Scroll Story panel, and
the rail's test page carries the same deliberately hostile theme block so the
probe proves it.

### Smooth scrolling

Eases the whole page's scrolling. No widget and no controls: it is on or off
for the whole site, from the settings screen, and it needs no Elementor — a
classic theme gets the same benefit.

Tuning is a filter, because a scroll feel is set once per site and then left
alone:

```php
add_filter(
	'eruda_smooth_scroll_options',
	function ( $options ) {
		$options['duration'] = 1.4; // seconds to settle; clamped to 0-5
		return $options;
	}
);
```

**Why Lenis rather than our own.** The usual home-made approach translates the
page with a transform and fakes the scrollbar. That breaks `position: sticky`,
anchor links, find-in-page, and `IntersectionObserver` — which every text
animation in this plugin depends on. Lenis drives the real scroll position once
per frame, so the browser still thinks it is an ordinary scroll. Verified, not
assumed: `tests/browser/scroll.html` asserts that a sticky header stays sticky
and that a heading deep down the page still animates while smoothing is on.

**Three things it deliberately does not do.** It does not run in the Elementor
editor, where it would fight the canvas, the drag and drop, and the panel. It
does not run for a visitor whose system asks for reduced motion, where forcing
it is worse than unhelpful. And it does not smooth touch scrolling, because
phones already have momentum and overriding it makes them feel broken.

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
