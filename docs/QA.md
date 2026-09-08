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

- [ ] **Settings → Eruda Toolkit** lists both modules, both checked
- [ ] Unchecking Duplicate Pages and saving removes the Duplicate row action
- [ ] Unchecking Numbered Accordion and saving removes the widget from the
      Elementor panel
- [ ] With Elementor deactivated: the settings page still loads, the accordion
      module shows "Elementor is not installed or not activated", and **the
      duplicator still works**
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
