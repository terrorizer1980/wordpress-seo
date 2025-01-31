<?php

namespace Yoast\WP\SEO\Tests\Unit\Actions\Importing;

use Mockery;
use Brain\Monkey;
use Yoast\WP\SEO\Actions\Importing\Aioseo_Default_Archive_Settings_Importing_Action;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Services\Importing\Aioseo_Replacevar_Handler;
use Yoast\WP\SEO\Tests\Unit\TestCase;
use Yoast\WP\SEO\Tests\Unit\Doubles\Actions\Importing\Aioseo_Default_Archive_Settings_Importing_Action_Double;

/**
 * Aioseo_Default_Archive_Settings_Importing_Action_Test class
 *
 * @group actions
 * @group importing
 *
 * @coversDefaultClass \Yoast\WP\SEO\Actions\Importing\Aioseo_Default_Archive_Settings_Importing_Action
 * @phpcs:disable Yoast.NamingConventions.ObjectNameDepth.MaxExceeded, Yoast.Yoast.AlternativeFunctions.json_encode_json_encode
 */
class Aioseo_Default_Archive_Settings_Importing_Action_Test extends TestCase {

	/**
	 * Represents the instance to test.
	 *
	 * @var Aioseo_Default_Archive_Settings_Importing_Action
	 */
	protected $instance;

	/**
	 * Represents the mock instance to test.
	 *
	 * @var Aioseo_Default_Archive_Settings_Importing_Action_Double
	 */
	protected $mock_instance;

	/**
	 * The mocked options helper.
	 *
	 * @var Mockery\MockInterface|Options_Helper
	 */
	protected $options;

	/**
	 * The replacevar handler.
	 *
	 * @var Mockery\MockInterface|Aioseo_Replacevar_Handler
	 */
	protected $replacevar_handler;

	/**
	 * An array of the total Default Archive Settings we can import.
	 *
	 * @var array
	 */
	protected $full_settings_to_import = [
		'author' => [
			'show'            => true,
			'title'           => 'Author Title',
			'metaDescription' => 'Author Desc',
			'advanced'        => [
				'showDateInGooglePreview' => true,
			],
		],
		'date'   => [
			'show'            => true,
			'title'           => 'Date Title',
			'metaDescription' => 'Date Desc',
			'advanced'        => [
				'showDateInGooglePreview' => true,
			],
		],
		'search' => [
			'show'            => true,
			'title'           => 'Search Title',
			'metaDescription' => 'Search Desc',
			'advanced'        => [
				'showDateInGooglePreview' => true,
			],
		],
	];

	/**
	 * The flattened array of the total Default Archive Settings we can import.
	 *
	 * @var array
	 */
	protected $flattened_settings_to_import = [
		'/author/show'                             => true,
		'/author/title'                            => 'Author Title',
		'/author/metaDescription'                  => 'Author Desc',
		'/author/advanced/showDateInGooglePreview' => true,
		'/date/show'                               => true,
		'/date/title'                              => 'Date Title',
		'/date/metaDescription'                    => 'Date Desc',
		'/date/advanced/showDateInGooglePreview'   => true,
		'/search/show'                             => true,
		'/search/title'                            => 'Search Title',
		'/search/metaDescription'                  => 'Search Desc',
		'/search/advanced/showDateInGooglePreview' => true,
	];

	/**
	 * Sets up the test class.
	 */
	protected function set_up() {
		parent::set_up();

		$this->options            = Mockery::mock( Options_Helper::class );
		$this->replacevar_handler = Mockery::mock( Aioseo_Replacevar_Handler::class );
		$this->instance           = new Aioseo_Default_Archive_Settings_Importing_Action( $this->options, $this->replacevar_handler );
		$this->mock_instance      = Mockery::mock(
			Aioseo_Default_Archive_Settings_Importing_Action_Double::class,
			[ $this->options, $this->replacevar_handler ]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests the getting of the source option_name.
	 *
	 * @covers ::get_source_option_name
	 */
	public function test_get_source_option_name() {
		$source_option_name = $this->instance->get_source_option_name();
		$this->assertEquals( $source_option_name, 'aioseo_options' );
	}

	/**
	 * Tests retrieving unimported AiOSEO settings.
	 *
	 * @param array $query_results The results from the query.
	 * @param bool  $expected      The expected retrieved data.
	 *
	 * @dataProvider provider_query
	 * @covers ::query
	 */
	public function test_query( $query_results, $expected ) {
		Monkey\Functions\expect( 'get_option' )
			->once()
			->andReturn( $query_results );

		$this->mock_instance->shouldReceive( 'get_unimported_chunk' )
			->with( $expected, null )
			->zeroOrMoreTimes()
			->andReturn( $expected );

		$settings_to_import = $this->mock_instance->query();
		$this->assertTrue( $settings_to_import === $expected );
	}

	/**
	 * Tests flattening AIOSEO default archive settings.
	 *
	 * @covers ::flatten_settings
	 */
	public function test_flatten_settings() {
		$flattened_sesttings = $this->mock_instance->flatten_settings( $this->full_settings_to_import );
		$expected_result     = $this->flattened_settings_to_import;

		$this->assertTrue( $expected_result === $flattened_sesttings );
	}

	/**
	 * Tests mapping AIOSEO default archive settings.
	 *
	 * @param string $setting         The setting at hand, eg. post or movie-category, separator etc.
	 * @param string $setting_value   The value of the AIOSEO setting at hand.
	 * @param int    $times           The times that we will import each setting, if any.
	 * @param int    $transform_times The times that we will transform each setting, if any.
	 *
	 * @dataProvider provider_map
	 * @covers ::map
	 */
	public function test_map( $setting, $setting_value, $times, $transform_times ) {
		$this->mock_instance->build_mapping();
		$aioseo_options_to_yoast_map = $this->mock_instance->get_aioseo_options_to_yoast_map();

		$this->options->shouldReceive( 'get_default' )
			->times( $times )
			->andReturn( 'not_null' );

		$this->replacevar_handler->shouldReceive( 'transform' )
			->times( $transform_times )
			->with( $setting_value )
			->andReturn( $setting_value );

		$this->options->shouldReceive( 'set' )
			->times( $times );

		$this->mock_instance->map( $setting_value, $setting );
	}

	/**
	 * Data provider for test_map().
	 *
	 * @return array
	 */
	public function provider_map() {
		return [
			[ '/author/title', 'Author Title', 1, 1 ],
			[ '/author/metaDescription', 'Author Desc', 1, 1 ],
			[ '/author/advanced/robotsMeta/noindex', true, 1, 0 ],
			[ '/date/show', 'Date Title', 0, 0 ],
			[ '/date/metaDescription', 'Date Title', 1, 1 ],
			[ '/date/advanced/robotsMeta/noindex', true, 1, 0 ],
			[ '/search/title', 'Search Title', 1, 1 ],
			[ '/randomSetting', 'randomeValue', 0, 0 ],
		];
	}

	/**
	 * Data provider for test_query().
	 *
	 * @return string
	 */
	public function provider_query() {
		$full_settings = [
			'searchAppearance' => [
				'archives'   => $this->full_settings_to_import,
				'postypes'   => [
					'post' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
				'taxonomies' => [
					'category' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
			],
		];

		$full_settings_expected = $this->flattened_settings_to_import;

		$missing_settings = [
			'searchAppearance' => [
				'postypes'   => [
					'post' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
				'taxonomies' => [
					'category' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
			],
		];

		$missing_settings_expected = [];

		$malformed_settings = [
			'searchAppearance' => [
				'archives'   => 'not_array',
				'postypes'   => [
					'post' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
				'taxonomies' => [
					'category' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
			],
		];

		$malformed_settings_expected = [];

		return [
			[ \json_encode( $full_settings ), $full_settings_expected ],
			[ \json_encode( $missing_settings ), $missing_settings_expected ],
			[ \json_encode( $missing_settings ), $missing_settings_expected ],
		];
	}
}
