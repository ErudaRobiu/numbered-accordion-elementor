# Browser test

`php tests/run.php` covers the PHP. This covers the part that kept shipping
broken: whether an animation *actually animates* in a real browser.

Three releases went out with presets that did nothing, because the code was
reviewed by reading it rather than by watching it run. Reading cannot catch
this class of bug. Measuring can.

## Running it

```sh
npm install puppeteer-core          # once, anywhere; not a plugin dependency
python3 -m http.server 8732         # from the repository root
node tests/browser/probe.js         # text animation presets
node tests/browser/story-probe.js   # Scroll Story
node tests/browser/rail-probe.js    # Scroll Rail
node tests/browser/spin-probe.js    # Eruda Spin on an existing widget
node tests/browser/header-probe.js  # Mega Header
node tests/browser/slab-probe.js    # Split Slab, stacked and not
node tests/browser/bench.js         # what it all costs, with every widget on one page
```

`spatial.html` is not a test. It is a working sheet for the six CSS properties
that turn a flat element in space -- perspective, the three rotations,
transform-origin, backface-visibility -- with a control on each so the values
can be felt rather than read. It exists because the question "could the toolkit
do 3D" is really a question about what those properties look like at the
settings a client would actually want, and that is not answerable by reading
the spec. Open it directly; it loads nothing from the plugin.

```sh
open http://localhost:8732/tests/browser/spatial.html
```

The explainer fixture is generated rather than hand-written, from the widgets'
own `render()`, so one command proves the PHP, the stylesheet and the script
together:

```sh
php tests/browser/explainer-fixture.php > tests/browser/explainer.html
```

The data table is generated the same way, and for the same reason:

```sh
php tests/browser/table-fixture.php > tests/browser/table.html
```

So are the process steps, which also have a probe of their own:

```sh
php tests/browser/steps-fixture.php > tests/browser/steps.html
node tests/browser/steps-probe.js
```

It lays out the slab with its step numbers empty and filled, the slab over a
background picture under the darkening veil, and the ledger with and without
its winning column marked.

The Flow Schematic has a generated fixture of its own, for the same reason --
the widget emits a grid that names columns, and only a browser turns those
names into positions:

```sh
php tests/browser/schematic-fixture.php > tests/browser/schematic.html
```

It draws the full loop with every band switched on, three stages with none of
them, a single stage unframed, and the loop again inside a 760px column, which
is the case that proves the diagram stacks on its own width rather than the
window's.

Both run headless on purpose. A scrubbed animation is driven by
`requestAnimationFrame`, and Chrome does not run rAF in a tab that is not
visible — drive a real window and every measurement freezes the moment the
window loses focus, which reads exactly like a broken build. That cost an hour
once already.

`slab-probe.js` asserts that the Split Slab is two panels beside its bar on a
wide screen, sharing the width the panel's control asked for, and one panel over
the other with the bar lying across it once the slab is narrow -- whether that
is because the window is narrow or because the column it sits in is. It injects
the rule Elementor writes for "weight of the light panel" before it measures,
because that rule is what used to beat the stacking rule and hold the slab in
two columns on a phone. It exits non-zero on any failure.

`rail-probe.js` asserts too: that the section is given exactly the runway its
row needs, that the row travels sideways and back and lands with the row used
up rather than overshooting, that the stage stays centred, that a picture of
any shape fills its card, that a linked card's cue is visible before hover and
blooms on it, that a card with no link does not pretend to be one, that tabbing
to a card off to the side brings it into view, and that a narrow screen gets a
swipeable row rather than a pinned rail.

`story-probe.js` asserts rather than prints: it checks that the sweep advances
with scroll and retreats on the way back, that the same scroll position gives
the same half-lit sentence either way, that the notch travels and keeps an even
clearance from both corners, that the panel stays centred, that media of any
shape fills the panel, and that the entrance animates rather than snapping. It
exits non-zero on any failure.

`header-probe.js` asserts that the bar is completely transparent at the top and
frosted once the page has moved, that a panel spans the bar rather than its own
label, that the page behind is pushed back by a backdrop-filtered sheet and not
by a `filter` on the content, that the panel is drawn above its own scrim, that
Escape closes it and leaves it closed, that a tap on a phone opens a panel and
does not immediately shut it again, that the drawer collapses completely, that
the button keeps both of its shadows and a letter-spacing that is a real
length, and -- with the script blocked outright -- that the panels still open.

`tests/` is excluded from the release zip, so none of this ships.

## What it prints

Sampled opacity, filter and transform for four cases, every 60ms. A working
preset walks its property from the start value to the end value. A broken one
sits at the end value the whole way.

This is what a broken Fade In looked like:

```
=== t-fade (above the fold)
  0ms   o=1  in=0
  63ms  o=0.996447  in=1     <- never started at 0
  125ms o=1  in=1
```

and a working one:

```
=== t-fade (above the fold)
  0ms   o=0  in=0
  62ms  o=0.34061  in=1
  123ms o=0.577074 in=1
  735ms o=0.999861 in=1
```

## What to check

- Every above-the-fold case starts at its start value, not its end value
- `t-blur` starts at `blur12`, not a fraction of a pixel
- `t-fade2`, below the fold, sits at `o=0` with `in=0` and does **not** drift
  downward — drifting means it is animating *into* hiding, which is the bug
  that broke Fade In and Blur In
- Transition events actually fire

## One trap

Do not pass `--force-prefers-reduced-motion=false` to Chrome. It is a switch,
not a boolean: any value turns reduced motion **on**, the module then correctly
declines to animate anything, and it looks exactly like a total failure. That
cost an hour.

## bench.js

Every widget on one page at once, because that is the case that matters: a
section that animates is cheap on its own and expensive in company.

It does **not** judge by frame duration. Headless paces `requestAnimationFrame`
at 30Hz, so every frame reads as 33.3ms whether the work took one millisecond
or fifteen. What it reads instead is Chrome's own counters, which nothing
paces: how many style recalculations and layouts the code forced, and how long
they took. Those go up exactly as often as the code makes them, which is the
thing being optimised.

It also counts `getComputedStyle` calls and geometric property reads by
wrapping them, because a single one of those inside a scroll loop is worth more
than any amount of guessing about which line is slow.

The pass in 2.19.0 moved it from:

```
  getComputedStyle calls      1668  (7 per frame)
  style recalculations        814  (124.0ms)
  layouts                     478  (82.0ms)
  total work                  331.0ms
```

to:

```
  getComputedStyle calls      2  (0 per frame)
  style recalculations        366  (63.0ms)
  layouts                     37  (3.0ms)
  total work                  111.0ms
```

## Page transitions

The only fixture that is a small site rather than a page, because a transition
needs somewhere to go. It also needs a server: `file://` gives every page a
null origin, and the covering flag is handed between pages in sessionStorage,
so over `file://` every page looks like a first visit.

```
php tests/browser/transitions-fixture.php
python3 -m http.server 8732
node tests/browser/transitions-probe.js
```

The probe checks what only a browser can answer: that the columns really do
stagger by the configured amount, that a page arriving under the curtain is
never shown uncovered for a frame, and that each of the timeout fallbacks ends
with the page revealed — including with every `animationend` suppressed, and
with `sessionStorage` throwing on access the way it does in private browsing.

If a run appears to hang with no output at all, the cause is almost always a
Chrome left behind by an earlier run that was interrupted. `pkill -f
chrome-headless-shell` before starting clears it; this cost three debugging
runs before the pattern was obvious, because the probe's own timeouts cannot
fire when the hang is in the connection to the browser rather than in the page.

`ETRN_TRACE=1` prints each section as it starts, which narrows down where. `ETRN_CHROME` points it at a Chrome binary and
`ETRN_HEADFUL=1` runs it visibly.
