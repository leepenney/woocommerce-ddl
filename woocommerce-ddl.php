<?php
/*
Plugin Name: Digital Data Layer Plugin for WooCommerce
Plugin URI: http://wordpress.org/extend/
Description: Adds a JavaScript data layer that conforms to the W3C spec
Author: Lee Penney
Author URI: http://viewfinderdesign.co.uk/
Version: 0.1
License: GPLv3
*/


/* Check if WooCommerce plugin is active before continuing */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
	global $ddl;
	$ddl = array();
	
	function woo_ddl_single_product_details() {
		global $post, $ddl;

		$product = get_product( $post->ID );
		
		$ddl['page']['pageInfo']['pageName'] = esc_js($post->post_title);
		$ddl['page']['category']['pageType'] = 'product';
		
		$ddl['product'][0]['productInfo']['productID'] = $post->ID;
		$ddl['product'][0]['productInfo']['productName'] = esc_js($post->post_title);
		$ddl['product'][0]['productInfo']['description'] = esc_js($post->post_content);
		$ddl['product'][0]['productInfo']['productURL'] = esc_js(get_permalink());
		
		$product_image_id = $product->get_image_id();
		if ($product_image_id) {
			$ddl['product'][0]['productInfo']['productThumbnail'] = esc_js(woo_ddl_get_image_src($product_image_id, 'shop_thumbnail'));
			$ddl['product'][0]['productInfo']['productImage'] = esc_js(woo_ddl_get_image_src($product_image_id, 'full'));
		}
		
		$sku = $product->get_sku();
		if (strlen($sku) > 0) {
			$ddl['product'][0]['productInfo']['sku'] = $sku;
		}
		
		$product_categories = get_the_terms($post->ID, 'product_cat');
		$first_cat = array_shift($product_categories);
		
		$ddl['product'][0]['category']['primaryCategory'] = esc_js($first_cat->name);
		$ddl['product'][0]['price']['price'] = $product->get_price();
		
		if ($product->is_on_sale()) {
			$ddl['product'][0]['price']['regular_unit_price'] = $product->get_regular_price();
		}
		
		if ($product->get_stock_quantity()) {
			$ddl['product'][0]['attributes']['stock'] = $product->get_stock_quantity();
		}
	}
	add_action('woocommerce_after_single_product', 'woo_ddl_single_product_details');
	
	function woo_ddl_cart_details() {
		global $woocommerce, $ddl;
		
		$ddl['page']['category']['pageType'] = 'basket';
		
		$ddl['cart']['price']['basePrice'] = preg_replace("/&#?[a-z0-9]+;/i","", strip_tags($woocommerce->cart->get_cart_subtotal()));
		$ddl['cart']['price']['cartTotal'] = preg_replace("/&#?[a-z0-9]+;/i","",strip_tags($woocommerce->cart->get_cart_total()));
		
		$shipping = $woocommerce->cart->get_cart_shipping_total();
		if (strpos($shipping,'Free') !== FALSE) {
			$shipping = '0.00';
		}
		$ddl['cart']['price']['shipping'] = $shipping;
		
		$ddl['cart']['item'] = array();
		
		$cart_contents = $woocommerce->cart->get_cart();
		foreach ($cart_contents as $item) {
			$product = get_product($item['product_id']);
			$item_details = array();

			$item_details['productInfo']['productID'] = $item['product_id'];
			$item_details['productInfo']['productName'] = esc_js($item['data']->post->post_title);
			$item_details['productInfo']['description'] = esc_js($item['data']->post->post_content);
			$item_details['quantity'] = $item['quantity'];
			$item_details['price']['basePrice'] = $item['line_subtotal']/$item['quantity'];
			$item_details['price']['priceWithTax'] = $item['line_total']/$item['quantity'];
			
			$product_image_id = $product->get_image_id();
			if ($product_image_id) {
				$item_details['productInfo']['productThumbnail'] = esc_js(woo_ddl_get_image_src($product_image_id, 'shop_thumbnail'));
				$item_details['productInfo']['productImage'] = esc_js(woo_ddl_get_image_src($product_image_id, 'full'));
			}
			
			$sku = $product->get_sku();
			if (strlen($sku) > 0) {
				$item_details['productInfo']['sku'] = $sku;
			}
			
			array_push($ddl['cart']['item'], $item_details);
		}
	}
	add_action('woocommerce_after_cart', 'woo_ddl_cart_details');
	
	function woo_ddl_purchase_confirmation() {
		global $ddl;
		$ddl['page']['category']['pageType'] = 'confirmation';
	}
	add_action('woocommerce_thankyou', 'woo_ddl_purchase_confirmation');
	
	function woo_ddl_checkout() {
		global $ddl, $order;
		$ddl['page']['category']['pageType'] = 'checkout';
		$ddl['transaction']['transactionID'] = $order->get_order_number();
	}
	add_action('woocommerce_after_checkout_form', 'woo_ddl_checkout');
	
	function woo_ddl_user_info() {
		if (is_user_logged_in()) {
			//if the user is logged in
			global $current_user, $post, $ddl;
			
			$ddl['user'][0]['profile'][0]['profileInfo']['email'] = $current_user->user_email;
		}
		woo_ddl_print_ddl_variable();
	}
	add_action('wp_footer', 'woo_ddl_user_info');
	
	function woo_ddl_get_image_src($img_id, $img_size='full') {
		$image_details = wp_get_attachment_image_src($img_id, $img_size);
		return $image_details[0];
	}
	
	function woo_ddl_print_ddl_variable() {
		global $ddl;
		
		echo '<script>',"\n";
		echo 'window.digitalData = window.digitalData || {};',"\n";
		echo 'window.digitalData = ',json_encode($ddl),"\n";
		echo '</script>',"\n";
	}
}