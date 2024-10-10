<?php

class SDM_Admin_File_Protection_Settings_Page {
	public function __construct() {
		$this->show_file_protection_settings();
	}

	public function show_file_protection_settings() {
        if (isset($_POST['sdm_file_protection_settings_submit']) && check_admin_referer('sdm_file_protection_settings_nonce_action')){
	        $settings = get_option('sdm_global_options', array());

            // Prepare settings array to save.
            $settings['file_protection_enable'] = isset($_POST['file_protection_enable']) ? 'checked="checked"' : '';

	        // Save the settings.
	        update_option('sdm_global_options', $settings);

            // Show settings update message.
	        echo '<div class="notice notice-success"><p>' . __('File protection settings updated successfully.', 'simple-download-monitor') . '</p></div>';

            // Trigger a action on settings update.
            do_action('sdm_file_protection_settings_updated');
        }

		$settings = get_option('sdm_global_options', array());

        $enable_file_protection = isset($settings['file_protection_enable']) && !empty($settings['file_protection_enable']) ? $settings['file_protection_enable'] : '';

		?>

        <h2><?php _e('File Protection Settings', 'simple-download-monitor') ?></h2>
        <p>
            <?php _e('Manage your file protection settings in this section. Read ', 'simple-download-monitor') ?><a href="https://simple-download-monitor.com/enhanced-file-protection-securing-your-downloads/" target="_blank"><?php _e('this guide', 'simple-download-monitor') ?></a><?php _e(' to learn more about the file protection feature.', 'simple-download-monitor') ?>
        </p>

        <form action="" method="post">
	        <?php if ( SDM_Utils_File_System_Related::is_nginx_server() ) { ?>
                <div class="notice inline notice-warning notice-alt">
                    <p>
				        <?php _e( 'Your website is using an Nginx server. To enable this file protection feature, please update the server configuration manually. ', 'simple-download-monitor' ) ?>
                    </p>
                    <p>
				        <?php _e( 'Add the following rule to your virtual host configuration file:', 'simple-download-monitor' ) ?>
                    </p>

                    <textarea rows="3" cols="50" readonly class="" style="white-space: pre; font-family: monospace; overflow: hidden; padding: 5px 8px; resize:none;">
location ~ ^/wp-content/uploads/<?php echo SDM_File_Protection_Handler::get_protected_dir_name() ?>/ {
	deny all;
}
				</textarea>
                    <p>
				        <?php _e( '<a href="https://simple-download-monitor.com/enhanced-file-protection-securing-your-downloads/#server-configuration-requirements" target="_blank">Read the full documentation</a> on how to configure the file protection feature on an Nginx server.', 'simple-download-monitor' ); ?>
                    </p>
                </div>
	        <?php } ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Enable File Protection', 'simple-download-monitor') ?></th>
                        <td>
                            <input name="file_protection_enable" id="file_protection_enable" type="checkbox" <?php echo esc_attr($enable_file_protection) ?>>
                            <p class="description"><?php _e('Check this box to enable the file protection feature.', 'simple-download-monitor') ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php echo wp_nonce_field('sdm_file_protection_settings_nonce_action') ?>

            <p class="submit">
                <input type="submit" name="sdm_file_protection_settings_submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'simple-download-monitor') ?>">
            </p>

        </form>
		<?php
	}
}