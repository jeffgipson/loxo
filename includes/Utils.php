<?php
namespace Loxo;

/**
 * Utility Class File.
 *
 * @package Loxo
 */

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
		print_r( $data );
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
