# Lenis

Vendored, not installed. Version 1.3.26, MIT, from https://github.com/darkroomengineering/lenis

Taken as-is from the npm package's `dist/`. Do not edit these files: the next
upgrade overwrites them. Anything this plugin needs to change belongs in
`modules/smoothscroll/assets/js/smooth-scroll.js`.

## Why this library rather than our own

Smooth scrolling is easy to do badly. The usual approach — translate the page
with a transform and fake the scrollbar — breaks `position: sticky`, breaks
anchor links, breaks find-in-page, and breaks `IntersectionObserver`, which
every text animation in this plugin depends on.

Lenis drives the real scroll position (`window.scrollTo` with
`behavior: instant`, once per frame), so the browser still thinks it is an
ordinary scroll. Observers fire, sticky headers stick, anchors work.

## Upgrading

```sh
npm pack lenis
tar -xzf lenis-*.tgz
cp package/dist/lenis.min.js package/dist/lenis.css package/LICENSE vendor/lenis/
```

Then re-run `node tests/browser/probe.js` and the smooth-scroll checks in
`docs/QA.md`.
