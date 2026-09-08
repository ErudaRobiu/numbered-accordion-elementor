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
