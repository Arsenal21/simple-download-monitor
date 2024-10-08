<?php

class SDM_Utils_File_System_Related {

	public static function get_server_software(){
		return $_SERVER['SERVER_SOFTWARE'];
	}

	public static function get_php_version() {
		return phpversion();
	}

	public static function get_server_os() {
		return php_uname();
	}

	public static function is_nginx_server() {
		$server_software = self::get_server_software();
		$pattern = "/nginx/i";
		if (preg_match($pattern, $server_software)) {
			return true;
		}

		return false;
	}

	public static function is_apache_server() {
		$server_software = self::get_server_software();
		$pattern = "/apache/i";
		if (preg_match($pattern, $server_software)) {
			return true;
		}

		return false;
	}

	public static function is_litespeed_server() {
		$server_software = self::get_server_software();
		$pattern = "/litespeed/i";
		if (preg_match($pattern, $server_software)) {
			return true;
		}

		return false;
	}

	/**
	 * Get path of a file that resides in wp-content directory from the file's url.
	 * If sub directory provided, the result will be evaluated with sub directory as well.
	 *
	 * @param string $file_url File URL
	 * @param string|array $sub_dir Sub file directory as string. Use array for nested directories.
	 * 
	 * @return string File Path. Empty string, if path couldn't be resolved.
	 */
	public static function get_uploaded_file_path_from_url($file_url, $sub_dir = ''){
		if(!empty($sub_dir)){
			$sub_dir = is_array($sub_dir) ? implode(DIRECTORY_SEPARATOR, $sub_dir) : $sub_dir;
			$sub_dir = trim(trim($sub_dir), DIRECTORY_SEPARATOR);

			$wp_content_dir = path_join(WP_CONTENT_DIR, $sub_dir);
			$wp_content_url = rtrim(WP_CONTENT_URL, '/') . '/' . $sub_dir;
		} else {
			$wp_content_dir = WP_CONTENT_DIR;
			$wp_content_url = WP_CONTENT_URL;
		}

		$file_path = path_join( $wp_content_dir, ltrim( substr( $file_url, strlen( $wp_content_url ) ), '/' ) );
		$file_path = realpath( $file_path );

		return $file_path;
	}

	/**
	 * Check if the download file is a hidden of no ext file.
	 *
	 * @param string $file_path
	 * 
	 * @return bool TRUE if hidden or no extension file, FALSE otherwise
	 */
	public static function check_is_hidden_or_no_extension_file($file_path){
		$path_parts = pathinfo( $file_path );

		if ( ( empty( $path_parts['filename'] ) || empty( $path_parts['extension'] ) ) ) {
			// Do not use PHP dispatch for hidden files and/or files without extension.
			return true;
		}

		return false;
	}

	/**
	 * Check if the extension of download file is allowed or not.
	 *
	 * @param string $file_path
	 * 
	 * @return bool
	 */
	public static function check_is_file_extension_allowed($file_path){
		$path_parts = pathinfo( $file_path );

		$main_option = get_option( 'sdm_downloads_options' );

		$disallowed_ext_opt = empty( $main_option['general_disallowed_file_ext_dispatch'] ) ? simpleDownloadManager::$disallowed_ext_dispatch_def : $main_option['general_disallowed_file_ext_dispatch'];

		$disallowed_ext_arr_raw = explode( ',', strtolower( $disallowed_ext_opt ) );

		$disallowed_ext_arr = array();

		foreach ( $disallowed_ext_arr_raw as $item ) {
			array_push( $disallowed_ext_arr, sanitize_text_field( $item ) );
		}

		if ( in_array( strtolower( $path_parts['extension'] ), $disallowed_ext_arr, true ) ) {
			// Disallowed file extension; Don't use PHP dispatching (instead use the normal redirect).
			return false;
		}

		return true;
	}

	public static function dl_file_type($filename) {
        // get base name of the filename provided by user
        $filename = basename($filename);

        // break file into parts seperated by .
        $filename = explode('.', $filename);

        // take the last part of the file to get the file extension
        $ext = $filename[count($filename) - 1];

        // find mime type
        return self::get_mime_type_form_ext($ext);
    }

	/**
     * Returns the size, in bytes, of a file whose path is specified by a URI. If the URI is a qualified URL and cURL is not
     * installed on the server, a string of "unknown" is returned.  Note: We use "URI" instead of "URL" because this is not
     * necessarily an HTTP request.
     *
     * @param string $uri FIle URI
     * @param string $user Username
     * @param string $pw Password
	 * 
     * @return string File size. Return "unknown" if no information available.
     */
    public static function dl_filesize($uri, $user = '', $pw = '') {
        if (preg_match("/^http/i", $uri) != 1) {
            // Not a qualified URL...
            $retVal = @filesize($uri); // Get file size.
            if ($retVal === false) {
                $retVal = 'unknown';
            }
            // Whitewash any stat() errors.
            return $retVal; // Return local file size.
        }
        if (!function_exists('curl_init')) {
            return 'unknown';
        }
        // If cURL not installed, size is "unknown."
        $ch = curl_init($uri); // Initialize cURL for this URI.
        if ($ch === false) {
            return 'unknown';
        }
        // Return "unknown" if initialization fails.
        curl_setopt($ch, CURLOPT_HEADER, true); // Request header in output.
        curl_setopt($ch, CURLOPT_NOBODY, true); // Exclude body from output).
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return transfer as string on curl_exec().
        // if auth is needed, do it here
        if (!empty($user) && !empty($pw)) { // Set optional authentication headers...
            $headers = array('Authorization: Basic ' . base64_encode($user . ':' . $pw));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $header = curl_exec($ch); // Retrieve the remote file header.
        if ($header === false) {
            return 'unknown';
        }
        // Return "unknown" if header could not be retrieved.
        // Parse the remote file header for the content length...
        if (preg_match('/Content-Length:\s([0-9].+?)\s/', $header, $matches) == 1) {
            return $matches[1]; // Return remote file size.
        } else {
            return 'unknown'; // Return "unknown" if no information available.
        }
    }

    public static function get_mime_type_form_ext($ext) {
        // Mime types array
        $mimetypes = array(
            "ez" => "application/andrew-inset",
            "hqx" => "application/mac-binhex40",
            "cpt" => "application/mac-compactpro",
            "doc" => "application/msword",
            "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "bin" => "application/octet-stream",
            "dms" => "application/octet-stream",
            "lha" => "application/octet-stream",
            "lzh" => "application/octet-stream",
            "exe" => "application/octet-stream",
            "class" => "application/octet-stream",
            "so" => "application/octet-stream",
            "dll" => "application/octet-stream",
            "oda" => "application/oda",
            "pdf" => "application/pdf",
            "ai" => "application/postscript",
            "eps" => "application/postscript",
            "ps" => "application/postscript",
            "smi" => "application/smil",
            "smil" => "application/smil",
            "wbxml" => "application/vnd.wap.wbxml",
            "wmlc" => "application/vnd.wap.wmlc",
            "wmlsc" => "application/vnd.wap.wmlscriptc",
            "bcpio" => "application/x-bcpio",
            "vcd" => "application/x-cdlink",
            "pgn" => "application/x-chess-pgn",
            "cpio" => "application/x-cpio",
            "csh" => "application/x-csh",
            "dcr" => "application/x-director",
            "dir" => "application/x-director",
            "dxr" => "application/x-director",
            "dvi" => "application/x-dvi",
            "spl" => "application/x-futuresplash",
            "gtar" => "application/x-gtar",
            "hdf" => "application/x-hdf",
            "js" => "application/x-javascript",
            "skp" => "application/x-koan",
            "skd" => "application/x-koan",
            "skt" => "application/x-koan",
            "skm" => "application/x-koan",
            "latex" => "application/x-latex",
            "nc" => "application/x-netcdf",
            "cdf" => "application/x-netcdf",
            "sh" => "application/x-sh",
            "shar" => "application/x-shar",
            "swf" => "application/x-shockwave-flash",
            "sit" => "application/x-stuffit",
            "sv4cpio" => "application/x-sv4cpio",
            "sv4crc" => "application/x-sv4crc",
            "tar" => "application/x-tar",
            "tcl" => "application/x-tcl",
            "tex" => "application/x-tex",
            "texinfo" => "application/x-texinfo",
            "texi" => "application/x-texinfo",
            "t" => "application/x-troff",
            "tr" => "application/x-troff",
            "roff" => "application/x-troff",
            "man" => "application/x-troff-man",
            "me" => "application/x-troff-me",
            "ms" => "application/x-troff-ms",
            "ustar" => "application/x-ustar",
            "src" => "application/x-wais-source",
            "xhtml" => "application/xhtml+xml",
            "xht" => "application/xhtml+xml",
            "zip" => "application/zip",
            "au" => "audio/basic",
            "snd" => "audio/basic",
            "mid" => "audio/midi",
            "midi" => "audio/midi",
            "kar" => "audio/midi",
            "mpga" => "audio/mpeg",
            "mp2" => "audio/mpeg",
            "mp3" => "audio/mpeg",
            "m4a" => "audio/mp4",
            "aif" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "aifc" => "audio/x-aiff",
            "m3u" => "audio/x-mpegurl",
            "ram" => "audio/x-pn-realaudio",
            "rm" => "audio/x-pn-realaudio",
            "rpm" => "audio/x-pn-realaudio-plugin",
            "ra" => "audio/x-realaudio",
            "wav" => "audio/x-wav",
            "pdb" => "chemical/x-pdb",
            "xyz" => "chemical/x-xyz",
            "bmp" => "image/bmp",
            "gif" => "image/gif",
            "ief" => "image/ief",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "jpg_backup" => "image/jpeg",
            "jpe" => "image/jpeg",
            "png" => "image/png",
            "tiff" => "image/tiff",
            "tif" => "image/tif",
            "djvu" => "image/vnd.djvu",
            "djv" => "image/vnd.djvu",
            "wbmp" => "image/vnd.wap.wbmp",
            "ras" => "image/x-cmu-raster",
            "pnm" => "image/x-portable-anymap",
            "pbm" => "image/x-portable-bitmap",
            "pgm" => "image/x-portable-graymap",
            "ppm" => "image/x-portable-pixmap",
            "rgb" => "image/x-rgb",
            "xbm" => "image/x-xbitmap",
            "xpm" => "image/x-xpixmap",
            "xwd" => "image/x-windowdump",
            "igs" => "model/iges",
            "iges" => "model/iges",
            "msh" => "model/mesh",
            "mesh" => "model/mesh",
            "silo" => "model/mesh",
            "wrl" => "model/vrml",
            "vrml" => "model/vrml",
            "css" => "text/css",
            "html" => "text/html",
            "htm" => "text/html",
            "asc" => "text/plain",
            "txt" => "text/plain",
            "rtx" => "text/richtext",
            "rtf" => "text/rtf",
            "sgml" => "text/sgml",
            "sgm" => "text/sgml",
            "tsv" => "text/tab-seperated-values",
            "wml" => "text/vnd.wap.wml",
            "wmls" => "text/vnd.wap.wmlscript",
            "etx" => "text/x-setext",
            "xml" => "text/xml",
            "xsl" => "text/xml",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "mpe" => "video/mpeg",
            "mp4" => "video/mp4",
            "qt" => "video/quicktime",
            "mov" => "video/quicktime",
            "mxu" => "video/vnd.mpegurl",
            "avi" => "video/x-msvideo",
            "movie" => "video/x-sgi-movie",
            "xls" => "application/vnd.ms-excel",
            "epub" => "application/epub+zip",
            "mobi" => "application/x-mobipocket-ebook",
            "ice" => "x-conference-xcooltalk",
        );

        if (isset($mimetypes[$ext])) {
            // return mime type for extension
            return $mimetypes[$ext];
        } else {
            // if the extension wasn't found return octet-stream
            return 'application/octet-stream';
        }
    }

}