<?php
namespace Loxo\Job;

use Loxo\Utils;
use Loxo\Base_Postdata;

class Data extends Base_Postdata {

	protected $post_type = 'loxo_job';
	protected $data_type = 'post';

	protected $data = array(
		'name'           => '',
		'slug'           => '',
		'status'         => '',
		'job_id'         => '',
		'description'    => '',

		'city'           => '',
		'zip'            => '',
		'country_code'   => '',
		'salary'         => '',
		'type'			 => '',
		'address'		 => '',
		
		'state_id'       => '',
		'category_ids'   => array(),

		'date_published' => '',
		'date_updated'   => '',

		'date_checked'   => '',
		'date_expires'   => '',
	);

	protected $post_fields = array(
		'name'           => array(
			'key' => 'post_title',
		),
		'slug'           => array(
			'key' => 'post_name',
		),
		'status'         => array(
			'key' => 'post_status',
		),
		'job_id'         => array(
			'key' => 'menu_order',
		),
		'description'    => array(
			'key' => 'post_content',
		),
		'date_published' => array(
			'key' => 'post_date',
		),
		'date_updated'   => array(
			'key' => 'post_modified',
		),
	);
	protected $meta_fields = array(
		'city'         => array(
			'key'    => 'city',
			'unique' => true,
		),
		'zip'          => array(
			'key'    => 'zip',
			'unique' => true,
		),
		'country_code' => array(
			'key'    => 'country_code',
			'unique' => true,
		),
		'salary'       => array(
			'key'    => 'salary',
			'unique' => true,
		),
		'type'       => array(
			'key'    => 'type',
			'unique' => true,
		),
		'address'       => array(
			'key'    => 'address',
			'unique' => true,
		),
		'date_checked' => array(
			'key'    => 'date_checked',
			'unique' => true,
		),
		'date_expires' => array(
			'key'    => 'date_expires',
			'unique' => true,
		),
	);

	protected $taxonomy_fields = array(
		'state_id'     => array(
			'key'    => 'loxo_job_state',
			'unique' => true,
		),
		'category_ids' => array(
			'key' => 'loxo_job_cat',
			'unique' => false,
		),
	);


	function __construct( $job = 0 ) {

		parent::__construct( $job );

		if ( is_numeric( $job ) && $job > 0 ) {
			$this->set_id( $job );
		} elseif ( is_string( $job ) ) {
			$this->set_slug( $job );
		} elseif ( $job instanceof self ) {
			$this->set_id( $job->get_id() );
		} else {
			$this->set_object_read( true );
		}

		if ( $this->get_id() > 0 || $this->get_slug() ) {
			$this->read();
		}
	}

	public function get_job_id() {
		return $this->get_prop( 'job_id' );
	}
	public function get_city() {
		return $this->get_prop( 'city' );
	}
	public function get_zip() {
		return $this->get_prop( 'zip' );
	}
	public function get_country_code() {
		return $this->get_prop( 'country_code' );
	}
	public function get_salary() {
		return $this->get_prop( 'salary' );
	}
	public function get_type() {
		return $this->get_prop( 'type' );
	}
	public function get_address() {
		return $this->get_prop( 'address' );
	}
	public function get_state_id() {
		return $this->get_prop( 'state_id' );
	}
	public function get_category_ids() {
		return $this->get_prop( 'category_ids' );
	}
	public function get_date_published() {
		return $this->get_prop( 'date_published' );
	}
	public function get_date_updated() {
		return $this->get_prop( 'date_updated' );
	}
	public function get_date_checked() {
		return $this->get_prop( 'date_checked' );
	}
	public function get_date_expires() {
		return $this->get_prop( 'date_expires' );
	}

	public function set_job_id( $value ) {
		$this->set_prop( 'job_id', $value );
	}
	public function set_city( $value ) {
		$this->set_prop( 'city', $value );
	}
	public function set_zip( $value ) {
		$this->set_prop( 'zip', $value );
	}
	public function set_country_code( $value ) {
		$this->set_prop( 'country_code', $value );
	}
	public function set_salary( $value ) {
		$this->set_prop( 'salary', $value );
	}
	public function set_type( $value ) {
		$this->set_prop( 'type', $value );
	}
	public function set_address( $value ) {
		$this->set_prop( 'address', $value );
	}
	public function set_state_id( $value ) {
		$this->set_prop( 'state_id', $value );
	}
	public function set_category_ids( $value ) {
		$this->set_prop( 'category_ids', $value );
	}
	public function set_date_published( $value ) {
		$this->set_prop( 'date_published', $value );
	}
	public function set_date_updated( $value ) {
		$this->set_prop( 'date_updated', $value );
	}
	public function set_date_checked( $value ) {
		$this->set_prop( 'date_checked', $value );
	}
	public function set_date_expires( $value ) {
		$this->set_prop( 'date_expires', $value );
	}

	public static function load( $data ) {
		$self = new self();
		if ( ! empty( $data ) ) {
			if ( is_object( $data ) ) {
				$data = get_object_vars( $data );
			}

			$self->set_defaults();
			$self->set_props( $self->pre_get_filter( $data ) );
			$self->apply_changes();
		}
		$self->set_object_read( true );
		return $self;
	}
}
