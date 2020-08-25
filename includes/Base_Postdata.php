<?php
/**
 * Abstract Data
 *
 * @package Loxo
 */

namespace Loxo;

use ReflectionMethod;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base_Termdata class
 */
abstract class Base_Postdata extends Base_Data
{
	protected $post_type;
	protected $data_type;

	protected $data = [
		'name'				=> '',
		'slug'				=> '',
		'status'			=> '',
		'priority'			=> '',
		'summary'			=> '',
		'description'		=> '',
		'user_id'			=> '',
		'date_created'		=> '',
		'date_updated'		=> ''
	];
	protected $meta_fields;
	protected $post_fields = [
		'name'				=> [
			'key' => 'post_title'
		],
		'slug'				=> [
			'key' => 'post_name'
		],
		'status'			=> [
			'key' => 'post_status'
		],
		'priority'			=> [
			'key' => 'menu_order'
		],
		'summary'			=> [
			'key' => 'post_excerpt'
		],
		'description'		=> [
			'key' => 'post_content'
		],
		'user_id'		=> [
			'key' => 'post_author'
		],
		'date_created'		=> [
			'key' => 'post_date'
		],
		'date_updated'		=> [
			'key' => 'post_modified'
		]
	];

	protected $taxonomy_fields = [];

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
	public function get_priority()
	{
		return $this->get_prop('priority');
	}
	public function get_status()
	{
		return $this->get_prop('status');
	}
	public function get_summary()
	{
		return $this->get_prop('summary');
	}
	public function get_description()
	{
		return $this->get_prop('description');
	}
	public function get_user_id()
	{
		return (int) $this->get_prop('user_id');
	}
	public function get_date_created()
	{
		return $this->get_prop('date_created');
	}
	public function get_date_updated()
	{
		return $this->get_prop('date_updated');
	}

	public function set_name($value)
	{
		$this->set_prop('name', $value);
	}
	public function set_slug($value)
	{
		$this->set_prop('slug', $value);
	}
	public function set_priority($value)
	{
		$this->set_prop('priority', $value);
	}
	public function set_status($value)
	{
		$this->set_prop('status', $value);
	}
	public function set_summary($value)
	{
		$this->set_prop('summary', $value);
	}
	public function set_user_id($value)
	{
		$this->set_prop('user_id', (int) $value);
	}
	public function set_description($value)
	{
		$this->set_prop('description', $value);
	}
	public function set_date_created($value)
	{
		$this->set_prop('date_created', $value);
	}
	public function set_date_updated($value)
	{
		$this->set_prop('date_updated', $value);
	}

	public function read()
	{
		if ( $this->get_id() ) {
			$post = get_post( $this->get_id() );
		} elseif ( $this->get_slug() ) {
			global $wpdb;
			$sql = $wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s",
				$this->get_slug(),
				$this->post_type
			);
			$post_id = $wpdb->get_var( $sql );
			$post = get_post( $post_id );
		}

		if (empty($post) || $post->post_type != $this->post_type) {
			$this->set_object_read(false);
			return false;
		}

		$data = [
			'id' => $post->ID
		];

		foreach ($this->post_fields as $key => $attrs) {
			if (array_key_exists($key, $this->data)) {
				$data[$key] = $post->{$attrs['key']};
			}
		}

		if (! empty($this->taxonomy_fields)) {
			foreach ($this->taxonomy_fields as $key => $attrs) {
				if ($terms = wp_get_object_terms( $post->ID, $attrs['key'], ['fields' => 'ids'] ) ) {
					if (! is_wp_error($terms)) {
						if ($attrs['unique']) {
							$data[$key] = array_shift($terms);
						} else {
							$data[$key] = $terms;
						}
					}
				}
			}
		}
		# Served_Api_Utils::d($data);

		foreach ($this->read_metadata($post->ID) as $key => $val) {
			$data[$key] = $val;
		}

		$this->set_props( $data );
		$this->set_object_read( true );
	}

	public function delete()
	{
		// let other plugin filter before a profile is being deleted
		try {
			do_action('prpg/'. $this->data_type .'_delete', $this->get_id());
		} catch(Exception $e ){
			throw new Exception($e->getMessage(), $e->getCode());
			return;
		}

		// hard delete, no trash days
		wp_delete_post($this->get_id(), true);

		// let other plugin know that a profile were deleted
		do_action('prpg/'. $this->data_type .'_deleted', $this->get_id());

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
			$data = apply_filters('prpg/'. $this->data_type .'_create', $this->get_changes());
		} catch(Exception $e ){
			throw new Exception($e->getMessage(), $e->getCode());
			return false;
		}

		if (! $this->get_date_created()) {
			$this->set_date_created(current_time('mysql'));
		}
		if (! $this->get_date_updated()) {
			$this->set_date_updated(current_time('mysql'));
		}

		$post_data = [
			'post_type' => $this->post_type
		];

		foreach ($this->post_fields as $key => $attrs) {
			$post_data[$attrs['key']] = $this->get_prop($key);
		}

    	$tax_input = [];
		if (! empty($this->taxonomy_fields)) {
			foreach ($this->taxonomy_fields as $key => $attrs) {
				$tax_input[$attrs['key']] = $this->get_prop($key);
			}
		}

		$insert = wp_insert_post($post_data);
		if (is_wp_error($insert )) {
			throw new Exception($insert->get_error_message());
			return;
		}

		$this->set_id($insert);
		$this->update_taxonomies($tax_input);
		$this->update_metadata($data);
		$this->apply_changes();

		do_action('prpg/'. $this->data_type .'_created', $this->get_id(), $this->get_data());
	}

	public function update_taxonomies($tax_input = [])
	{
		if ( ! empty( $tax_input ) ) {
			foreach ( $tax_input as $taxonomy => $tags ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( ! $taxonomy_obj ) {
					_doing_it_wrong( __FUNCTION__, sprintf( __( 'Invalid taxonomy: %s.' ), $taxonomy ), '4.4.0' );
					continue;
				}

				if ( is_array( $tags ) ) {
					$tags = array_filter( $tags );
				}

				wp_set_post_terms( $this->get_id(), $tags, $taxonomy );
			}
		}
	}

	public function update()
	{
		if (! $this->validate_save()) {
			return false;
		}

		// let other plugin filter profile data or throw exception
		try {
			$changes = apply_filters('prpg/'. $this->data_type .'_update', $this->get_changes(), $this->get_id());
		} catch(Exception $e ){
			throw new Exception($e->getMessage(), $e->getCode());
			return;
		}

		// Only update when the device data changes.
		if (! empty($changes)) {
			$post_data = [];
			foreach ($this->post_fields as $key => $attrs) {
				if (array_key_exists($key, $changes)) {
					$post_data[$attrs['key']] = $changes[$key];
				}
			}

			if (! empty($this->taxonomy_fields)) {
				$post_data['tax_input'] = [];
				foreach ($this->taxonomy_fields as $key => $attrs) {
					if (array_key_exists($key, $changes)) {
						$post_data['tax_input'][$attrs['key']] = $changes[$key];
					}
				}
			}

			#\Loxo\Utils::d( $post_data );

			if (! empty($post_data)) {
				if (doing_action('save_post')) {
					global $wpdb;
					$wpdb->update($wpdb->posts, $post_data, ['ID' => $this->get_id()]);
				} else {
					$post_data['ID'] = $this->get_id();
					$update = wp_update_post($post_data);

					if (is_wp_error($update)) {
						throw new Exception($update->get_error_message());
						return false;
					}
				}
			}

			$this->update_metadata($changes);
			$this->apply_changes();
		}

		do_action('prpg/'. $this->data_type .'_updated', $this->get_id(), $this->get_data(), $changes);
	}

	protected function validate_save()
	{
		return true;
	}
}
