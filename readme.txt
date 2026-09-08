=== Eruda Toolkit ===
Contributors: erudarobiu
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later

A small toolkit of site-building modules: a numbered accordion widget for
Elementor, and a page duplicator.

== Description ==

Two modules, each switchable from Settings > Eruda Toolkit. Modules are on by
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

= 2.0.0 =
The plugin is now called Eruda Toolkit and has gained a page duplicator. This is
an in-place update: the plugin folder is unchanged, your accordions are
untouched, and nothing needs re-saving.

== Changelog ==

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
