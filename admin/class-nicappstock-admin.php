<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://efraim.cat
 * @since      1.0.0
 *
 * @package    Nicappstock
 * @subpackage Nicappstock/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Nicappstock
 * @subpackage Nicappstock/admin
 * @author     Efraim Bayarri <efraim@efraim.cat>
 */
class Nicappstock_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action('init', array( $this, 'nicappstock_custom_post_types' ));
		add_action('admin_menu', array( $this, 'addPluginAdminMenu' ), 9);
		add_action('add_meta_boxes_nicappstockproviders', array( $this, 'setupProvidersMetaboxes' ));
		add_action('add_meta_boxes_nicappstockproducts', array( $this, 'setupProductsMetaboxes' ));
		add_action('save_post_nicappstockproviders', array( $this, 'saveProvidersMetaBoxData' ));
		add_action('save_post_nicappstockproducts', array( $this, 'saveProductsMetaBoxData' ));
		add_filter('manage_nicappstockproviders_posts_columns', array( $this, 'nicappstockproviders_columns'));
		add_filter('manage_nicappstockproducts_posts_columns', array( $this, 'nicappstockproduct_columns'));
		add_action('manage_nicappstockproviders_posts_custom_column', array( $this, 'fill_nicappstockproviders_columns' ), 10, 2);
		add_action('manage_nicappstockproducts_posts_custom_column', array( $this, 'fill_nicappstockproducts_columns' ), 10, 2);
		add_filter('cron_schedules', array( $this, 'nicappstock_cron_schedules' ));
		add_action('admin_init', array( $this, 'CheckNewSchedule' ));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/nicappstock-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/nicappstock-admin.js', array( 'jquery' ), $this->version, false );

	}
	
	/**
	 * Register the Cron Job.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function nicappstockCron()
	{
	    $this->nicappstockMaintenance();
	    $this->nicappstockUpdateStock();
	}

	/**
	 * Register the Cron Job Schedule.
	 *
	 * @since 1.0.3
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function nicappstock_cron_schedules( $schedules )
	{
	    $interval = get_option( $this->plugin_name . '_ScheduleInterval' );
	    if ( $interval < 1 ) $interval = 60;
	    if (!isset ($schedules["nicappstockCronSchedule"]) ){
	        $schedules["nicappstockCronSchedule"] = array(
	            'interval' => 60*$interval,
	            'display' => __('Every', $this->plugin_name) . ' ' . $interval . ' ' . __('minutes', $this->plugin_name));
	    }
	    return $schedules;
	}
	
	/**
	 * Utility: scheduled job timestamp.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param
	 *            void
	 *
	 */
	private function scheduledJob()
	{
	    if (wp_next_scheduled('nicappstockCronJob')) {
	        $date_format = get_option('date_format');
	        $time_format = get_option('time_format');
	        esc_html_e(wp_date("{$date_format} {$time_format}", wp_next_scheduled('nicappstockCronJob'), get_option('timezone_string')));
	    } else {
	        _e('No scheduled jobs. No calendar entries will be checked.', $this->plugin_name);
	    }
	}
	
	/**
	 * Check New Schedule Interval
	 *
	 * @since 1.0.3
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function CheckNewSchedule()
	{
	    if (isset($_POST['NewScheduleInterval'])) {
	        $NewScheduleInterval = (int)$_POST['NewScheduleInterval'];
	        if ( $NewScheduleInterval < 1 || $NewScheduleInterval > 60*12 ){
	            $type = 'error';
	            $message = __( 'ERROR. Incorrect Interval', $this->plugin_name );
	        }else{
	            update_option( $this->plugin_name . '_ScheduleInterval', sanitize_text_field($_POST['NewScheduleInterval'] ));
	            add_filter( ‘cron_schedules’, array( $this, ‘nicappstock_cron_job_recurrence’ ) );
	            wp_clear_scheduled_hook('nicappstockCronJob');
	            wp_schedule_event(time(), 'nicappstockCronSchedule', 'nicappstockCronJob');
	            $type = 'updated';
	            $message = __( 'Successfully updated', $this->plugin_name ) . ' to ' . $NewScheduleInterval . ' ' . __( 'minutes', $this->plugin_name );
	        }
	        add_settings_error(
	            'NewScheduleInterval',
	            esc_attr( 'settings_updated' ),
	            $message,
	            $type
	            );
	    }
	}
	
	/**
	 * New Schedule Interval
	 *
	 * @since 1.0.3
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function nicappstock_cron_job_recurrence( $schedules ){
	    $interval = 60*(int)get_option( $this->plugin_name . '_ScheduleInterval' );
	    $schedules['nicappstockCronSchedule'] = array(
	        'interval' => $interval,
	        'display' => __('Every', $this->plugin_name) . get_option( $this->plugin_name . '_ScheduleInterval' ) . __('minutes', $this->plugin_name),
	    );
	    return $schedules;
	}
	
	/**
	 * Register Custom post types.
	 *
	 * @since    1.0.0
	 */
	public function nicappstock_custom_post_types(){
	    $customPostTypeArgs = array(
	        'label' => __('Nic-app Stock Providers', $this->plugin_name),
	        'labels'=>
	        array(
	            'name' => __('Providers', $this->plugin_name),
	            'singular_name' => __('Provider', $this->plugin_name),
	            'add_new' => __('Add Provider', $this->plugin_name),
	            'add_new_item' => __('Add New Provider', $this->plugin_name),
	            'edit_item' => __('Edit Provider', $this->plugin_name),
	            'new_item' => __('New Provider', $this->plugin_name),
	            'view_item' => __('View Provider', $this->plugin_name),
	            'search_items' => __('Search Provider', $this->plugin_name),
	            'not_found' => __('No Providers Found', $this->plugin_name),
	            'not_found_in_trash' => __('No Providers Found in Trash', $this->plugin_name),
	            'menu_name' => __('Providers', $this->plugin_name),
	            'name_admin_bar' => __('Providers', $this->plugin_name),
	        ),
	        'public'=>false,
	        'description' => __('Nic-app Stock Providers', $this->plugin_name),
	        'exclude_from_search' => true,
	        'show_ui' => true,
	        'show_in_menu' => $this->plugin_name,
	        'supports'=>array('title', 'custom_fields'),
	        'taxonomies'=>array());
	        
        // Post type, $args - the Post Type string can be MAX 20 characters
        register_post_type( 'nicappstockproviders', $customPostTypeArgs );
        //
        $customPostTypeArgs = array(
            'label' => __('Nic-app Stock Products', $this->plugin_name),
            'labels'=>
            array(
                'name' => __('Products', $this->plugin_name),
                'singular_name' => __('Product', $this->plugin_name),
                'add_new' => __('Add Product', $this->plugin_name),
                'add_new_item' => __('Add New Product', $this->plugin_name),
                'edit_item' => __('Edit Product', $this->plugin_name),
                'new_item' => __('New Product', $this->plugin_name),
                'view_item' => __('View Product', $this->plugin_name),
                'search_items' => __('Search Product', $this->plugin_name),
                'not_found' => __('No Products Found', $this->plugin_name),
                'not_found_in_trash' => __('No Products Found in Trash', $this->plugin_name),
                'menu_name' => __('Products', $this->plugin_name),
                'name_admin_bar' => __('Products', $this->plugin_name),
            ),
            'public'=>false,
            'description' => __('Nic-app Stock Products', $this->plugin_name),
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => $this->plugin_name,
            'supports'=>array('title', 'custom_fields'),
            'taxonomies'=>array());
            
        // Post type, $args - the Post Type string can be MAX 20 characters
        register_post_type( 'nicappstockproducts', $customPostTypeArgs );
	}
	
	/**
	 * Admin menu.
	 *
	 * @since    1.0.0
	 */
	public function addPluginAdminMenu() {
	    //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	    add_menu_page( 'Nic-app Stock', 'Nic-app Stock', 'administrator', $this->plugin_name, array( $this, 'display_plugin_admin_dashboard' ), plugin_dir_url(dirname(__FILE__)) . 'admin/img/nic-app-logo.png', 26 );
	    // add_submenu_page( '$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	    add_submenu_page($this->plugin_name, __('Nic-app Stock Scheduling', $this->plugin_name), __('Scheduling', $this->plugin_name), 'administrator', $this->plugin_name . '-scheduling', array( $this, 'displayPluginAdminScheduling' ));
	    add_submenu_page($this->plugin_name, __('Nic-app Stock Import-Export', $this->plugin_name), __('Import', $this->plugin_name), 'administrator', $this->plugin_name . '-import', array( $this, 'displayPluginAdminImport' ));
	}
	
	/**
	 * Admin menu display.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function display_plugin_admin_dashboard(){
	    require_once 'partials/nicappstock-admin-display.php';
	}
	
	/**
	 * Providers metaboxes.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function setupProvidersMetaboxes(){
	    add_meta_box('providers_data_meta_box', __('Providers information', $this->plugin_name), array($this,'providers_data_meta_box'), 'nicappstockproviders', 'normal', 'high');
	    remove_meta_box('wpseo_meta', 'nicappstockproviders', 'normal');
	    //add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	}
	
	/**
	 * Products metaboxes.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function setupProductsMetaboxes(){
	    add_meta_box('products_data_meta_box', __('Product information', $this->plugin_name), array($this,'products_data_meta_box'), 'nicappstockproducts', 'normal', 'high');
	    remove_meta_box('wpseo_meta', 'nicappstockproducts', 'normal');
	    //add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	}
	
	/**
	 * Providers metaboxes content.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function providers_data_meta_box( $post ){
	    wp_nonce_field($this->plugin_name . '_providers_meta_box', $this->plugin_name . '_providers_meta_box_nonce');
	    ?><div class="nicappstockproviders_containers"><?php
        ?><ul class="nicappstockproviders_data_metabox"><?php
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_providerURL' ); ?>"> <?php _e('Provider URL', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_providerURL',
            'name' => $this->plugin_name . '_providerURL',
            'required' => 'required',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        ?> <strong>(<?php esc_html_e($post->ID);?>)</strong></li><?php
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_consumerKey' ); ?>"> <?php _e('Consumer Key', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_consumerKey',
            'name' => $this->plugin_name . '_consumerKey',
            'required' => 'required',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        ?></li><?php
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_consumerSecret' ); ?>"> <?php _e('Consumer Secret', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_consumerSecret',
            'name' => $this->plugin_name . '_consumerSecret',
            'required' => 'required',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        ?></li><?php
        $notes = get_post_meta( $post->ID, $this->plugin_name.'_notes', true );
        $args = array( 'textarea_name' => $this->plugin_name.'_notes', );
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_notes' ); ?>"> <?php _e('Notes', $this->plugin_name);?></label> <?php
        wp_editor( $notes, $this->plugin_name.'_notes_editor',$args);
        ?></li><?php
	    ?></ul></div><?php 
	}
	
	/**
	 * Products metaboxes content.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param
	 *            void
	 *
	 */
	public function products_data_meta_box( $post ){
	    wp_nonce_field($this->plugin_name . '_products_meta_box', $this->plugin_name . '_products_meta_box_nonce');
	    ?><div class="nicappstockproducts_containers"><?php
        ?><ul class="nicappstockproducts_data_metabox"><?php
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_ProductSKU' ); ?>"> <?php _e('Product SKU', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_ProductSKU',
            'name' => $this->plugin_name . '_ProductSKU',
            'required' => 'required',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        ?></li><?php
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_VariantSKU' ); ?>"> <?php _e('Variant SKU', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_VariantSKU',
            'name' => $this->plugin_name . '_VariantSKU',
            'required' => '',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        ?></li><?php
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_ProviderProductSKU' ); ?>"> <?php _e('Provider Product SKU', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_ProviderProductSKU',
            'name' => $this->plugin_name . '_ProviderProductSKU',
            'required' => 'required',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_ProviderVariantSKU' ); ?>"> <?php _e('Provider Variant SKU', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_ProviderVariantSKU',
            'name' => $this->plugin_name . '_ProviderVariantSKU',
            'required' => '',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        ?></li><?php
        //
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_ProviderID' ); ?>"> <?php _e('Provider ID', $this->plugin_name);?></label> <?php
        $this->nicappstock_render_settings_field(array(
            'type' => 'input',
            'subtype' => 'text',
            'id' => $this->plugin_name . '_ProviderID',
            'name' => $this->plugin_name . '_ProviderID',
            'required' => 'required',
            'get_options_list' => '',
            'value_type' => 'normal',
            'wp_data' => 'post_meta',
            'post_id' => $post->ID,
        ));
        //
        ?></li><?php
        $notes = get_post_meta( $post->ID, $this->plugin_name.'_notes', true );
        $args = array( 'textarea_name' => $this->plugin_name.'_notes', );
        ?><li><label for="<?php esc_html_e($this->plugin_name . '_notes' ); ?>"> <?php _e('Notes', $this->plugin_name);?></label> <?php
        wp_editor( $notes, $this->plugin_name.'_notes_editor',$args);
        ?></li><?php
	    ?></ul></div><?php 
	}
	
	/**
	 * Providers Metabox Save fields.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $post_id
	 *
	 */
	public function saveProvidersMetaBoxData( $post_id ){
	    // Check if our nonce is set.
	    if (! isset($_POST[$this->plugin_name . '_providers_meta_box_nonce'])) return;
	    // Verify that the nonce is valid.
	    if (! wp_verify_nonce($_POST[$this->plugin_name . '_providers_meta_box_nonce'], $this->plugin_name . '_providers_meta_box')) return;
	    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	    // Check the user's permissions.
	    if (! current_user_can('manage_options')) return;
	    // Make sure that it is set.
	    if (! isset($_POST[$this->plugin_name . '_providerURL']) ) return;
	    
	    $providerURL = sanitize_text_field( $_POST[$this->plugin_name . '_providerURL'] );
	    $consumerKey = sanitize_text_field( $_POST[$this->plugin_name . '_consumerKey'] );
	    $consumerSecret = sanitize_text_field( $_POST[$this->plugin_name . '_consumerSecret'] );
	    $Notes = sanitize_text_field( $_POST[$this->plugin_name . '_notes'] );
	    
	    update_post_meta($post_id, $this->plugin_name . '_providerURL', $providerURL);
	    update_post_meta($post_id, $this->plugin_name . '_consumerKey', $consumerKey);
	    update_post_meta($post_id, $this->plugin_name . '_consumerSecret', $consumerSecret);
	    update_post_meta($post_id, $this->plugin_name . '_notes', $Notes);
	}
	
	/**
	 * Providers Metabox Save fields.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $post_id
	 *
	 */
	public function saveProductsMetaBoxData( $post_id ){
	    // Check if our nonce is set.
	    if (! isset($_POST[$this->plugin_name . '_products_meta_box_nonce'])) return;
	    // Verify that the nonce is valid.
	    if (! wp_verify_nonce($_POST[$this->plugin_name . '_products_meta_box_nonce'], $this->plugin_name . '_products_meta_box')) return;
	    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	    // Check the user's permissions.
	    if (! current_user_can('manage_options')) return;
	    // Make sure that it is set.
	    if (! isset($_POST[$this->plugin_name . '_ProductSKU']) ) return;
	    
	    $ProductSKU = sanitize_text_field( $_POST[$this->plugin_name . '_ProductSKU'] );
	    $VariantSKU = sanitize_text_field( $_POST[$this->plugin_name . '_VariantSKU'] );
	    $ProviderProductSKU = sanitize_text_field( $_POST[$this->plugin_name . '_ProviderProductSKU'] );
	    $ProviderVariantSKU = sanitize_text_field( $_POST[$this->plugin_name . '_ProviderVariantSKU'] );
	    $ProviderID = sanitize_text_field( $_POST[$this->plugin_name . '_ProviderID'] );
	    $Notes = sanitize_text_field( $_POST[$this->plugin_name . '_notes'] );
	    
	    update_post_meta($post_id, $this->plugin_name . '_ProductSKU', $ProductSKU);
	    update_post_meta($post_id, $this->plugin_name . '_VariantSKU', $VariantSKU);
	    update_post_meta($post_id, $this->plugin_name . '_ProviderProductSKU', $ProviderProductSKU);
	    update_post_meta($post_id, $this->plugin_name . '_ProviderVariantSKU', $ProviderVariantSKU);
	    update_post_meta($post_id, $this->plugin_name . '_ProviderID', $ProviderID);
	    update_post_meta($post_id, $this->plugin_name . '_notes', $Notes);
	}

	/**
	 * Display Columns in providers post type page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $columns
	 *
	 */
	public function nicappstockproviders_columns( $columns ){
	    return array(
	        'cb' => '<input type="checkbox" />',
	        'provider' => __('Provider', $this->plugin_name),
	        'URL' => __('URL', $this->plugin_name),
	        'providerID' => __('Provider ID', $this->plugin_name),
	    );
	}

	/**
	 * Display Columns in products post type page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $columns
	 *
	 */
	public function nicappstockproduct_columns( $columns ){
	    return array(
	        'cb' => '<input type="checkbox" />',
	        'Product' => __('Product', $this->plugin_name),
	        'ProductSKU' => __('Product SKU', $this->plugin_name),
	        'VariantSKU' => __('Variant SKU', $this->plugin_name),
	        'ProviderProductSKU' => __('Provider Product SKU', $this->plugin_name),
	        'ProviderVariantSKU' => __('Provider Variant SKU', $this->plugin_name),
	        'Provider' => __('Provider', $this->plugin_name),
	        'ProviderID' => __('Provider ID', $this->plugin_name),
	    );
	}
	
	/**
	 * Fill Columns in providers post type page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $column
	 *
	 * @param string $postID
	 *
	 */
	public function fill_nicappstockproviders_columns( $column, $postID ){
	    switch ( $column ) {
	        case 'provider':
	            esc_html_e( get_the_title( $postID ) );
	            break;
	        case 'providerID':
	            esc_html_e( $postID );
	            break;
	        case 'URL':
	            esc_html_e( get_post_meta($postID, $this->plugin_name . '_providerURL', true) );
	            break;
	    }
	}
	
	/**
	 * Fill Columns in products post type page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $column
	 *
	 * @param string $postID
	 *
	 */
	public function fill_nicappstockproducts_columns( $column, $postID ){
	    switch ( $column ) {
	        case 'Product':
	            esc_html_e( get_the_title( $postID ) );
	            break;
	        case 'ProductSKU':
	            esc_html_e( get_post_meta($postID, $this->plugin_name . '_ProductSKU', true) );
	            if( !empty( get_post_meta($postID, $this->plugin_name . '_ProductSKU', true) ) ){
	                $product_id = wc_get_product_id_by_sku( get_post_meta($postID, $this->plugin_name . '_ProductSKU', true) );
	                ( $product_id > 0 ) ? esc_html_e('') : esc_html_e('!');
	            }
	            break;
	        case 'VariantSKU':
	            esc_html_e( get_post_meta($postID, $this->plugin_name . '_VariantSKU', true) );
	            break;
	        case 'ProviderProductSKU':
	            esc_html_e( get_post_meta($postID, $this->plugin_name . '_ProviderProductSKU', true) );
	            break;
	        case 'ProviderVariantSKU':
	            esc_html_e( get_post_meta($postID, $this->plugin_name . '_ProviderVariantSKU', true) );
	            break;
	        case 'ProviderID':
	            esc_html_e( get_post_meta($postID, $this->plugin_name . '_ProviderID', true) );
	            break;
	        case 'Provider':
	            if( !empty( get_post_meta($postID, $this->plugin_name . '_ProviderID', true) ))
	                esc_html_e( get_the_title( get_post_meta($postID, $this->plugin_name . '_ProviderID', true) ) );
	            break;
	    }
	}
	
	/**
	 * Custom Post Type Metabox Render fields.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $args
	 *
	 */
	public function nicappstock_render_settings_field($args){
	    if ($args['wp_data'] == 'option') {
	        $wp_data_value = get_option($args['name']);
	    } elseif ($args['wp_data'] == 'post_meta') {
	        $wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
	    }
	    
	    switch ($args['type']) {
	        case 'input':
	            $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
	            if ($args['subtype'] != 'checkbox') {
	                $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
	                $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                    $step = (isset($args['step'])) ? 'step="' . $args['step'] . '"' : '';
                    $min = (isset($args['min'])) ? 'min="' . $args['min'] . '"' : '';
                    $max = (isset($args['max'])) ? 'max="' . $args['max'] . '"' : '';
                    $size = (isset($args['size'])) ? 'size="' . $args['size'] . '"' : 'size="40"';
                    if (isset($args['disabled'])) {
	                        // hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
                        echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '_disabled" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '_disabled" ' . $size . ' disabled value="' . esc_attr($value) . '" /><input type="hidden" id="' . $args['id'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
                    } else {
                        echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" ' . $size . ' value="' . esc_attr($value) . '" />' . $prependEnd;
                    }
	                    /* <input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" /> */
	            } else {
	                $checked = ($value) ? 'checked' : '';
	                ?><input type="<?php esc_html_e( $args['subtype'] ); ?>" id="<?php esc_html_e( $args['id'] ); ?>" <?php esc_html_e( $args['required'] ); ?> name="<?php esc_html_e( $args['name'] ); ?>" size="40" value="1" <?php esc_html_e( $checked ); ?> /><?php
                }
                break;
            default:
                break;
        }
    }
    
    /**
     * Display Admin settings error messages.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            $error_message
     *
     */
    public function pluginNameSettingsMessages($error_message)
    {
        switch ($error_message) {
            case '1':
                $message = __('There was an error adding this setting. Please try again.  If this persists, shoot us an email.', $this->plugin_name);
                $err_code = esc_attr('nicappstock_setting');
                $setting_field = 'nicappstock_setting';
                break;
        }
        $type = 'error';
        add_settings_error($setting_field, $err_code, $message, $type);
    }
    
    /**
     * Display Stock Scheduling.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     *
     */
    public function displayPluginAdminScheduling(){
        if (isset($_GET['error_message'])) {
            add_action('admin_notices', array( $this, 'pluginNameSettingsMessages' ));
            do_action('admin_notices', sanitize_text_field($_GET['error_message']));
        }
        require_once 'partials/' . $this->plugin_name . '-admin-scheduling-display.php';
    }
    
    /**
     * Display Stock Import/Export.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     *
     */
    public function displayPluginAdminImport(){
        if (isset($_GET['error_message'])) {
            add_action('admin_notices', array( $this, 'pluginNameSettingsMessages' ));
            do_action('admin_notices', sanitize_text_field($_GET['error_message']));
        }
        require_once 'partials/' . $this->plugin_name . '-admin-import-display.php';
    }
    
    /*
     * 
     * CHECK STOCK
     * 
     */
    
    /**
     * Update Stock
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     *            
     * Loops on all products.           
     */
    public function nicappstockUpdateStock(){
        global $post;
        $time_from = new DateTime();
        $this->custom_logs('nicappstockUpdateStock begins.');
        $args = array(
            'post_type' => 'nicappstockproducts',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        $loop = new WP_Query( $args ); 
        while ( $loop->have_posts() ) : $loop->the_post(); 
            $this->UpdateStockProduct( $post->ID );
        endwhile;
        wp_reset_postdata();
        $timediff = $time_from->diff( new DateTime() );
        $this->custom_logs('nicappstockUpdateStock time: ' . $timediff->format( '%h hours %i minutes %s seconds' ));
        $this->custom_logs('nicappstockUpdateStock ends.');
        $this->custom_logs('---');
    }
    
    /**
     * Update Stock Product
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     * Checks if product definition is consistent. Then sends the call to Simple or Variation update stock function.           
     */
    private function UpdateStockProduct( $postID ){
        if( empty( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ) ) || 
            empty( get_post_meta( $postID, $this->plugin_name . '_ProductSKU', true ) ) || 
            empty( get_post_meta( $postID, $this->plugin_name . '_ProviderProductSKU', true )) ) return;
        if( empty( get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_providerURL', true ) ) || 
            empty( get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_consumerKey', true ) ) || 
            empty( get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_consumerSecret', true ) ) ) return;
        if( empty( get_post_meta( $postID, $this->plugin_name . '_ProviderVariantSKU', true ) ) ){
            $this->UpdateStockProductSimple( $postID );
        }else{
            $this->UpdateStockProductVariant( $postID );
        }
    }

    /**
     * Update Stock Product Simple
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     * Conects to provider, checks is product exists and get the stock quantity. Then passes the call to product stock change function.           
     */
    private function UpdateStockProductSimple( $postID ){
        $this->woocommerce = new Automattic\WooCommerce\Client(
            get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_providerURL', true ),
            get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_consumerKey', true ),
            get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_consumerSecret', true ),
            [ 'wp_api' => true, 'version' => 'wc/v2', ]
        );
        try{
            $results = $this->woocommerce->get('products', ['sku' => get_post_meta( $postID, $this->plugin_name . '_ProviderProductSKU', true )] );
        } catch (Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            $this->custom_logs('UpdateStockProductsimple ERROR. ' . $e->getMessage() );
            return;
        }
        if( empty( $results ) ) {
            $this->custom_logs('UpdateStockProductSimple. ERROR: Provider non existing product.' );
            return;
        }
        if( empty( get_post_meta( $postID, $this->plugin_name . '_ProviderVariantSKU', true ) ) ){ //local product is not a variantion
            $this->UpdateProduct( $postID, get_post_meta( $postID, $this->plugin_name . '_ProductSKU', true ), $results[0]->stock_quantity );
        }else{
            $this->UpdateProduct( $postID, get_post_meta( $postID, $this->plugin_name . '_VariantSKU', true ), $results[0]->stock_quantity );
        }
    }
    
    /**
     * Update Stock Product Variant
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     * Conects to provider, checks is product exists and get the stock quantity. Then passes the call to product stock change function.
     */
    private function UpdateStockProductVariant( $postID ){
        $this->woocommerce = new Automattic\WooCommerce\Client(
            get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_providerURL', true ),
            get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_consumerKey', true ),
            get_post_meta( get_post_meta( $postID, $this->plugin_name . '_ProviderID', true ), $this->plugin_name . '_consumerSecret', true ),
            [ 'wp_api' => true, 'version' => 'wc/v2', ]
            );
        try {
            $results = $this->woocommerce->get('products', ['sku' => get_post_meta( $postID, $this->plugin_name . '_ProviderProductSKU', true )] );
        } catch (Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            $this->custom_logs('UpdateStockProductVariant ERROR. ' . $e->getMessage() );
            return;
        }
        $ProviderProductID = $results[0]->id;
        if( empty( $ProviderProductID ) ){
            $this->custom_logs('UpdateStockProductVariant. ERROR: Provider non existing product.' );
            return;
        }
        try {
            $variations = $this->woocommerce->get('products/' . $ProviderProductID . '/variations' );
        } catch (Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            $this->custom_logs('UpdateStockProductVariant variations ERROR. ' . $e->getMessage() );
            return;
        }
        if( empty( $variations ) ) {
            $this->custom_logs('UpdateStockProductVariant. ERROR: Provider non existing variant.' );
            return;
        }
        foreach( $variations as $variation ){
            if( $variation->sku == get_post_meta( $postID, $this->plugin_name . '_ProviderVariantSKU', true ) ){
                if( empty( get_post_meta( $postID, $this->plugin_name . '_VariantSKU', true ) ) ){ //local product is not a variantion
                    $this->UpdateProduct( $postID, get_post_meta( $postID, $this->plugin_name . '_ProductSKU', true ), $variation->stock_quantity );
                }else{
                    $this->UpdateProduct( $postID, get_post_meta( $postID, $this->plugin_name . '_VariantSKU', true ), $variation->stock_quantity );
                }
            }
        }
    }
    
    /**
     * Update Stock
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     * updates product stocks.            
     */
    private function UpdateProduct( $postID, $ProductSKU, $stock_quantity ){
        $product_id = wc_get_product_id_by_sku( $ProductSKU );
        if( $product_id == 0){
            $this->custom_logs('UpdateProduct. ERROR: Local non existing product.' );
            return;
        }
        $product = wc_get_product( $product_id );
        $old_stock = $product->get_stock_quantity();
        $product->set_stock_quantity( $stock_quantity );
        $product->save();
        $this->custom_logs('UpdateProduct: ' . $postID . ' : ' . get_the_title( $postID ). '. Changed From ' . $old_stock . ' To ' . $stock_quantity );
    }
    
    /*
     * 
     * UTILITIES
     * 
     */

    /**
     * Upload File
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     *
     */
    private function UploadFile(){
        if(isset($_POST['but_submit'])){
            if($_FILES['file']['name'] != ''){
                $uploadedfile = $_FILES['file'];
                $upload_overrides = array( 'test_form' => false );
                
                $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
                if ( $movefile && ! isset( $movefile['error'] ) ) {
                    $this->ImportUploadFile( $movefile['file'] );
                } else {
                    esc_html_e( $movefile['error'] ) ;
                }
            }
        }
    }
    
    /**
     * Upload File
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     *
     */
    private function ImportUploadFile( $filename ){
        ?><h2><?php _e('Importing file', $this->plugin_name); ?></h2><?php esc_html_e( basename( $filename ) );
		$info = pathinfo( $filename );
		if ( $info['extension'] == 'csv'){
			$open = fopen( $filename, "r" );
			$the_big_array = [];
			$count = 0;
		    while ( ( $data = fgetcsv( $open, 1000, "," ) ) !== FALSE) {
                $the_big_array[] = $data;
            }
			fclose( $open );
			$this->custom_logs('ImportUploadFile begins.');
			foreach( $the_big_array as $key=>$fileline ){
			    if ( $this->UploadFileLine( $key, $fileline ) ) $count++ ;
			}
			$this->custom_logs('ImportUploadFile ends. ' . $count . ' imports.' );
			$this->custom_logs('---');
			esc_html_e( ' ' . __('Entries Processed:', $this->plugin_name ) . ' ' . $count );
		}else{
		    ?><h2><?php esc_html_e( 'ERROR:' . ' ' . basename( $filename ) . ' ' . __('Invalid file type', $this->plugin_name) ); ?></h2><?php
		}
		unlink( $filename );
    }

    /**
     * Explore Upload File Line
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     *
     *  [0] Provider
     *  [1] Product SKU
     *  [2] Variant SKU
     *  [3] Provider Product SKU
     *  [4] Provider Variant SKU
     *  [5] Title
     *  Rest of columns ignored
     */
    private function UploadFileLine(  $key, $fileline ){
        // first line with fields
        if( $key == 0 ) return false;
        // Empty Provider
        if ( empty( sanitize_text_field( $fileline[0] ) ) ){
            $this->custom_logs('UploadFileLine: ' . $key . ' -> Empty Provider' );
            return false;
        }
        // Empty SKU
        if ( empty( sanitize_text_field( $fileline[1] ) ) ){
            $this->custom_logs('UploadFileLine: ' . $key . ' -> Empty SKU' );
            return false;
        }
        // Empty Provider SKU
        if ( empty( sanitize_text_field( $fileline[3] ) ) ){
            $this->custom_logs('UploadFileLine: ' . $key . ' -> Empty Provider SKU' );
            return false;
        }
        global $post;
        $args = array(
            'post_type'        => 'nicappstockproducts',
            'order'            => 'ASC',
            'orderby'          => 'meta_value',
            'posts_per_page'   => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => $this->plugin_name . '_ProductSKU',
                    'value' => sanitize_text_field( $fileline[1] ),
                    'compare' => '=',
                ),
                array(
                    'key' => $this->plugin_name . '_VariantSKU',
                    'value' => sanitize_text_field( $fileline[2] ),
                    'compare' => '=',
                ),
                array(
                    'key' => $this->plugin_name . '_ProviderID',
                    'value' => sanitize_text_field( $fileline[0] ),
                    'compare' => '=',
                )
            )
        );
        $the_query  = new WP_Query( $args );
        if ( $the_query->have_posts() ) {
            $this->custom_logs('UploadFileLine: ' . $key . ' -> Existing Product. Updating. SKU: ' . sanitize_text_field( $fileline[1] ) . ' Variant SKU: ' . sanitize_text_field( $fileline[2] ));
            $the_query->the_post();
            $post_update = array(
                'ID'         => $post->ID,
                'post_title' => sanitize_text_field( $fileline[5] )
            );
            wp_update_post( $post_update );
            update_post_meta($post->ID, $this->plugin_name . '_ProductSKU', sanitize_text_field( $fileline[1] ));
            update_post_meta($post->ID, $this->plugin_name . '_VariantSKU', sanitize_text_field( $fileline[2] ));
            update_post_meta($post->ID, $this->plugin_name . '_ProviderProductSKU', sanitize_text_field( $fileline[3] ));
            update_post_meta($post->ID, $this->plugin_name . '_ProviderVariantSKU', sanitize_text_field( $fileline[4] ));
            update_post_meta($post->ID, $this->plugin_name . '_ProviderID', sanitize_text_field( $fileline[0] ));
        } else {
            $this->custom_logs('UploadFileLine: ' . $key . ' -> Non Existing Product. Creating. SKU: ' . sanitize_text_field( $fileline[1] ) );
            $post_arr = array(
                'post_title'   => $fileline[5],
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'nicappstockproducts',
                'meta_input'   => array(
                    $this->plugin_name . '_ProviderID' => sanitize_text_field( $fileline[0] ),
                    $this->plugin_name . '_ProductSKU' => sanitize_text_field( $fileline[1] ),
                    $this->plugin_name . '_VariantSKU' => sanitize_text_field( $fileline[2] ),
                    $this->plugin_name . '_ProviderProductSKU' => sanitize_text_field( $fileline[3] ),
                    $this->plugin_name . '_ProviderVariantSKU' => sanitize_text_field( $fileline[4] ),
                ),
            );
            wp_insert_post( $post_arr );
        }
        wp_reset_postdata();
        return true;
    }
        
    /**
     * Download File
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     *
     */
    private function DownloadFile(){
        if(isset($_POST['but_download'])){
            $args = array(
                'post_type' => 'nicappstockproducts',
                'orderby' => $orderby,
                'posts_per_page'=>-1,
            );
            $wp_query = new WP_Query($args);
            $upload_dir = wp_upload_dir();
            $file = $upload_dir['basedir'] . '/nicappstock-logs/nicappstock-download.csv';
            unlink( $file );
            $open = fopen( $file, "a" );
            $message = 'ProviderID,ProductSKU,VariantSKU,ProviderProductSKU,ProviderVariantSKU,Name';
            $ban = "$message\r\n";
            fputs( $open, $ban );
            while ($wp_query->have_posts()) : $wp_query->the_post();
                $product_id = get_the_ID() ;
                $message = get_post_meta($product_id, $this->plugin_name . '_ProviderID', true);
                $message .= ',' . get_post_meta($product_id, $this->plugin_name . '_ProductSKU', true);
                $message .= ',' . get_post_meta($product_id, $this->plugin_name . '_VariantSKU', true);
                $message .= ',' . get_post_meta($product_id, $this->plugin_name . '_ProviderProductSKU', true);
                $message .= ',' . get_post_meta($product_id, $this->plugin_name . '_ProviderVariantSKU', true);
                $message .= ',' . get_the_title();
                $ban = "$message\r\n";
                fputs( $open, $ban );
            endwhile;
            fclose( $open );
            wp_reset_query();
        }
    }
    
    /**
     * Cron job maintenance tasks.
     *
     * @since 1.0.0
     * @access protected
     * @param
     *            void
     *
     */
    protected function nicappstockMaintenance()
    {
        $this->custom_logs('nicappstockMaintenance begins');
        $upload_dir = wp_upload_dir();
        $files = scandir( $upload_dir['basedir'] . '/nicappstock-logs' );
        foreach ($files as $file) {
            if (substr($file, - 4) == '.log') {
                $this->custom_logs('Logfile: ' . $file . ' -> ' . date("d-m-Y H:i:s", filemtime( $upload_dir['basedir'] . '/nicappstock-logs/' . $file)));
                if (time() > strtotime('+1 week', filemtime( $upload_dir['basedir'] . '/nicappstock-logs/' . $file))) {
                    $this->custom_logs('Old logfile');
                    unlink( $upload_dir['basedir'] . '/nicappstock-logs/' . $file);
                }
            }
        }
        $this->custom_logs('nicappstockMaintenance ends');
        $this->custom_logs('---');
        return;
    }
    
    
    /**
     * Utility: log files.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     *
     */
    private function logFiles()
    {
        $upload_dir = wp_upload_dir();
        $files = scandir( $upload_dir['basedir'] . '/nicappstock-logs' );
        ?>
			<form action="" method="post">
				<ul>	
					<?php foreach ( $files as $file ) { ?>
						<?php if( substr( $file , -4) == '.log'){?>
							<li><input type="radio" id="age[]" name="logfile" value="<?php esc_html_e( $file ); ?>">
								<?php esc_html_e( $file . ' -> ' . date("d-m-Y H:i:s", filemtime( $upload_dir['basedir'] . '/nicappstock-logs/' . $file  ) ) ); ?>
							</li>
						<?php }?>
					<?php }?>
				</ul>
				<div class="nicappstock-send-logfile">
					<input type="submit" value="<?php _e( 'View log file', $this->plugin_name ); ?>">
				</div>
			</form>
		<?php
    }

    /**
     * Utility: show log file.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     *            
     */
    private function ShowLogFile()
    {
        $upload_dir = wp_upload_dir();
        if (isset($_POST['logfile'])) {
            ?>
				<hr />
				<h3><?php esc_html_e( $_POST['logfile'] ); ?> </h3>
				<textarea id="nicappstocklogfile" name="nicappstocklogfile" rows="30" cols="180" readonly>
					<?php esc_html_e( ( file_get_contents( $upload_dir['basedir'] . '/nicappstock-logs/' . $_POST['logfile'] ) ) ); ?>
				</textarea>
			<?php
        }
    }
    
    /**
     * Utility: create entry in the log file.
     *
     * @since 1.0.0
     * @access private
     * @param string|array $message
     *
     */
    private function custom_logs($message){
        $upload_dir = wp_upload_dir();
        if (is_array($message)) {
            $message = json_encode($message);
        }
        if (!file_exists( $upload_dir['basedir'] . '/nicappstock-logs') ) {
            mkdir( $upload_dir['basedir'] . '/nicappstock-logs' );
        }
        $time = date("Y-m-d H:i:s");
        $ban = "#$time: $message\r\n";
        $file = $upload_dir['basedir'] . '/nicappstock-logs/nicappstock-log-' . date("Y-m-d") . '.log';
        $open = fopen($file, "a");
        fputs($open, $ban);
        fclose( $open );
    }
}
