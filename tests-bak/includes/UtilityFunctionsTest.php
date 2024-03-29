<?php

declare (strict_types = 1);

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UtilityFunctionsTest extends TestCase
{
    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\stubEscapeFunctions();
        Functions\stubTranslationFunctions();

        require_once "simple-download-monitor/includes/sdm-utility-functions.php";
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_sdm_get_download_button_colors()
    {
		$filtered_data = array(
			'indigo' => 'Indigo',
		);
		
        Filters\expectApplied('sdm_download_button_color_options')->once()->andReturn($filtered_data);

		$expected_data = $filtered_data;
		
        self::assertSame($expected_data , sdm_get_download_button_colors());
    }
}
