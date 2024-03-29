<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Media_API
 */
class Disciple_Tools_Media_API {

    public static $option_dt_media_connection_objects = 'dt_media_connection_objects';

    public static function list_supported_connection_types(): array {
        return [
            'aws' => [
                'key' => 'aws',
                'api' => 's3',
                'label' => 'AWS S3',
                'enabled' => true
            ],
            'backblaze' => [
                'key' => 'backblaze',
                'api' => 's3',
                'label' => 'Backblaze',
                'enabled' => true
            ],
            'minio' => [
                'key' => 'minio',
                'api' => 's3',
                'label' => 'MinIO',
                'enabled' => true
            ]
        ];
    }

    public static function fetch_option_connection_objs(): object {
        $option = get_option( self::$option_dt_media_connection_objects );

        if ( ! empty( $option ) ) {

            $connection_objs = [];
            foreach ( json_decode( $option ) as $id => $connection_obj ) {
                if ( isset( $connection_obj->id ) ){
                    $connection_objs[ $connection_obj->id ] = $connection_obj;
                }
            }

            return (object) $connection_objs;
        }

        return (object) [];
    }

    public static function update_option_connection_obj( $connection_obj ): void {
        $option_connection_objs = self::fetch_option_connection_objs();

        $option_connection_objs->{$connection_obj->id} = $connection_obj;

        // Save changes.
        update_option( self::$option_dt_media_connection_objects, json_encode( $option_connection_objs ) );
    }

    public static function fetch_option_connection_obj( $connection_obj_id ) {
        $option_connection_objs = self::fetch_option_connection_objs();

        return ( isset( $option_connection_objs->{$connection_obj_id} ) ) ? $option_connection_objs->{$connection_obj_id} : (object) [];
    }

    public static function delete_option_connection_obj( $connection_obj_id ): void {
        $option_connection_objs = self::fetch_option_connection_objs();

        // Do we have a match?
        if ( isset( $option_connection_objs->{$connection_obj_id} ) ) {

            // Remove connection object from options.
            unset( $option_connection_objs->{$connection_obj_id} );

            // Save changes
            update_option( self::$option_dt_media_connection_objects, json_encode( $option_connection_objs ) );
        }
    }
}
