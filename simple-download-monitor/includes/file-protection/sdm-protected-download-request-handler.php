<?php

class SDM_Protected_Download_Request_Handler
{
	public function __construct() {
		if(SDM_File_Protection_Handler::is_file_protection_enabled()){
			add_action('sdm_process_download_request', array($this, 'handle_process_protected_download_request'), 10, 2);
		}
	}

	public function handle_process_protected_download_request($download_id, $download_link){
		$main_option = get_option( 'sdm_downloads_options' );

		// Get protected directory file path
		$file_path = SDM_Utils_File_System_Related::get_uploaded_file_path_from_url(
			$download_link,
			 	array(
					'uploads',
					SDM_File_Protection_Handler::get_upload_dir()
				)
			);

		// Check if the download link is located inside our protected folder.
		if(! is_file( $file_path ) ){
			// This is not a protected file path, nothing to do here.
			return;
		}

		$is_hidden_or_noext_file_disallowed = empty( $main_option['general_allow_hidden_noext_dispatch'] );
		//Check if hidden or no-extension file download option is allowed.
		if( $is_hidden_or_noext_file_disallowed ){
			//Hidden or no-extension file download is NOT allowed. Let's check if this is request for a hidden or no-ext file download.
			if ( SDM_Utils_File_System_Related::check_is_hidden_or_no_extension_file($file_path) ) {
				// Found a hidden or no-ext file. Do not use PHP dispatch.
				wp_die(__('Hidden file or no extension filename detected. File could not be dispatched!', 'simple-download-monitor'));
			}
		}

		// Check if the file extension is disallowed.
		if ( ! SDM_Utils_File_System_Related::check_is_file_extension_allowed($file_path) ) {
			// Disallowed file extension; Don't use PHP dispatching (instead use the normal redirect).
			wp_die(__('Disallowed extension, file could not be dispatched!', 'simple-download-monitor'));
		}

		// TODO: Add code to choose file dispatcher function.
		// Try to dispatch file (terminates script execution on success)

		// Dispatch the file and check if response is true. If not, then response is a message string.
		$response = self::sdm_download_using_fopen($file_path);
		if ($response !== true) {
			trigger_error( $response );
		}
		exit;
	}

	public static function sdm_download_using_fopen($file_path, $chunk_blocks = 8, $session_close = false) {
		SDM_Debug::log('Trying to dispatch file using fopen.', true);
		$file_name = basename($file_path);
		// Download methods #1, #2, #4 and #5.
		// -- The Assurer, 2010-10-22.
		$chunk_size = 1024 * $chunk_blocks; // Number of bytes per chunk.
		$fp = @fopen($file_path, "rb"); // Open source file.
		if ($fp === false) {
			// File could not be opened...
			return "Error on fopen('$file_path')"; // Catch any fopen() problems.
		}
		if ($session_close) {
			@session_write_close();
		}
		// Close current session, if requested.
		// Write headers to browser...
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		$mimetype = SDM_Utils_File_System_Related::dl_file_type($file_path);
		header("Content-Type: " . $mimetype);
		header("Content-Disposition: attachment; filename=\"$file_name\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . SDM_Utils_File_System_Related::dl_filesize($file_path));
	
		//Trigger an action hook so additional headers can be added from a 3rd party plugin or custom code.
		do_action('wpec_fopen_after_download_headers', $file_path);
	
		$chunks_transferred = 0; // Reset chunks transferred counter.
		while (!feof($fp)) {
			// Process source file in $chunk_size byte chunks...
			$chunk = @fread($fp, $chunk_size); // Read one chunk from the source file.
			if ($chunk === false) {
				// A read error occurred...
				@fclose($fp);
				return 'Error on fread() after ' . number_format($chunks_transferred) . ' chunks transferred.';
			}
			// Chunk was successfully read...
			print($chunk); // Send the chunk on its way.
			flush(); // Flush the PHP output buffers.
			$chunks_transferred += 1; // Increment the transferred chunk counter.
			// Check connection status...
			// Note: it is a known problem that, more often than not, connection_status() will always return a 0...  8(
			$constat = connection_status();
			if ($constat != 0) {
				// Something happened to the browser connection...
				@fclose($fp);
				switch ($constat) {
					case 1:
						return 'Connection aborted by client.';
					case 2:
						return 'Connection timeout.';
					default:
						return "Unrecognized connection_status(). Value: " . $constat;
				}
			}
		}
		// Well, we finally made it without detecting any server-side errors!
		@fclose($fp); // Close the source file.
		return true; // Success!
	}
	
}

new SDM_Protected_Download_Request_Handler();