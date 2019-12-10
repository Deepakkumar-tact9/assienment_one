<?php
/**
 * Plugin Name: Assignment One
 * Text Domain: assignment-one
 * Plugin URI: https://www.wisetr.com
 * Author: Deepak Kumar
 * Author URI: https://www.wisetr.com
 * Description: Deepak kumar progress first assignment
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
include_once(ABSPATH.'wp-admin/includes/plugin.php');

class AsignOne_plugin {

	private static $_instance = null;
    public $discount_percentage = 20;
	function __construct() {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action( 'admin_init', array( $this, 'woocommerce_active' ) );
			return;
		}

		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'custom_html' ), 15 );

		// Simple
		add_filter( 'woocommerce_product_get_price', array( $this, 'return_custom_price' ), 99, 2 );

		// Variable
		add_filter('woocommerce_product_variation_get_regular_price', array( $this, 'return_custom_price' ), 99, 2 );
		add_filter('woocommerce_product_variation_get_price', array( $this, 'return_custom_price' ) , 99, 2 );

		// Variations (of a variable product)
		add_filter('woocommerce_variation_prices_price', array( $this, 'return_custom_price' ), 99, 3 );
		add_filter('woocommerce_variation_prices_regular_price', array( $this, 'return_custom_price' ), 99, 3 );

		add_action( 'wp', array( $this, 'save_cookie'));
	}

	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	private function woocommerce_active() {
		add_action( 'admin_notices', array( $this, 'deactivate_notice' ) );
	}

	private function deactivate_notice() {
		echo '<div class="notice notice-error">This plugin requires WooCommerce plugin in order to run. Kindly install it.
		</div>';
	}

	public function custom_html() {
		$discount = $this->discount_percentage;
		$html = '<form id="fcs_form_wrap" action="' . get_permalink() . '" class="fcs_form_wrap" method="post">
    				<p>Get '.$discount.'% discount now! Just enter the email</p>
    				<input type="text" name="fcs_input" class="fcs_input" placeholder="Your email address" style="border: 1px solid #eee;border-radius: 5px;padding: 5px 6px;display: block;max-width: 250px;margin-bottom: 10px;">
    				<button type="submit" name="fcs_btn" class="fcs_btn" style="padding:8px 15px;font-size:16px;background: #222;color: #fff;border-radius: 5px;display: inline-block;margin-bottom: 20px;">Get the discount</button>
    			</form>
    			
    			<script type="text/javascript"> 
	                document.getElementById("fcs_form_wrap").onsubmit = function() {
	                    var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
	                    var fcs_emailValue = this.elements[0];
	                    if(fcs_emailValue.value.match(mailformat)){
	                    	return true;
	                    }else{ 
	                        if(document.getElementById("fcs_formError") == null){
		                        var fcs_formError = document.createElement("p");
		                        fcs_formError.innerHTML = "Please enter valid email";
		                        fcs_formError.id = "fcs_formError";
		                        fcs_formError.style.cssText = "font-size:12px;line-height:20px;color:#f00";
		                        fcs_emailValue.after(fcs_formError);
		                     }
	                        fcs_emailValue.focus();
	                        return false;
	                    }
	                };	                
    			</script>';

			if(isset($_COOKIE['fcs_form_email'])) {
				$html = '<p style="color:#018001">Thanks! enjoy '.$discount.'% off on all products</p>';
			}
		echo $html;
	}

	public function save_cookie() {
		//setcookie('fcs_form_email', null, strtotime('-1 day'), '/');
		if(isset($_POST['fcs_btn']) && $_POST['fcs_input'] != ''){
			setcookie( "fcs_form_email", $_POST['fcs_input'], strtotime('+1 day'), '/' ); //set cookie for one day
			wp_redirect($_SERVER['REQUEST_URI']);
		}
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return float|int
	 */
	public function return_custom_price( $price, $product ) {


		$reg_price = $product->get_regular_price();
		$opt_ids = array();
		$opt_price = array();

		if(isset($_COOKIE['fcs_form_email'])) {
			wc_delete_product_transients($product->get_id());
			if($price > 0 ) {

				$price = $price - ( $price * ( $this->discount_percentage / 100 ) );

				if ( is_cart() ) {
					$dis_price = ( $reg_price * ( $this->discount_percentage / 100 ) );

					foreach( WC()->cart->get_cart() as $cart_item ){
						if(isset($cart_item['tmhasepo']) && isset($cart_item['tm_epo_options_prices'])) {
							if ( ( $cart_item['tmhasepo'] == true ) && ( $cart_item['tm_epo_options_prices'] > 0 ) ) {
								foreach ( $cart_item['tmcartepo'] as $elements ) {
									$opt_price[] = $elements['price'];

								}
								$opt_ids[] = $cart_item['product_id'] . ' ';
							}

						}
					}

					if(in_array($product->get_id(), $opt_ids)){
						$price = $reg_price + array_sum($opt_price) - $dis_price;
					}
				}
			}
		}

		return $price;
	}
}

if ( ! function_exists( 'AsignOne_plugin' ) ) {
	function AsignOne_plugin() {
		return AsignOne_plugin::get_instance();
	}
}

AsignOne_plugin();