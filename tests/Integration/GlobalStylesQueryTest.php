<?php
/**
 * Tests querying the GlobalStyles object.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;
use SnapWP\Helper\Utils\Utils;

/**
 * Class - GlobalStylesQueryTest
 */
class GlobalStylesQueryTest extends IntegrationTestCase {
	public function testGlobalStylesQuery(): void {
		$query = 'query testGlobalStyles {
			globalStyles {
				bigImageSizeThreshold
				customCss
				renderedFontFaces
				stylesheet
			}
		}';

		$actual = $this->graphql( compact( 'query' ) );

		// Check if the query was successful.
		$this->assertArrayNotHasKey( 'errors', $actual );

		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'globalStyles',
					[
						$this->expectedField( 'customCss', self::NOT_FALSY ),
						$this->expectedField( 'customCss', self::NOT_FALSY ),
						$this->expectedField( 'renderedFontFaces', self::NOT_FALSY ),
						$this->expectedField( 'stylesheet', self::NOT_FALSY ),
					]
				),
			]
		);

		$this->assertStringContainsString( 'wp-fonts-local', $actual['data']['globalStyles']['renderedFontFaces'] );
		// Check for the @font-face property
		$this->assertStringContainsString( '@font-face', $actual['data']['globalStyles']['renderedFontFaces'] );
	}

	public function testFontFacesQuery(): void {
		$query = 'query testFontFaces {
			globalStyles {
				fontFaces {
					ascentOverride
					css
					descentOverride
					fontDisplay
					fontFamily
					fontFeatureSettings
					fontStretch
					fontStyle
					fontVariant
					fontVariationSettings
					fontWeight
					sizeAdjust
					src
					unicodeRange
				}
			}
		}';

		$expected = \WP_Font_Face_Resolver::get_fonts_from_theme_json();
		$expected = array_reduce( $expected, 'array_merge', [] );

		$actual = $this->graphql( compact( 'query' ) );

		// Check if the query was successful.
		$this->assertArrayNotHasKey( 'errors', $actual );

		$this->assertNotEmpty( $actual['data']['globalStyles']['fontFaces'] );

		for ( $i = 0; $i < count( $expected ); $i++ ) {
			// Fonts will always have a src and a family.
			$this->assertArrayHasKey( 'src', $actual['data']['globalStyles']['fontFaces'][ $i ] );
			$this->assertArrayHasKey( 'fontFamily', $actual['data']['globalStyles']['fontFaces'][ $i ] );

			// For all other properties, we check if they are present in the expected array.
			foreach ( $expected[ $i ] as $key => $value ) {
				$actual_key = Utils::kebab_to_camel_case( $key );
				$this->assertArrayHasKey( $actual_key, $actual['data']['globalStyles']['fontFaces'][ $i ] );
				$this->assertEquals( $value, $actual['data']['globalStyles']['fontFaces'][ $i ][ $actual_key ] );
			}
		}
	}

	public function testBigImageSizeThreshold(): void {
		$query = 'query testBigImageSizeThreshold {
			globalStyles {
				bigImageSizeThreshold
			}
		}';

		$actual = $this->graphql( compact( 'query' ) );

		// Check if the query was successful.
		$this->assertArrayNotHasKey( 'errors', $actual );

		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'globalStyles',
					[
						$this->expectedField( 'bigImageSizeThreshold', 2560 ),
					]
				),
			]
		);

		// Override the value using add_filter.
		add_filter(
			'big_image_size_threshold',
			static function () {
				return 2048;
			}
		);

		$actual = $this->graphql( compact( 'query' ) );

		// Check if the query was successful.
		$this->assertArrayNotHasKey( 'errors', $actual );

		// Validate that the overridden value is returned.
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'globalStyles',
					[
						$this->expectedField( 'bigImageSizeThreshold', 2048 ),
					]
				),
			]
		);
	}
}
