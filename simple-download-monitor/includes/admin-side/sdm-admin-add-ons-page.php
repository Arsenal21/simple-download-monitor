<?php
$output = '';
echo '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/css/sdm_addons_listing.css" />' . "\n";
?>

<div class="wrap">
    <h1>Add-ons</h1>

    <div id="poststuff"><div id="post-body">

            <?php
            $addons_data = array();
            
            $addon_1 = array(
                "name" => "Squeeze Form Addon",
                "thumbnail" => WP_SIMPLE_DL_MONITOR_URL . "/images/addons/sdm-squeeze-form-addon-icon.png",
                "description" => "This addone lets you collect visitor's name and email address in exchange for a downloadable item of your site.",
                "page_url" => "https://simple-download-monitor.com/squeeze-form-addon-for-simple-download-monitor/",
            );
            array_push($addons_data, $addon_1);
            
            $addon_2 = array(
                "name" => "Email on Download",
                "thumbnail" => WP_SIMPLE_DL_MONITOR_URL . "/images/addons/sdm-email-on-download-addon-icon.png",
                "description" => "The Email Notification Addon sends you (the site admin) an email whenever one of your files is downloaded.",
                "page_url" => "https://simple-download-monitor.com/email-notification-on-download-addon-for-the-simple-download-monitor/",
            );
            array_push($addons_data, $addon_2);

            $addon_3 = array(
                "name" => "Hidden Downloads",
                "thumbnail" => WP_SIMPLE_DL_MONITOR_URL . "/images/addons/sdm-hidden-downloads-addon-icon.png",
                "description" => "Allows you to create hidden download buttons for your downloadable items so the actual location of the file is never revealed.",
                "page_url" => "https://simple-download-monitor.com/hidden-downloads-for-simple-download-monitor/",
            );
            array_push($addons_data, $addon_3);
            
            $addon_4 = array(
                "name" => "Amazon S3 Integration",
                "thumbnail" => WP_SIMPLE_DL_MONITOR_URL . "/images/addons/sdm-amazon-s3-addon-icon.png",
                "description" => "Allows you to securely store and deliver digital downloads using Amazon's Simple Storage Service (S3)",
                "page_url" => "https://simple-download-monitor.com/amazon-s3-integration-addon/",
            );
            array_push($addons_data, $addon_4);
            
            $addon_5 = array(
                "name" => "All File Type Uploads",
                "thumbnail" => WP_SIMPLE_DL_MONITOR_URL . "/images/addons/sdm-allow-uploads-addon-icon.png",
                "description" => "WordPress by default doesn't allow you to upload all file types. This addon will remove the limitation and allow you to upload all file types.",
                "page_url" => "https://simple-download-monitor.com/allow-more-file-types-to-be-uploaded-via-wordpress/",
            );
            array_push($addons_data, $addon_5);
            
            $addon_6 = array(
                "name" => "Dropbox Integration",
                "thumbnail" => WP_SIMPLE_DL_MONITOR_URL . "/images/addons/sdm-dropbox-integration-addon.png",
                "description" => "Allows you to configure downloads from your Dropbox account so the visitors can download it from your site.",
                "page_url" => "https://simple-download-monitor.com/dropbox-addon-for-the-simple-download-monitor/",
            );
            array_push($addons_data, $addon_6);
            
            $addon_7 = array(
                "name" => "WP eMember Integration",
                "thumbnail" => WP_SIMPLE_DL_MONITOR_URL . "/images/addons/sdm-emember-integration.png",
                "description" => "Allows you to view which member is downloading which item(s). So you can create more downloads that members will like.",
                "page_url" => "https://simple-download-monitor.com/tracking-member-downloads/",
            );
            array_push($addons_data, $addon_7);            
            
            /*** Show the addons list ***/
            foreach ($addons_data as $addon) {
                $output .= '<div class="sdm_addon_item_canvas">';

                $output .= '<div class="sdm_addon_item_thumb">';
                $img_src = $addon['thumbnail'];
                $output .= '<img src="' . $img_src . '" alt="' . $addon['name'] . '">';
                $output .= '</div>'; //end thumbnail

                $output .='<div class="sdm_addon_item_body">';
                $output .='<div class="sdm_addon_item_name">';
                $output .= '<a href="' . $addon['page_url'] . '" target="_blank">' . $addon['name'] . '</a>';
                $output .='</div>'; //end name

                $output .='<div class="sdm_addon_item_description">';
                $output .= $addon['description'];
                $output .='</div>'; //end description

                $output .='<div class="sdm_addon_item_details_link">';
                $output .='<a href="' . $addon['page_url'] . '" class="sdm_addon_view_details" target="_blank">View Details</a>';
                $output .='</div>'; //end detils link      
                $output .='</div>'; //end body

                $output .= '</div>'; //end canvas
            }

            echo $output;
            ?>

        </div></div><!-- end of poststuff and post-body -->        
</div><!-- end of .wrap -->