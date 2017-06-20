<?php
/**
* Plugin Name: Prices by roles for Woocommerce
* Description: This plugin add fields to woocommerce settings for set discounts percent for each user role.
* Version: 1.0
 * Author: Senorcoders
 * Author URI: http://senorcoders.com
 * Developer: Milton Espinoza
 * Text Domain: senorcoders
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    // Put your plugin code here

		/**
		 * Create the section beneath the products tab
		 **/
		add_filter( 'woocommerce_get_sections_products', 'scpbr_add_section' );
		function scpbr_add_section( $sections ) {

			$sections['tselpricesbyrole'] = __( 'Prices by role', 'senorcoders' );
			return $sections;

		}


		/**
		 * Add settings to the specific section we created before
		 */
		 add_filter( 'woocommerce_get_settings_products', 'scpbr_all_settings', 10, 2 );
		 function scpbr_all_settings( $settings, $current_section ) {
		 	/**
		 	 * Check the current section is what we want
		 	 **/
			 if ( $current_section == 'tselpricesbyrole' ) {
		 		$settings_prices_by_roles = array();
		 		// Add Title to the Settings
		 		$settings_prices_by_roles[] = array( 'name' => __( 'Prices by Roles Settings', 'senorcoders' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure Prices by Roles', 'senorcoders' ), 'id' => 'tselPricesByRole' );

				$editable_roles = get_editable_roles();
				foreach ($editable_roles as $role => $details) {
					// Add  text field option for put the discount for each role
			 		$settings_prices_by_roles[] = array(
			 			'name'     => __( $details['name'], 'senorcoders' ),
			 			'desc_tip' => __( 'This will set the discount percent to the role '.$role, 'senorcoders' ),
			 			'id'       => 'scpbr_'.$role,
			 			'type'     => 'text',
			 			'desc'     => __( '% (discount for the role '.$role.')', 'senorcoders' ),
			 		);
				}
		 		$settings_prices_by_roles[] = array( 'type' => 'sectionend', 'id' => 'tselPricesByRole' );
		 		return $settings_prices_by_roles	;
		 	/**
		 	 * If not, return the standard settings
		 	 **/
		 	} else {
		 		return $settings;
		 	}
		 }

		 function scpbr_custom_price_message( $price  ) {
		     global $woocommerce_loop, $product;
		       $current_user = wp_get_current_user();
					 if(0 <> $current_user->ID){
							$prices = array_map( function( $item ) {
											return array( $item, (float) preg_replace( "/[^0-9.]/", "", html_entity_decode( $item, ENT_QUOTES, 'UTF-8' ) ) );
									}, explode( ' ', strip_tags( $price ) ) );
						 $roles = $current_user->roles;
						 $roles = $roles;
						 $discount = get_site_option( 'scpbr_'.$roles[0] );
						 $price_value = str_replace(get_woocommerce_currency_symbol(), '', $prices[0][1]);

						 $price = $price_value - ($price_value*$discount/100);
						 $price = number_format($price, 2, '.', ',');
						 $price = isset( $prices[0][0] ) ? '<span class="orig-price">' . sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $price) . '</span>' : '';
					 }
		     return $price;
		 }
		 add_filter( 'woocommerce_get_price_html', 'scpbr_custom_price_message' );
		 add_filter( 'woocommerce_cart_item_price', 'scpbr_custom_price_message' );
 }


 // Changing the displayed price (here if we want it can add a custom label)
 add_filter( 'woocommerce_before_calculate_totals', 'scpbr_change_product_price_cart', 10, 1 );
 function scpbr_change_product_price_cart( $cart_object ) {

     if ( is_admin() && ! defined( 'DOING_AJAX' ) )
         return;

     foreach ( $cart_object->get_cart() as $cart_item ) {
					$price = $cart_item['data']->get_price();
		 			$current_user = wp_get_current_user();
		 			if ( 0 == $current_user->ID ) {
		 				return $price;
		 			}
		 			$roles = $current_user->roles;
					$discount = get_site_option( 'scpbr_'.$roles[0] );
		 			if ($cart_item['variation_id'] > 0) {
		 				$price = $price - ($price*$discount/100);
						$price = number_format($price, 2, '.', ',');
		 			}else{
		 				$price = $price - ($price*$discount/100);
						$price = number_format($price, 2, '.', ',');
					}
		       // WooCommerce versions compatibility
		     if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		         $cart_item['data']->price = $price; // Before WC 3.0
		     } else {
		         $cart_item['data']->set_price( $price ); // WC 3.0+
		     }
     }
 }


 // Changing the displayed price (here if we want it can add a custom label)
 add_filter( 'woocommerce_cart_item_price', 'scpbr_display_product_price_cart', 10, 3 );
 function scpbr_display_product_price_cart( $price, $cart_item, $cart_item_key ) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return $price;
		}
		$roles = $current_user->roles;
		$discount = get_site_option( 'scpbr_'.$roles[0] );
		$price = $cart_item['data']->get_price();
		$price = number_format($price, 2, '.', ',');

		return get_woocommerce_currency_symbol().$price;
 }
