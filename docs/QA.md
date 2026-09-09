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

- [ ] **Settings → Eruda Toolkit** lists all three modules, all checked
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
