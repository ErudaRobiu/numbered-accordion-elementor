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

## Image Compare

`php tests/browser/compare-fixture.php > tests/browser/compare.html` builds the
fixture from the widget's own `render()`. The probe in that page asserts the
divider actually moves rather than merely existing.

- [ ] **Drag the divider across.** The second picture is revealed as it goes,
      with no lag between the line and the edge of the reveal
- [ ] Clicking anywhere in the frame jumps the divider there
- [ ] **Tab to it.** The grip takes a visible focus ring, and the arrow keys,
      Home and End all move the divider
- [ ] A screen reader announces it as a slider with a percentage
- [ ] Set **Moves on** to Hover: it follows the mouse and springs back to its
      starting position when the pointer leaves
- [ ] On a phone it drags with a finger in both modes, and the page does not
      scroll while dragging
- [ ] **Up and down** clips from the top, and the grip turns with it
- [ ] Two pictures of different shapes: the first sets the height, the second is
      cropped to fill rather than squashed
- [ ] Setting a fixed height crops both, and clearing it hands the height back
      to the first picture
- [ ] Nothing jumps about as the pictures load
- [ ] Setting only one picture renders nothing at all, rather than half a widget
- [ ] The labels fade as the divider passes under them, and an empty label is
      gone rather than blank

## Flow Schematic

`php tests/browser/schematic-fixture.php > tests/browser/schematic.html` builds
the fixture from the widget's own `render()`. Serve the folder rather than
opening it from the file system.

- [ ] The widget appears in the Elementor panel under **Eruda Toolkit**
- [ ] **Adding and removing a stage re-flows the whole diagram** — the
      connectors stay between the nodes, and the return path and monitoring bar
      still span the stages they name
- [ ] A stage with no pin number opens no gap where one would be
- [ ] The four treatments all render: standard, accent, quiet (no fill) and
      dashed
- [ ] A connector label set to sit **on** the line cuts it; set to sit **above**
      it, the line runs unbroken underneath
- [ ] **The surface colour matches whatever is behind the diagram.** Every label
      that cuts a line paints this colour behind itself, so a mismatch shows as
      a pale block on the rule
- [ ] Switching the card frame off leaves the diagram sitting straight on the
      section, with no border and no padding
- [ ] Switching off the return path, the monitoring bar or the boundary removes
      the band **and** the space it occupied — no empty strip is left behind
- [ ] "From stage" and "to stage" typed in backwards still describe the same
      band, and a number past the last stage is pulled back rather than
      breaking the grid
- [ ] A one-stage diagram renders, and drops the return path rather than drawing
      a bracket from a node to itself
- [ ] **Narrow the browser**: the diagram turns through ninety degrees. The
      connectors stand up as 1px rules with the arrow pointing down, and the
      three bands become labelled horizontal rules
- [ ] **Put the widget in a half-width column on a full-size desktop.** It
      stacks there too — it asks its own width, not the window's
- [ ] A stage carrying a link is a real link: it lifts on hover, takes keyboard
      focus, and does not move for a visitor with "reduce motion" on
- [ ] Every colour control changes what it names and nothing else
- [ ] **Set a diagram under Artwork** and it replaces the stages entirely
- [ ] With no card, the artwork sits on the section with no border, no
      background and no padding — a transparent SVG shows the section through it
- [ ] **Narrow the browser until the artwork stops fitting.** It pans sideways
      rather than shrinking, and the slider appears under it
- [ ] The slider and the frame stay in step both ways: dragging the handle moves
      the diagram, and swiping the diagram moves the handle
- [ ] Widen it again and the slider disappears rather than sitting there doing
      nothing
- [ ] **Block the script.** The diagram still pans by swipe and by keyboard, and
      no slider appears
- [ ] "Smallest readable width" changes the point at which panning begins
- [ ] **An SVG letters in the site's own typeface**, not Helvetica — compare a
      label in the diagram against the same face in the body copy beside it
- [ ] Its labels can be selected with the cursor and found with the browser's
      own find
- [ ] Switching "print the SVG into the page" off falls back to a picture, and
      the diagram still pans
- [ ] A raster image is never inlined, and still pans
- [ ] **Upload an SVG carrying a script tag** (or an onclick attribute) and
      confirm neither reaches the page — view source on the front end

## Split Slab and Comparison Ledger

`php tests/browser/explainer-fixture.php > tests/browser/explainer.html` builds
the fixture from the widgets' own `render()`, with the slab twice (numbers empty
and filled), the slab over a background picture, and the ledger both with and
without its winning column marked. Serve the folder rather than opening it from
the file system.

- [ ] Both widgets appear in the Elementor panel under **Eruda Toolkit**, and
      neither is in General
- [ ] **The step numbers start empty.** The label sits hard against the left
      edge of the panel with no gap where a number would be
- [ ] Typing `01` into one panel shows it and opens the gap; clearing it again
      closes the gap rather than leaving a hole
- [ ] A number in one panel and not the other looks deliberate, not broken
- [ ] **Panel 2 takes a background picture** from its own background group, and
      position, size and repeat all work on it
- [ ] **Raise "Darken behind the text"** and the white heading, the body and the
      green step number all keep their contrast over the picture. At 0 the
      panel is flat ink again
- [ ] The veil sits under the words and over the picture -- never over the text
- [ ] The diagram loses its black background against the plate. Switching the
      plate off leaves the artwork on the panel with its own background intact
- [ ] Both panel footers -- the pills and the spec rows -- sit on the bottom
      edge when one panel is longer than the other
- [ ] The motes drift across the diagram, and stop entirely for a visitor with
      "reduce motion" on. Setting the count to 0 removes them
- [ ] Pictures come out of the media library with their own dimensions and
      srcset, and both are lazily loaded
- [ ] **The ledger's third column is tinted and dotted.** Switching off "Mark
      the third column" removes both and leaves the row readable
- [ ] Switching off the header row removes it without moving the rows
- [ ] **On a phone the ledger stacks**, the header row disappears, and the
      middle value is prefixed with the column 2 heading and a middot
- [ ] Adding a sixth row and deleting one both work without touching the styling
- [ ] **The slab fills the container it is dropped into**, at every breakpoint,
      with no dead strip on the right
- [ ] **The slab stacks on a narrow screen** -- one panel over the other, the
      bar lying across between them -- with the "weight of the light panel"
      slider set to something other than its default, and nothing cut off the
      right-hand edge of either panel
- [ ] **A slab dropped into a half-width column stacks too**, on a full-size
      desktop, because it is the slab that is narrow and not the window
- [ ] Every one of the nine typography groups changes the text it names and
      nothing else
- [ ] Every spacing slider moves what it names, and can differ on Tablet and
      Mobile
- [ ] The four pill styles all render, and the pill colours, gap, height, width
      and radius controls all take
- [ ] A pill lifts under the pointer and does not move for a visitor with
      "reduce motion" on
- [ ] A slab saved before 2.33.0 opens unchanged, and the new controls start
      empty rather than overriding what it already looked like
- [ ] **Watch the motes for a full crossing.** No two are the same size or
      speed, they rise and fall rather than tracking straight, and each drags a
      short trail
- [ ] They are already spread across the plate on load, not queued at the left
- [ ] Widen the browser: they still cross the whole plate, and still do at a
      phone width
- [ ] Colour, size and speed all take, and switching trails off removes them
- [ ] With "reduce motion" on, no mote moves at all
- [ ] **Heading fill set to gradient**: the letters carry it on both panels, the
      text can still be selected, and a screen reader still reads it
- [ ] **Set to a word at a time**: the words wait dimmed and fill as the panel
      is reached. With JavaScript blocked they are readable, not dimmed
- [ ] **Check the spacing sliders at a narrow window, not just a wide one.**
      Panel padding in particular: it is the one that was being overruled by
      the stylesheet below 1200px
- [ ] "Picture to footer" moves the pills in one panel and the spec rows in the
      other, by the same amount, and opens the space **above** the spec box
      rather than inside its border
- [ ] The diagram caption takes its own colour on the plate, and setting an
      italic in the panel italicises both halves of it
- [ ] Both widgets rise into view once and stay; a re-render in the editor does
      not leave anything stuck at zero opacity
- [ ] With JavaScript blocked, both widgets are fully visible

## Process Steps

`php tests/browser/steps-fixture.php > tests/browser/steps.html` builds the
fixture from the widget's own render(); `node tests/browser/steps-probe.js`
measures it. The fixture carries the widget three times over -- on grey, on a
dark section, and inside something that flattens 3D.

- [ ] **Scroll slowly past the list: each illustration turns as it goes.** It
      should read as walking past objects on a bench, not as things fading in.
      All four turning in step means they are sharing one number instead of
      each reading their own
- [ ] **The line between the numbers draws downwards** as each step arrives,
      and reaches the next disc. A short stub under every number means the rail
      is only as tall as its own disc again
- [ ] **Point at an illustration.** The chosen plane steps forward, the sensors
      rise one after another, the bars set back under the baseline, the top
      sheet lifts. Nothing should jump
- [ ] Move the pointer around inside one: it should lean towards you a little,
      and settle back to its scroll angle when you leave
- [ ] **The widget has no background.** Drop it on a dark section and set the
      faces to a translucent white, the edges to a light line, and clear the
      shadow. If it only works on light, something is hardcoded
- [ ] Exactly one element in each illustration carries the accent. Two is one
      too many
- [ ] **Set "which side" to alternating**: the words and the illustration swap
      on every other step, and **the numbers stay in one column** throughout. A
      zigzagging spine means the disc has moved with them
- [ ] Alternating, the illustrations on the left should look like the same
      objects seen from the other side, not like objects lit from the wrong
      one. If they do, the eye has not mirrored with them
- [ ] **Drag "illustration size"**: the drawing should get bigger without the
      perspective getting harder. If it starts to bulge, the camera is not
      moving back with it
- [ ] **Push size, width and height all the way up at once.** Nothing may be
      drawn outside its own box, no shadow may touch any step's words, and the
      page must not scroll sideways. This is the setting that broke it: the box
      was something the layout reserved and the drawing ignored
- [ ] The shadow stays under the drawing at every height, rather than sinking
      to the bottom of a tall box and leaving the object floating above it
- [ ] Set the space between steps to nought at a large size: the steps should
      butt up against each other and still not bleed into one another
- [ ] **Narrow the window: the illustration drops under the words**, in line
      with the text rather than with the numbers. Alternating, every step
      should keep the words above the drawing -- there is one column to stack
      into and nothing left to alternate
- [ ] Set "how close the eye is" to 300: the perspective should get harder,
      not break. Under about 400 it stops reading as depth
- [ ] **Block the script.** The steps, the numbers, the line and the
      illustrations must all still be there, holding the angle they were drawn
      at. Only the scroll camera goes
- [ ] Turn on "reduce motion" in the OS: the camera stops following the scroll
      and the illustrations hold still. Pointing at one still works, it just
      arrives at once
- [ ] **If custom CSS puts `overflow` or `filter` on `.estp__scene`**, the
      widget must notice and fall back to its flat arrangement rather than
      quietly becoming a pile of rectangles. An ancestor with `overflow:
      hidden` does *not* do this and must not trigger the fallback

## Data Table

`php tests/browser/table-fixture.php > tests/browser/table.html` builds the
fixture from the widget's own render(), so one command proves the PHP and the
stylesheet together. It carries a hostile theme block, which is what caught the
header fill being covered by a theme's own `td { background }`.

- [ ] **The header row is filled and its text is legible on it.** Grey cells
      with white text on them means a theme is setting `td { background }` and
      winning
- [ ] Banding alternates, and the first row under the header is the plain one.
      Reversed, the header stops reading as a header
- [ ] **Clear the third heading: the table becomes two columns**, and any third
      cells already typed into the rows disappear with it. Elementor keeps
      those values, so seeing them come back as a third column means the row is
      no longer being fitted to the table
- [ ] Fill the third heading back in and the cells return, still typed
- [ ] **Narrow the window past 768px: each row folds into a block** with its
      heading above every value. It must not scroll sideways and must not
      squeeze into columns of single words
- [ ] Folded, the last column takes the ordinary text colour back. Left dimmed
      it reads as disabled rather than as a note
- [ ] Switch the header row off: the headings are still drawn beside the values
      on a phone, because they are the only thing labelling them
- [ ] **Select the table and copy it into a spreadsheet.** It should arrive as
      columns. One run of text means the markup is no longer a real table
- [ ] Set a corner radius: the header fill is clipped to it rather than showing
      square corners inside a rounded box
- [ ] Type a column width like `2fr 1fr 1fr` and it takes; clear it and the
      default returns. It is responsive, so check Tablet separately
- [ ] Leave a row completely empty: it should draw nothing, not a blank stripe

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
- [ ] **It also comes away from the top and shrinks to fit its own contents**,
      as one movement -- the bar should hug the logo, the menu and the button
      with nothing to spare
- [ ] **The change eases the whole way.** Watch the first moment especially: a
      jump followed by a smooth glide means the width is snapping to an
      intrinsic size before it animates
- [ ] **Scrolling down takes the header off the screen entirely**, and turning
      round brings it straight back, already folded. It should slide, not blink
- [ ] Open a panel, then scroll: the header must not slide away underneath it
- [ ] **Watch the bar as it leaves.** It should go at full width. Seeing it
      shrink on its way out means the fold is no longer waiting until it is
      off screen
- [ ] Hover a dropdown on the folded bar: the bar must not resize under the
      panel. The two moving at once is what judders
- [ ] **The panel sits off the bar with a gap and keeps its corners rounded.**
      Move the pointer slowly from the menu item down into the panel -- it must
      not close on the way across the gap
- [ ] "Gap below the bar", "Corner radius" and "Inset from the sides" all work,
      and can be set differently on Tablet and Mobile
- [ ] The phone drawer is spaced and rounded the same way, and is completely
      flat when shut
- [ ] **In the drawer, tapping the word goes to its page** -- tapping
      "Services" must land on /services. Tapping it and getting a list instead
      is the old behaviour, where the page could not be reached from the menu
      at all
- [ ] **The chevron beside it opens the list**, and tapping it again closes it.
      One chevron per row, at the right-hand edge, big enough for a thumb
- [ ] **Close it and watch the chevron: it must turn back the right way up.**
      A tap leaves `:hover` behind on a touch screen, and a caret still upside
      down over a closed panel is that, not the panel failing to close
- [ ] The label must lose its green with it, and the rolling label must settle
      back on the word rather than staying on its understudy
- [ ] An item with no sub-links gets no chevron at all

### Panel links

- [ ] **Existing headers are untouched.** Open a saved header: every panel still
      lists what it listed before, in the same order, with the same descriptions
- [ ] "Links come from" is set to **A typed list** on every existing row, and
      the textarea is where it was
- [ ] Menu items that open a panel now show a caret beside their name in the
      repeater list, and the ones that do not, do not
- [ ] A line of `Label | /path | small print` still renders all three parts
- [ ] **A page slug links to the page.** Type `Waste heat | waste-heat-recovery`
      and the rendered link must be that page's real permalink. Then move the
      page under a different parent and reload the front end — the link must
      follow it without touching the header
- [ ] `Some label | #42` links to post 42. A slug or ID that matches nothing
      falls back to `/the-slug` rather than vanishing
- [ ] **A line with no label names itself**: `/services/feasibility` alone
      renders "Feasibility", and a slug that resolves takes the page's own title
- [ ] A line with just a word — `Services` — is still a heading with no link
- [ ] Paste a list of URLs, one per line, with nothing else: every one becomes a
      labelled link
- [ ] `Heat \| power | /chp` renders "Heat | power" as one label
- [ ] **A link to another site opens in a new tab** with `rel="noopener"`, and a
      link to your own site does not — check a `https://` link to your own
      domain, and the same with `www.` in front of it
- [ ] `^` on the end of any link opens it in a new tab
- [ ] Switch "Links come from" to **A WordPress menu**: the picker lists every
      menu, and every menu item that has children, and nothing else
- [ ] Picking `Menu → what is under "Services"` lists exactly those children, in
      the menu's order, with each item's Description as its small print
- [ ] Picking the menu itself lists its top-level items and nothing nested
- [ ] A menu item set to open in a new tab does so here
- [ ] Reorder the children in Appearance > Menus and reload the page: the panel
      follows, with no Elementor save
- [ ] **A panel with nothing in it is not a panel**: set a row to a menu and
      leave the menu unpicked — no caret, and nothing opens
- [ ] The editor still loads at a normal speed on a site with several long
      menus, and the front end makes no extra queries counting them (the picker
      is built in the admin only)
- [ ] "Goes back to full width" set to **Only at the top** keeps it compact
      while you scroll up mid-page, and restores it when you reach the top.
      Set to **Whenever you scroll up** it comes back on any upward scroll
- [ ] **The menu text takes the colour you picked**, and the hover colour too.
      Grey or black text that ignores the setting means the theme's own link
      colour is winning
- [ ] On a trackpad, scroll gently up and down around the changeover: it must
      not flicker between the two. "Reacts after scrolling" is the setting
- [ ] Below 1025px the compact bar stays full width. A little tab floating in
      the middle of a phone screen means the fit-to-contents rule has leaked
      past the breakpoint
- [ ] **Open a panel while it is floating: the bar stays exactly where it is.**
      It used to snap back to full width, and the panel fading in while the bar
      resized under it is what juddered
- [ ] **Open the same panel at the very top of the page: the bar must stay
      transparent.** The panel gets a fill; the bar does not, and the hero
      should still be visible straight through it
- [ ] **And the blur behind it runs the full height of the window**, bar
      included. A crisp strip of hero across the top with the rest pushed back
      is the scrim starting below a bar that has nothing to protect
- [ ] Scroll down until the header leaves, scroll back up, then open a panel:
      **the frosted bar must look exactly as it did the first time.** Muddier
      or darker means the scrim has crept up underneath it and the frost is
      blurring its own blur
- [ ] **And the panel opens at the same width in both places.** Open one at the
      top, scroll down, open it again — it should be the same card in the same
      position, not a sheet across the window and then a card
- [ ] The bar carries the button **once**. Two side by side means the drawer's
      copy is not being hidden
- [ ] **Menu labels shuffle their own letters on hover** and settle back on the
      word. Every frame should be an anagram of the label -- random symbols
      mean the wrong effect is running
- [ ] Labels do not jitter their neighbours while shuffling. If they do, the
      width is not being pinned
- [ ] The other three settings work too: whole-word roll, letter by letter, and
      plain colour
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
- [ ] **Hover an item whose panel has a picture in it: it opens wide**, not
      just under the word. A panel the width of its own label means the item
      has picked up a `position` it should not have
- [ ] **An item whose panel is nothing but links opens as a dropdown instead**,
      sized to its links and hanging under the word. One of those stretched
      across the header is the thing this replaced
- [ ] Put a links-only panel on the **last** item in the menu: it should open
      to the left rather than running off the side of the bar
- [ ] **A panel is two sides: the list, and the picture with the eyebrow and
      blurb under it.** The picture should finish level with the bottom of the
      list -- a band of white under it means it has kept a shape of its own
- [ ] **Hover the picture: nothing should happen to it.** It eases in a little
      while the panel is open and holds there. A fade, a grey, or a zoom that
      follows the pointer is the theme's own `img:hover` getting through
- [ ] Clear a panel's picture and its blurb: the links should take the whole
      width rather than leaving half a panel empty
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
- [ ] **Sliders take the unit you pick.** Switch a padding to em or a width to
      %, and the bar should actually change. Nothing happening means the
      selector is still pinned to px
- [ ] Each unit has a sensible range -- switching to em should not leave you
      dragging a slider that goes to 160
- [ ] Settings saved before this still read the same: 40px stays 40px
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
