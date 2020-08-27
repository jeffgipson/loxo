<?php
/**
 * Utility Class File.
 *
 * @package Loxo
 */

namespace Loxo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility Class.
 *
 * @class Loxo_Utils
 */
class Utils {

	/**
	 * Pretty print variable.
	 *
	 * @param  mixed $data Variable.
	 */
	public static function p( $data ) {
		echo '<pre>';
		print_r( $data ); // phpcs:ignore
		echo '</pre>';
	}

	/**
	 * Pretty print & exit execution.
	 *
	 * @param  mixed $data Variable.
	 */
	public static function d( $data ) {
		self::p( $data );
		exit;
	}
}
