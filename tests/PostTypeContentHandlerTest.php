<?php

declare (strict_types = 1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostTypeContentHandlerTest extends WP_UnitTestCase
{
    /**
     * @dataProvider sdm_post_type_title_cases
     */
    public function test_filter_sdm_post_type_title(string $title, int | null $id): void
    {
        // Functions\when('get_post_type')->justReturn('sdm_downloads');
        require_once "simple-download-monitor/sdm-post-type-content-handler.php";
        $result = filter_sdm_post_type_title($title, $id);

        $this->assertSame($title, $result);
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
