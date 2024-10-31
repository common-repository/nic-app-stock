<?php 

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://efraim.cat
 * @since      1.0.0
 *
 * @package    Nicappcrono
 * @subpackage Nicappcrono/admin/partials
 */

?>
<div class="wrap">
	<div id="icon-themes" class="icon32"></div>
	<h2><?php esc_html_e( get_admin_page_title() .' '.$this->version ); ?></h2>
	<?php settings_errors(); ?>
	<h1>Import / Export products</h1>
	<div id="nicappstockimport">
		<p><?php _e('You can import a file regularly with the definition of the products that you want to synchronize with other WooCommerce in CSV format.', $this->plugin_name)?></p>
		<p><?php _e('This file must have fixed columns with the necessary information. The first is the provider number (Provider ID) that you will find in the providers section.', $this->plugin_name)?></p>
		<p><?php _e('The second and third column the local SKU of the product and its variation if it has it.', $this->plugin_name)?></p>
		<p><?php _e('The fourth and fifth the SKU of the supplier\'s product and its variation if it has it.', $this->plugin_name)?></p>
		<p><?php _e('The last column is the name of the product. It is only used within the plugin in the products section. It is independent of the name that is displayed in the store', $this->plugin_name)?></p>
		<p><?php _e('If the SKU and vendor number match one that already exists, the product is updated. If not, a new product is created.', $this->plugin_name)?></p>
		<p><?php _e('At any time you can download a file with the current definitions.', $this->plugin_name)?></p>
	</div>
	<hr />
	<div id="nicappstockbuttons">
	<h1>Upload File</h1>
    <!-- Form -->
	<form method='post' action='' name='myform' enctype='multipart/form-data'>
  		<table>
    		<tr>
      			<td><?php _e( 'Upload file', $this->plugin_name ); ?></td>
      			<td><input type='file' name='file'></td>
    		</tr>
    		<tr>
    	  		<td>&nbsp;</td>
      			<td><input type='submit' name='but_submit' "<?php _e( 'Submit', $this->plugin_name ); ?>"></td>
    		</tr>
  		</table>
	</form>
	<hr />
	<h1>Download File</h1>
	<?php if(isset($_POST['but_download'])){
	    $upload_dir = wp_upload_dir();
	    ?><button><a href="<?php esc_html_e($upload_dir['baseurl'] . '/nicappstock-logs/nicappstock-download.csv'); ?>"><?php _e('Download CSV');?></a></button><?php
	}else{ ?>
    <!-- Form -->
	<form method='post' action='' name='myform' enctype='multipart/form-data'>
  		<table>
    		<tr>
    	  		<td>&nbsp;</td>
      			<td><input type='submit' name='but_download' "<?php _e( 'Download', $this->plugin_name ); ?>"></td>
    		</tr>
  		</table>
	</form>
	<?php }?>
	<hr />
	</div>
	<?php $this->UploadFile(); ?>
	<?php $this->DownloadFile(); ?>
</div>
