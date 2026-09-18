# Manual QA checklist

`php tests/run.php` covers the pure logic. This file covers what it cannot: the
hook wiring, the capability checks and the Elementor round trip. Work through it
on a real site before cutting a release.

## Upgrade from 1.0.x

The 2.0.0 rebrand is display-only, and this is the part most worth proving.

- [ ] A site running 1.0.3 sees 2.0.0 offered in **Dashboard → Updates**
- [ ] Updating in place leaves the plugin directory named
      `numbered-accordion-elementor` and the plugin list entry renamed to
      "Eruda Toolkit"
- [ ] A page built with the accordion in 1.0.3 still renders identically after
      the update, without being re-saved
- [ ] The widget is still found in the Elementor panel under its old name
- [ ] Update notices still arrive after 2.0.0 is installed (the channel survived
      its own rename)

## Modules and settings

- [ ] **Settings → Eruda Toolkit** lists all five modules, all checked
- [ ] Unchecking Duplicate Pages and saving removes the Duplicate row action
- [ ] Unchecking Numbered Accordion and saving removes the widget from the
      Elementor panel
- [ ] Unchecking Impact Grid and saving removes that widget from the panel too
- [ ] With Elementor deactivated: the settings page still loads, the accordion
      and impact grid modules both show "Elementor is not installed or not
      activated", and **the duplicator still works**
- [ ] An Editor (not an Administrator) cannot see the settings page

## Duplicator

- [ ] A **Duplicate** link appears on hover in Pages and in Posts
- [ ] It does not appear on Media, or on a CPT the theme registers
- [ ] Duplicating lands back on the list table with a success notice and an
      "Edit the copy" link
- [ ] The copy is a **Draft**, titled `<original> (Copy)`, authored by whoever
      clicked, with its own slug
- [ ] Featured image, categories, tags, page template, parent and menu order all
      carried over
- [ ] Comments on the original did **not** carry over
- [ ] Bulk-selecting several pages and choosing Duplicate reports the right count
- [ ] Editing a nonce'd Duplicate URL by hand (change `post=`) is rejected
- [ ] A Contributor, who cannot publish pages, is not offered Duplicate on a page

## Duplicator + Elementor

This is where a naive duplicator goes wrong, so test it deliberately.

- [ ] Duplicate a page built in Elementor; open the copy in Elementor
- [ ] The layout is intact — every section, widget and setting
- [ ] Text containing double quotes and apostrophes survives (the slashing path)
- [ ] The copy's styling is correct on the front end, **and editing the copy does
      not change the original's appearance** (this is what the `_elementor_css`
      denylist prevents)
- [ ] Editing the original afterwards does not change the copy

## Accordion fixes in 2.0.0

- [ ] Multi-open on, "allow closing every item" off: open three rows, confirm
      two of them can be closed and the last one cannot
- [ ] Single-open, "allow closing every item" off: the open row still refuses to
      close
- [ ] With a screen reader, a **closed** row announces only its title — not its
      eyebrow
- [ ] An item with a blank title: the remaining rows number 01, 02, 03 with no gap
- [ ] A blank first item with "keep one open": the first *rendered* row opens

## Impact Grid

The parsing, the figure detection and the numbering are covered by
`php tests/run.php`. Everything below is what it cannot reach.

### Rendering

- [ ] Dropping the widget in gives six cards with the shipped copy
- [ ] Uploading an icon to a card shows it; a card with no icon closes up
      without leaving a gap
- [ ] The icon Width slider tracks all the way to 320px, including well above
      the uploaded file's own width, and stays sharp there
- [ ] A wide icon in a narrow column is contained rather than overflowing
- [ ] Cards 4-6 render ticks, and card 6 nests its two bullets under its one
      check item
- [ ] Emptying a card's every field drops it, and the cards after it renumber
      without leaving a gap in the sequence
- [ ] Switching a card between Figure and Checklist shows the right controls
- [ ] Setting Number format to Hidden removes the badges and hides the badge
      style section
- [ ] Columns responds at tablet and mobile breakpoints

### Stacking

- [ ] At mobile width the cards pin and pile, each leaving a ledge of the one
      beneath, and the pile scrolls away cleanly at the end
- [ ] At desktop width the cards behave as an ordinary grid, with no card
      shifted down the page
- [ ] Setting Card layout to stack on desktop piles them there too
- [ ] The Stack ledge slider changes the visible sliver
- [ ] With a sticky site header, Stack offset from top clears it
- [ ] Stacking still reads correctly with the card background left opaque, and
      visibly breaks with a transparent one (expected; the control says so)
- [ ] An Elementor container with overflow hidden above the widget does not
      kill the pinning

### Motion

- [ ] Scrolling the grid into view reveals the cards left to right in a
      stagger, not all at once
- [ ] Figures count up from zero and land on exactly the typed string,
      commas included
- [ ] A figure typed as `30,400+`, `~5` or `45%` does not count, and shows
      exactly as typed
- [ ] Ticks draw their ring then their mark
- [ ] Hovering a landed card lifts it immediately — no delay inherited from
      the reveal stagger
- [ ] A card far below the fold, reaching the viewport on its own, appears
      without waiting out the earlier cards' delays
- [ ] With Count-up duration set to 0, figures appear at their final value
- [ ] With **System Settings → Accessibility → Reduce motion** on: every card
      is visible immediately, nothing moves, and figures show their final
      value
- [ ] With JavaScript disabled: the whole grid is visible and readable, and
      every figure shows its final value

### Editor and assets

- [ ] Editing a card in the Elementor editor re-renders it and the animation
      re-runs
- [ ] Two Impact Grids on one page both animate, and each counts its own
      figures
- [ ] The CSS and JS load only on pages carrying the widget
- [ ] A screen reader reads each figure once, at its final value, and does not
      read a mid-animation number

## Text animations

- [ ] `node tests/browser/probe.js` shows every above-the-fold case starting at
      its start value. Run this before any release that touches the CSS or the
      script; three releases shipped dead animations because nothing measured
      them
- [ ] **The section is actually there.** Select a Heading, open **Style**, and
      find "Eruda Text Animation". This is the 2.3.0 regression: the controls
      were registered on a hook that never fires per widget, so the feature
      shipped invisible. Check this first, on every release that touches the
      hook
- [ ] Unchecking Text Animations removes the Style section from the Heading and
      Text Editor widgets
- [ ] The section appears on Heading and Text Editor, and on nothing else:
      Button, Icon Box, Image Box and a container all show an unchanged
      Style tab
- [ ] The section appears exactly once on a widget, not repeated
- [ ] A native Heading with Words Up animates on the live page and in the editor
- [ ] Scroll Highlight sweeps word by word as the paragraph crosses the middle
      of the screen, and dims again when scrolling back up
- [ ] Scroll Highlight on a one-line heading still sweeps rather than flicking
      on all at once
- [ ] Scroll Highlight hides Duration, Stagger, Delay, Easing and the trigger
      controls, and shows "Dimmed to" instead
- [ ] "Dimmed to" changes how faint the unlit words are
- [ ] Scroll Highlight tracks the scrollbar rather than lagging behind it; if it
      eases or stutters, a transition is still applied to the words
- [ ] Words Build matches the GSAP reference out of the box: words 60ms apart,
      500ms each, lifting 15px, easing "Natural"
- [ ] Changing the preset in the editor re-splits and animates the new way
- [ ] Dragging the Stagger slider changes the timing without a re-render
- [ ] **Replay animation** plays it again in the preview, without a reload
- [ ] Replay works repeatedly on the same widget without duplicating or
      mangling the text, and on a heading containing bold and a link
- [ ] Replay works after changing the preset, and after changing the duration
- [ ] No "Replay animation" button appears on the live page
- [ ] Setting Starts to "On page load" hides "Starts at" and "Replay every time"
- [ ] A Text Editor of three paragraphs staggers continuously across all three,
      sweeping top to bottom rather than each paragraph restarting
- [ ] A heading containing `<strong>` and `<a>` keeps both, and the link works
- [ ] A heading containing `<br>` still breaks in the same place
- [ ] Text stays selectable, and copies with normal single spaces
- [ ] Descenders (the tails on g, y, p) are not clipped by a masked preset
- [ ] Lines Reveal groups correctly, and regroups after a resize from desktop
      width to mobile width
- [ ] Lines Reveal on a heading with `<strong>` mid-line groups the bold words
      with their neighbours rather than on a line of their own
- [ ] With JavaScript disabled, every animated heading renders plainly and
      completely
- [ ] With Reduce Motion on in the OS, nothing splits and nothing moves
- [ ] A page with no animated widget loads neither `text-animation.css` nor
      `text-animation.js`
- [ ] A 1.0.x-era accordion page is unaffected

Everything above the Lines Reveal items, plus the markup, Reduce Motion and
trigger behaviour, is covered headlessly by the jsdom harness described in
`docs/superpowers/plans/2026-09-17-eruda-text-animations.md`. What genuinely
needs a browser is line grouping (jsdom reports every `offsetTop` as 0), the
descender clipping, and anything visual.

## Smooth scrolling

`node tests/browser/probe.js` and `tests/browser/scroll.html` cover the
mechanics. These are the things only a real site shows.

- [ ] Scrolling the live site eases rather than jumping, on trackpad and on a
      mouse wheel
- [ ] A sticky header still sticks
- [ ] An in-page anchor link glides to its target and lands in the right place
- [ ] The Elementor editor scrolls normally: the canvas, the panel and drag and
      drop are all unaffected
- [ ] wp-admin scrolls normally
- [ ] On a phone, scrolling feels native -- momentum and rubber-banding intact
- [ ] With Reduce Motion on in the OS, scrolling is completely normal
- [ ] Text animations still fire correctly while smooth scrolling is on
- [ ] Unchecking Smooth Scrolling in Settings restores normal scrolling, and
      neither lenis.min.js nor lenis.css is loaded
- [ ] A very long page still reaches the bottom, and the scrollbar is the right
      length
