=== Numbered Accordion for Elementor ===
Contributors: erudarobiu
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later

A modern, lightly animated numbered accordion widget for Elementor.

== Description ==

Adds a single widget, "Numbered Accordion", to the Elementor panel under the
General category.

Each row shows a title on the left and a sequence number on the right. Opening a
row reveals an optional eyebrow label and a description, and draws an accent rule
along the bottom of the row.

Features:

* Repeater-driven items: title, optional eyebrow, optional description
* Automatic numbering (01, 02 ... or 1, 2 ... or hidden), with a configurable
  starting value
* Single-open or multi-open behaviour, and an option to keep one item always open
* Full Elementor style controls for typography, colours, spacing and motion
* Keyboard accessible: arrow keys, Home and End move between headers
* Correct ARIA wiring (aria-expanded, aria-controls, role="region", inert panels)
* Honours prefers-reduced-motion
* Assets load only on pages where the widget is actually used

== Safety ==

The plugin refuses to boot unless PHP, WordPress, Elementor and the specific
Elementor classes it extends are all present. Every failure path shows an admin
notice and returns; none of them can produce a front-end fatal error. Widget
registration is additionally wrapped in a try/catch.

This is a classic (V3) Elementor widget. It renders correctly on Elementor V4
Atomic pages and can be placed alongside atomic elements.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/ or install the zip through
   Plugins > Add New > Upload Plugin.
2. Activate it.
3. Edit a page with Elementor and search the panel for "Numbered Accordion".

== Changelog ==

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
