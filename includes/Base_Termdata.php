<?php
/**
 * Abstract Data
 *
 * @package Loxo
 */

namespace Loxo;

use ReflectionMethod;
use Loxo\Exception\Exception;
use Loxo\Exception\Resource_Exists;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base_term_data class
 */
abstract class Base_Termdata extends Base_Data {

	protected $taxonomy;
	protected $data_type;
	protected $meta_type = 'term';

	protected $data = [
		'name' => '',
		'slug' => ''
	];

	protected $term_fields = [
		'name' => [
			'key' => 'name'
		],
		'slug' => [
			'key' => 'slug'
		]
	];

	public function __construct($id = 0)
	{
		parent::__construct($id);

		if (is_numeric($id ) && $id > 0) {
			$this->read($id);
		} else {
			$this->set_object_read(true);
		}
	}

	public function get_name()
	{
		return $this->get_prop('name');
	}
	public function get_slug()
	{
		return $this->get_prop('slug');
	}

	public function set_name($value)
	{
		$this->set_prop('name', $value);
	}
	public function set_slug($value)
	{
		$this->set_prop('slug', $value);
	}

	public function read($id = 0)
	{
		$term = get_term($id);
		if (empty($term) || $term->taxonomy != $this->taxonomy) {
			$this->set_object_read(false);
			return false;
		}

		$data = [
			'id' => $term->term_id
		];
		foreach ($this->term_fields as $key => $attrs) {
			if (array_key_exists($key, $this->data)) {
				$data[$key] = $term->{$attrs['key']};
			}
		}

		foreach ($this->read_metadata($id) as $key => $val) {
			$data[$key] = $val;
		}

    	$data = $this->pre_set_props($data, $id);
		$this->set_props($data);
		$this->apply_changes();
		$this->set_object_read(true);
	}

	public function delete()
	{
		// let other plugin filter before a profile is being deleted
		try {
			do_action('loxo/'. $this->data_type .'_delete', $this->get_id());
		} catch(Exception $e ){
			throw new Exception($e->getMessage(), $e->getCode());
			return;
		}

		// hard delete, no trash days
		wp_delete_term($this->get_id(), $this->taxonomy);

		// let other plugin know that a profile were deleted
		do_action('loxo/'. $this->data_type .'_deleted', $this->get_id());

		return true;
	}

	public function save()
	{
		if ($this->get_id() && $this->object_read == true ) {
			$this->update();
		} else {
			$this->create();
		}
	}

	public function create()
	{
		if (! $this->validate_save()) {
			return false;
		}

		// let other plugin filter profile data or throw exception
		try {
			$data = apply_filters( 'loxo/'. $this->data_type .'_create', $this->get_changes());
		} catch ( Exception $e ) {
			throw new Exception($e->getMessage(), $e->getCode());
			return false;
		}

		$term_data = [
			'taxonomy' => $this->taxonomy
		];

		foreach ($this->term_fields as $key => $attrs) {
			$term_data[$attrs['key']] = $this->get_prop($key);
		}

		$insert = wp_insert_term( $this->get_name(), $this->taxonomy, $term_data );
		#\Loxo\Utils::d($insert);

		if ( is_wp_error( $insert ) ) {
			if ( 'term_exists' === $insert->get_error_code() ) {
				throw new Resource_Exists( $insert->get_error_message(), $insert->get_error_data( 'term_exists' ) );
			} else {
				throw new Exception( $insert->get_error_message() );
			}
		}

		$this->set_id($insert['term_id']);
		$this->update_metadata($data);
		$this->apply_changes();

		do_action('loxo/'. $this->data_type .'_created', $this->get_id(), $this->get_data());
	}

	public function update()
	{
		if (! $this->validate_save()) {
			return false;
		}

		// let other plugin filter profile data or throw exception
		try {
			$changes = apply_filters('loxo/'. $this->data_type .'_update', $this->get_changes(), $this->get_id());
		} catch(Exception $e ){
			throw new Exception($e->getMessage(), $e->getCode());
			return;
		}

		// Only update when the device data changes.
		if (! empty($changes)) {
			$term_data = [];
			foreach ($this->term_fields as $key => $attrs) {
				if (array_key_exists($key, $changes)) {
					$term_data[$attrs['key']] = $changes[$key];
				}
			}

			if (! empty($term_data)) {
				if (doing_action('save_term')) {
					global $wpdb;
					$wpdb->update($wpdb->terms, $term_data, ['term_id' => $this->get_id()]);
				} else {
					$update = wp_update_term($this->get_id(), $this->taxonomy, $term_data);
					if (is_wp_error($update)) {
						throw new Exception($update->get_error_message());
						return false;
					}
				}
			}

			$this->update_metadata($changes);
			$this->apply_changes();
		}

		do_action('loxo/'. $this->data_type .'_updated', $this->get_id(), $this->get_data(), $changes);
	}

	protected function validate_save()
	{
		return true;
	}
}
