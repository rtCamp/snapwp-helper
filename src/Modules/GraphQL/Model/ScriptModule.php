<?php
/**
 * ScriptModule Model class
 *
 * @package SnapWP\Helper\Modules\GraphQL\Model
 */

namespace SnapWP\Helper\Modules\GraphQL\Model;

use GraphQLRelay\Relay;
use WPGraphQL\Model\Model;

/**
 * Class - ScriptModule
 *
 * @phpstan-import-type ScriptModuleData from \SnapWP\Helper\Modules\GraphQL\Utils\ScriptModuleUtils
 *
 * @property string $id The Global ID of the script module.
 * @property string $handle The handle of the script module.
 * @property ?string $src The source URL of the script module.
 * @property ?string $version The version of the script module.
 * @property ?array{id:string,import?:string} $dependencies The dependencies of the script module.
 */
class ScriptModule extends Model {
	/**
	 * {@inheritDoc}
	 *
	 * @var ScriptModuleData
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param mixed[] $module The script module data.
	 *
	 * @throws \InvalidArgumentException If the required keys are not present in the resolved template data.
	 */
	public function __construct( array $module ) {
		if ( ! isset( $module['src'] ) || ! isset( $module['id'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Invalid script module data. The "src" and "id" keys are required.', 'snapwp-helper' )
			);
		}

		/** @var ScriptModuleData $module */
		$this->data = $module;

		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		if ( empty( $this->fields ) ) {
			$this->fields = [
				'id'           => fn (): string => Relay::toGlobalId( 'script_module', $this->data['id'] ),
				'handle'       => fn (): ?string => $this->data['id'] ?: null,
				'src'          => fn (): ?string => $this->data['src'] ?: null,
				'version'      => fn (): ?string => ! empty( $this->data['version'] ) ? $this->data['version'] : null,
				'dependencies' => fn (): ?array => ! empty( $this->data['dependencies'] ) ? $this->data['dependencies'] : null,
			];
		}
	}
}
