<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Data Store.
 *
 * @since    2.7.0
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Data_Store {

	/**
	 * Contains an instance of the data store class that we are working with.
	 */
	private $instance = null;

	/**
	 * Contains an array of default WC supported data stores.
	 * Format of object name => class name.
	 * Example: 'product' => 'WC_Product_Data_Store_CPT'
	 * You can aso pass something like product_<type> for product stores and
	 * that type will be used first when avaiable, if a store is requested like
	 * this and doesn't exist, then the store would fall back to 'product'.
	 * Ran through `woocommerce_data_stores`.
	 */
	private $stores = array(
		'coupon'        => 'WC_Coupon_Data_Store_CPT',
		'payment-token' => 'WC_Payment_Token_Data_Store_Table',
	);
	/**
	 * Contains the name of the current data store's class name.
	 */
	private $current_class_name = '';

	/**
	 * Tells WC_Data_Store which object (coupon, product, order, etc)
	 * store we want to work with.
	 *
	 * @param string $object_type Name of object.
	 */
	public function __construct( $object_type ) {
		$this->stores = apply_filters( 'woocommerce_data_stores', $this->stores );

		// If this object type can't be found, check to see if we can load one
		// level up (so if product_type isn't found, we try product).
		if ( ! array_key_exists( $object_type, $this->stores ) ) {
			$pieces = explode( '_', $object_type );
			$object_type = $pieces[0];
		}

		if ( array_key_exists( $object_type, $this->stores ) ) {
			$store = apply_filters( 'woocommerce_' . $object_type . '_data_store', $this->stores[ $object_type ] );
			if ( ! class_exists( $store ) ) {
				throw new Exception( __( 'Invalid data store.', 'woocommerce' ) );
			}
			$this->current_class_name = $store;
			$this->instance           = new $store;
		} else {
			throw new Exception( __( 'Invalid data store.', 'woocommerce' ) );
		}
	}

	/**
	 * Loads a data store for us or returns null if an invalid store.
	 *
	 * @param string $object_type Name of object.
	 * @since 2.7.0
	 */
	public static function load( $object_type ) {
		try {
			return new WC_Data_Store( $object_type );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Returns the class name of the current data store.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_current_class_name() {
		return $this->current_class_name;
	}

	/**
	 * Reads an object from the data store.
	 *
	 * @since 2.7.0
	 * @param WC_Data
	 */
	public function read( &$data ) {
		$this->instance->read( $data );
	}

	/**
	 * Create an object in the data store.
	 *
	 * @since 2.7.0
	 * @param WC_Data
	 */
	public function create( &$data ) {
		$this->instance->create( $data );
	}

	/**
	 * Update an object in the data store.
	 *
	 * @since 2.7.0
	 * @param WC_Data
	 */
	public function update( &$data ) {
		$this->instance->update( $data );
	}

	/**
	 * Delete an object from the data store.
	 *
	 * @since 2.7.0
	 * @param WC_Data
	 * @param bool $force_delete True to permently delete, false to trash.
	 */
	public function delete( &$data, $force_delete = false ) {
		$this->instance->delete( $data, $force_delete );
	}

	/**
	 * Data stores can define additional functions (for example, coupons have
	 * some helper methods for increasing or decreasing usage). This passes
	 * through to the instance if that function exists.
	 *
	 * @since 2.7.0
	 * @param $method
	 * @param $parameters
	 */
	public function __call( $method, $parameters ) {
		if ( is_callable( array( $this->instance, $method ) ) ) {
			$object = array_shift( $parameters );
			return call_user_func_array( array( $this->instance, $method ), array_merge( array( &$object ), $parameters ) );
		}
	}

}
