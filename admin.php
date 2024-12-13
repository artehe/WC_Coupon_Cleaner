<?php
defined('ABSPATH') or exit;

class WCCouponCleaner_Admin {

	private $woocommerce_coupon_cleanup_options;

	public function __construct() {
		add_action('admin_menu', array($this, 'woocommerce_coupon_cleanup_add_plugin_page'), 99);
		add_action('admin_init', array($this, 'woocommerce_coupon_cleanup_page_init'));
		$plugin = plugin_basename(__FILE__);
		add_filter('plugin_action_links_' . str_replace('-admin', '', $plugin), array(&$this, 'woo_clean_coupon_plugin_settings_links'));
	}

	public function woo_clean_coupon_plugin_settings_links($links) {
		$plugin_links = array(
			'<a href="' . esc_url(admin_url('/admin.php?page=wc-coupon-cleaner')) . '">' . __('Settings', 'wc-coupon-cleaner') . '</a>'
		);
		return array_merge((array) $plugin_links, $links);
	}

	public function woocommerce_coupon_cleanup_add_plugin_page() {
		add_menu_page( 
			'WC Coupon Cleaner', 
			'WC Coupon Cleaner', 
			'manage_options', 
			'wc-coupon-cleaner', 
			array($this, 'woocommerce_coupon_cleanup_create_admin_page'), 
			'dashicons-clipboard' 
		);
	}

	public function woocommerce_coupon_cleanup_create_admin_page() {
		$this->woocommerce_coupon_cleanup_options = get_option('woocommerce_coupon_cleanup_option_name');
		?>

		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<p>Next cleanup scheduled at: <?php echo date('d-m-Y, H:i', wp_next_scheduled('delete_expired_coupons')); ?></p>
				<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields('woocommerce_coupon_cleanup_option_group');
				do_settings_sections('wc-coupon-cleaner-admin');
				submit_button('Save Settings');
				?>
			</form>
		</div>
	<?php
	}

	public function woocommerce_coupon_cleanup_page_init() {
		register_setting(
			'woocommerce_coupon_cleanup_option_group', // option_group
			'woocommerce_coupon_cleanup_option_name', // option_name
			array($this, 'woocommerce_coupon_cleanup_sanitize') // sanitize_callback
		);

		add_settings_section(
			'woocommerce_coupon_cleanup_setting_section', // id
			'Settings', // title
			array($this, 'woocommerce_coupon_cleanup_section_info'), // callback
			'wc-coupon-cleaner-admin' // page
		);

		add_settings_field(
			'WCCouponCleaner_RemovalType', // id
			'Removal Type', // title
			array($this, 'removal_type_callback'), // callback
			'wc-coupon-cleaner-admin', // page
			'woocommerce_coupon_cleanup_setting_section' // section
		);

		add_settings_field(
			'WCCouponCleaner_Frequency', // id
			'Frequency', // title
			array($this, 'frequency_callback'), // callback
			'wc-coupon-cleaner-admin', // page
			'woocommerce_coupon_cleanup_setting_section' // section
		);

		add_settings_field(
			'WCCouponCleaner_DeletionDelay', // id
			'Deletion Delay', // title
			array($this, 'deletion_delay_callback'), // callback
			'wc-coupon-cleaner-admin', // page
			'woocommerce_coupon_cleanup_setting_section' // section
		);

		add_settings_field(
			'WCCouponCleaner_CouponCodeFilter', // id
			'Coupon Code Filter', // title
			array($this, 'code_filter_callback'), // callback
			'wc-coupon-cleaner-admin', // page
			'woocommerce_coupon_cleanup_setting_section' // section
		);
	}

	public function woocommerce_coupon_cleanup_sanitize($input) {
		$sanitary_values = array();

		if (isset($input['WCCouponCleaner_RemovalType'])) {
			$sanitary_values['WCCouponCleaner_RemovalType'] = $input['WCCouponCleaner_RemovalType'];
		}

		if (isset($input['WCCouponCleaner_Frequency'])) {
			$sanitary_values['WCCouponCleaner_Frequency'] = $input['WCCouponCleaner_Frequency'];
		}

		if (isset($input['WCCouponCleaner_DeletionDelay'])) {
			$sanitary_values['WCCouponCleaner_DeletionDelay'] = absint($input['WCCouponCleaner_DeletionDelay']);
		}

		if (isset($input['WCCouponCleaner_CouponCodeFilter'])) {
			$sanitary_values['WCCouponCleaner_CouponCodeFilter'] = sanitize_text_field($input['WCCouponCleaner_CouponCodeFilter']);
		}

		return $sanitary_values;
	}

	public function woocommerce_coupon_cleanup_section_info() { }

	public function removal_type_callback() { 		
		$setting = get_option('WCCouponCleaner_RemovalType');
		$setting = $setting ? $setting : 'trash';
		?>
		<fieldset>
		<label for="removal_type_0-0">
			<input type="radio" name="woocommerce_coupon_cleanup_option_name[WCCouponCleaner_RemovalType]" id="removal_type_0-0" value="trash" <?php echo $setting === 'trash' ? 'checked' : ''; ?>> 
			Trash
		</label>
		<br>
		<label for="removal_type_0-1">
			<input type="radio" name="woocommerce_coupon_cleanup_option_name[WCCouponCleaner_RemovalType]" id="removal_type_0-1" value="delete" <?php echo $setting === 'delete' ? 'checked' : ''; ?>> 
			Permanently remove
		</label>
		</fieldset> 
		<?php
	}

	public function frequency_callback() {
		$setting = get_option('WCCouponCleaner_Frequency');
		$setting = $setting ? $setting : 'daily';
		?>
		<fieldset>
		<label for="frequency_1-0">
			<input type="radio" name="woocommerce_coupon_cleanup_option_name[WCCouponCleaner_Frequency]" id="frequency_1-0" value="hourly" <?php echo $setting === 'hourly' ? 'checked' : ''; ?>>
			Hourly
		</label>
		<br>
		<label for="frequency_1-1">
			<input type="radio" name="woocommerce_coupon_cleanup_option_name[WCCouponCleaner_Frequency]" id="frequency_1-1" value="twicedaily" <?php echo $setting === 'twicedaily' ? 'checked' : ''; ?>> 
			Twice a Day
		</label>
		<br>
		<label for="frequency_1-2">
			<input type="radio" name="woocommerce_coupon_cleanup_option_name[WCCouponCleaner_Frequency]" id="frequency_1-2" value="daily" <?php echo $setting === 'daily' ? 'checked' : ''; ?>> 
			Daily
		</label>
		<br>
		<label for="frequency_1-3">
			<input type="radio" name="woocommerce_coupon_cleanup_option_name[WCCouponCleaner_Frequency]" id="frequency_1-3" value="weekly" <?php echo $setting === 'weekly' ? 'checked' : ''; ?>> 
			Weekly
		</label>
		<br>
		<?php
	}

	public function deletion_delay_callback() {
		$setting = get_option('WCCouponCleaner_DeletionDelay');
		$setting = $setting ? $setting : 0;
		echo "<input id='WCCouponCleaner_DeletionDelayInput' min='0' name='WCCouponCleaner_DeletionDelay' type='number' value='" . esc_attr( $setting ) . "' />";

		// Help text below the field
		echo '<p>The number of days to wait before deleting an expired coupon</p>';
	}

	public function code_filter_callback() {
		$setting = get_option('WCCouponCleaner_CouponCodeFilter');
		$setting = $setting ? $setting : '';
		echo "<input id='WCCouponCleaner_CouponCodeFilterInput' name='WCCouponCleaner_CouponCodeFilter' type='text' value='" . esc_attr( $setting ) . "' />";

		// Help text below the field
		echo '<p>Only deletes coupon codes that contain this text within them; Leave blank to target all coupons.</p>';
	}
}

new WCCouponCleaner_Admin();
