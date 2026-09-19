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

- [ ] **Settings → Eruda Toolkit** lists all six modules, all checked
- [ ] The widget panel has an "Eruda Toolkit" section holding the accordion,
      the impact grid and the scroll story, and none of them appear in General
- [ ] **Automatic updates** is checked by default; unchecking it and saving
      makes an available update wait for a deliberate click
- [ ] With automatic updates on, a new release installs itself within a day
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

## Scroll Story

`node tests/browser/story-probe.js` measures the scrub, the notch travel, the
centring and the entrance in headless Chrome, against
`tests/browser/story.html`. Run it before touching any of them. The rest needs
a real site.

- [ ] **Scroll Story is actually in the panel.** Search the widget panel for
      "Scroll Story" and add it. 2.6.0 shipped without it being addable at all
- [ ] Dropping the widget in gives three items and a centred panel

### The scrubbed highlight

- [ ] Stopping mid-item leaves the sweep stopped mid-sentence. It must not
      carry on to the end of the line on its own — that is the 2.6 behaviour
      this replaced
- [ ] Scrolling back up puts the letters out again, in reverse order
- [ ] Letters passing through the flash colour do so only on the way *in*.
      Going out is a plain fade with no flash
- [ ] Scrolling faster makes the sweep faster. There is no fixed per-letter
      delay any more
- [ ] Returning to the same scroll position gives the same half-lit sentence,
      whichever direction you arrived from
- [ ] "Highlight starts at" and "finishes at" move where on screen the sweep
      runs. Setting finish above start is refused and falls back to 85/45

### The panel

- [ ] The panel is vertically centred on the screen while pinned, with equal
      space above and below
- [ ] Changing the panel height, the column gap or the space between items
      leaves it centred
- [ ] "Vertical nudge" shifts it off centre for a sticky site header
- [ ] Nothing flashes black in the middle of a change. The outgoing picture
      stays put and the incoming one arrives over it
- [ ] Each of the four transitions plays, and each is mirrored when you scroll
      back up rather than repeating the downward version
- [ ] An image of any shape fills the panel with no bars. Try a very tall one,
      a very wide one, and one far smaller than the panel — a small picture is
      scaled up to fill, not centred in a gap
- [ ] On a theme that styles images heavily, the media still fills. This is the
      one that used to break in the wild and never in the test page
- [ ] Let a change finish, then look hard at the top and bottom of the settled
      picture: no band, no gradient, no veil left over from the transition
- [ ] "Focal point" changes which part of a cropped image survives
- [ ] A video item plays muted and looped while it is the one being read, and
      pauses when it is not
- [ ] An item with no media leaves the previous media showing rather than
      going blank
- [ ] The panel stays pinned for the whole section and releases at the end

### The notch

- [ ] The notch cuts *into* the panel rather than sticking out of it, and its
      corners are curved rather than square
- [ ] The notch travels down the panel's left edge as you scroll the section
- [ ] **It stops well short of both corners** and never merges into one. At the
      top of the section it is already some way down; at the bottom it is
      still some way up
- [ ] "Notch travel" widens and narrows that range, and the clearance stays
      even at both ends
- [ ] Resizing the browser rebuilds the notch to the new panel width instead of
      leaving it stretched or clipped
- [ ] With a border width set, the border follows the notch instead of being
      clipped away, and it is the width asked for
- [ ] **The notch depth, length and travel controls actually do something.**
      All three were dead until 2.8.0 because the panel shadowed the value they
      wrote
- [ ] **A notched panel still has rounded corners**, the radius control works
      with the notch on, and the border follows the corners as well as the
      notch
- [ ] Turn the radius up to its maximum with a long notch: the notch still has
      a straight stretch of edge above and below it and never merges into a
      corner
- [ ] Turning the notch off leaves the radius working as an ordinary CSS
      radius, and the border becomes an ordinary CSS border

### Labels and type

- [ ] The eyebrow renders as a pill: dot, then uppercase text, hairline border
- [ ] An item with an empty eyebrow has no pill at all, and the ones around it
      are unaffected
- [ ] Turning the dot off leaves the text centred in the pill
- [ ] Heading and description take their colours and type independently. Set
      them to different waiting colours and confirm both are honoured
- [ ] Switching the label to Number restores the old numbering
- [ ] The heading tag control changes the tag without changing the styling

### Everything else

- [ ] **Below 1024px each picture moves under its own item**, after the words,
      and the panel is gone. Nothing scrolls sideways
- [ ] The highlight still runs on a phone, and an item's words finish while
      its picture is still on the way up rather than long after
- [ ] **Each stacked picture has the notch along its bottom edge**, and it
      travels as you scroll that item past. Down the side is the desktop one,
      and along the top is where it was before 2.20.0 -- there it cuts into the
      words above rather than sitting on the seam below
- [ ] **A stacked picture keeps the border the panel has.** With a border width
      set, it follows the notch as well as the corners and is the width asked
      for, exactly as on the desktop panel. Turn the notch off and it becomes
      an ordinary border
- [ ] Give one item no picture of its own: every other item's notch still
      lands on its own picture rather than on the one below it
- [ ] "Notch depth on a phone" and "Notch length on a phone" change it. If the
      notch looks flat against both corners, its length is longer than the
      picture's edge can hold
- [ ] **It is still moving as the picture leaves the top of the screen**, not
      parked at the far end. It is driven by the picture's own crossing, so it
      starts as the picture appears and finishes as it goes
- [ ] If the travel looks slight, check the corner radius and the notch length
      before anything else -- both eat the room it has to move in, and a 44px
      radius on a 310px picture leaves about 31px of travel where a 16px one
      leaves 68px
- [ ] Widen the browser again: every picture goes back into the panel, in the
      right order, and the pinning works as before
- [ ] "Picture shape", "Space above the picture" and the phone item spacing
      all work
- [ ] With JavaScript disabled: every item's text is readable in its read
      colour, and the first picture is visible
- [ ] With Reduce Motion on: no letter sweep at all, no entrance, no drift;
      the panel still changes and the section is still readable
- [ ] Editing an item in the editor re-renders it and the tracking still works
- [ ] Two Scroll Story widgets on one page track independently

## Scroll Rail

`node tests/browser/rail-probe.js` measures the travel, the runway, the
centring, the cue and the narrow-screen fallback in headless Chrome, against
`tests/browser/rail.html`. Run it before touching any of them.

- [ ] **Scroll Rail is in the panel.** Search the widget panel for "Scroll
      Rail" and add it. Two releases have shipped a widget nobody could add
- [ ] Dropping it in gives four cards in a row
- [ ] Scrolling down moves the row sideways, and scrolling up moves it back

### Flow against pinned

- [ ] **On the default (Pinned), the page stops while the row crosses.** Scroll
      into the section: it settles in the middle of the screen, holds still for
      a moment, the row crosses without the page moving at all, it holds again,
      and only then does the page carry on
- [ ] **The heading and copy above the row do not move while the row crosses.**
      This is the whole point of "What holds still", which defaults to the
      section. Set it to "Just the row of cards" and they should scroll away
      again, which is the behaviour it replaced
- [ ] The section lets go promptly once the row has arrived and the pause is
      spent. If it stays stuck for screens afterwards, the wrapper that bounds
      the hold is not being created — check the browser console
- [ ] With "A specific element", a class you put on a container in Elementor's
      Advanced tab and type here with the dot works, and a selector that
      matches nothing falls back to holding the row alone rather than breaking
- [ ] Nothing is left behind when the rail is not driving: resize to a phone
      width and the wrapper and spacer should be gone from the DOM
- [ ] **The row is centred on screen and never clipped**, including when the
      section is taller than the window. Shrink the browser until the section
      cannot fit: the cards should stay whole and centred, and the top of the
      section should run off above them instead
- [ ] A section that does fit is centred whole, with everything in it on screen
- [ ] "Keep centred" forces either answer: Always the cards, or Always the
      section (which is the old behaviour — heading whole, cards clipped)
- [ ] "Pause before it sets off" and "Pause before it lets go" lengthen those
      two held moments
- [ ] Pinned makes the section taller, and what follows sits further down the
      page. That is the mechanism, not a bug — it is what reserves the
      scrolling the crossing is measured against
- [ ] **On Flow the rail does not push anything down.** Put a section after it,
      note where it starts, then switch between the modes: Pinned should move
      it down, Flow should put it back
- [ ] On Flow the section is exactly as tall as its cards, with no dead space
      above or below the row. Change the card width or shape and the section
      follows
- [ ] **The row does not start moving until you can see the cards**, and has
      not finished before you have read them. Scroll slowly into the section
      and watch: at the frame it sets off, most of the section should be on
      screen
- [ ] "Starts once this much is on screen" and "Finishes while this much is
      still on screen" move those two moments
- [ ] "Extra scroll" at zero adds nothing. Raising it makes the crossing
      gentler and does push what follows down, which is the trade it is for
- [ ] Switching "Section height" to a fixed share of the screen brings the vh
      slider back and works
- [ ] On Pinned the section holds still on screen while the row crosses, and
      the stage stays centred
- [ ] "Scroll distance" appears only on Pinned, where it is the only thing it
      could mean
- [ ] The row is still for a moment as it arrives and as it leaves, rather than
      snapping into motion
- [ ] The row finishes exactly as the last card reaches the edge. You never
      scroll past a rail that has stopped moving
- [ ] On Pinned, adding or removing cards changes how far you scroll without
      touching a setting
- [ ] The stage sits centred on the screen, and stays centred when you change
      the section height
- [ ] A picture of any shape or size fills its card, including one smaller than
      the card. Try it on a theme that styles images heavily
- [ ] The progress bar fills as the row travels

### The click cue

- [ ] Every linked card shows the cue **before** you hover it
- [ ] Hovering fills the cue in, grows it, moves the arrow, lifts the card,
      brightens its border and brings the picture back to colour
- [ ] A card with no link has no cue, is not a link, and shows no pointer
- [ ] Tab through the cards: each one is brought into view as it takes focus,
      and the focus ring is visible. This was doubly-offset before 2.9.0 and
      put the focused card off the far side of the screen
- [ ] The cue's corner, mark, size and colours all work

### Everything else

- [ ] Below 1024px nothing pins: the row is a swipeable strip with snap points
- [ ] **On a phone the row runs edge to edge and the first card lines up with
      the heading above it.** Set "Gutter on a phone" to match your section's
      padding
- [ ] The next card peeks off the screen edge rather than being cut off at the
      section padding
- [ ] Swiping snaps each card to that gutter, not to the middle of the screen
- [ ] The progress bar and the count are both visible on a phone, both start
      from the same gutter as the cards, and both move as you swipe
- [ ] The count reads the card you are actually looking at
- [ ] **Nothing makes the page scroll sideways.** Check on a real phone, not
      just a narrow window
- [ ] Turning "Edge to edge on a phone" off boxes the row back inside the
      section padding, and still does not overflow
- [ ] **Both ends of the row fade rather than cutting a card off**, and they
      fade to whatever is behind the section -- put the rail over a picture or
      a gradient and the fade still reads as the card leaving
- [ ] "How wide the fade is" changes it per device, and turning "Fade the ends"
      off leaves a hard edge
- [ ] The fade does not clip a card's shadow or its hover lift above and below
      the row
- [ ] With Reduce Motion on, the same. No pinning, no hover transitions, and
      the cue is simply always visible
- [ ] With JavaScript disabled the row is still a usable horizontal scroller
- [ ] Resizing between wide and narrow switches between the two cleanly, with
      no leftover height or transform
- [ ] Editing a card in the editor re-renders it and the rail still works
- [ ] Two Scroll Rails on one page travel independently

## Mega Header

`node tests/browser/header-probe.js` measures the two scroll states, the panel
geometry, the scrim, the button's two shadows, the phone drawer and the
script-blocked fallback, against `tests/browser/header.html`.

- [ ] Drop it into your header template and set the side padding to match your
      sections', or the logo will not sit above the words under it
- [ ] **At the very top the bar is completely invisible** — no fill, no blur,
      no line under it. The hero should look untouched
- [ ] Scroll a little: it frosts, and the fill, the blur, the hairline and the
      shadow all arrive together rather than one after another
- [ ] **It also comes away from the top and narrows to 80% of the window**, as
      one movement. "Gap from the top", "How wide it becomes" and "Side padding
      once frosted" are the three settings involved
- [ ] **Open a panel while it is floating: the bar snaps back to full width and
      back up to the top**, and bar and panel become one sheet. Close it and it
      returns to the floating bar
- [ ] The bar carries the button **once**. Two side by side means the drawer's
      copy is not being hidden
- [ ] Menu labels roll on hover — one copy out, one in. Try all four settings,
      including letter by letter and scramble
- [ ] "Preview a state" holds it frosted or holds a panel open so you can look
      at it in the editor. **Put it back to Normal before you publish**
- [ ] Set the button to a solid colour: it genuinely goes solid. A gradient
      still showing means the fill is layering rather than replacing
- [ ] The button's text is white over the gradient, and stays white on hover
- [ ] Scroll back up: it goes fully transparent again
- [ ] "What it does on scroll" set to Fill gives a flat colour with no blur,
      and None leaves it transparent the whole way down
- [ ] Over a **busy photograph**, check the menu text is still readable once
      frosted. If it is not, that is the fill's opacity, not the blur
- [ ] **Hover an item with a panel: it opens full width**, not just under the
      word. A panel the width of its own label means the item has picked up a
      `position` it should not have
- [ ] Sweep the pointer straight across the bar to the far side: **no panel
      should open on the way**. If they flash open in turn, raise "Pause before
      a panel opens"
- [ ] Move from one open panel straight to the next: it swaps immediately, with
      no pause
- [ ] The page behind the panel blurs and darkens, and **the panel itself stays
      sharp**. A blurry panel means the bar has lost its stacking order
- [ ] The bar is never blurred by its own scrim
- [ ] Click the scrim: everything closes
- [ ] Press Escape: the panel closes and **stays closed**, with focus back on
      the item that opened it
- [ ] Tab through the bar: each panel opens as you reach it and closes as you
      leave, and you can tab into the links inside it
- [ ] **On a phone, tap an item with a panel: the links appear and stay.** If
      the caret flips and the label lights but nothing opens, the panel is
      being opened by focus and closed by the click that follows
- [ ] The drawer is completely flat when shut — no sliver of white hanging
      under the bar
- [ ] The drawer's picture and blurb are gone; only the links remain
- [ ] The button moves into the drawer, full width, and the burger becomes a
      cross
- [ ] Following any link shuts the drawer behind you
- [ ] Resize from phone to desktop with the drawer open: it closes cleanly
- [ ] **Nothing makes the page scroll sideways.** Check on a real phone
- [ ] With Reduce Motion on, everything still opens and closes — at once,
      rather than not at all
- [ ] **With JavaScript disabled the menu still works**: panels open on hover,
      links are all reachable
- [ ] The button keeps its gradient and both shadows, and its letter spacing
      actually renders. If tracking looks like it is doing nothing, check the
      unit is em rather than per cent
- [ ] Something on the page drawing over the header means raising "Stacking
      order"
- [ ] Two headers on one page is not a supported arrangement, but neither
      should take the other down

## Eruda Spin (on an existing widget)

`node tests/browser/spin-probe.js` measures the turning, the spin-down and the
two targets, against `tests/browser/spin.html`.

- [ ] Add an Image widget, open Style, find **Eruda Spin**, switch it on. The
      picture turns
- [ ] It slows to a stop under the pointer rather than stopping dead, and the
      widget lifts. Moving away starts it again
- [ ] The section appears on Image, Icon, Button, Image Box and Site Logo, and
      **not** on a Heading
- [ ] "What turns" set to the whole widget turns the container instead, and the
      picture inside it no longer turns on its own
- [ ] "Under the pointer" set to keep turning means hovering does not stop it,
      and the stopping-time control disappears
- [ ] Anticlockwise reverses it
- [ ] It works in the editor preview, not only on the live page
- [ ] The assets load only on a page that has a widget with it switched on
- [ ] On a touch screen, tapping slows it and it starts again on release
- [ ] With Reduce Motion on nothing turns, and the lift still works
- [ ] With JavaScript disabled it still turns, from the stylesheet, and simply
      does not slow down

## Performance

`node tests/browser/bench.js` puts every widget on one page and reports what
they cost. Run it after touching anything that happens on scroll.

- [ ] `getComputedStyle calls` stays at zero per frame. One of those in a
      scroll loop is a style recalculation the browser did not need to do
- [ ] `layouts` stays in the tens, not the hundreds. Hundreds means something
      is reading a geometric property after writing a style
- [ ] `total work` does not climb. It was 331ms before 2.19.0 and is 111ms now
- [ ] On a real mid-range phone, scroll the whole page: no stutter through the
      story, the rail, or past a spinning image
- [ ] Two or three of these widgets on one page still scroll smoothly. One on
      its own proves nothing
