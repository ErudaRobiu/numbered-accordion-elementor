=== Eruda Toolkit ===
Contributors: erudarobiu
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.52.1
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

= Flow Schematic =

Adds a "Flow Schematic" widget: a process diagram built out of stages and the
connectors between them.

* Up to five stages, each with a name, an eyebrow, a detail line, an optional
  pin number, an optional link and one of four treatments
* A label on each connector, sitting on the line or floating above it
* An optional source block on the left and an optional target group on the right
* A return path under the stages it names, with inline markers for a pump and a
  vessel
* A monitoring bar over the stages it reads, dropping onto each one
* A boundary line at any position across the diagram, with its own label and a
  caption either side
* Colours, line weight, corner radius, node height and the run between stages
  all set from the panel
* Stacks into a vertical flow when the diagram's own box gets narrow
* Or set a finished drawing instead — an SVG or an image — and it pans sideways
  with a slider once it is wider than the space it has
* An SVG is printed into the page, so it letters in the site's own typeface and
  its labels stay selectable; it is stripped back to drawing instructions first

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

= 2.52.1 =
* **Fixed: an industry with no picture set threw every picture after it onto
  the wrong industry**, and once the position ran past the end of the shortened
  list nothing was lit at all and the frame simply stopped changing. Every
  industry now has a slot whether or not a picture is set; one without shows
  the empty frame.

= 2.52.0 =
* **Industry Showcase: tablet and phone now follow the reference rather than
  turning into a list.** It stays one industry at a time, driven by scrolling,
  at every width — only the arrangement changes. The picture takes the upper
  part of the screen, the words sit beneath it, the position ticks move to the
  right edge and the names step aside, since scrolling is how the list is
  walked there.
* Fixed: the pinned panel was sized in vh and then padded, which on a phone put
  its own last line under the browser's toolbar where nothing could scroll it
  into view. It is sized in svh now, and the widget sets its own box-sizing
  rather than assuming the theme has.
* With no script the stack takes over, so every industry can still be read.

= 2.51.0 =
* **Fixed: the industry names were picking up the theme's button styling** — a
  fill, a border, a radius, uppercase letters. They are buttons, every theme
  styles buttons, and a single class loses to most of what a theme writes. The
  reset now carries enough weight to win and puts back every property a button
  might have been given, not only the ones seen so far.
* Keyboard focus is given back deliberately, as a ring in the accent colour
  shown for keyboard focus only, rather than left to whatever the theme did.
* The list itself no longer inherits markers or an indent.
* The browser fixture now ships a deliberately loud theme of its own, so the
  widget is measured against the thing it has to survive rather than in a clean
  room.

= 2.50.0 =
* **Industry Showcase: the background can be set on the widget**, under Style →
  Background, and it is pinned along with the panel. A background set on the
  section scrolls with the whole showcase, so a pattern on it slides past the
  panel standing still in front of it; set on the widget it holds still too.
* The pictures are pinned to their frame in a way no theme or framework reset
  can undo. Elementor's own `.elementor img { height: auto }` outranks a single
  class, so anything sizing a picture here now carries enough weight to beat
  it.
* Fixed: the section label and the counter were being dropped into the grid as
  real items, which wrapped the three columns onto a second row and pushed the
  panel out of its own pinned box on a short window.
* The width and spacing controls now ship unset, so the responsive defaults
  hold until something is chosen. An Elementor default is written out at every
  width and would have flattened them to one number.

= 2.49.0 =
* **Industry Showcase: the dial is gone**, and the panel now spans whatever
  section it is dropped into rather than sitting in a column of its own.
* **New controls.** Picture width and shape, text width, the gap between the
  columns, the side padding, the gap between the names and the gap inside the
  text — all of them responsive. Type for the names, heading, description,
  link, section label and counter, left unset so the theme carries through
  until something is chosen.
* Set the side padding to zero to let the section's own padding do the work.
* The picture fills its frame and is cropped to do it, rather than being fitted
  inside it with bars down the sides: the frame's proportions are a decision,
  the photograph's are an accident.

= 2.48.0 =
* **Industry Showcase: the turning dial is in.** Three cut rings behind the
  list that turn as the section is scrolled — the one part of the showcase that
  answers to scroll position continuously rather than in steps, which is what
  tells a visitor the section is responding to them between one industry and
  the next. It bleeds off the left edge on purpose; a whole circle in the
  margin reads as a logo rather than as background.
* How far it turns, and its colour, are controls. It can be switched off.

= 2.47.0 =
* **New: Industry Showcase.** A list of industries walked by scrolling. The
  section is taller than the screen and the panel inside it is pinned, so
  scrolling past steps through the list rather than moving it: names on the
  left lighting up in turn, one picture in the middle that settles in as it
  changes, and the heading and description on the right. Clicking a name lands
  on that industry.
* The picture crossfades over six tenths of a second while easing down from a
  six percent overscale over twice that, which is what stops the change reading
  as a blink.
* Built for a dark section, with every colour exposed as a control. It draws no
  background of its own.
* On a phone it stops pinning entirely and reads as a plain stack, because a
  panel driven by scroll position fights the browser on touch, where scrolling
  is also how the address bar is dismissed.

= 2.46.0 =
* **New: the preloader logo's width is now a setting, given as a percentage of
  the loading bar.** The two used to be sized on separate scales, which meant
  one of them was always slightly wrong against the other. The bar is the ruler
  now: 70 percent is the default and puts a logo about 224px across above a
  320px bar.
* The logo is sized from a width rather than a height, so it centres on the bar
  and keeps the file's own proportions. A square mark at a wide setting is
  capped so it cannot fill the screen, and nothing runs off a phone.

= 2.45.0 =
* **New: choose the preloader's logo.** A picker under Settings -> Eruda
  Toolkit, using the media library. It falls back to the site logo when nothing
  is chosen, so a site that only needs one image never picks it twice — the
  reason to set one is a curtain in a dark brand colour, which usually wants a
  lighter version of the mark than the header does.
* The chosen image is never lazily loaded: it is the only thing on screen, and
  a lazy one would not start fetching until after the curtain it sits on had
  been drawn.

= 2.44.2 =
* **Fixed: the logo appeared without animating.** The animation was keyed to
  the curtain going up, but a logo set in WordPress is a media library file and
  therefore a request that has usually not arrived by then — so it played out
  on an empty box and the image then appeared on its own, with nothing to see.
  It now waits for the image and animates when there is something to animate,
  and the curtain holds open long enough for it to finish rather than cutting
  it off half-drawn.
* The logo fades and wipes in together, from the left.

= 2.44.1 =
* **Fixed: the curtain swept a second time, seconds after the preloader had
  finished.** Two timers race to open the preloader — the frame loop once the
  page is ready, and a fallback for when the frame loop is not running at all —
  and the fallback was never cancelled by the one that won. It fired a little
  over five seconds later and drew a fresh curtain across a page the visitor was
  already reading. Whichever gets there first now shuts the other down, and a
  page can only be revealed once.
* The logo fades in rather than wiping in, and starts as soon as the curtain is
  there.

= 2.44.0 =
* **New: Page Transitions.** A curtain of coloured columns sweeps upward to
  cover the page, the next page loads underneath it, and the same columns carry
  on upward to reveal it — one movement rather than two. On the first page of a
  visit the curtain is already closed and carries the site's logo and a
  progress bar instead, then opens when the page is genuinely ready.
* The progress bar follows the page rather than a stopwatch: it eases toward
  ninety percent and only completes once the fonts and the images above the
  fold have arrived, with a minimum so it cannot flash and a maximum so it can
  never hold a visitor on a slow connection.
* Everything about it is set under **Settings → Eruda Toolkit** — colour,
  column count, speed, stagger, and whether the preloader runs at all. The
  colour starts from the site's Elementor primary, so on most sites there is
  nothing to set.
* Nothing runs for a visitor who has asked for reduced motion: no curtain, no
  preloader, and no delay before the page they asked for. It is also off in the
  Elementor editor.
* The first module with settings of its own. Modules now declare what they
  expose and the settings screen renders it, so the next one needs no screen
  work at all.

= 2.43.1 =
* **Fixed: at large illustration sizes the shadow landed on the next step's
  heading.** The shadow sat inside the drawing and was scaled along with it —
  blur included — so at the top of the size range a soft contact shadow became
  a smear hanging well below its own box. It is measured against the box now
  rather than scaled with the drawing, and it stays under the object that casts
  it at any height instead of sinking to the floor of a tall box.
* **Fixed: the illustrations were not kept inside their own box.** The box was
  something the layout reserved and the drawing ignored — at the largest
  setting it ran over 260px above and below — so the only thing that stopped
  one step reaching the next was adding space nobody wanted. It is clipped now.
* **Fixed: a wide illustration could take the whole page sideways.** Its column
  could not give way when there was no room for it; now it can.

= 2.43.0 =
* **Process Steps: the illustrations are bigger**, and there is a size control
  for them. It scales the drawing rather than the box it sits in, and moves the
  eye back as the drawing grows — so it gets larger without the perspective
  hardening, which is what scaling the object alone would have done.
* **New: the words and the illustration can alternate sides.** Set "which side
  the illustration sits on" to alternating and every other step swaps them.
  The numbers stay in their own column either way: a sequence that zigzags
  stops reading as a sequence.
* **The illustrations turn towards the words they belong to.** Moved to the
  left of the page, one keeps the camera angle it had on the right and stops
  looking like the same object seen from the other side. Both the turn and the
  eye mirror with the layout.
* On a phone alternating has nothing left to alternate, so every step keeps the
  words above the drawing.

= 2.42.0 =
* **New: a "Process Steps" widget.** A numbered process read downwards, with
  the words on the left and a small object standing in space on the right.
  Switchable at Settings > Eruda Toolkit like every other module.
* **Scrolling moves the camera rather than fading anything in.** Each step
  carries one number — nought as it arrives at the bottom of the window, one as
  it leaves the top — and every illustration reads that same number and does
  something different with it. Going down the list is walking past a row of
  objects on a bench.
* **Five illustrations, all built from the same four things**: planes, bars,
  sensors and sheets. Choosing between options, measuring a surface, a baseline
  across past readings, a stack with the report on top, and something that
  keeps running. Four bespoke drawings would read as four pieces of clipart in
  a row; four arrangements of one vocabulary read as one drawing broken into
  steps.
* **Pointing at one opens it.** The chosen plane steps forward and the options
  it beat set back, the sensors rise one after another like a scan, the bars
  fall away under the baseline, the top sheet lifts clear of its workings.
* **It carries no background of its own**, so it sits on whatever colour the
  section behind it is. Every colour in it is a setting, including the shadow
  the drawings stand on — clear that on a dark section, where a dark shadow
  does nothing.
* **Without the script it is still a numbered list with illustrations**, drawn
  at the angle they were designed at. Only the scroll camera goes. Reduced
  motion gets the same treatment, and still answers the pointer.

= 2.41.0 =
* **New: a "Data Table" widget.** A reference table of two or three columns
  with a filled header row, banded rows under it, and a last column — the one
  holding a source, a status or a note — that can be set back a shade so it
  stops competing with the two carrying the argument. Switchable at
  Settings > Eruda Toolkit like every other module.
* **It is a real table.** The layout is a grid, but the markup underneath is a
  proper `<table>` with column headers, so a screen reader reads "Source of
  the value, Field sensors" as one fact, and selecting the table and pasting it
  into a spreadsheet gives you columns rather than one run of text.
* **Clearing the third heading makes it a two-column table**, and any third
  cells already typed are left out with it. Elementor never forgets a repeater
  field, so without that the orphaned cells would come back as a third column
  nobody asked for.
* **On a phone each row folds into a block**, with its heading above every
  value. A table that scrolls sideways hides the column that matters behind a
  gesture nobody makes, and one that merely narrows turns every cell into a
  column of single words.
* **It survives a theme that styles cells.** `td { background }` is in half the
  themes on the planet and it covered the header fill completely — white text
  on the theme's grey. The cells are held transparent at a specificity that
  beats a theme's own zebra striping.

= 2.40.0 =
* **Fixed: on a phone, a menu item with sub-links could not reach its own
  page.** Tapping "Services" opened the list under it and tapping again closed
  it, so /services was not reachable from the menu at all. The row is two
  things now: the word goes to the page, and a chevron beside it opens the
  list. Tapping the chevron again closes it.
* **Fixed: the chevrons did not turn back after closing.** A tap leaves
  `:hover` behind on a touch screen — there is no pointer to move away — so
  every hover state in the header latched. The panel really had closed; the
  caret was being held upside down by the stylesheet. The label's hover colour
  and the rolling label were stuck the same way, and are not any more.
* **Fixed: the page behind an open panel was not blurred behind the bar
  itself.** At the top of the page the bar has no fill, so starting the blur
  below it only drew a line — a crisp strip of hero across the top of the
  window with the rest of the picture pushed back underneath. It runs the full
  height there now. Frosted, it still starts below the bar, which is what stops
  the frost blurring its own blur.
* **Fixed: the frosted bar came back muddy after scrolling down and up.**
  Scrolling down hides the bar with a transform, and the measurement that
  followed read its position as the top of the window and wrote that as the
  blur's starting line. Nothing re-measured on the way back up, so for the rest
  of the session the bar sat on a blur that started above it.

= 2.39.0 =
* **Fixed: opening a menu at the top of the page turned the whole header
  white.** The bar is meant to be transparent over a hero, and it was —
  until you hovered a menu item, at which point the full width of the window
  went opaque behind a dropdown a fraction of its size. The panel gets a fill
  now; the bar keeps the picture behind it and only hardens once it has
  already frosted.
* **The dropdown opens at one width, wherever you are on the page.** It used
  to span the bar, and the bar is two widths — full bleed at the top, folded
  to its own contents once it frosts — so the same menu gave you a sheet
  across the window at the top and a card lower down. It is the folded width
  in both, centred, landing in the same place either way.
* **A panel is two sides rather than three.** The links, and the picture with
  whatever the panel says about itself underneath it. The middle column was a
  sentence given the largest share of the panel, and it left the picture
  stranded at the end with the panel's own height showing under it.
* **The picture fills its side.** It had a fixed shape regardless of the list
  beside it, and the difference between the two came out as white space. It
  takes its height from the list now, so the two sides finish level.
* **And it no longer flinches at the pointer.** Whatever a theme does to an
  `img` on hover — a fade, a filter, a lightbox cursor — is switched off
  inside a panel, where it reads as a fault rather than a flourish. What
  replaces it is a slow drift that belongs to the panel being open. Set "how
  far the picture drifts" to nought to hold it still.
* **A panel that is only links is a dropdown.** Four short rows stretched
  across the width of the header is the other half of what looked wrong. One
  with nothing but links now sizes to its links and hangs under the word that
  opened it, flipping to the other edge when it would otherwise run off the
  side of the bar.
* **Two panel controls have moved.** "Columns" described three of them and is
  now "How the two sides divide"; "Picture shape" is now "Shortest the picture
  may be", since the picture takes its height from the list. Both are new
  settings rather than renamed ones, so a header that had either tuned by hand
  takes the new default and needs setting again — nothing else is affected.

= 2.38.0 =
* **Fixed: an SVG diagram did not letter in the site's typeface.** An SVG
  referenced with <img> is loaded as a document of its own, and that isolation
  means it cannot reach the fonts the page loads — so a diagram drawn in the
  site's face silently fell back to Helvetica or Arial for every visitor.
  Measured rather than assumed: rendering the same file through <img> with the
  webfont on the page and without it produced byte-identical pixels, and both
  differed from the same file printed into the page.
* **So an SVG is printed into the page instead**, which also makes its labels
  selectable and searchable. Switch "print the SVG into the page" off to go
  back to using it as a picture.
* **The file is reduced to drawing instructions first.** Inlining gives up the
  isolation an <img> provides, so the markup is parsed and rebuilt from an
  allowlist before any of it reaches the document: script and foreignObject go
  with their subtrees, every on* handler goes, a reference may point inside the
  file and nowhere else, and a style that fetches something is dropped. Raster
  images are untouched and still load as pictures.

= 2.37.0 =
* **New: artwork mode on the Flow Schematic.** Set a diagram — an SVG, ideally
  — and it is drawn instead of the stages. Some drawings are not a row of
  stages, and forcing one through a grid is how a diagram ends up almost
  aligned.
* **A wide diagram pans rather than shrinks.** Below the width at which its
  labels stop being readable, the frame scrolls sideways with a slider under
  it. The frame scrolls natively, so the diagram is reachable by swipe,
  trackpad and keyboard whether the script runs or not; the slider is the
  affordance over that, because a phone shows no scrollbar and nothing else
  says there is more drawing to the right. It stays hidden until there is
  actually somewhere to pan, so it is never a control that does nothing.
* **The card is off by default now.** A diagram sits straight on the section
  behind it, with no border and nothing painted underneath, which is what a
  transparent SVG wants. Switch "draw the card around it" on to get the old
  framed look.
* Fixed: a vertical connector rendered as a 6px bar. The horizontal line
  carries a 6px floor so a short run stays visible, and turned upright that
  floor became the line's thickness.

= 2.36.0 =
* **New: Flow Schematic.** A process diagram as a widget — stages in a row,
  connectors between them, and the three bands a real system drawing needs: a
  return path under the middle, a monitoring bar over it, and a boundary line
  through it. A source block on the left and a target group on the right are
  optional, as is every band.
* **Drawn in CSS, not exported as a picture.** Every label stays real text, so
  it is selectable, translatable, searchable and readable by a screen reader,
  and every colour, weight and radius stays reachable from the panel. An
  exported SVG is none of those things, and it goes stale the first time a
  word changes.
* **It turns rather than shrinks.** Below the width the row needs, the diagram
  becomes a vertical flow: the connectors stand up, and the return path, the
  monitoring bar and the boundary stop being brackets and become labelled
  rules. It asks its own box, not the window, so a schematic dropped into a
  half-width column stacks on a full-size desktop.

= 2.35.2 =
* **Fixed: the Split Slab never stacked.** Below 1200px the two panels were
  meant to become one over the other with the bar lying between them. They did
  not: "Weight of the light panel" writes a rule carrying the element's own id,
  which outranked the stacking rule in the stylesheet, so the slab held two
  columns down to a phone -- and because the slab clips what overruns it, most
  of panel two was simply cut off. The control writes a custom property now,
  which is a value the layout reads rather than a layout of its own, so it
  still sets the split on the desktop and stops arguing with the phone.
* **The slab stacks on its own width, not only the window's.** A slab dropped
  into a half-width column is narrow at any screen size, and a media query
  cannot see that. It answers to both now.
* Nothing inside a panel can force the panel wider than its share of the slab,
  so a long spec value wraps instead of being clipped, and the two captions
  under the diagram sit on separate lines on a phone rather than being pushed
  into one.

= 2.35.1 =
* **Fixed: "Picture to footer" opened the space inside the spec box rather than
  above it.** The spec rows are a bordered box, so padding on them left an empty
  band inside the border instead of separating the box from the diagram. The
  space now sits between the two, which is where it was always meant to be, and
  at zero the box sits straight under the diagram.

= 2.35.0 =
* **Fixed: the panel padding control did nothing below 1200px.** The stylesheet
  re-set the padding inside a media query on the panel itself, and a custom
  property set on a descendant beats the same one set where the controls write,
  so the slider was being overruled at exactly the widths people check. The
  default is fluid now and there is nothing left to overrule it.
* **Fixed: "Picture to footer" only moved the pills.** The ink panel's spec rows
  had no gap of their own, so the diagram and the rows under it could not be
  separated. Both panels take the setting now.
* **Fixed: an italic caption was italic on one half only.** The stylesheet
  forced the right half back to normal, so a font style set in the panel applied
  to "hot contaminated exhaust in" and not to "cooled air out".
* **The diagram caption is now styleable.** It sits on the plate rather than on
  the panel, so it has its own colour -- and its own colour for the right half,
  and its own gap under the diagram. It used to inherit the panel's text colour
  and sink into the black.

= 2.34.0 =
* **The motes on the diagram are no longer five identical dots on a straight
  line.** Each one now has its own size, speed, brightness and wander, and
  carries a thin trail behind it, so they read as particles carried through the
  coils rather than as a conveyor belt. Depth comes from size and brightness
  together, so some pass close and some far off.
* They also start already in flight, spread across the crossing, so the plate is
  never empty and they never enter as a group.
* Their travel is measured against the plate, so a mote crosses it at any width.
  It used to be a fixed 560px, which stopped short or overshot on every screen
  but one.
* New **Motes** section: colour, size, speed and a switch for the trails.
* **A heading fill.** Panel headings can pour a gradient through the letters --
  with a from colour, a to colour and an angle -- or fill in a word at a time as
  the panel is reached, which is the motion from the original design. The text
  stays real text either way: selectable, searchable and readable aloud.
* The gradient starts from the heading's own colour unless told otherwise, so it
  works on the light panel and the ink one without being set up twice.

= 2.33.0 =
* **Fixed: the Split Slab did not fill its container.** The ink panel's grid
  track was `auto`, which sizes to its contents, so the slab stopped short of
  the width it was given. It is `1fr` now and the two panels plus the bar use
  the whole width.
* **The Split Slab is now properly styleable.** Nine typography groups, one per
  role -- step number, step label, panel headings, body, footer label, pills,
  spec label, spec value and the diagram caption -- so every piece of text can
  take its own family, size, weight, case and letter spacing.
* **A Spacing section**, all of it per device: panel padding, the gap between
  the step line and the heading, the heading and the body, the body and the
  picture, the picture and the footer, the footer label and the pills, plus the
  spec rows' height, padding and corner radius.
* Heading and body line length are controls now, in characters, pixels or a
  percentage, instead of being fixed at 16 and 46 characters.
* **A Picture section**: how large it is allowed to get, how it is aligned in
  the panel, and its corner radius.
* **The pills are no longer plain outlines.** Each carries a lit dot with a
  halo, sits on a soft gradient rather than flat white, and lifts under the
  pointer with the dot's halo opening out. Four styles -- Lit dot, Filled,
  Glass and Plain -- with their own colours, gap, height, width and radius.
* Nothing moved in the markup, so a slab already on a page keeps its layout and
  simply gains the controls.

= 2.32.0 =
* A new **Image Compare** widget: two pictures in one frame with a divider you
  drag across to swap between them.
* The divider runs side to side or up and down, and moves on drag and click or
  on hover -- hover springs back where it started when the pointer leaves, and
  a finger drags either way because a touch has no hover.
* The control is a real range input laid invisibly over the frame, so it takes
  focus, answers the arrow keys, Home and End, announces itself and its value,
  and a click anywhere jumps the divider there.
* The first picture sits in the flow and gives the frame its height, so nothing
  jumps about as it loads. Set a fixed height instead and both are cropped to
  fill it.
* Labels fade out of the way as the divider passes under them.

= 2.31.0 =
* Two new widgets, **Split Slab** and **Comparison Ledger**, under a module
  called Split Slab and Ledger.
* The slab is one rounded object divided by a coloured bar: a light panel with
  a step line, heading, body, picture and a row of pills, and an ink panel with
  the same plus a diagram on a plate and a list of specs. Both footers sit on
  the bottom edge whichever panel runs longer.
* The step numbers are a text control on each panel, not fixed to 01 and 02.
  Leaving one empty hides the number and closes the gap the label would
  otherwise sit after; both ship empty.
* The ink panel takes a full background group -- picture, position, size,
  repeat, or a gradient -- over its colour, with a **Darken behind the text**
  slider that lays a black veil between the picture and the words so the white
  text and the accent keep their contrast.
* The diagram is screened onto its plate, so artwork drawn on black loses the
  black and keeps only the drawing. Switch the plate off for artwork that
  brings a background of its own.
* Both widgets ship carrying the Lepido section's own wording, so dropping
  either one in gives the section as designed rather than placeholders to
  replace. Every field is still a control; nothing is fixed in the markup.
* The ledger is a row per point across three columns, with the third tinted and
  marked as the one that won. Both the tint and the header row can be switched
  off. On a phone the columns stack and the middle value takes the header's
  wording inline, so it still says what it is.

= 2.30.0 =
* The dropdown now sits off the bar as a card of its own, with three new
  per-device settings: **Gap below the bar**, **Corner radius** and **Inset
  from the sides**. Set them differently on Desktop, Tablet and Mobile.
* The gap is a transparent border rather than an offset, so the panel's box
  still touches the bar -- move the pointer down into it and nothing closes
  under your hand on the way.
* The phone drawer takes the same three settings, so it can be spaced and
  rounded to match.
* With a gap set, the bar keeps its own bottom corners instead of squaring
  them off against a panel that is no longer touching it.

= 2.29.0 =
* **You no longer watch the header fold.** It leaves at full width and folds
  once it is off the screen, with transitions frozen for the frame it takes, so
  the morph between the two shapes is never something you see. Letting it back
  out at the top still eases, because there the bar is in front of you and the
  expansion is the point.
* **The bar keeps its shape when a panel opens.** It used to snap back to full
  width, so the panel faded in at the same moment the bar resized underneath
  it and two animations fought over the same corner of the screen. The panel
  takes its width from the bar, so leaving the bar alone costs nothing.

= 2.28.0 =
* **The header now gets out of the way.** It slides up and off the screen
  while you read down the page, and slides back in -- already folded -- the
  moment you turn round, because coming back up is when you want the menu. The
  top of the page is always the full-width bar, on show.
* The slide is a transform, so it is composited and free: measured frame by
  frame it moves in 24 steps with a largest single frame of 12px.
* Opening a panel brings a hidden header back with it, and it never hides while
  one is open.
* "What it does as you scroll" has the two previous behaviours as options:
  stay put and fold on the way up, or stay put and fold on the way down.

= 2.27.0 =
* The menu colour now actually applies. Two attempts at winning on specificity
  were not enough -- plenty of themes write their link colour with !important,
  which nothing but !important beats. The colour declarations carry it now, on
  `var(--ehdr-ink)` rather than on a fixed colour, so the value is still
  entirely yours; it only stops the theme overriding it.
* **The folded bar now appears when you scroll back up**, and reading down the
  page gets the plain full-width header. That is the way round it was wanted:
  going back up is what you do when you want the menu. "When scrolling down"
  is still there as a setting.
* Fixed the button hanging out past the rounded edge between roughly 1025 and
  1150px. The folded bar is as wide as its contents plus the clearance it
  keeps, and it no longer folds into a space that cannot hold it.
* The drawer's breakpoint moves from 1024 to 1199. A six-item menu, a logo and
  a button do not fit across a 1025px window whatever the padding does, so
  that band gets the drawer, which is the right layout for it.
* Side padding and both gaps now give way on a narrow window instead of
  pushing the row wider than the screen. On a roomy one they are exactly the
  numbers you set.
* Fifteen settings are now per-device, so Tablet and Mobile can differ: the
  gap from the top, how wide it folds to, edge clearance, both paddings,
  corner radius, both border widths, logo heights, item spacing, and the
  button's radius and padding. The phone rules were rewritten to read the same
  properties, so a value set on Tablet or Mobile is no longer ignored.

= 2.26.0 =
* Fixed the menu text ignoring the colour you picked. Themes and Elementor kits
  ship `.elementor a { color: ... }`, which is two classes and beat the menu's
  own one-class rule, so whatever you chose was written and then overridden --
  the setting looked broken because the text stayed the theme's grey. The menu
  links, panel links, brand text, blurb and burger are all scoped to win now,
  hover colours included.
* Fixed the compacting animation jumping. It ran from a percentage width to
  `fit-content`, and an intrinsic keyword has nothing to interpolate towards --
  measured frame by frame, it snapped 296px on the first frame and eased the
  remaining 89. The width the row actually needs is measured up front, so the
  change runs length to length: 21 steps across 385px now, largest single frame
  64px. It re-measures once webfonts have loaded.
* The gap from the top is a transform rather than a margin -- composited
  instead of laid out.
* New setting, "Goes back to full width": **Only at the top of the page**, the
  new default, keeps the compact bar while you scroll up mid-page and restores
  the full one when you reach the top. **Whenever you scroll up** is the old
  behaviour.
* The change is a little longer and on a softer curve.

= 2.25.0 =
* Mega Header's sliders now take the unit you want. Lengths accept px, em and
  rem, side spacing and widths add % and vw, radii add %, durations accept ms
  or s, and letter spacing takes em, px or rem. Each unit has a range of its
  own, so switching to em does not leave you dragging a slider that goes to 160.
* Four settings stay in one unit on purpose -- "Stays full width for the first",
  "Reacts after scrolling", "Pause before a panel opens" and "Time per letter".
  Their values are read by the script as plain numbers, where "0.3" meaning
  seconds would be taken as 0.3 milliseconds.
* Existing settings are untouched: a value already saved as 40px is still 40px.

= 2.24.0 =
* Mega Header answers to scroll **direction** now, the way the reference does.
  Scrolling down compacts the bar; scrolling back up gives it back, wherever
  you are on the page. The old version flipped at a fixed distance down the
  page, which fought you on the way back up and felt abrupt.
* The compact bar is **sized to its own contents** rather than to a share of
  the window, so it hugs the logo, the menu and the button however wide those
  happen to be. A share of the window is still there as a setting.
* Menu labels now shuffle their own letters on hover and settle back on the
  word -- every frame an anagram of the label, locking in one character at a
  time from the left, which is the reference's own effect. Labels pin their
  width first so they cannot jitter their neighbours. The roll, the letter-by-
  letter roll and plain colour are the other three settings.
* Panel links shuffle too.
* Fixed the compact bar shrinking to a small tab on a phone, where the menu is
  in the drawer and there is nothing in the row to fit to.
* Smoother movement: the geometry eases on its own curve over 420ms rather than
  sharing the fill's, and the whole change reads as one movement.
* New settings: how far you have to scroll before it reacts, how much clearance
  the floating bar keeps from the screen edges, and the time per letter.

= 2.23.0 =
* Mega Header now follows the reference's geometry exactly, measured at three
  window widths: full bleed at the top of the page, 80% of the window and 16px
  down once you scroll, and **back to full bleed the moment a panel opens**, so
  the bar and the panel become one sheet. That last move was missing.
* Content width setting, defaulting to 1440px. The bar still runs the full
  width of the window -- the frost has to -- but what sits inside it is capped
  and centred.
* Fixed the button ignoring its colour. Elementor's background group control
  writes the colour and the gradient as two separate declarations, so a flat
  colour was being written underneath a gradient that was still on top of it.
  Fill is now Gradient or Solid, and Solid genuinely clears the gradient.
* Fixed the button's text taking the theme's link colour instead of white.
* The button is properly customisable: fill, gradient ends and angle, text
  colour, both hover colours, border width and colour, radius, padding,
  typography, letter spacing, and both shadows.
* A border on the bar: width all round, bottom-only width, and a corner radius
  for when it floats. The width is always reserved and only its colour arrives
  with the frost, so nothing shifts by a pixel.
* Menu items animate. The label rolls over itself on hover -- whole word, or
  letter by letter with a stagger -- with a scramble as a third option and
  plain colour as a fourth.
* "Preview a state" holds the header frosted, or holds a panel open, so it can
  be looked at in the editor where you cannot scroll or hover.
* Fixed the bar showing its button twice on a desktop.

= 2.22.0 =
* New widget: **Mega Header**. A navigation bar that sits over your hero
  completely invisibly -- no fill, no blur, no line -- and frosts as soon as the
  page moves. What it does on scroll is a setting: frost, a flat fill, or
  nothing at all.
* Full-width mega menu panels with a link column, a description and a picture,
  opened on hover with a pause first so sweeping across the bar does not flash
  every panel in turn. The page behind them is blurred and darkened while one is
  open.
* A phone gets a drawer instead, with the panels as accordions inside it and the
  button carried along full width. The picture and the description are dropped
  there, where they would only push the links off the screen.
* The navigation works with JavaScript disabled: the panels open on hover and on
  keyboard focus in CSS alone. The script adds the frost, the blur behind the
  panels, Escape and the screen-reader state.
* The button carries a gradient and both an inner and an outer shadow, each
  adjustable, and its letter spacing is in em -- a percentage is not a valid
  letter-spacing and browsers drop it, which is why tracking set that way never
  appears to do anything.

= 2.21.0 =
* Scroll Story: on a phone the notch now travels for the whole time its picture
  is on screen -- starting as the picture appears at the bottom and finishing
  as it leaves at the top -- instead of arriving at the far end halfway up and
  sitting there. It was scrubbed by the same reading band as the text, and that
  band is meant to finish early, because words that light as they leave are
  words nobody reads.
* Scroll Story: fixed stacked pictures being clipped away entirely on a phone
  until the reader scrolled. The notch's shape was only drawn on scroll, and a
  clip path with no shape yet hides everything inside it.
* If the phone notch's travel looks slight, the corner radius and "Notch length
  on a phone" are the two settings that eat it. A 44px radius on a 310px
  picture leaves about 31px of travel where a 16px one leaves 68px.

= 2.20.0 =
* Scroll Rail: the two ends of the row now fade instead of stopping at a hard
  edge, so a card leaves the frame rather than being cut in half at it. It
  fades whatever is behind the row, so it works over a picture or a gradient as
  well as over a flat colour. On by default, with a "How wide the fade is"
  setting per device.
* Scroll Story: on a phone the notch now runs along the bottom of each picture
  rather than the top. It belongs on the seam between a picture and the item
  under it; along the top it cut into the words it was meant to sit below.
* Scroll Story: a stacked picture keeps the border the pinned panel has. A
  notched one is outlined by a stroked copy of its own clip path, the way the
  desktop panel is, and an unnotched one takes an ordinary border. Before this
  the section quietly lost its outline on a phone.
* Scroll Story: fixed a phone notch landing on the wrong picture when an item
  had no media of its own.

= 2.19.0 =
* Everything that animates on scroll is about three times cheaper. Values that
  cannot change between frames -- the notch's measurements, where each card
  sits in the row -- are now read once instead of on every frame, and nothing
  is written unless it has actually changed. Measured with every widget on one
  page: the work per scroll went from 331ms to 111ms, forced layouts from 478
  to 37, and the page holds 60 frames a second where it had been dropping to
  30. On a phone that is the difference between six milliseconds a frame and
  two.
* Compositing layers are now asked for only while something is moving on them,
  rather than for every picture in a panel and for the carousel even on a
  phone where it is scrolled rather than moved.
* Scroll Story: the notch now appears on a phone too, running along the top of
  each picture instead of down its side, and travelling with the item it
  belongs to. New "Notch depth on a phone" and "Notch length on a phone"
  settings, since a wide short picture cannot hold the desktop proportions.

= 2.18.0 =
* Scroll Rail is properly built for a phone. The row now runs edge to edge
  while the first card still lines up with the heading above it, so the next
  card peeks off the screen instead of being cut off at the section's padding,
  and cards snap to that same gutter rather than to the middle of the screen.
* The progress bar is no longer hidden on a phone, where it was the only thing
  telling anyone the row scrolls at all, and a "1 / 6" count sits beside it.
  Both follow your finger.
* New Scroll Rail settings: edge to edge on a phone, the gutter to measure
  from, card width on a phone, and the counter's colours.
* Fix: the Scroll Rail could give the page a sideways scroll on a theme that
  does not set box-sizing. It now sets its own.
* Removed: the "Spin Badge" widget added in 2.16.0. The "Eruda Spin" section
  added in 2.17.0 does the same thing to a picture you already have, which is
  what was wanted.

= 2.17.0 =
* New "Eruda Spin" section on the Image, Icon, Button, Image Box and Site Logo
  widgets. Switch it on and the picture you already have turns, slows to a stop
  under the pointer, and lifts -- without swapping it for anything.
* Scroll Story now translates properly to a phone. Below 1024px each picture is
  moved out of the pinned panel and placed under the item it belongs to, after
  the words, so the section reads text, picture, text, picture. Widening puts
  them back. The highlight still runs, and is measured against the words rather
  than the picture below them, so it no longer finishes late.
* New Scroll Story settings for the phone layout: picture shape, space above
  the picture, and space between items.
* Spin Badge can now take a background picture, with the colour over it as a
  layer of its own so it can be faded without fading the ring text.
* Spin Badge now has an inner shadow, cast inside the disc alongside the ring
  and the shadow it casts.

= 2.16.0 =
* New "Spin Badge" widget: a round link whose text turns around its edge,
  slows to a stop under the pointer, and lifts. Built for a "calculate
  savings" sticker and useful for any round call to action.
* The ring is drawn as text on a circle rather than as a picture of one, so it
  stays sharp at any size on any screen and costs no image request. Nothing in
  it is blurred or scaled.
* However many times the text goes round, it is stretched to meet itself
  exactly once around the circle, so it never laps itself or falls short --
  including after a webfont has loaded.
* Hovering slows it down evenly rather than stopping it dead, the way a wheel
  stops. On a touch screen it starts again when you lift your finger.
* Under Reduce Motion it does not turn at all, and is still a link.
* Removed: the "Hotspot Stats" widget added in 2.15.0.

= 2.15.0 =
* New "Hotspot Stats" widget: figures pinned to points on a photograph, joined
  to them by leader lines that draw themselves in when the section arrives.
* Hotspots are positioned as a percentage of the picture, so one stays on the
  same part of a product at every screen width and in any column.
* The figures count up. A figure is written however you want it read -- 13+,
  1,240, $4.5m -- and only the number in the middle counts; the rest is kept
  exactly as typed.
* Hovering or tabbing to one hotspot dims the others, so the eye is led by
  contrast rather than by anything getting brighter.
* Below 768px the lines and dots come off the picture and the figures become a
  plain list under it.

= 2.14.0 =
* Scroll Rail now works out where to hold a section from where the row sits
  inside it, so the cards are centred on screen and never clipped at the
  bottom, even when the section is taller than the window. The top of the
  section runs off above them instead, which costs the heading its top margin
  long before any of its words.
* A section that does fit on screen is still centred whole. New "Keep centred"
  setting forces either answer when the automatic one is not the wanted one.

= 2.13.0 =
* Scroll Rail now holds the whole section still, not just the row. A heading
  and an introduction above the cards stay exactly where they are while the
  cards cross, instead of scrolling away as though the row were pushing them
  out of the way. New "What holds still" setting, which defaults to the
  section and can be pointed at any container by its CSS class.
* Fix: a held section could stay held for screens after the row had finished,
  because a sticky element keeps sticking for as long as its containing block
  has room and the containing block was the whole page. The section and its
  scrolling now sit in a wrapper of their own, so the hold is exactly as long
  as it should be.

= 2.12.0 =
* Scroll Rail is pinned by default again, and pinning now works the way it
  should: the page stops, the section settles in the middle of the screen and
  holds still, the row crosses without the page moving at all, it holds again,
  and only then does scrolling carry on down the page.
* New "Pause before it sets off" and "Pause before it lets go" settings, in
  screen-heights, which are those two held moments.
* Fix: the crossing was measured from the top of the window rather than from
  where the section actually pins, putting every position out by most of a
  card's height once the stage was centred.
* Flow is still there for when a section cannot afford the height, under
  "How it travels".

= 2.11.0 =
* Scroll Rail sections are now as tall as their cards make them, instead of a
  fixed share of the screen that left dead space above and below the row. A
  fixed height is still available from the new "Section height" setting.
* Fix: the row started travelling while the section was barely in view and had
  finished before it left, so the first and last cards went past unread. It now
  waits until the section is on screen and finishes while it still is, with
  both moments as controls.
* New "Space above and below" setting, which is the room a card's shadow and
  its hover lift need in order not to be clipped.
* New "Extra scroll" setting for Flow. Flow can only spend the scrolling the
  section already has, so a very wide row crosses quickly; this buys more. It
  is the one setting in Flow that does push what follows down the page, and it
  defaults to zero.

= 2.10.0 =
* Scroll Rail no longer pushes the rest of the page down. Pinning a section
  works by giving it extra page height to scroll against, and that height moved
  everything after it down by the width of the row. The new "How it travels"
  setting defaults to Flow, which spends the section's own journey across the
  screen instead and adds no height at all.
* Pinned is still there for when the more deliberate effect is wanted, and
  "Scroll distance" now appears only on that setting, where it is the only
  thing it could mean.

= 2.9.0 =
* New "Scroll Rail" widget: a row of linked cards that travels sideways as you
  scroll down past it. The section is given exactly the height its own travel
  needs, so adding or removing cards changes how far you scroll without
  touching a setting, and you never scroll past a rail that stopped moving.
* Each card carries a picture, a title, a description and a link, with the
  picture desaturated at rest and returning to full colour on hover.
* Cards say they are clickable before you touch them: an arrow sits on the
  picture from the start and fills in, grows and travels on hover or keyboard
  focus. A card with no link is not a link, shows no arrow and keeps the
  default cursor.
* Nothing pins on a phone or under Reduce Motion. The row is a plain swipeable
  strip with snap points, which is also what it is before the script runs.
* Tabbing through the cards brings each one into view as it takes focus.

= 2.8.0 =
* Fix: the notch depth, length and travel controls did nothing. Their defaults
  were declared on the panel itself, which overrode the value the controls
  wrote onto its container.
* A notched panel now keeps its corner radius. The corners are built into the
  same generated path as the notch, and the notch band is kept between them, so
  the two can never meet. The radius control is no longer hidden when the notch
  is on, and the panel's border follows the rounded corners too.
* The notch always leaves a straight stretch of edge between itself and each
  corner, so a generous radius or a long band can no longer leave it starting
  exactly where the corner curve does.
* New "Reveal" transition, now the default: a soft-edged sweep with both
  pictures visible through the feather.
* The picture being replaced now eases back and away while the new one arrives
  over it, rather than sitting still underneath.
* Fix: media could still be shrunk off the panel by a theme's own
  `img { height: auto }` rule, leaving the panel's background showing as a
  band. Size, fit and focal point are now forced and cannot be overridden.
  A picture smaller than the panel is scaled up to fill it.

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
