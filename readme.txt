=== Eruda Toolkit ===
Contributors: erudarobiu
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.7.0
License: GPLv2 or later

A small toolkit of site-building modules: a numbered accordion and an impact
grid widget for Elementor, and a page duplicator.

== Description ==

Three modules, each switchable from Settings > Eruda Toolkit. Modules are on by
default.

= Numbered Accordion =

Adds a single widget, "Numbered Accordion", to the Elementor panel under the
General category.

Each row shows a title on the left and a sequence number on the right. Opening a
row reveals an optional eyebrow label and a description, and draws an accent rule
along the bottom of the row.

* Repeater-driven items: title, optional eyebrow, optional description
* Automatic numbering (01, 02 ... or 1, 2 ... or hidden), with a configurable
  starting value
* Single-open or multi-open behaviour, and an option to keep one item always open
* Full Elementor style controls for typography, colours, spacing and motion
* Keyboard accessible: arrow keys, Home and End move between headers
* Correct ARIA wiring (aria-expanded, aria-controls, role="region", inert panels)
* Honours prefers-reduced-motion
* Assets load only on pages where the widget is actually used

Requires Elementor 3.5 or greater. This is a classic (V3) Elementor widget. It
renders correctly on Elementor V4 Atomic pages and can be placed alongside
atomic elements.

= Impact Grid =

Adds a single widget, "Impact Grid", to the Elementor panel under the General
category. A grid of numbered cards for presenting results: energy saved, tonnes
avoided, whatever the figures are.

Each card is one of two kinds. A figure card carries a large number, a unit, a
rule, an optional second figure and a caption. A checklist card carries ticked
points, each of which can have plain bullets nested under it.

* Repeater-driven cards, each with its own icon, type and accent
* Two accent colours set once, with every card choosing a side
* Automatic numbering (1, 2 ... or 01, 02 ... or hidden), with a configurable
  starting value
* Figures count up from zero as their card scrolls into view, in the number
  format they were typed in
* Cards rise and fade in a stagger, ticks draw themselves, rules sweep open
* Full Elementor style controls for typography, colours, spacing and motion
* Honours prefers-reduced-motion: cards appear in place and figures print
* Assets load only on pages where the widget is actually used

Requires Elementor 3.5 or greater.

= Duplicate Pages =

Adds a "Duplicate" link to the Pages and Posts list tables, and a matching bulk
action.

* The copy is always created as a draft, so nothing is published by surprise
* Copies content, excerpt, all meta, all taxonomy terms, the featured image,
  the page template, the parent and the menu order
* Elementor layouts are copied correctly, including their escaping
* The copy belongs to whoever duplicated it
* Comments are not copied
* Requires permission both to edit the original and to create posts of that type

This module needs nothing but WordPress. It works on sites with no Elementor
installed.

== Safety ==

Each module checks its own requirements and reports them on the settings screen.
The accordion module simply does not load when Elementor is missing; the
duplicator is unaffected. None of these paths can produce a front-end fatal
error, and every duplicator hook is admin-side only.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/ or install the zip through
   Plugins > Add New > Upload Plugin.
2. Activate it.
3. Optionally visit Settings > Eruda Toolkit to switch modules off.

== Upgrade Notice ==

= 2.2.0 =
Impact Grid cards can now stack on scroll, and the icon width control works
above the width of the uploaded file.

= 2.1.0 =
From 2.0.0, this adds an Impact Grid widget and changes nothing else. From
1.0.x it is a large update: the plugin becomes Eruda Toolkit and gains a
settings screen, a page duplicator and the Impact Grid widget. Either way it is
an in-place update - the plugin folder is unchanged, existing accordions are
untouched, and no page needs re-saving.

= 2.0.0 =
The plugin is now called Eruda Toolkit and has gained a page duplicator. This is
an in-place update: the plugin folder is unchanged, your accordions are
untouched, and nothing needs re-saving.

== Changelog ==

= 2.7.0 =
* The Scroll Story highlight now follows your scroll instead of playing on a
  timer. Stop mid-sentence and it stops mid-sentence; scroll back up and the
  letters go out again in reverse. How fast you scroll is how fast it sweeps,
  so the old "letter stagger" control has gone.
* New "Highlight starts at" and "finishes at" controls set where on screen the
  sweep runs.
* The media panel is now centred on the screen whatever its height, and stays
  centred when you change the panel height, the column gap or the spacing
  between items. "Pin below" is now a vertical nudge on top of that.
* The notch no longer runs into the panel's corners. It travels a share of the
  panel height -- 40% by default, and a control -- centred in what is left
  over, so it always stops short of both ends.
* The notch shape is closer to the reference: its proportions were re-measured
  off the live clip path.
* New panel transitions: Wipe, Zoom, Push and Dissolve, each mirrored when you
  scroll back up rather than repeating the downward version, with easing and
  length controls and an optional slow drift.
* Fix: the panel no longer flashes its own background part-way through a
  change. Pictures now stack rather than cross-fading both ways at once.
* Media always fills the panel whatever shape it is, with a new "Focal point"
  control for which part of a cropped picture survives.
* Items can now use a video instead of an image. It plays muted and looped
  only while its item is the one being read.
* Fix: an item with no image used to shift every item below it onto the wrong
  picture. An item without media now simply keeps showing the one above it.
* Item numbers are now eyebrow pills -- a dot and a short uppercase line --
  with controls for the text, dot, border, background, radius and padding.
  Numbers are still available from the new "Item label" setting.
* Heading and description are now styled entirely separately, each with its
  own typography and its own waiting, flash and read colours.
* New border width and colour for the panel. Under a notch the border follows
  the notch instead of being clipped away.

= 2.6.2 =
* The Scroll Story notch is now the right shape: it cuts into the panel's left
  edge with rounded corners and a diagonal run, rather than stepping outwards
  with square ones. The proportions are taken from the reference's own clip
  path, so any notch depth keeps the same shape.
* The panel is no longer full height. It defaults to 88% of the screen and
  pins 40px below the top, which leaves the breathing room the reference has.
* The notch path is rebuilt when the panel is resized.

= 2.6.1 =
* Fix: the Scroll Story widget never appeared in the Elementor panel. The
  module looked for a widget class under the wrong name, and that check fails
  silently by design, so 2.6.0 shipped the widget without a way to add it.
* All of this plugin's widgets now live in their own "Eruda Toolkit" section of
  the widget panel instead of being mixed into General.
* New "Automatic updates" setting, on by default: Eruda Toolkit installs its
  own updates. Turn it off in Settings > Eruda Toolkit to go back to a
  deliberate click.
* Tests now assert that every widget class a module registers actually exists,
  which is what would have caught this release's bug and 2.3.0's.

= 2.6.0 =
* New "Scroll Story" widget: numbered text items on the left, a pinned panel on
  the right that cross-fades to whichever item you are reading.
* Each letter sweeps from a waiting colour through a flash colour to its read
  colour, staggered along the line. All three colours and both timings are
  controls.
* Optional notched panel edge that travels as you scroll the section.
* Controls for the column split, gap, panel height, how far apart the items
  are, and how far below a sticky header the panel pins.
* Stacks to one column below 1024px, where nothing is pinned.

= 2.5.0 =
* New "Scroll Highlight" preset: the words sit dim and light up as the
  paragraph crosses the middle of the screen, following the scrollbar in both
  directions rather than playing once.
* New "Dimmed to" control sets how faint the unlit words are.
* Scroll Highlight animates opacity rather than a hard-coded colour, so it
  works against whatever text colour the widget already has.
* Because the scrollbar is its timeline, it hides the Duration, Stagger, Delay,
  Easing and trigger controls, which would otherwise do nothing.

= 2.4.2 =
* "Words Build" now matches the standard GSAP word reveal it is named after:
  words 60ms apart, 500ms each, lifting 15px, on power2.out. The previous
  version made each word land before the next began, which is a different
  animation from the one people mean by this name.
* New "Natural" easing (power2.out / easeOutCubic), now the default.
* Default duration is 500ms rather than 800ms, which is the convention for
  text. Widgets already saved keep the timing they were given.

= 2.4.1 =
* New "Words Build" preset: the sentence assembles a word at a time, each word
  landing before the next begins. Unlike the existing staggered presets, which
  have every word in flight at once, this one is deliberately sequential.
  Duration and Stagger still apply; the preset scales them.

= 2.4.0 =
* New Smooth Scrolling module: eases the whole page's scrolling, the way a
  showcase site does. On by default; switch it off in Settings > Eruda Toolkit.
* Works on any theme, with or without Elementor.
* Off inside the Elementor editor, and off for visitors whose system asks for
  reduced motion.
* Native momentum is left alone on touch devices, where it is already better
  than anything a script can impose.
* In-page anchor links glide rather than jump.
* Tune the feel with the eruda_smooth_scroll_options filter.

= 2.3.4 =
* New "Replay animation" button in the editor, under the animation controls.
  Plays the animation again in the preview without reloading, so you can tune
  duration and stagger and see each change. Editor only; it adds nothing to the
  live page.

= 2.3.3 =
* Fix: Fade In and Blur In did not animate. The transition was declared in the
  same rule as the start state, so applying the start state was itself
  animated: the text transitioned into being hidden instead of snapping there,
  then reversed a frame later. Above the fold nothing appeared to happen; below
  the fold the text visibly faded out before it faded in. The start state is
  now applied with transitions off and switched on afterwards.
* Added a browser test that measures whether an animation actually animates.
  This class of bug cannot be caught by reading the code.

= 2.3.2 =
* New "Fade In" preset: the plain one, the whole block at once, with a
  direction control (from below, above, left, right, or no movement) and a
  travel distance.
* "Words Fade" takes the same direction and travel controls.
* Fix: Blur In could finish before it started. An element already on screen
  could have its start and end states applied inside one style recalculation,
  so no transition ran and the text simply appeared. The start state is now
  committed first. This affected the presets that split nothing hardest, Blur
  In among them.
* Fix: every CSS custom property now carries a fallback. One that failed to
  resolve invalidated the whole declaration, which dropped transition-duration
  to zero and looked exactly like a broken animation.

= 2.3.1 =
* Fix: the Eruda Text Animation controls did not appear in Elementor at all.
  They were registered on a hook that fires once on Elementor's shared common
  stack rather than on each widget, so the widget check never matched and the
  section was never added. The controls now live on the Style tab of the
  Heading and Text Editor widgets.

= 2.3.0 =
* New Text Animations module: an "Eruda Text Animation" section on the Advanced
  tab of the Heading and Text Editor widgets.
* Eight presets: words up, words fade, characters cascade, characters flip,
  lines reveal, blur in, scale pop and slide in.
* Duration, stagger, delay, easing, how far into view it starts, and whether it
  replays on every entry.
* Animated text stays readable with JavaScript disabled, and does not animate at
  all when the operating system asks for reduced motion.
* Pages with no animated widget load neither the stylesheet nor the script.

= 2.2.0 =
* New: Impact Grid cards can stack on scroll. Style > Layout > Card layout,
  set per breakpoint: each card pins while the next slides over it, leaving a
  ledge of the one beneath. Stacked on mobile by default, grid elsewhere, with
  sliders for the ledge and for clearing a sticky site header. It is pure CSS,
  so there is no scroll handler to stutter on a slow phone.
* Fix: the Impact Grid's icon width control did nothing above a certain size.
  It set a maximum width, which can only shrink an image below its natural
  size, never enlarge it - so the slider stopped responding at the width of the
  file WordPress had served. It now sets the width outright, and the control is
  relabelled "Width" to match. Icons are also requested at full size so a
  scaled-down file cannot cap the control, with an explicit sizes hint so the
  browser still downloads an appropriately small one.

= 2.1.0 =
* New: "Impact Grid" module. Adds an Impact Grid widget to Elementor: a grid of
  numbered figure and checklist cards that reveal in a stagger as they scroll
  into view, with figures counting up from zero.
* The module is on by default on upgrade, and adds nothing to a page until the
  widget is placed. Existing accordions and duplicator behaviour are untouched.

= 2.0.0 =
* New: the plugin is now Eruda Toolkit, a modular plugin. The numbered accordion
  becomes one module of it. This is a display-only rename: the plugin directory
  and the update channel are unchanged, existing accordions keep working, and
  nothing needs to be re-saved.
* New: Settings > Eruda Toolkit, with a switch per module.
* New: "Duplicate Pages" module. Adds a Duplicate row action and bulk action to
  Pages and Posts. Copies content, meta, taxonomies and the featured image into
  a new draft, handling Elementor layouts correctly.
* Change: Elementor is now a requirement of the accordion module rather than of
  the whole plugin, so the duplicator runs on sites without Elementor.
* Fix: with "allow multiple open" on and "allow closing every item" off, opening
  more than one row left every row stuck open. Only the last open row is now
  held open.
* Fix: a collapsed row's eyebrow label stayed in the accessibility tree, so a
  screen reader read it as part of the closed row's button label.
* Fix: an item with a blank title left a gap in the numbering (01, 03, 04), and
  could stop "keep one item always open" opening anything.
* Add: a translation template in languages/.

= 1.0.3 =
* Fix: on themes that style <button> elements, the accordion trigger picked up
  the theme's button fill (background colour, gradient, border or shadow), most
  visibly on the focused/open row. The trigger reset is now high specificity and
  covers the hover, focus, active and visited states.

= 1.0.2 =
* Correct the plugin author name.

= 1.0.1 =
* Fix: the plugin refused to load with "could not find the Elementor widget API".
  Elementor's autoloader cannot resolve \Elementor\Widget_Base (it derives
  ELEMENTOR_PATH/widget-base.php, but the file lives in includes/base/), so the
  class does not exist until the widgets manager requires it just before the
  elementor/widgets/register hook. The check now happens in the register
  callback instead of at plugins_loaded.

= 1.0.0 =
* Initial release.
