# Eruda Toolkit Text Animations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `motion` module to Eruda Toolkit that animates the text of Heading and Text Editor widgets as they scroll into view.

**Architecture:** A module in the existing four-module pattern. PHP injects a control section into the Advanced tab of two widget types and lets Elementor carry the values to the DOM through `prefix_class` and `selectors` — the module writes no markup. A ~4KB vanilla script splits the text into word, character or line spans and toggles one attribute; CSS transitions do every animation. The split mode for each preset is declared once, in the stylesheet, as a `--eanm-split` custom property the script reads back.

**Tech Stack:** PHP 7.4, WordPress 6.0+, Elementor 3.5+, vanilla ES5 JavaScript, CSS custom properties. No build step, no package manager, no dependencies.

**Spec:** `docs/superpowers/specs/2026-09-17-eruda-text-animations-design.md`

## Global Constraints

- PHP 7.4 minimum (`ERUDA_MIN_PHP`). No arrow functions, no typed properties, no named arguments.
- Elementor 3.5.0 minimum (`ERUDA_MIN_ELEMENTOR`). Checked by the module, not by a `Requires Plugins` header.
- Text domain is `numbered-accordion` everywhere, including in new files. It does not match the product name and must not be changed.
- Zero dependencies. No Composer, no npm, no CDN. The one vendored library stays the only one.
- Asset handle prefix for this module: `eanm-`. CSS class prefix: `eanm-`.
- Control IDs (`eanm_preset`, `eanm_trigger`, `eanm_duration`, `eanm_stagger`, `eanm_delay`, `eanm_ease`, `eanm_threshold`, `eanm_replay`) and preset values (`none`, `words-up`, `words-fade`, `chars-cascade`, `chars-flip`, `lines-mask`, `blur-in`, `scale-pop`, `slide-left`) are frozen once shipped. Saved Elementor JSON carries them.
- **The content must survive the script.** CSS may never hide text on its own. Text becomes invisible only under `[data-eanm-ready]`, which only JavaScript sets. Script blocked, script errored, browser too old, Reduce Motion on — the text renders plainly and completely.
- `is_available()` on a module must not call a translation function and must not reference `\Elementor\Widget_Base` (neither is ready at `plugins_loaded`).
- Every PHP file starts with the `if ( ! defined( 'ABSPATH' ) ) { exit; }` guard and carries a `@package ErudaToolkit` docblock.
- `php tests/run.php` must pass, and `php -l` must pass on every file, before anything is called done.
- **Do not run `bin/release.sh` and do not push.** It tags, pushes and cuts a GitHub release. Committing locally is the end of this plan.

---

### Task 1: Preset registry

The single source of truth for what presets exist. Pure PHP — no WordPress beyond a translation call — so the test suite can load it directly.

**Files:**
- Create: `modules/motion/class-motion-presets.php`
- Create: `modules/motion/index.php`
- Modify: `tests/bootstrap.php`
- Test: `tests/run.php`

**Interfaces:**
- Consumes: nothing
- Produces:
  - `Motion_Presets::all(): array` — value => `array( 'label' => string, 'split' => string )`
  - `Motion_Presets::options(): array` — value => label, for an Elementor SELECT
  - `Motion_Presets::split_mode( string $value ): string` — one of `none|words|chars|lines`
  - `Motion_Presets::is_valid( $value ): bool`
  - `Motion_Presets::easings(): array` — value => label, for an Elementor SELECT
  - Class constants `SPLIT_NONE`, `SPLIT_WORDS`, `SPLIT_CHARS`, `SPLIT_LINES`

- [ ] **Step 1: Add the stubs the new file needs to the test bootstrap**

`tests/bootstrap.php` stubs only `__()` and `wp_slash()`. The presets file calls `esc_html__()`, and Task 2's file calls `apply_filters()`. Add both now so the bootstrap is not touched twice.

In `tests/bootstrap.php`, after the existing `__()` stub, add:

```php
/**
 * Escaping translation stub. Escaping is WordPress's job and is not what
 * these tests are about; the pass-through keeps assertions readable.
 *
 * @param string $text   Text.
 * @param string $domain Text domain.
 * @return string
 */
function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore
	return $text;
}

/**
 * Filter stub. Returns the value untouched, which is what an unhooked
 * filter does. A test that needs a hooked filter overrides this by
 * setting $GLOBALS['eruda_test_filters'].
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Value.
 * @return mixed
 */
function apply_filters( $hook, $value ) { // phpcs:ignore
	if ( isset( $GLOBALS['eruda_test_filters'][ $hook ] ) ) {
		return call_user_func( $GLOBALS['eruda_test_filters'][ $hook ], $value );
	}

	return $value;
}
```

And at the bottom, alongside the existing `require_once` lines:

```php
require_once dirname( __DIR__ ) . '/modules/motion/class-motion-presets.php';
```

- [ ] **Step 2: Write the failing tests**

Append to `tests/run.php`, immediately before the `/* ---- report --- */` block. Also add `use ErudaToolkit\Modules\Motion\Motion_Presets;` to the `use` statements at the top of the file.

```php
/* --------------------------------------------------- Motion_Presets --- */

$presets = Motion_Presets::all();

check( 'nine presets including none', 9, count( $presets ) );
check( 'none is present', true, isset( $presets['none'] ) );

foreach ( array( 'words-up', 'words-fade', 'chars-cascade', 'chars-flip', 'lines-mask', 'blur-in', 'scale-pop', 'slide-left' ) as $value ) {
	check( "preset {$value} exists", true, isset( $presets[ $value ] ) );
}

foreach ( $presets as $value => $preset ) {
	check( "preset {$value} has a label", true, isset( $preset['label'] ) && '' !== $preset['label'] );
	check(
		"preset {$value} has a valid split mode",
		true,
		in_array(
			isset( $preset['split'] ) ? $preset['split'] : null,
			array( Motion_Presets::SPLIT_NONE, Motion_Presets::SPLIT_WORDS, Motion_Presets::SPLIT_CHARS, Motion_Presets::SPLIT_LINES ),
			true
		)
	);
}

check( 'options() maps every value to its label', array_keys( $presets ), array_keys( Motion_Presets::options() ) );
check( 'words-up splits by word', Motion_Presets::SPLIT_WORDS, Motion_Presets::split_mode( 'words-up' ) );
check( 'chars-flip splits by character', Motion_Presets::SPLIT_CHARS, Motion_Presets::split_mode( 'chars-flip' ) );
check( 'lines-mask splits by line', Motion_Presets::SPLIT_LINES, Motion_Presets::split_mode( 'lines-mask' ) );
check( 'blur-in does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( 'blur-in' ) );

// An unknown value must degrade to "do nothing", never to a split mode.
check( 'an unknown preset does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( 'nonsense' ) );
check( 'null does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( null ) );

check( 'a known preset is valid', true, Motion_Presets::is_valid( 'scale-pop' ) );
check( 'an unknown preset is not valid', false, Motion_Presets::is_valid( 'nonsense' ) );
check( 'an array is not a valid preset', false, Motion_Presets::is_valid( array() ) );
check( 'null is not a valid preset', false, Motion_Presets::is_valid( null ) );

$easings = Motion_Presets::easings();

check( 'five easings', 5, count( $easings ) );
check( 'the default easing exists', true, isset( $easings['out-expo'] ) );
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `php tests/run.php`

Expected: a fatal error, `Class "ErudaToolkit\Modules\Motion\Motion_Presets" not found`, because the bootstrap now requires a file that does not exist yet.

- [ ] **Step 4: Create the module directory's silence file**

Every directory in this plugin has one. `modules/motion/index.php`:

```php
<?php
// Silence is golden.
```

- [ ] **Step 5: Write the presets class**

`modules/motion/class-motion-presets.php`:

```php
<?php
/**
 * The preset catalogue.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Motion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What animations exist, and how each one cuts the text up.
 *
 * Deliberately free of WordPress beyond translation, so the test suite can
 * load it without a WordPress install.
 *
 * The split mode recorded here is the PHP-side copy. The stylesheet declares
 * the same fact as a --eanm-split custom property per preset, and the script
 * reads it from there rather than carrying a third copy. This array exists for
 * the tests and for any future PHP that needs to reason about a preset without
 * a browser.
 */
final class Motion_Presets {

	const SPLIT_NONE  = 'none';
	const SPLIT_WORDS = 'words';
	const SPLIT_CHARS = 'chars';
	const SPLIT_LINES = 'lines';

	/**
	 * Every preset, in the order they appear in the dropdown.
	 *
	 * The keys are frozen: saved Elementor JSON stores them.
	 *
	 * @return array<string, array{label: string, split: string}>
	 */
	public static function all() {
		return array(
			'none'          => array(
				'label' => esc_html__( 'None', 'numbered-accordion' ),
				'split' => self::SPLIT_NONE,
			),
			'words-up'      => array(
				'label' => esc_html__( 'Words Up', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
			'words-fade'    => array(
				'label' => esc_html__( 'Words Fade', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
			'chars-cascade' => array(
				'label' => esc_html__( 'Characters Cascade', 'numbered-accordion' ),
				'split' => self::SPLIT_CHARS,
			),
			'chars-flip'    => array(
				'label' => esc_html__( 'Characters Flip', 'numbered-accordion' ),
				'split' => self::SPLIT_CHARS,
			),
			'lines-mask'    => array(
				'label' => esc_html__( 'Lines Reveal', 'numbered-accordion' ),
				'split' => self::SPLIT_LINES,
			),
			'blur-in'       => array(
				'label' => esc_html__( 'Blur In', 'numbered-accordion' ),
				'split' => self::SPLIT_NONE,
			),
			'scale-pop'     => array(
				'label' => esc_html__( 'Scale Pop', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
			'slide-left'    => array(
				'label' => esc_html__( 'Slide In', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
		);
	}

	/**
	 * Value => label, shaped for an Elementor SELECT control.
	 *
	 * @return array<string, string>
	 */
	public static function options() {
		$options = array();

		foreach ( self::all() as $value => $preset ) {
			$options[ $value ] = $preset['label'];
		}

		return $options;
	}

	/**
	 * How a preset cuts the text up.
	 *
	 * Anything unrecognised returns SPLIT_NONE. A preset value that no longer
	 * exists -- a downgrade, a hand-edited layout -- must leave the text alone
	 * rather than guess.
	 *
	 * @param mixed $value Preset value.
	 * @return string
	 */
	public static function split_mode( $value ) {
		$all = self::all();

		if ( ! self::is_valid( $value ) ) {
			return self::SPLIT_NONE;
		}

		return $all[ $value ]['split'];
	}

	/**
	 * Is this a preset this version ships?
	 *
	 * @param mixed $value Candidate.
	 * @return bool
	 */
	public static function is_valid( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		return array_key_exists( $value, self::all() );
	}

	/**
	 * The easing curves offered, as a SELECT's options.
	 *
	 * The values are semantic rather than the curves themselves, so the stored
	 * layout stays readable and the curve can be retuned in CSS later without
	 * touching a single saved page. The stylesheet maps each one to a
	 * cubic-bezier via the .eanm-ease-* class.
	 *
	 * @return array<string, string>
	 */
	public static function easings() {
		return array(
			'out-expo'  => esc_html__( 'Smooth', 'numbered-accordion' ),
			'out-quart' => esc_html__( 'Gentle', 'numbered-accordion' ),
			'out-back'  => esc_html__( 'Overshoot', 'numbered-accordion' ),
			'in-out'    => esc_html__( 'Even', 'numbered-accordion' ),
			'linear'    => esc_html__( 'Linear', 'numbered-accordion' ),
		);
	}
}
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php tests/run.php`

Expected: `OK — N assertions passed`, where N is about 40 higher than before the task.

- [ ] **Step 7: Lint**

Run: `php -l modules/motion/class-motion-presets.php && php -l tests/bootstrap.php && php -l tests/run.php`

Expected: `No syntax errors detected` three times.

- [ ] **Step 8: Commit**

```bash
git add modules/motion/class-motion-presets.php modules/motion/index.php tests/bootstrap.php tests/run.php
git commit -m "Add the text animation preset catalogue

Nine presets, each declaring how it cuts the text up. An unrecognised
value returns 'no split' rather than guessing, so a downgrade or a
hand-edited layout leaves the text alone.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Control definitions and the supported-widget list

Which widgets get the section, and what the section contains. The control definitions are built as a plain array by a pure method, so they can be asserted without Elementor loaded; `inject()` is the thin part that talks to Elementor.

**Files:**
- Create: `modules/motion/class-motion-controls.php`
- Modify: `tests/bootstrap.php`
- Test: `tests/run.php`

**Interfaces:**
- Consumes: `Motion_Presets::options()`, `Motion_Presets::easings()`
- Produces:
  - `Motion_Controls::supported_widgets(): array` — filtered widget names
  - `Motion_Controls::is_supported( $name ): bool`
  - `Motion_Controls::control_definitions(): array` — control ID => Elementor args, with `type` held as a token string
  - `Motion_Controls::inject( $element, $args ): void` — the hook callback
  - Class constant `SECTION_ID = 'eanm_section'`

- [ ] **Step 1: Require the new file from the test bootstrap**

At the bottom of `tests/bootstrap.php`, after the presets require:

```php
require_once dirname( __DIR__ ) . '/modules/motion/class-motion-controls.php';
```

This is safe even though the file references Elementor classes: it does so only inside `inject()`, which the tests never call, and PHP resolves class names at call time.

- [ ] **Step 2: Write the failing tests**

Append to `tests/run.php` before the report block, and add `use ErudaToolkit\Modules\Motion\Motion_Controls;` at the top.

```php
/* -------------------------------------------------- Motion_Controls --- */

check(
	'only the two plain-text widgets are supported',
	array( 'heading', 'text-editor' ),
	Motion_Controls::supported_widgets()
);

check( 'heading is supported', true, Motion_Controls::is_supported( 'heading' ) );
check( 'text-editor is supported', true, Motion_Controls::is_supported( 'text-editor' ) );
check( 'a button is not supported', false, Motion_Controls::is_supported( 'button' ) );
check( 'an icon box is not supported', false, Motion_Controls::is_supported( 'icon-box' ) );
check( 'this plugin\'s own accordion is not supported', false, Motion_Controls::is_supported( 'nacc-numbered-accordion' ) );
check( 'an empty name is not supported', false, Motion_Controls::is_supported( '' ) );
check( 'null is not supported', false, Motion_Controls::is_supported( null ) );

// The filter is the documented extension point. A site adding a widget must
// work, and a site returning nonsense must not produce a PHP warning.
$GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] = function ( $list ) {
	$list[] = 'button';
	return $list;
};

check( 'the filter can add a widget', true, Motion_Controls::is_supported( 'button' ) );
check( 'the filter keeps the defaults', true, Motion_Controls::is_supported( 'heading' ) );

$GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] = function () {
	return array();
};

check( 'a filter can switch the section off entirely', false, Motion_Controls::is_supported( 'heading' ) );

$GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] = function () {
	return 'not an array';
};

check( 'a corrupt filter return supports nothing', false, Motion_Controls::is_supported( 'heading' ) );

unset( $GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] );

check( 'the defaults come back once the filter is gone', true, Motion_Controls::is_supported( 'heading' ) );

$controls = Motion_Controls::control_definitions();

check(
	'the section carries exactly the frozen control ids',
	array( 'eanm_preset', 'eanm_trigger', 'eanm_duration', 'eanm_stagger', 'eanm_delay', 'eanm_ease', 'eanm_threshold', 'eanm_replay' ),
	array_keys( $controls )
);

check( 'the preset control offers every preset', array_keys( Motion_Presets::all() ), array_keys( $controls['eanm_preset']['options'] ) );
check( 'the preset control defaults to none', 'none', $controls['eanm_preset']['default'] );
check( 'the preset control writes the wrapper class', 'eanm-preset-', $controls['eanm_preset']['prefix_class'] );
check( 'the easing control offers every easing', array_keys( Motion_Presets::easings() ), array_keys( $controls['eanm_ease']['options'] ) );

// Everything but the preset dropdown stays hidden until a preset is chosen,
// so the section reads as a single control when it is not in use.
foreach ( $controls as $id => $definition ) {
	if ( 'eanm_preset' === $id ) {
		check( 'the preset control is never conditional', false, isset( $definition['condition'] ) );
		continue;
	}

	check(
		"{$id} is hidden while the preset is none",
		'none',
		isset( $definition['condition']['eanm_preset!'] ) ? $definition['condition']['eanm_preset!'] : null
	);
}

// Neither of these means anything for an animation that fires on load.
foreach ( array( 'eanm_threshold', 'eanm_replay' ) as $id ) {
	check(
		"{$id} is hidden unless the trigger is scroll",
		'scroll',
		isset( $controls[ $id ]['condition']['eanm_trigger'] ) ? $controls[ $id ]['condition']['eanm_trigger'] : null
	);
}

// Every value has to reach the DOM somehow: a class, or a custom property.
// A control with neither is a control that does nothing.
foreach ( $controls as $id => $definition ) {
	check(
		"{$id} reaches the DOM",
		true,
		isset( $definition['prefix_class'] ) || isset( $definition['selectors'] )
	);
}

// The custom properties the stylesheet and the script read by name.
check(
	'duration writes --eanm-duration',
	array( '{{WRAPPER}}' => '--eanm-duration: {{SIZE}}ms;' ),
	$controls['eanm_duration']['selectors']
);
check(
	'stagger writes --eanm-stagger',
	array( '{{WRAPPER}}' => '--eanm-stagger: {{SIZE}}ms;' ),
	$controls['eanm_stagger']['selectors']
);
check(
	'delay writes --eanm-delay',
	array( '{{WRAPPER}}' => '--eanm-delay: {{SIZE}}ms;' ),
	$controls['eanm_delay']['selectors']
);
check(
	'threshold writes --eanm-threshold',
	array( '{{WRAPPER}}' => '--eanm-threshold: {{SIZE}};' ),
	$controls['eanm_threshold']['selectors']
);

check( 'duration defaults to 800ms', 800, $controls['eanm_duration']['default']['size'] );
check( 'stagger defaults to 60ms', 60, $controls['eanm_stagger']['default']['size'] );
check( 'delay defaults to none', 0, $controls['eanm_delay']['default']['size'] );
check( 'threshold defaults to 20 per cent', 20, $controls['eanm_threshold']['default']['size'] );
check( 'replay is off by default', '', $controls['eanm_replay']['default'] );
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `php tests/run.php`

Expected: fatal error, `Class "ErudaToolkit\Modules\Motion\Motion_Controls" not found`.

- [ ] **Step 4: Write the controls class**

`modules/motion/class-motion-controls.php`:

```php
<?php
/**
 * The injected control section.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Motion;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an "Eruda Text Animation" section to the Advanced tab of the widgets
 * whose content is a single block of plain text.
 *
 * The module writes no markup and no inline styles. Every value reaches the
 * DOM through Elementor's own prefix_class and selectors, which Elementor
 * applies live in the editor as well as on the front end -- a render-time
 * attribute would never appear on a widget with a content_template(), and the
 * native Heading has one.
 */
final class Motion_Controls {

	const SECTION_ID = 'eanm_section';

	/**
	 * Which widgets get the section.
	 *
	 * Heading and Text Editor hold one block of text and nothing else. A
	 * composite widget -- an Icon Box, a Price Table -- raises a question this
	 * feature has no good answer to: whether its title and its description are
	 * one staggered run or two.
	 *
	 * Everything downstream is widget-agnostic, so a site that wants the
	 * controls elsewhere adds the widget name here and nothing else changes.
	 *
	 * @return string[]
	 */
	public static function supported_widgets() {
		$widgets = apply_filters(
			'eruda_motion_supported_widgets',
			array( 'heading', 'text-editor' )
		);

		return is_array( $widgets ) ? $widgets : array();
	}

	/**
	 * Is this widget one of them?
	 *
	 * @param mixed $name Widget name from Widget_Base::get_name().
	 * @return bool
	 */
	public static function is_supported( $name ) {
		if ( ! is_string( $name ) || '' === $name ) {
			return false;
		}

		return in_array( $name, self::supported_widgets(), true );
	}

	/**
	 * Every control in the section, in order.
	 *
	 * 'type' holds a token rather than a Controls_Manager constant so this
	 * method stays pure: the test suite asserts the whole section without
	 * Elementor loaded. inject() swaps the tokens for constants.
	 *
	 * @return array<string, array>
	 */
	public static function control_definitions() {
		$active = array( 'eanm_preset!' => 'none' );

		return array(
			'eanm_preset'    => array(
				'label'        => esc_html__( 'Animation', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'none',
				'options'      => Motion_Presets::options(),
				'prefix_class' => 'eanm-preset-',
				// A changed preset means a different split, so the widget has
				// to be re-rendered for the script to run again.
				'render_type'  => 'template',
			),
			'eanm_trigger'   => array(
				'label'        => esc_html__( 'Starts', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'scroll',
				'options'      => array(
					'scroll' => esc_html__( 'When scrolled into view', 'numbered-accordion' ),
					'load'   => esc_html__( 'On page load', 'numbered-accordion' ),
				),
				'prefix_class' => 'eanm-trigger-',
				'render_type'  => 'template',
				'condition'    => $active,
			),
			'eanm_duration'  => array(
				'label'      => esc_html__( 'Duration', 'numbered-accordion' ),
				'type'       => 'slider',
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 100,
						'max'  => 3000,
						'step' => 50,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 800,
				),
				'selectors'  => array( '{{WRAPPER}}' => '--eanm-duration: {{SIZE}}ms;' ),
				'condition'  => $active,
			),
			'eanm_stagger'   => array(
				'label'       => esc_html__( 'Stagger', 'numbered-accordion' ),
				'description' => esc_html__( 'The gap between one word or letter starting and the next.', 'numbered-accordion' ),
				'type'        => 'slider',
				'size_units'  => array( 'ms' ),
				'range'       => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 300,
						'step' => 5,
					),
				),
				'default'     => array(
					'unit' => 'ms',
					'size' => 60,
				),
				'selectors'   => array( '{{WRAPPER}}' => '--eanm-stagger: {{SIZE}}ms;' ),
				'condition'   => $active,
			),
			'eanm_delay'     => array(
				'label'      => esc_html__( 'Delay', 'numbered-accordion' ),
				'type'       => 'slider',
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 3000,
						'step' => 50,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 0,
				),
				'selectors'  => array( '{{WRAPPER}}' => '--eanm-delay: {{SIZE}}ms;' ),
				'condition'  => $active,
			),
			'eanm_ease'      => array(
				'label'        => esc_html__( 'Easing', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'out-expo',
				'options'      => Motion_Presets::easings(),
				'prefix_class' => 'eanm-ease-',
				'condition'    => $active,
			),
			'eanm_threshold' => array(
				'label'       => esc_html__( 'Starts at', 'numbered-accordion' ),
				'description' => esc_html__( 'How much of the text has to be on screen before it animates.', 'numbered-accordion' ),
				'type'        => 'slider',
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 20,
				),
				// Read back by the script, not used by the stylesheet. A custom
				// property is how the other modules pass numbers to their JS.
				'selectors'   => array( '{{WRAPPER}}' => '--eanm-threshold: {{SIZE}};' ),
				'condition'   => array(
					'eanm_preset!'  => 'none',
					'eanm_trigger'  => 'scroll',
				),
			),
			'eanm_replay'    => array(
				'label'        => esc_html__( 'Replay every time', 'numbered-accordion' ),
				'description'  => esc_html__( 'Animate again whenever the text scrolls back into view.', 'numbered-accordion' ),
				'type'         => 'switcher',
				'default'      => '',
				'return_value' => 'yes',
				'prefix_class' => 'eanm-replay-',
				'condition'    => array(
					'eanm_preset!' => 'none',
					'eanm_trigger' => 'scroll',
				),
			),
		);
	}

	/**
	 * Register the section on a widget.
	 *
	 * Hooked to elementor/element/common/_section_style/after_section_end,
	 * which fires for every widget, so the guard does the selecting.
	 *
	 * @param mixed $element Element being built.
	 * @param array $args    Section arguments.
	 */
	public function inject( $element, $args ) {
		if ( ! $element instanceof \Elementor\Widget_Base ) {
			return;
		}

		if ( ! self::is_supported( $element->get_name() ) ) {
			return;
		}

		$element->start_controls_section(
			self::SECTION_ID,
			array(
				'label' => esc_html__( 'Eruda Text Animation', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		foreach ( self::control_definitions() as $id => $definition ) {
			$definition['type'] = self::control_type( $definition['type'] );
			$element->add_control( $id, $definition );
		}

		$element->end_controls_section();
	}

	/**
	 * Token to Elementor control constant.
	 *
	 * @param string $token One of select|slider|switcher.
	 * @return string
	 */
	private static function control_type( $token ) {
		switch ( $token ) {
			case 'select':
				return Controls_Manager::SELECT;
			case 'slider':
				return Controls_Manager::SLIDER;
			case 'switcher':
				return Controls_Manager::SWITCHER;
		}

		return Controls_Manager::TEXT;
	}
}
```

Two controls carry a second condition: `eanm_threshold` and `eanm_replay` are also conditional on `eanm_trigger` being `scroll`, since neither means anything for an animation that fires on load. That is what the second loop in Step 2 asserts.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php tests/run.php`

Expected: `OK — N assertions passed`.

- [ ] **Step 6: Lint**

Run: `php -l modules/motion/class-motion-controls.php && php -l tests/run.php && php -l tests/bootstrap.php`

Expected: `No syntax errors detected` three times.

- [ ] **Step 7: Commit**

```bash
git add modules/motion/class-motion-controls.php tests/bootstrap.php tests/run.php
git commit -m "Add the text animation control section

Heading and Text Editor only, guarded by a filtered allowlist on the
common hook rather than per-widget hooks, which would attach relative to
a named section of one widget and land the controls on the Content tab.

The definitions are built as a plain array by a pure method so the whole
section can be asserted without Elementor loaded.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: The module class and its registration

Makes the module real: it appears in Settings, boots when enabled, registers its assets, and injects the controls.

**Files:**
- Create: `modules/motion/class-motion-module.php`
- Create: `modules/motion/assets/index.php`, `modules/motion/assets/css/index.php`, `modules/motion/assets/js/index.php`
- Create: `modules/motion/assets/css/text-animation.css` (empty placeholder, filled in Task 4)
- Create: `modules/motion/assets/js/text-animation.js` (empty placeholder, filled in Task 5)
- Modify: `includes/class-toolkit.php:26-39` (the `$registry` array)
- Test: `tests/run.php`

**Interfaces:**
- Consumes: `Motion_Controls::is_supported()`, `Motion_Presets::is_valid()`
- Produces:
  - `Motion_Module` implementing `\ErudaToolkit\Module`
  - Constants `Motion_Module::STYLE_HANDLE = 'eanm-text-animation'` and `SCRIPT_HANDLE = 'eanm-text-animation'`
  - Registry key `motion` in `Toolkit::$registry`

- [ ] **Step 1: Write the failing test**

Append to `tests/run.php` before the report block:

```php
/* ------------------------------------------------- Toolkit registry --- */

check( 'the motion module is registered', true, in_array( 'motion', Toolkit::instance()->ids(), true ) );
check( 'four modules ship', 4, count( Toolkit::instance()->ids() ) );
check( 'motion is on by default', true, Toolkit::is_enabled( 'motion', array() ) );
check( 'motion can be switched off', false, Toolkit::is_enabled( 'motion', array( 'motion' => false ) ) );
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php tests/run.php`

Expected: FAIL with `the motion module is registered / expected: true / actual: false` and `four modules ship / expected: 4 / actual: 3`.

- [ ] **Step 3: Create the silence files and empty assets**

```bash
printf '<?php\n// Silence is golden.\n' > modules/motion/assets/index.php
printf '<?php\n// Silence is golden.\n' > modules/motion/assets/css/index.php
printf '<?php\n// Silence is golden.\n' > modules/motion/assets/js/index.php
touch modules/motion/assets/css/text-animation.css
touch modules/motion/assets/js/text-animation.js
```

- [ ] **Step 4: Write the module class**

`modules/motion/class-motion-module.php`:

```php
<?php
/**
 * Text animation module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Motion;

use ErudaToolkit\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects the animation controls and loads the assets that act on them.
 */
final class Motion_Module implements Module {

	const STYLE_HANDLE  = 'eanm-text-animation';
	const SCRIPT_HANDLE = 'eanm-text-animation';

	/**
	 * Have the assets been enqueued for this request?
	 *
	 * @var bool
	 */
	private $enqueued = false;

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'motion';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Text Animations', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds an "Eruda Text Animation" section to the Advanced tab of the Heading and Text Editor widgets: words, letters or lines that animate as they scroll into view.', 'numbered-accordion' );
	}

	/**
	 * Is Elementor present and recent enough?
	 *
	 * As with the other Elementor modules, do NOT test for
	 * \Elementor\Widget_Base here -- it does not exist at plugins_loaded.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return self::elementor_loaded() && self::elementor_recent_enough();
	}

	/**
	 * The same checks, said out loud.
	 *
	 * @return string[]
	 */
	public static function requirement_messages() {
		if ( ! self::elementor_loaded() ) {
			return array( esc_html__( 'Elementor is not installed or not activated.', 'numbered-accordion' ) );
		}

		if ( ! self::elementor_recent_enough() ) {
			return array(
				sprintf(
					/* translators: %s: required Elementor version */
					esc_html__( 'Elementor %s or greater is required.', 'numbered-accordion' ),
					ERUDA_MIN_ELEMENTOR
				),
			);
		}

		return array();
	}

	/**
	 * Has Elementor booted?
	 *
	 * @return bool
	 */
	private static function elementor_loaded() {
		return (bool) did_action( 'elementor/loaded' );
	}

	/**
	 * Is the Elementor version high enough?
	 *
	 * @return bool
	 */
	private static function elementor_recent_enough() {
		return defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, ERUDA_MIN_ELEMENTOR, '>=' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		require_once ERUDA_PATH . 'modules/motion/class-motion-presets.php';
		require_once ERUDA_PATH . 'modules/motion/class-motion-controls.php';

		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );

		$controls = new Motion_Controls();
		add_action( 'elementor/element/common/_section_style/after_section_end', array( $controls, 'inject' ), 10, 2 );

		// Front end: load nothing until a widget actually asks for it.
		add_action( 'elementor/frontend/before_render', array( $this, 'maybe_enqueue' ) );

		// Editor preview: always load, so a preset animates the moment it is
		// picked. Four kilobytes inside the editor is not worth conditioning.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Register the stylesheet.
	 */
	public function register_styles() {
		wp_register_style(
			self::STYLE_HANDLE,
			ERUDA_URL . 'modules/motion/assets/css/text-animation.css',
			array(),
			ERUDA_VERSION
		);
	}

	/**
	 * Register the script.
	 */
	public function register_scripts() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			ERUDA_URL . 'modules/motion/assets/js/text-animation.js',
			array(),
			ERUDA_VERSION,
			true
		);
	}

	/**
	 * Load the assets if this element is animated.
	 *
	 * get_style_depends() is not available here: the controls live on widgets
	 * this plugin does not own. A page with no animated widget therefore loads
	 * neither file.
	 *
	 * Enqueuing this late can put the stylesheet after first paint. That costs
	 * nothing, because the pre-animation state is "visible": the CSS only hides
	 * anything under [data-eanm-ready], which the script sets.
	 *
	 * @param mixed $element Element about to render.
	 */
	public function maybe_enqueue( $element ) {
		if ( $this->enqueued ) {
			return;
		}

		if ( ! $element instanceof \Elementor\Widget_Base ) {
			return;
		}

		if ( ! Motion_Controls::is_supported( $element->get_name() ) ) {
			return;
		}

		$preset = $element->get_settings_for_display( 'eanm_preset' );

		if ( 'none' === $preset || ! Motion_Presets::is_valid( $preset ) ) {
			return;
		}

		$this->enqueue();
	}

	/**
	 * Load the assets.
	 */
	public function enqueue() {
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );

		$this->enqueued = true;
	}
}
```

- [ ] **Step 5: Register the module**

In `includes/class-toolkit.php`, add to `$registry` after the `impact` entry:

```php
		'motion'     => array(
			'file'  => 'modules/motion/class-motion-module.php',
			'class' => '\ErudaToolkit\Modules\Motion\Motion_Module',
		),
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php tests/run.php`

Expected: `OK — N assertions passed`.

- [ ] **Step 7: Lint every PHP file, as the release script does**

Run: `find . -path ./vendor -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null && echo "all files parse"`

Expected: `all files parse`.

- [ ] **Step 8: Commit**

```bash
git add modules/motion includes/class-toolkit.php tests/run.php
git commit -m "Register the text animation module

Fourth module in the registry, so it appears in Settings and can be
switched off like the others.

Assets load lazily: the front end enqueues on the first widget that
actually carries a preset, so an unanimated page loads neither file. The
editor preview loads them unconditionally, which is what makes a preset
animate the moment it is picked.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: The stylesheet and a standalone harness

Every preset, plus the `--eanm-split` tokens the script reads back. A harness page lets the next three tasks be verified in a browser without a WordPress install; `tests/` is excluded from the release zip, so it never ships.

**Files:**
- Modify: `modules/motion/assets/css/text-animation.css`
- Create: `tests/manual/text-animation.html`

**Interfaces:**
- Consumes: the class names Elementor generates from `prefix_class` — `eanm-preset-*`, `eanm-ease-*`, `eanm-trigger-*`, `eanm-replay-yes`
- Produces:
  - `--eanm-split` on each preset class, valued `words`, `chars`, `lines` or `none`
  - `.eanm-u` (outer, masks) and `.eanm-i` (inner, moves) as the contract the splitter builds to
  - `--eanm-i` as the per-unit stagger index the script sets

- [ ] **Step 1: Write the stylesheet**

`modules/motion/assets/css/text-animation.css`:

```css
/**
 * Eruda Toolkit - text animations
 *
 * Nothing here hides text on its own. Every rule that makes something
 * invisible is scoped to [data-eanm-ready], and only the script sets that.
 * If the script never runs, this file has no visible effect at all.
 *
 * The split mode per preset is declared here rather than in the script, so
 * the mapping lives in one place. The script reads it back with
 * getComputedStyle().
 */

.eanm-preset-words-up      { --eanm-split: words; }
.eanm-preset-words-fade    { --eanm-split: words; }
.eanm-preset-chars-cascade { --eanm-split: chars; }
.eanm-preset-chars-flip    { --eanm-split: chars; }
.eanm-preset-lines-mask    { --eanm-split: lines; }
.eanm-preset-blur-in       { --eanm-split: none; }
.eanm-preset-scale-pop     { --eanm-split: words; }
.eanm-preset-slide-left    { --eanm-split: words; }

/* Easing. The control stores a name; the curve lives here, so it can be
   retuned later without touching a single saved page. */
.eanm-ease-out-expo  { --eanm-ease: cubic-bezier(0.16, 1, 0.3, 1); }
.eanm-ease-out-quart { --eanm-ease: cubic-bezier(0.25, 1, 0.5, 1); }
.eanm-ease-out-back  { --eanm-ease: cubic-bezier(0.34, 1.56, 0.64, 1); }
.eanm-ease-in-out    { --eanm-ease: cubic-bezier(0.65, 0, 0.35, 1); }
.eanm-ease-linear    { --eanm-ease: linear; }

/* Defaults, so a widget whose sliders were never touched still animates. */
[class*="eanm-preset-"] {
	--eanm-duration: 800ms;
	--eanm-stagger: 60ms;
	--eanm-delay: 0ms;
	--eanm-ease: cubic-bezier(0.16, 1, 0.3, 1);
	--eanm-threshold: 20;
}

/* The split spans. Layout, not state: these apply whether or not the
   animation has run. */
.eanm-u {
	display: inline-block;
}

.eanm-i {
	display: inline-block;
	transition-property: transform, opacity, filter;
	transition-duration: var(--eanm-duration);
	transition-timing-function: var(--eanm-ease);
	transition-delay: calc(var(--eanm-delay) + var(--eanm-stagger) * var(--eanm-i, 0));
}

/* Only while there is something still to animate. Left on permanently, a
   will-change per word is a real cost on a long page. */
[data-eanm-ready]:not([data-eanm-in]) .eanm-i {
	will-change: transform, opacity;
}

/* Masked presets: the outer span clips, the inner one slides out from
   behind it. overflow:hidden on an inline-block cuts descenders off, so pad
   the box and pull the padding back out of the layout. */
.eanm-preset-words-up .eanm-u,
.eanm-preset-slide-left .eanm-u,
.eanm-preset-chars-cascade .eanm-u,
.eanm-preset-lines-mask .eanm-u {
	overflow: hidden;
	padding-bottom: 0.18em;
	margin-bottom: -0.18em;
	vertical-align: bottom;
}

/* Pre-animation states. Every one of these is gated on [data-eanm-ready]. */
.eanm-preset-words-up [data-eanm-ready] .eanm-i,
.eanm-preset-chars-cascade [data-eanm-ready] .eanm-i,
.eanm-preset-lines-mask [data-eanm-ready] .eanm-i {
	transform: translateY(110%);
}

.eanm-preset-slide-left [data-eanm-ready] .eanm-i {
	transform: translateX(-110%);
}

.eanm-preset-words-fade [data-eanm-ready] .eanm-i {
	opacity: 0;
	transform: translateY(0.35em);
}

.eanm-preset-chars-flip [data-eanm-ready] {
	perspective: 600px;
}

.eanm-preset-chars-flip [data-eanm-ready] .eanm-i {
	opacity: 0;
	transform: rotateX(-90deg);
	transform-origin: 50% 100%;
}

.eanm-preset-scale-pop [data-eanm-ready] .eanm-i {
	opacity: 0;
	transform: scale(0.8);
}

/* blur-in splits nothing: the target element is itself the moving part. */
.eanm-preset-blur-in [data-eanm-ready] {
	opacity: 0;
	filter: blur(12px);
	transition-property: opacity, filter;
	transition-duration: var(--eanm-duration);
	transition-timing-function: var(--eanm-ease);
	transition-delay: var(--eanm-delay);
	will-change: opacity, filter;
}

/* The animated state. Specificity has to beat every pre-state above, which
   is why the preset class is matched by attribute here. */
[class*="eanm-preset-"] [data-eanm-ready][data-eanm-in] .eanm-i {
	transform: none;
	opacity: 1;
}

.eanm-preset-blur-in [data-eanm-ready][data-eanm-in] {
	opacity: 1;
	filter: blur(0);
}

/*
 * The script declines to split at all when Reduce Motion is set, so this is
 * only for the case where the setting changes after a split has happened.
 */
@media (prefers-reduced-motion: reduce) {
	.eanm-i,
	.eanm-preset-blur-in [data-eanm-ready] {
		transition: none;
	}
}
```

- [ ] **Step 2: Write the harness page**

`tests/manual/text-animation.html`. The markup mirrors what Elementor emits: an `.elementor-element` wrapper carrying the prefix classes, a `.elementor-widget-container`, and the widget's own text element inside.

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Eruda text animations - harness</title>
<link rel="stylesheet" href="../../modules/motion/assets/css/text-animation.css">
<style>
	body { font: 16px/1.5 system-ui, sans-serif; margin: 0; padding: 0 24px 80vh; max-width: 860px; }
	h1, h2 { font-size: clamp(28px, 6vw, 56px); line-height: 1.05; margin: 0; }
	.case { padding: 40vh 0 0; border-bottom: 1px solid #eee; }
	.case > p.note { font-size: 13px; color: #666; margin: 8px 0 0; }
	.controls { position: fixed; top: 0; right: 0; background: #111; color: #fff; padding: 8px 12px; font-size: 12px; z-index: 10; }
</style>
</head>
<body>

<div class="controls">scroll down &mdash; each block animates once</div>

<div class="case">
	<div class="elementor-element eanm-preset-words-up eanm-ease-out-expo eanm-trigger-scroll">
		<div class="elementor-widget-container">
			<h2 class="elementor-heading-title">Words rise out of a mask</h2>
		</div>
	</div>
	<p class="note">words-up &mdash; check descenders on "g" and "y" are not clipped</p>
</div>

<div class="case">
	<div class="elementor-element eanm-preset-chars-cascade eanm-ease-out-expo eanm-trigger-scroll">
		<div class="elementor-widget-container">
			<h2 class="elementor-heading-title">Every single letter arrives</h2>
		</div>
	</div>
	<p class="note">chars-cascade</p>
</div>

<div class="case">
	<div class="elementor-element eanm-preset-chars-flip eanm-ease-out-back eanm-trigger-scroll">
		<div class="elementor-widget-container">
			<h2 class="elementor-heading-title">Flipping letters</h2>
		</div>
	</div>
	<p class="note">chars-flip, overshoot easing</p>
</div>

<div class="case">
	<div class="elementor-element eanm-preset-words-up eanm-ease-out-expo eanm-trigger-scroll">
		<div class="elementor-widget-container">
			<h2 class="elementor-heading-title">Keeps <strong>bold</strong> and <a href="#x">a working link</a><br>across a manual break</h2>
		</div>
	</div>
	<p class="note">markup preservation &mdash; the bold must still be bold, the link must still be clickable, and the break must still break</p>
</div>

<div class="case">
	<div class="elementor-element eanm-preset-lines-mask eanm-ease-out-expo eanm-trigger-scroll">
		<div class="elementor-widget-container">
			<h2 class="elementor-heading-title">A longer headline that wraps onto several lines so the line grouping has something to group</h2>
		</div>
	</div>
	<p class="note">lines-mask &mdash; resize the window and scroll back; lines must regroup</p>
</div>

<div class="case">
	<div class="elementor-element eanm-preset-blur-in eanm-ease-out-quart eanm-trigger-scroll">
		<div class="elementor-widget-container">
			<h2 class="elementor-heading-title">Blurring into focus</h2>
		</div>
	</div>
	<p class="note">blur-in &mdash; splits nothing; inspect and confirm there are no spans inside</p>
</div>

<div class="case">
	<div class="elementor-element eanm-preset-scale-pop eanm-ease-out-back eanm-trigger-scroll eanm-replay-yes">
		<div class="elementor-widget-container">
			<h2 class="elementor-heading-title">Pops back every time</h2>
		</div>
	</div>
	<p class="note">scale-pop with replay on &mdash; scroll past and back, it must animate again</p>
</div>

<div class="case">
	<div class="elementor-element eanm-preset-slide-left eanm-ease-out-expo eanm-trigger-scroll">
		<div class="elementor-widget-container">
			<p>A text editor holds more than one paragraph.</p>
			<p>The stagger carries on across both of them rather than restarting.</p>
		</div>
	</div>
	<p class="note">slide-left on a multi-paragraph text editor</p>
</div>

<script src="../../modules/motion/assets/js/text-animation.js"></script>
</body>
</html>
```

- [ ] **Step 3: Confirm the stylesheet alone changes nothing visible**

The script is still empty, so nothing sets `data-eanm-ready`.

Run: `open tests/manual/text-animation.html`

Expected: every heading is fully visible, in its normal position, with no animation and nothing hidden. This is the "content survives the script" rule being true by construction — confirm it now, while it is easy to see.

- [ ] **Step 4: Commit**

```bash
git add modules/motion/assets/css/text-animation.css tests/manual/text-animation.html
git commit -m "Add the text animation stylesheet

Eight presets, plus the --eanm-split token each one declares for the
script to read back, so the preset-to-split-mode mapping lives in one
place instead of being duplicated in JS.

Nothing here hides text except under [data-eanm-ready], which only the
script sets. With the script absent the file has no visible effect, and
the harness page demonstrates it.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: The splitter

Cuts text into word or character spans without destroying inline markup, and reveals it on page load. Scroll triggering arrives in Task 6.

**Files:**
- Modify: `modules/motion/assets/js/text-animation.js`

**Interfaces:**
- Consumes: `--eanm-split` from the stylesheet; the `.eanm-u` / `.eanm-i` / `--eanm-i` contract
- Produces (module-internal, used by Tasks 6 and 7):
  - `initRoot( rootElement )` — split and arm one `.elementor-element`
  - `initAll( scopeElement )` — every root inside a scope
  - `walk( node, mode, state )` where `state` is `{ index: Number }`
  - `targets( rootElement ) -> Element[]`
  - `readProp( element, name, fallback ) -> String`

- [ ] **Step 1: Write the script**

`modules/motion/assets/js/text-animation.js`:

```js
/**
 * Eruda Toolkit - text animations
 *
 * Vanilla JS, no dependencies. Safe to load twice, safe to run against the
 * same DOM twice, and it never throws if the markup is not what it expects.
 *
 * The script's only jobs are to cut the text up and to decide when it moves.
 * What the movement looks like lives entirely in the stylesheet, and the text
 * is already complete in the HTML -- so if this file fails to load, or bails
 * out below, every heading is still there and still readable. Nothing here is
 * load-bearing for content.
 */
( function () {
	'use strict';

	var ROOT_SELECTOR = '[class*="eanm-preset-"]';
	var PRESET_CLASS = /(?:^|\s)eanm-preset-([a-z-]+)/;
	var TARGET_SELECTOR = 'h1, h2, h3, h4, h5, h6, p, li, blockquote';
	var READY_ATTR = 'data-eanm-ready';
	var IN_ATTR = 'data-eanm-in';

	/**
	 * Elements whose contents must not be cut up.
	 */
	var SKIP_TAGS = /^(BR|IMG|SVG|IFRAME|VIDEO|AUDIO|SCRIPT|STYLE|INPUT|TEXTAREA|SELECT|BUTTON)$/;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	/**
	 * Would motion be unwelcome here?
	 *
	 * @return {boolean}
	 */
	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Does this browser have everything the module needs?
	 *
	 * IntersectionObserver is the only modern thing here. Without it, the
	 * text stays exactly as authored.
	 *
	 * @return {boolean}
	 */
	function isSupported() {
		return (
			typeof window.IntersectionObserver === 'function' &&
			typeof window.requestAnimationFrame === 'function'
		);
	}

	/**
	 * Read a CSS custom property off an element.
	 *
	 * @param {Element} element  Element to read from.
	 * @param {string}  name     Property name.
	 * @param {string}  fallback Value to use when unreadable.
	 * @return {string}
	 */
	function readProp( element, name, fallback ) {
		var raw = '';

		try {
			raw = ( window.getComputedStyle( element ).getPropertyValue( name ) || '' ).trim();
		} catch ( e ) {
			raw = '';
		}

		return raw || fallback;
	}

	/**
	 * The elements inside a widget whose text should animate.
	 *
	 * Falls back to the widget's container, which covers a Heading rendered
	 * as a bare link and a Text Editor holding an unwrapped string.
	 *
	 * @param {Element} root Widget wrapper.
	 * @return {Element[]}
	 */
	function targets( root ) {
		var found = toArray( root.querySelectorAll( TARGET_SELECTOR ) ).filter( function ( el ) {
			return ( el.textContent || '' ).trim() !== '';
		} );

		if ( found.length ) {
			return found;
		}

		var container = root.querySelector( '.elementor-widget-container' ) || root;

		return ( container.textContent || '' ).trim() ? [ container ] : [];
	}

	/**
	 * Put an element back the way it was authored, remembering it the first
	 * time. This is what makes re-running safe: the editor re-renders a
	 * widget on every keystroke.
	 *
	 * @param {Element} el Target element.
	 */
	function restore( el ) {
		if ( typeof el.eanmOriginal === 'string' ) {
			el.innerHTML = el.eanmOriginal;
			return;
		}

		el.eanmOriginal = el.innerHTML;
	}

	/**
	 * Build one animating unit: an outer span that can mask, and an inner one
	 * that moves.
	 *
	 * @param {string} text  The unit's text.
	 * @param {Object} state Carries the running index.
	 * @return {Element}
	 */
	function unit( text, state ) {
		var outer = document.createElement( 'span' );
		var inner = document.createElement( 'span' );

		outer.className = 'eanm-u';
		outer.style.setProperty( '--eanm-i', state.index );
		state.index += 1;

		inner.className = 'eanm-i';
		inner.appendChild( document.createTextNode( text ) );
		outer.appendChild( inner );

		return outer;
	}

	/**
	 * Split a word into characters by code point, so an emoji or a surrogate
	 * pair stays one unit rather than becoming two broken halves.
	 *
	 * @param {string} word Word.
	 * @return {string[]}
	 */
	function toChars( word ) {
		if ( typeof Array.from === 'function' ) {
			return Array.from( word );
		}

		return word.split( '' );
	}

	/**
	 * Replace a text node with its split units.
	 *
	 * Whitespace is re-inserted as plain text rather than being absorbed into
	 * a span, so inline-block words still wrap across lines normally.
	 *
	 * @param {Text}   node  Text node.
	 * @param {string} mode  'words' or 'chars'.
	 * @param {Object} state Carries the running index.
	 */
	function splitTextNode( node, mode, state ) {
		var parts = ( node.data || '' ).split( /(\s+)/ );
		var fragment = document.createDocumentFragment();

		parts.forEach( function ( part ) {
			if ( '' === part ) {
				return;
			}

			if ( /^\s+$/.test( part ) ) {
				fragment.appendChild( document.createTextNode( part ) );
				return;
			}

			if ( 'chars' === mode ) {
				toChars( part ).forEach( function ( character ) {
					fragment.appendChild( unit( character, state ) );
				} );
				return;
			}

			fragment.appendChild( unit( part, state ) );
		} );

		node.parentNode.replaceChild( fragment, node );
	}

	/**
	 * Walk an element's children, splitting text and recursing into markup.
	 *
	 * Recursing rather than rewriting innerHTML is what keeps <strong>, <a>
	 * and <br> intact: the elements themselves are never touched, only the
	 * text nodes between them.
	 *
	 * @param {Element} node  Element to walk.
	 * @param {string}  mode  'words' or 'chars'.
	 * @param {Object}  state Carries the running index.
	 */
	function walk( node, mode, state ) {
		toArray( node.childNodes ).forEach( function ( child ) {
			if ( 3 === child.nodeType ) {
				if ( '' === ( child.data || '' ).trim() ) {
					return;
				}

				splitTextNode( child, mode, state );
				return;
			}

			if ( 1 === child.nodeType && ! SKIP_TAGS.test( child.tagName ) ) {
				walk( child, mode, state );
			}
		} );
	}

	/**
	 * Split and arm one widget.
	 *
	 * @param {Element} root Widget wrapper.
	 */
	function initRoot( root ) {
		if ( ! root || 1 !== root.nodeType || ! PRESET_CLASS.test( root.className || '' ) ) {
			return;
		}

		if ( root.classList.contains( 'eanm-preset-none' ) ) {
			return;
		}

		var mode = readProp( root, '--eanm-split', 'none' );

		targets( root ).forEach( function ( el ) {
			restore( el );
			el.removeAttribute( IN_ATTR );

			if ( 'words' === mode || 'chars' === mode ) {
				walk( el, mode, { index: 0 } );
			}

			el.setAttribute( READY_ATTR, '' );

			// Scroll triggering lands in the next task. For now everything
			// reveals on the next frame.
			window.requestAnimationFrame( function () {
				el.setAttribute( IN_ATTR, '' );
			} );
		} );
	}

	/**
	 * Arm every widget inside a scope.
	 *
	 * @param {Element|Document} scope Where to look.
	 */
	function initAll( scope ) {
		toArray( ( scope || document ).querySelectorAll( ROOT_SELECTOR ) ).forEach( initRoot );
	}

	if ( prefersReducedMotion() || ! isSupported() ) {
		return;
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}
}() );
```

- [ ] **Step 2: Verify the split in a browser**

Run: `open tests/manual/text-animation.html`

Expected, on load, without scrolling:

1. Every heading animates in immediately (scroll triggering is not built yet).
2. The markup case still shows **bold** as bold, the link still navigates, and the `<br>` still breaks the line.
3. `blur-in` animates as one block.

- [ ] **Step 3: Verify the DOM in the console**

In the browser console on that page:

```js
document.querySelector('.eanm-preset-words-up .elementor-heading-title').innerHTML
```

Expected: `<span class="eanm-u" style="--eanm-i: 0;"><span class="eanm-i">Words</span></span> <span class="eanm-u" style="--eanm-i: 1;">…` — note the plain space between spans.

```js
document.querySelector('.eanm-preset-blur-in .elementor-heading-title').querySelectorAll('.eanm-u').length
```

Expected: `0`. A non-splitting preset must not build spans.

```js
document.querySelector('.eanm-preset-words-up a').outerHTML
```

Expected: an `<a href="#x">` still wrapping its own split spans — the element survived, its text was split inside it.

- [ ] **Step 4: Verify re-running is safe**

The script exposes nothing globally, so drive the guard directly. In the console:

```js
var h = document.querySelector('.eanm-preset-chars-cascade .elementor-heading-title');
var first = h.querySelectorAll('.eanm-u').length;

typeof h.eanmOriginal;          // "string" — the pre-split HTML is stashed
h.innerHTML = h.eanmOriginal;   // what restore() does on a re-run
h.querySelectorAll('.eanm-u').length;  // 0 — back to plain text
```

Expected: `"string"`, then `0`. The stash holds the text as authored, so a second split starts from clean markup rather than splitting spans that were already split. Task 8 relies on this every time Elementor re-renders a widget.

Reload before moving on.

- [ ] **Step 5: Verify Reduce Motion**

Turn on **System Settings → Accessibility → Display → Reduce motion**, reload the harness.

Expected: no animation, and `document.querySelectorAll('.eanm-u').length` is `0`. Nothing was split; the DOM is exactly as authored. Turn the setting back off.

- [ ] **Step 6: Commit**

```bash
git add modules/motion/assets/js/text-animation.js
git commit -m "Split animated text into word and character spans

Walks child nodes recursively rather than rewriting innerHTML, so bold,
links and manual breaks survive being split. Whitespace goes back as
plain text nodes so inline-block words still wrap.

The pre-split HTML is stashed on the element, which is what will make
re-initialising safe when the editor re-renders a widget on every
keystroke.

Reduce Motion and a browser without IntersectionObserver both bail out
before splitting, leaving the DOM exactly as authored.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: Scroll triggering

Replaces the reveal-immediately placeholder with a real trigger, honouring the threshold, the trigger mode and the replay switch.

**Files:**
- Modify: `modules/motion/assets/js/text-animation.js`

**Interfaces:**
- Consumes: `initRoot()`, `readProp()` from Task 5; `--eanm-threshold`; classes `eanm-trigger-load`, `eanm-replay-yes`
- Produces: `observe( element, threshold, replay )`

- [ ] **Step 1: Add the observer**

In `modules/motion/assets/js/text-animation.js`, insert before `initRoot`:

```js
	/**
	 * One observer per distinct threshold, shared by every element using it.
	 *
	 * @type {Object}
	 */
	var observers = {};

	/**
	 * Clamp a percentage into a usable IntersectionObserver threshold.
	 *
	 * 1.0 is deliberately unreachable: an element taller than the viewport
	 * can never be 100% visible, and an animation that never fires would
	 * leave its text hidden.
	 *
	 * @param {number} percent Percentage from the control.
	 * @return {number} Ratio between 0 and 0.99.
	 */
	function clampThreshold( percent ) {
		if ( isNaN( percent ) ) {
			return 0.2;
		}

		return Math.min( Math.max( percent / 100, 0 ), 0.99 );
	}

	/**
	 * React to an element crossing its threshold.
	 *
	 * @param {IntersectionObserverEntry[]} entries Entries.
	 */
	function onIntersect( entries ) {
		entries.forEach( function ( entry ) {
			var el = entry.target;

			if ( entry.isIntersecting ) {
				el.setAttribute( IN_ATTR, '' );

				if ( ! el.eanmReplay && el.eanmObserver ) {
					el.eanmObserver.unobserve( el );
				}

				return;
			}

			if ( el.eanmReplay ) {
				el.removeAttribute( IN_ATTR );
			}
		} );
	}

	/**
	 * Watch an element until it comes into view.
	 *
	 * @param {Element} el        Target.
	 * @param {number}  threshold Ratio.
	 * @param {boolean} replay    Animate again on every entry?
	 */
	function observe( el, threshold, replay ) {
		var key = String( threshold );

		if ( ! observers[ key ] ) {
			observers[ key ] = new window.IntersectionObserver( onIntersect, { threshold: threshold } );
		}

		// Re-arming an element the editor just re-rendered.
		if ( el.eanmObserver ) {
			el.eanmObserver.unobserve( el );
		}

		el.eanmReplay = !! replay;
		el.eanmObserver = observers[ key ];
		observers[ key ].observe( el );
	}
```

- [ ] **Step 2: Use it from `initRoot`**

Replace the body of the `targets( root ).forEach(...)` callback in `initRoot` with:

```js
		targets( root ).forEach( function ( el ) {
			restore( el );
			el.removeAttribute( IN_ATTR );

			if ( 'words' === mode || 'chars' === mode ) {
				walk( el, mode, { index: 0 } );
			}

			el.setAttribute( READY_ATTR, '' );

			if ( onLoad ) {
				window.requestAnimationFrame( function () {
					el.setAttribute( IN_ATTR, '' );
				} );
				return;
			}

			observe( el, threshold, replay );
		} );
```

And add these three reads just after `var mode = ...` in `initRoot`:

```js
		var onLoad = root.classList.contains( 'eanm-trigger-load' );
		var replay = root.classList.contains( 'eanm-replay-yes' );
		var threshold = clampThreshold( parseFloat( readProp( root, '--eanm-threshold', '20' ) ) );
```

- [ ] **Step 3: Verify scroll triggering**

Run: `open tests/manual/text-animation.html`

Expected:

1. On load, the first heading is already on screen and animates; the rest are still in their pre-state below the fold (words sitting behind their masks, nothing visible where a heading will be).
2. Scrolling down animates each block as it comes into view, once.
3. The `scale-pop` block (`eanm-replay-yes`) animates again every time you scroll it out and back.
4. Every other block stays put once animated — scrolling back up does not replay it.

- [ ] **Step 4: Verify the on-load trigger**

In the console:

```js
var el = document.querySelector('.eanm-preset-chars-flip');
el.classList.add('eanm-trigger-load');
```

Reload, and watch the chars-flip block below the fold. Scroll to it.

Expected: it is already animated in by the time you reach it — it fired on load rather than on view.

Undo by removing the class, or just reload.

- [ ] **Step 5: Verify the threshold**

The threshold is read once, at init, so it has to be in the markup before the page loads. Edit the `slide-left` case in `tests/manual/text-animation.html`, adding an inline custom property to its wrapper:

```html
<div class="elementor-element eanm-preset-slide-left eanm-ease-out-expo eanm-trigger-scroll" style="--eanm-threshold: 90">
```

Reload and scroll down to it slowly.

Expected: it holds in its pre-state while its first lines are on screen, and only animates once nearly all of it is visible — noticeably later than the blocks above it, which fire at 20%.

Revert the inline style before committing.

- [ ] **Step 6: Commit**

```bash
git add modules/motion/assets/js/text-animation.js
git commit -m "Trigger text animations on scroll

One IntersectionObserver per distinct threshold, shared by every element
using it, and unobserved after firing unless replay is on.

The threshold clamps to 0.99: an element taller than the viewport can
never be fully visible, and an animation that never fires would leave its
text hidden -- the one way this module could lose content.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 7: Line grouping

`lines-mask` needs every word on a rendered line to share one delay. No extra DOM: the words are already split and masked, so grouping is only a matter of what index each word carries.

**Files:**
- Modify: `modules/motion/assets/js/text-animation.js`

**Interfaces:**
- Consumes: the `.eanm-u` spans Task 5 builds
- Produces: `assignLineIndices( element )`, `watchResize( element )`

- [ ] **Step 1: Add line grouping**

Insert before `initRoot`:

```js
	/**
	 * Give every word on the same rendered line the same stagger index.
	 *
	 * This is the whole of the line treatment. The words are already split
	 * and already masked, so lines need no wrapper element of their own --
	 * sharing a delay is what makes them move as a line. That also means a
	 * heading with bold or a link inside it groups correctly, which a
	 * wrapper-based approach could not manage without restructuring markup
	 * it has no business restructuring.
	 *
	 * @param {Element} el Target element.
	 */
	function assignLineIndices( el ) {
		var line = -1;
		var top = null;

		toArray( el.querySelectorAll( '.eanm-u' ) ).forEach( function ( span ) {
			var offset = span.offsetTop;

			// A couple of pixels of slack: sub-pixel layout and mixed font
			// sizes on one line do not mean a new line.
			if ( null === top || Math.abs( offset - top ) > 2 ) {
				line += 1;
				top = offset;
			}

			span.style.setProperty( '--eanm-i', line );
		} );
	}

	/**
	 * Re-group lines when the element's width changes.
	 *
	 * Line breaks are a property of the rendered box, so they are the one
	 * thing here that has to be measured again after a resize.
	 *
	 * @param {Element} el Target element.
	 */
	function watchResize( el ) {
		if ( typeof window.ResizeObserver !== 'function' || el.eanmResize ) {
			return;
		}

		var timer = null;

		el.eanmResize = new window.ResizeObserver( function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				assignLineIndices( el );
			}, 150 );
		} );

		el.eanmResize.observe( el );
	}
```

- [ ] **Step 2: Call it from `initRoot`**

In `initRoot`, replace:

```js
			if ( 'words' === mode || 'chars' === mode ) {
				walk( el, mode, { index: 0 } );
			}
```

with:

```js
			if ( 'words' === mode || 'chars' === mode ) {
				walk( el, mode, { index: 0 } );
			}

			if ( 'lines' === mode ) {
				// Split to words first; the grouping then rewrites the
				// indices so each rendered line shares one.
				walk( el, 'words', { index: 0 } );
				assignLineIndices( el );
				watchResize( el );
			}
```

- [ ] **Step 3: Verify line grouping**

Run: `open tests/manual/text-animation.html` and scroll to the `lines-mask` block.

Expected: the headline reveals line by line, every word on a line moving together, each line starting one stagger after the one above it.

In the console:

```js
toArray = l => Array.prototype.slice.call(l);
toArray(document.querySelectorAll('.eanm-preset-lines-mask .eanm-u'))
  .map(s => s.style.getPropertyValue('--eanm-i'));
```

Expected: a non-decreasing run like `0,0,0,0,1,1,1,1,1,2,2,2` — repeats within a line, stepping up at each break. The count of distinct values must equal the number of lines you can see.

- [ ] **Step 4: Verify regrouping on resize**

Narrow the browser window until the headline wraps differently, wait a moment, then re-run the console snippet above.

Expected: a different grouping, matching the new line breaks. Scroll away and back (the block is not set to replay, so re-run with `document.querySelector('.eanm-preset-lines-mask .elementor-heading-title').removeAttribute('data-eanm-in')` to watch it again).

- [ ] **Step 5: Verify grouping survives inline markup**

Edit the `lines-mask` case in `tests/manual/text-animation.html` so the headline has bold in the middle of it, and keep this version — it is worth having in the harness permanently:

```html
<h2 class="elementor-heading-title">A longer headline with <strong>bold words set inside it</strong> that wraps onto several lines so the line grouping has something to group</h2>
```

Reload, scroll to it, and re-run the index snippet from Step 3.

Expected: the bold is still bold, and its words carry the same line index as the unbolded words beside them on the same line. Grouping by shared index rather than by wrapper is what makes this work; a wrapper would have had to break the `<strong>` in two.

- [ ] **Step 6: Commit**

```bash
git add modules/motion/assets/js/text-animation.js tests/manual/text-animation.html
git commit -m "Group lines by sharing a stagger index

lines-mask needs every word on a rendered line to move together. Since
the words are already split and masked, that is only a question of which
index each one carries -- no line wrapper, no restructured markup, and it
works on a heading with bold or a link inside it.

A debounced ResizeObserver re-groups on width change, since line breaks
are a property of the rendered box rather than of the text.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 8: Elementor editor integration

The harness proves the DOM work. This task makes it behave inside Elementor, where a widget is re-rendered on every keystroke.

**Files:**
- Modify: `modules/motion/assets/js/text-animation.js`

**Interfaces:**
- Consumes: `initRoot()`, `initAll()`
- Produces: nothing further; this is the last change to the script

- [ ] **Step 1: Hook Elementor's re-render**

At the bottom of `modules/motion/assets/js/text-animation.js`, after the `DOMContentLoaded` block and before the closing `}() );`:

```js
	/**
	 * Re-arm a widget Elementor has just re-rendered.
	 *
	 * frontend/element_ready/global fires for every widget on the front end
	 * and again in the editor on every re-render, which is exactly when a
	 * preset change needs a fresh split. initRoot() restores from its stash
	 * first, so running against already-split DOM is safe.
	 */
	function hookElementor() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function ( scope ) {
			var el = scope && scope[0] ? scope[0] : scope;

			if ( el && 1 === el.nodeType ) {
				initRoot( el );
			}
		} );
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', hookElementor );
	}
```

- [ ] **Step 2: Lint the whole plugin**

Run: `find . -path ./vendor -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null && php tests/run.php`

Expected: `No syntax errors detected` for every file, then `OK — N assertions passed`.

- [ ] **Step 3: Verify on a real Elementor site**

This is the first step that needs WordPress. Install the plugin directory on a site with Elementor and work through:

1. **Settings → Eruda Toolkit** lists four modules, all checked.
2. Edit a page, select a **Heading** widget, open **Advanced**. An "Eruda Text Animation" section is there, showing one dropdown.
3. Choose **Words Up**. The remaining controls appear, and the heading animates in the editor preview immediately.
4. Change the preset to **Characters Cascade**. The editor re-renders and the animation changes — this is the re-split working.
5. Drag **Stagger** up and down. The timing changes live without a re-render.
6. Set **Starts** to *On page load*. **Starts at** and **Replay every time** both disappear.
7. Select a **Text Editor** widget. The same section is there.
8. Select a **Button**, an **Icon Box** and an **Image**. None of them has the section.
9. Publish and view the page. The animation fires on scroll.
10. View source: `text-animation.css` and `text-animation.js` are both loaded.
11. Add a page with no animated widget. Neither file is loaded.
12. Uncheck **Text Animations** in Settings, save, reload the editor. The section is gone from the Heading widget, and the published page no longer animates — but the heading is still there, fully readable.

- [ ] **Step 4: Verify the failure modes on the real site**

1. Disable JavaScript in the browser and load the animated page. Every heading renders plainly and completely.
2. Turn on OS Reduce Motion and reload. Nothing animates, nothing is hidden, and no `.eanm-u` spans exist in the DOM.
3. Select the animated heading's text with the mouse and copy it. The clipboard holds the sentence with normal single spaces.

- [ ] **Step 5: Commit**

```bash
git add modules/motion/assets/js/text-animation.js
git commit -m "Re-split animated text when Elementor re-renders a widget

frontend/element_ready/global fires on every editor re-render, which is
when a changed preset needs a fresh split. initRoot() restores from its
stash first, so running against already-split DOM is safe.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 9: Documentation and version

**Files:**
- Modify: `numbered-accordion-elementor.php:5` (description), `:6` (version header), `:44` (`ERUDA_VERSION`)
- Modify: `readme.txt`
- Modify: `README.md`
- Modify: `docs/QA.md`

- [ ] **Step 1: Bump the version**

In `numbered-accordion-elementor.php`:

- `* Version:           2.2.0` becomes `2.3.0`
- `define( 'ERUDA_VERSION', '2.2.0' );` becomes `'2.3.0'`
- The `Description:` header becomes: `A small toolkit of site-building modules: a numbered accordion, an impact grid and text animations for Elementor, and a page duplicator.`

In `readme.txt`, `Stable tag:` becomes `2.3.0`.

- [ ] **Step 2: Add the changelog entry**

In `readme.txt`, at the top of the `== Changelog ==` section:

```
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
```

- [ ] **Step 3: Document the module in README.md**

Follow the structure the accordion and impact grid already use in that file: a section naming the module, what it adds, which widgets it attaches to, the list of presets, and the `eruda_motion_supported_widgets` filter with a worked example:

```php
// Offer the animation controls on the Button widget too.
add_filter(
	'eruda_motion_supported_widgets',
	function ( $widgets ) {
		$widgets[] = 'button';
		return $widgets;
	}
);
```

Note in that section that the module is deliberately limited to widgets holding a single block of text, and why.

- [ ] **Step 4: Add the QA checklist**

In `docs/QA.md`, add a `## Text animations` section after the impact grid's:

```markdown
## Text animations

- [ ] **Settings → Eruda Toolkit** lists four modules, all checked
- [ ] Unchecking Text Animations removes the Advanced section from the Heading
      and Text Editor widgets
- [ ] The section appears on Heading and Text Editor, and on nothing else:
      Button, Icon Box, Image Box and a container all show an unchanged
      Advanced tab
- [ ] A native Heading with Words Up animates on the live page and in the editor
- [ ] Changing the preset in the editor re-splits and animates the new way
- [ ] Dragging the Stagger slider changes the timing without a re-render
- [ ] Setting Starts to "On page load" hides "Starts at" and "Replay every time"
- [ ] A Text Editor of three paragraphs staggers continuously across all three
- [ ] A heading containing `<strong>` and `<a>` keeps both, and the link works
- [ ] A heading containing `<br>` still breaks in the same place
- [ ] Text stays selectable, and copies with normal single spaces
- [ ] Lines Reveal groups correctly, and regroups after a resize from desktop
      width to mobile width
- [ ] With JavaScript disabled, every animated heading renders plainly and
      completely
- [ ] With Reduce Motion on in the OS, nothing splits and nothing moves
- [ ] A page with no animated widget loads neither `text-animation.css` nor
      `text-animation.js`
- [ ] A 1.0.x-era accordion page is unaffected
```

- [ ] **Step 5: Run the full pre-release check**

Run: `find . -path ./vendor -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null && php tests/run.php`

Expected: no syntax errors, then `OK — N assertions passed`.

- [ ] **Step 6: Commit**

```bash
git add numbered-accordion-elementor.php readme.txt README.md docs/QA.md
git commit -m "Document text animations and bump to 2.3.0

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

- [ ] **Step 7: Stop**

Do not run `bin/release.sh`. It bumps, builds, tags, pushes to origin and cuts a GitHub release, which puts the update in front of every client site. Cutting the release is a separate, explicit decision.

---

## Notes for the implementer

**The one duplicated fact.** Which preset splits which way is stated in PHP (`Motion_Presets::all()`) and in CSS (`--eanm-split`). The script reads the CSS copy, so the PHP copy is only used by the tests and by `maybe_enqueue()`. If you add a preset, add it in both places — the test suite checks the PHP side is well-formed but cannot see the stylesheet.

**Why `prefix_class` and not render attributes.** `add_render_attribute()` runs during PHP rendering. The native Heading widget has a `content_template()`, so in the editor it is rendered client-side by Backbone and never goes through PHP. Values written that way would work on the live page and be invisible while editing — which is the one place you need to see them.

**Why the threshold clamps below 1.** An element taller than the viewport can never reach a 1.0 intersection ratio. Its animation would never fire, and because the pre-state hides the text, that is the single way this module could make content disappear.

**`lines-mask` on text with inline markup.** Grouping works by giving words a shared index rather than wrapping them, so nested `<strong>` and `<a>` group correctly. This was the second design; a wrapper-based one could not do it without rebuilding markup the module does not own.
