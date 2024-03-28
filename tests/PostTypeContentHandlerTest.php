<?php

declare (strict_types = 1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostTypeContentHandlerTest extends TestCase
{
    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\stubEscapeFunctions();
        Functions\stubTranslationFunctions();
        Functions\stubs(
            [
                'sanitize_text_field',
             ]
        );

        require_once "simple-download-monitor/sdm-post-type-content-handler.php";
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
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

    #[DataProvider('sdm_post_type_title_cases') ]
    public function test_filter_sdm_post_type_title(string $title, int | null $id): void
    {
        Functions\when('get_post_type')->justReturn('sdm_downloads');

        $result = filter_sdm_post_type_title($title, $id);

        $this->assertSame($title, $result);
    }

    /**
     * TODO: Need to adjust this
     */
    #[DataProvider('sdm_post_type_content_cases') ]
    public function test_filter_sdm_post_type_content(string $content, bool $single_page_disable): void
    {
        // Functions\when('get_post_meta')->justReturn($single_page_disable);

        // global $post;
        // $post = new class(){
        //     public $post_type = 'sdm_downloads';
        //     public $ID = 1;
        // };

        $result = filter_sdm_post_type_content($content);

        $this->assertSame($content, $result);

        unset($post);
    }

    public static function sdm_post_type_title_cases()
    {
        return [
            [ 'Simple title', null ],
            [ 'Simple title', 99 ],
         ];
    }

    public static function sdm_post_type_content_cases()
    {
        return [
            [ 'Simple post content', true ],
            [ 'Simple post content', true ],
         ];
    }
}
