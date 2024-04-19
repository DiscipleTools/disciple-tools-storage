<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Storage_Endpoints
{
    public $permissions = [ 'manage_dt' ];

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
