<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://efraim.cat
 * @since      1.0.0
 *
 * @package    Nicappstock
 * @subpackage Nicappstock/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
	<h2><?php esc_html_e( get_admin_page_title() .' '.$this->version); ?></h2>
	<!--NEED THE settings_errors below so that the errors/success messages are shown after submission - wasn't working once we started using add_menu_page and stopped using add_options_page so needed this-->
	<?php settings_errors(); ?>
	<h3><?php _e( 'Nic-app Stock Settings', $this->plugin_name )?></h3>
	<img
		src="<?php esc_html_e( plugin_dir_url( __DIR__ ) . 'img/' ); ?>NIC-APP-03-350x233.png"
		alt="nic-app" width="350" height="233">
	<hr />
	<div id="nicappstock-intro">
		<p><?php _e( 'Nic-app Stock.', $this->plugin_name ); ?></p>
		<p>
			<?php _e( 'Configuration is an important part of proper operation and must be given the necessary attention. You can find all the necessary help in', $this->plugin_name ); ?>
			<a href="mailto:efraim@efraim.cat">efraim@efraim.cat</a>
		</p>
	</div>
	<hr />
	<div id="nicappstock-info">
		<p><?php _e( 'This plugin creates a relationship between the SKU of the local product of its variation and another product defined in another WooCommerce system and updates the local stock with which the supplier has.', $this->plugin_name ); ?></p>
		<p><?php _e( 'The connection is made through the WooCommerce REST API. You need to have a REST API key on the provider\'s system. You can find information in this', $this->plugin_name ); ?>
		<a href="https://docs.woocommerce.com/document/woocommerce-rest-api/" target="_blank"> <?php _e('document', $this->plugin_name)?> </a> 
		<?php _e( 'on how to create the key.', $this->plugin_name ); ?></p>
		<p><?php _e( 'The key is assigned to a Wordpress user with sufficient rights. It is not necessary to know the user\'s data such as name or password since all communication is done from the REST API.', $this->plugin_name ); ?></p>
		<p><?php _e( 'This REST API key only needs to have read rights. Remember to take note of the consumer key and the consumer secret as it is only displayed during key creation.', $this->plugin_name ); ?></p>
	</div>
	<hr />
	<p>
		<a href="mailto:efraim@efraim.cat" > <?php _e( 'Contact us', $this->plugin_name )?></a> <?php _e( 'with any questions.', $this->plugin_name ); ?></p>
</div>