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
node tests/browser/badge-probe.js   # Spin Badge
node tests/browser/spin-probe.js    # Eruda Spin on an existing widget
```

Both run headless on purpose. A scrubbed animation is driven by
`requestAnimationFrame`, and Chrome does not run rAF in a tab that is not
visible — drive a real window and every measurement freezes the moment the
window loses focus, which reads exactly like a broken build. That cost an hour
once already.

`badge-probe.js` samples the ring's angle every 60ms across a hover and checks
it keeps moving, by less each time, before settling -- a badge that jammed
would go from a full step to nothing between two samples. It also checks the
ring closes (the text ends within 16 degrees of where it starts, against 59
unstretched) and that no element in the badge carries a filter or is a bitmap.

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
