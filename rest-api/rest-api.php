<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Storage_Endpoints
{
    /**
     * @todo Set the permissions your endpoint needs
     * @link https://github.com/DiscipleTools/Documentation/blob/master/theme-core/capabilities.md
     * @var string[]
     */
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];


    /**
     * @todo define the name of the $namespace
     * @todo define the name of the rest route
     * @todo defne method (CREATABLE, READABLE)
     * @todo apply permission strategy. '__return_true' essentially skips the permission check.
     */
    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'disciple_tools_storage/v1';

        register_rest_route(
            $namespace, '/validate_connection', [
                'methods'  => 'POST',
                'callback' => [ $this, 'validate_connection' ],
                'permission_callback' => function( WP_REST_Request $request ) {
                    return $this->has_permission();
                },
            ]
        );
    }


    public function validate_connection( WP_REST_Request $request ): array {
        $response = [];

        $params = $request->get_params();
        if ( isset( $params['connection_type_api'], $params[ $params['connection_type_api'] ] ) ) {
            $response['valid'] = DT_Storage::validate_connection_details( $params['connection_type_api'], $params[ $params['connection_type_api'] ] );
        }

        return $response;
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }
    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }
}
Disciple_Tools_Storage_Endpoints::instance();
