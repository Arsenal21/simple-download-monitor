<?php

declare (strict_types = 1);

namespace TTHQ\SDM\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PostTypeContentHandlerTest extends \WP_UnitTestCase
{
    public static function set_up_before_class()
    {
        require 'simple-download-monitor/main.php';

        sdm_install_db_table();
    }
    public function set_up() {
        parent::set_up();
        // The specific set up for the tests within this class.

        require_once "simple-download-monitor/sdm-post-type-content-handler.php";
        require_once "simple-download-monitor/includes/sdm-utility-functions.php";
    }

    public function tear_down() {
        // Clean up specific to the tests within this class.
        parent::tear_down();
    }

    public function test_has_filters(): void
    {
        //Handle the title for the SDM download type post
        $this->assertNotFalse(has_filter('the_title', 'filter_sdm_post_type_title'));

        //Handle the main post content for hte SDM download type post
        $this->assertNotFalse(has_filter('the_content', 'filter_sdm_post_type_content'));

        //The following filters are applied to the output of the SDM description field.
        $this->assertNotFalse(has_filter('sdm_downloads_description', 'do_shortcode'));
        $this->assertNotFalse(has_filter('sdm_downloads_description', 'wptexturize'));
        $this->assertNotFalse(has_filter('sdm_downloads_description', 'convert_smilies'));
        $this->assertNotFalse(has_filter('sdm_downloads_description', 'convert_chars'));
        $this->assertNotFalse(has_filter('sdm_downloads_description', 'wpautop'));
        $this->assertNotFalse(has_filter('sdm_downloads_description', 'shortcode_unautop'));
        $this->assertNotFalse(has_filter('sdm_downloads_description', 'prepend_attachment'));

        //Add adsense or ad code below the description (if applicable)
        $this->assertNotFalse(has_filter('sdm_cpt_below_download_description', 'sdm_add_ad_code_below_description'));
        $this->assertNotFalse(has_filter('sdm_fancy1_below_download_description', 'sdm_add_ad_code_below_description'));
    }

    /**
     * @dataProvider data_filter_sdm_post_type_title
     */
    public function test_filter_sdm_post_type_title(string $title, int | null $id): void
    {
        // Functions\when('get_post_type')->justReturn('sdm_downloads');
        $result = filter_sdm_post_type_title($title, $id);

        $this->assertSame($title, $result);
    }

    /**
     * @dataProvider data_filter_sdm_post_type_content
     */
    public function test_filter_sdm_post_type_content(
        bool $is_disabled_single_page = false,
        string $is_post_password = 'no',
        string $thumbnail = '',
        string $file_size = '',
        string $version = '',
        bool $show_date_fd = false,
        bool $new_window = false,
        bool $hide_dl_btn_single_dl_page = false,
    ): void
    {
        global $post;
        if (!defined('WP_SIMPLE_DL_MONITOR_SITE_HOME_URL')){
            define('WP_SIMPLE_DL_MONITOR_SITE_HOME_URL', "http://localhost");
        }
        $test_post = self::factory()->post->create_and_get([
            'post_title' => "A test Post title",
            'post_content' => "A test Post Content",
            'post_type' => 'sdm_downloads',
            'post_password' => $is_post_password,
        ]);

        $post = $test_post;

        add_post_meta( $test_post->ID, 'sdm_item_disable_single_download_page', $is_disabled_single_page );
        add_post_meta( $test_post->ID, 'sdm_upload_thumbnail', $thumbnail );
        add_post_meta( $test_post->ID, 'sdm_item_file_size', $file_size );
        add_post_meta( $test_post->ID, 'sdm_item_version', $version );
        add_post_meta( $test_post->ID, 'sdm_item_show_date_fd', $show_date_fd );
        add_post_meta( $test_post->ID, 'sdm_item_new_window', $new_window );
        add_post_meta( $test_post->ID, 'sdm_item_hide_dl_button_single_download_page', $hide_dl_btn_single_dl_page );

        $result = filter_sdm_post_type_content($test_post->post_content);

        if ($is_disabled_single_page){
            $this->assertStringContainsString( '<div class="sdm_post_single_download_page_disabled_msg">', $result);
            return;
        }

        if ($is_post_password == 'yes'){
            $this->assertStringContainsString( '<span class="sdm_enter_password_label_text">', $result );
        }else{
            $this->assertStringContainsString( '<div class="sdm_post_item">', $result );
        }

        if ($thumbnail){
            $this->assertStringContainsString( '<img class="sdm_post_thumbnail_image" src="'.$thumbnail.'" alt = "'.get_the_title($test_post).'" />', $result );
        }
        if ($file_size){
            $this->assertStringContainsString( '<div class="sdm_post_download_file_size">', $result );
        }
        if ($version){
            $this->assertStringContainsString( '<div class="sdm_post_download_version">', $result );
        }
        if ($show_date_fd){
            $this->assertStringContainsString( '<div class="sdm_post_download_published_date">', $result );
        }

        if ($hide_dl_btn_single_dl_page){
            $this->assertStringContainsString( '<div class="sdm_post_single_download_page_disabled_dl_button_msg">', $result );
        }else{
            $this->assertStringContainsString( '<div class="sdm_download_link">', $result );
        }

//        echo PHP_EOL;
//        print_r($filterd_content);
    }

    public static function data_filter_sdm_post_type_title()
    {
        return [
            "Title with id" => [ 'Simple title', 99 ],
            "Title with null id" => [ 'Simple title', null ],
         ];
    }

    public static function data_filter_sdm_post_type_content()
    {
        return [
            "Single Page Disabled" => [
                'is_disabled_single_page' => true,
            ],
            "Having Post Password" => [
                'is_disabled_single_page' => false,
                'is_post_password' => 'yes',
            ],
            "Having thumbnail" => [
                "is_disabled_single_page" => false,
                "is_post_password" => 'no',
                "thumbnail" => 'http://localhost/#',
            ],
            "Having file-size version data" => [
                "is_disabled_single_page" => false,
                "is_post_password" => 'no',
                "thumbnail" => '',
                "file_size" => '5 MB',
                "version" => '1.0.2',
                "show_date_fd" => true,
                "new_window" => true,
                "hide_dl_btn_single_dl_page" => true
            ],
         ];
    }
}
