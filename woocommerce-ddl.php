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
	
	function woo_ddl_page_details() {
		global $post, $ddl;
		
		$ddl['page']['pageInfo']['pageName'] = esc_js($post->post_title);
		$ddl['page']['pageInfo']['pageID'] = esc_js($post->post_title);
		$ddl['page']['pageInfo']['destinationURL'] = esc_js(get_permalink());

	}
	add_action('wp_footer', 'woo_ddl_page_details');
	
	function woo_ddl_single_product_details() {
		global $post, $ddl;

		$product = get_product( $post->ID );
		
		$product_categories = array_values(wp_get_post_terms($post->ID, 'product_cat', array('orderby' => 'parent')));
		
		$ddl['page']['category']['pageType'] = 'product';
		$ddl['page']['category']['primaryCategory'] = esc_js($product_categories[0]->name);
		if ($product_categories[1]) {
			$ddl['page']['category']['subCategory1'] = esc_js($product_categories[1]->name);
		}
		
		$ddl['product'][0]['productInfo']['productID'] = (string)$post->ID;
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
			$ddl['product'][0]['productInfo']['sku'] = (string)$sku;
		}
		
		$ddl['product'][0]['category']['primaryCategory'] = esc_js($product_categories[0]->name);
		if ($product_categories[1]) {
			$ddl['product'][0]['category']['subCategory1'] = esc_js($product_categories[1]->name);
		}
		
		$ddl['product'][0]['price']['currency'] = get_woocommerce_currency();
		$ddl['product'][0]['price']['price'] = (double)$product->get_price();
		$ddl['product'][0]['price']['priceWithTax'] = $product->get_price_including_tax();
		$ddl['product'][0]['price']['basePrice'] = $product->get_price_excluding_tax();
		
		if ($product->is_on_sale()) {
			$ddl['product'][0]['price']['regular_unit_price'] = (double)$product->get_regular_price();
		}
		
		if ($product->get_stock_quantity()) {
			$ddl['product'][0]['attributes']['stock'] = $product->get_stock_quantity();
		} else {
			$ddl['product'][0]['attributes']['stock'] = $product->is_in_stock() ? 1 : 0;
		}
	}
	add_action('woocommerce_after_single_product', 'woo_ddl_single_product_details');
	
	function woo_ddl_cart_details() {
		global $woocommerce, $wp, $ddl;
		
		$ddl['page']['category']['pageType'] = 'basket';
		
		$ddl['cart']['price']['currency'] = get_woocommerce_currency();
		$ddl['cart']['price']['basePrice'] = $woocommerce->cart->subtotal_ex_tax;
		$ddl['cart']['price']['cartTotal'] = $woocommerce->cart->total;
		$ddl['cart']['price']['priceWithTax'] = $woocommerce->cart->subtotal;
		$ddl['cart']['price']['shipping'] = $woocommerce->cart->shipping_total;
		
		$ddl['cart']['item'] = array();
		
		foreach ($woocommerce->cart->get_cart() as $item_key => $item) {
			$item_details = array();

			$item_details['productInfo']['productID'] = (string)$item['product_id'];
			$item_details['productInfo']['productName'] = esc_js($item['data']->post->post_title);
			$item_details['productInfo']['description'] = esc_js($item['data']->post->post_content);
			$item_details['productInfo']['productURL'] = esc_js($item['data']->get_permalink());
			
			$item_details['quantity'] = $item['quantity'];
			$item_details['price']['basePrice'] = $item['line_subtotal']/$item['quantity'];
			$item_details['price']['priceWithTax'] = $item['line_total']/$item['quantity'];
			$item_details['price']['currency'] = get_woocommerce_currency();
			
			$product_image_id = $item['data']->get_image_id();
			if ($product_image_id) {
				$item_details['productInfo']['productThumbnail'] = esc_js(woo_ddl_get_image_src($product_image_id, 'shop_thumbnail'));
				$item_details['productInfo']['productImage'] = esc_js(woo_ddl_get_image_src($product_image_id, 'full'));
			}

			$sku = $item['data']->get_sku();
			if (strlen($sku) > 0) {
				$item_details['productInfo']['sku'] = (string)$sku;
			}
			
			array_push($ddl['cart']['item'], $item_details);
		}
	}
	add_action('woocommerce_after_cart', 'woo_ddl_cart_details');
	
	function woo_ddl_purchase_confirmation($order_id) {
		global $ddl;
		$order = new WC_Order( $order_id );
		
		if ($order->status != 'failed') {
			// only display if the order succeeded
			$ddl['page']['category']['pageType'] = 'confirmation';
			$ddl['transaction']['transactionID'] = $order_id;
		}
	}
	add_action('woocommerce_thankyou', 'woo_ddl_purchase_confirmation');
	
	function woo_ddl_checkout() {
		global $ddl;
		$ddl['page']['category']['pageType'] = 'checkout';
	}
	add_action('woocommerce_after_checkout_form', 'woo_ddl_checkout');
	
	function woo_ddl_user_info() {
		if (is_user_logged_in()) {
			//if the user is logged in
			global $current_user, $ddl;
			
			$ddl['user'][0]['profile'][0]['profileInfo']['email'] = $current_user->user_email;
			if (strlen($current_user->user_firstname) > 0) {
				$name = $current_user->user_firstname;
				
				if (strlen($current_user->user_lastname) > 0) $name .= ' '.$current_user->user_lastname;
				
				$ddl['user'][0]['profile'][0]['profileInfo']['userName'] = $name;
			}
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