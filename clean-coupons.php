<?php
/**
 * Plugin Name: WC Coupon Cleaner
 * Plugin URI: https://github.com/artehe/WC_Coupon_Cleaner
 * Description: Removes expired coupons from WooCommerce
 * Version: 1.0.0
 * Stable tag: 1.0.0
 * Requires PHP: 5.6
 * Requires at least: 5.5
 * Tested up to: 6.7.1
 * Author: Artehe
 * Author URI: https://github.com/artehe/WC_Coupon_Cleaner
 * WC tested up to: 6.7.1
 */
defined('ABSPATH') or exit;

class WCCouponCleaner_CleanCoupons {

	public function __construct() {
		require_once __DIR__ . '/admin.php';
		register_activation_hook(__FILE__, array($this, 'wp_schedule_delete_expired_coupons'));
		register_deactivation_hook(__FILE__, array($this, 'wp_remove_scheduled_delete_expired_coupons'));
		add_action('delete_expired_coupons', array($this, 'wp_delete_expired_coupons'));
		add_filter('pre_update_option_woocommerce_coupon_cleanup_option_name', array($this, 'update_hook_value'), 10, 2);
	}

	public function wp_schedule_delete_expired_coupons() {
		if (!wp_next_scheduled('delete_expired_coupons')) {
			$woocommerce_coupon_cleanup_options = get_option('woocommerce_coupon_cleanup_option_name');
			$freq = is_array($woocommerce_coupon_cleanup_options) && isset($woocommerce_coupon_cleanup_options['WCCouponCleaner_Frequency']) ? $woocommerce_coupon_cleanup_options['WCCouponCleaner_Frequency'] : 'daily';

			// Delay the initial start time by 1 hour
			$startTime = time() + (60 * 60);

			wp_schedule_event($startTime, $freq, 'delete_expired_coupons');
		}
	}

	public function wp_remove_scheduled_delete_expired_coupons() {
		wp_clear_scheduled_hook('delete_expired_coupons');
	}

	public function update_hook_value($value, $old_value) {
		if ($value['WCCouponCleaner_Frequency'] && $old_value['WCCouponCleaner_Frequency'] != $value['WCCouponCleaner_Frequency']) {
			wp_clear_scheduled_hook('delete_expired_coupons');
			
			// Delay the start time by 1 hour
			$startTime = time() + (60 * 60);

			wp_schedule_event($startTime, $value['WCCouponCleaner_Frequency'], 'delete_expired_coupons');
		}
		return $value;
	}

	public function wp_delete_expired_coupons() {
		$woocommerce_coupon_cleanup_options = get_option('woocommerce_coupon_cleanup_option_name');

		// Work out the time to search for based on the deletion delay in days
		$deletionDelay = time() - ($woocommerce_coupon_cleanup_options['WCCouponCleaner_DeletionDelay'] * 24 * 60 * 60);

		$args = array(
			'numberposts' => -1,
			'post_type' => 'shop_coupon',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'date_expires',
					'value' => $deletionDelay,
					'compare' => '<='
				),
				array(
					'key' => 'date_expires',
					'value' => '',
					'compare' => '!='
				)
			)
		);
		$coupons = get_posts($args);
		if (!is_array($woocommerce_coupon_cleanup_options)) {
			$woocommerce_coupon_cleanup_options = [];
		}
		if (!empty($coupons)) {
			foreach ($coupons as $coupon) {
				$isMatched = true;
				if (isset($woocommerce_coupon_cleanup_options['WCCouponCleaner_CouponCodeFilter'])) {
					$couponCode = $coupon->get_code();

					$couponCodeLc = strtolower($couponCode);
					$filterValueLc = strtolower($woocommerce_coupon_cleanup_options['WCCouponCleaner_CouponCodeFilter']);
					$isMatched = str_contains($couponCodeLc, $filterValueLc);
				}

				if ($isMatched) {
					if (!isset($woocommerce_coupon_cleanup_options['WCCouponCleaner_RemovalType']) || $woocommerce_coupon_cleanup_options['WCCouponCleaner_RemovalType'] == 'trash') {
						wp_trash_post($coupon->ID);
					} else {
						wp_delete_post($coupon->ID, true);
					}	
				}
			}
		}
	}
}

new WCCouponCleaner_CleanCoupons();
