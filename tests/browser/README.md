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
node tests/browser/probe.js
```

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
