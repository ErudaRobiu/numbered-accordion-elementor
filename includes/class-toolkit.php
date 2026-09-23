<?php
/**
 * Module registry.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the list of modules, decides which of them run, and boots them.
 */
final class Toolkit {

	const OPTION = 'eruda_toolkit_modules';

	/**
	 * Module id => path to the file defining the class, relative to ERUDA_PATH.
	 *
	 * @var array<string, array{file: string, class: string}>
	 */
	private static $registry = array(
		'accordion'  => array(
			'file'  => 'modules/accordion/class-accordion-module.php',
			'class' => '\ErudaToolkit\Modules\Accordion\Accordion_Module',
		),
		'duplicator' => array(
			'file'  => 'modules/duplicator/class-duplicator-module.php',
			'class' => '\ErudaToolkit\Modules\Duplicator\Duplicator_Module',
		),
		'header'     => array(
			'file'  => 'modules/header/class-header-module.php',
			'class' => '\ErudaToolkit\Modules\Header\Header_Module',
		),
		'impact'     => array(
			'file'  => 'modules/impact/class-impact-module.php',
			'class' => '\ErudaToolkit\Modules\Impact\Impact_Module',
		),
		'motion'     => array(
			'file'  => 'modules/motion/class-motion-module.php',
			'class' => '\ErudaToolkit\Modules\Motion\Motion_Module',
		),
		'story'      => array(
			'file'  => 'modules/story/class-story-module.php',
			'class' => '\ErudaToolkit\Modules\Story\Story_Module',
		),
		'smoothscroll' => array(
			'file'  => 'modules/smoothscroll/class-smoothscroll-module.php',
			'class' => '\ErudaToolkit\Modules\SmoothScroll\SmoothScroll_Module',
		),
		'rail'       => array(
			'file'  => 'modules/rail/class-rail-module.php',
			'class' => '\ErudaToolkit\Modules\Rail\Rail_Module',
		),
		'badge'      => array(
			'file'  => 'modules/badge/class-badge-module.php',
			'class' => '\ErudaToolkit\Modules\Badge\Badge_Module',
		),
		'explainer'  => array(
			'file'  => 'modules/explainer/class-explainer-module.php',
			'class' => '\ErudaToolkit\Modules\Explainer\Explainer_Module',
		),
		'compare'    => array(
			'file'  => 'modules/compare/class-compare-module.php',
			'class' => '\ErudaToolkit\Modules\Compare\Compare_Module',
		),
		'schematic'  => array(
			'file'  => 'modules/schematic/class-schematic-module.php',
			'class' => '\ErudaToolkit\Modules\Schematic\Schematic_Module',
		),
		'table'      => array(
			'file'  => 'modules/table/class-table-module.php',
			'class' => '\ErudaToolkit\Modules\Table\Table_Module',
		),
		'steps'      => array(
			'file'  => 'modules/steps/class-steps-module.php',
			'class' => '\ErudaToolkit\Modules\Steps\Steps_Module',
		),
	);

	/**
	 * Singleton instance.
	 *
	 * @var Toolkit|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @return Toolkit
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load every module class and boot the ones that should run.
	 */
	public function boot() {
		Settings::instance()->boot();

		require_once ERUDA_PATH . 'includes/class-panel-category.php';
		Panel_Category::boot();

		foreach ( array_keys( self::$registry ) as $id ) {
			$class = $this->load( $id );

			if ( null === $class ) {
				continue;
			}

			if ( ! self::is_enabled( $id, get_option( self::OPTION, array() ) ) ) {
				continue;
			}

			if ( ! $class::is_available() ) {
				continue;
			}

			try {
				$module = new $class();
				$module->boot();
			} catch ( \Throwable $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'Eruda Toolkit: module "%s" failed to boot - %s', $id, $e->getMessage() ) );
				}
			}
		}
	}

	/**
	 * Require a module's file and return its class name.
	 *
	 * @param string $id Module id.
	 * @return string|null Fully qualified class name, or null if unavailable.
	 */
	public function load( $id ) {
		if ( ! isset( self::$registry[ $id ] ) ) {
			return null;
		}

		$file  = ERUDA_PATH . self::$registry[ $id ]['file'];
		$class = self::$registry[ $id ]['class'];

		if ( ! class_exists( $class ) ) {
			if ( ! is_readable( $file ) ) {
				return null;
			}
			require_once $file;
		}

		return class_exists( $class ) ? $class : null;
	}

	/**
	 * Every registered module id.
	 *
	 * @return string[]
	 */
	public function ids() {
		return array_keys( self::$registry );
	}

	/**
	 * Key for the auto-update preference, kept in the same option.
	 *
	 * The leading underscore is what keeps it out of the module namespace: a
	 * module id is a directory name and can never start with one.
	 */
	const AUTO_UPDATE = '_auto_update';

	/**
	 * Should this plugin install its own updates?
	 *
	 * A missing key counts as yes, which is what turns it on for sites that
	 * were installed before the setting existed.
	 *
	 * @param array $stored The stored option value.
	 * @return bool
	 */
	public static function auto_update_enabled( $stored ) {
		if ( ! is_array( $stored ) || ! array_key_exists( self::AUTO_UPDATE, $stored ) ) {
			return true;
		}

		return (bool) $stored[ self::AUTO_UPDATE ];
	}

	/**
	 * Is a module switched on?
	 *
	 * A missing key counts as enabled. That is what lets a future release add a
	 * module that is on by default without needing an upgrade routine to write
	 * its key into the stored option.
	 *
	 * @param string $id      Module id.
	 * @param array  $stored  The stored option value.
	 * @return bool
	 */
	public static function is_enabled( $id, $stored ) {
		if ( ! is_array( $stored ) || ! array_key_exists( $id, $stored ) ) {
			return true;
		}

		return (bool) $stored[ $id ];
	}
}
