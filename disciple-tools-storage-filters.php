<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


class DT_Storage {

    /**
     * @return object|null
     */
    private static function get_connection(){
        $storage_connection_id = dt_get_option( 'dt_storage_connection_id' );
        if ( empty( $storage_connection_id ) ) {
            return null;
        }
        return Disciple_Tools_Storage_API::fetch_option_connection_obj( $storage_connection_id );
    }

    /**
     * @return bool
     */
    public static function is_enabled(): bool {
        $connection = self::get_connection();
        return !empty( $connection ) && isset( $connection->enabled ) && $connection->enabled;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function get_file_url( string $key ): string {
        $connection = self::get_connection();
        if ( !empty( $connection ) ) {
            return dt_storage_connections_obj_url( null, $connection->id, $key, [ 'keep_alive' => '+24 hours' ] );
        }
        return '';
    }

    /**
     * @param string $key_prefix like 'users', 'contacts', 'comments
     * @param array $upload
     * @param string $existing_key
     * @return false|mixed
     */
    public static function upload_file( string $key_prefix = '', array $upload = [], string $existing_key = '', array $args = [] ){
        $key_prefix = trailingslashit( $key_prefix );
        $connection = self::get_connection();
        $merged_args = array_merge( $args, [
            'auto_generate_key' => empty( $existing_key ),
            'include_extension' => empty( $existing_key ),
            'default_key' => $existing_key,
            'auto_generate_thumbnails' => in_array( strtolower( trim( $upload['type'] ?? '' ) ), [
                'image/gif',
                'image/jpeg',
                'image/png'
            ] ),
            'thumbnails_desired_width' => 32 // Heights are automatically calculated, based on specified width.
        ] );
        if ( !empty( $connection ) ) {
            return dt_storage_connections_obj_upload( null, $connection->id, $key_prefix, $upload, $merged_args );
        }
        return false;
    }

    /**
     * @param string $connection_type_api like 's3'
     * @param array $details
     * @return bool
     */
    public static function validate_connection_details( $connection_type_api, $details ): bool {
        return dt_storage_connection_validation( false, $connection_type_api, $details );
    }
}




add_filter( 'dt_storage_connection_types', 'dt_storage_connection_types', 10, 1 );
function dt_storage_connection_types( $connection_types ) {
    foreach ( Disciple_Tools_Storage_API::list_supported_connection_types() as $key => $type ) {
        if ( $type['enabled'] ) {
            $connection_types[ $key ] = $type;
        }
    }

    return $connection_types;
}

add_filter( 'dt_storage_connections', 'dt_storage_connections', 10, 1 );
function dt_storage_connections( $connections ) {
    foreach ( Disciple_Tools_Storage_API::fetch_option_connection_objs() as $id => $connection ) {
        if ( $connection->enabled ) {
            $connections[] = (array) $connection;
        }
    }

    return $connections;
}

add_filter( 'dt_storage_connections_by_id', 'dt_storage_connections_by_id', 10, 2 );
function dt_storage_connections_by_id( $connections, $id ) {
    $connection = Disciple_Tools_Storage_API::fetch_option_connection_obj( $id );

    if ( !empty( $connection ) && isset( $connection->enabled ) && $connection->enabled ) {
        $connections[] = (array) $connection;
    }

    return $connections;
}

add_filter( 'dt_storage_connections_enabled', 'dt_storage_connections_enabled', 10, 2 );
function dt_storage_connections_enabled( $response, $id ): bool {
    return !empty( apply_filters( 'dt_storage_connections_by_id', [], $id ) );
}

add_filter( 'dt_storage_connection_validation', 'dt_storage_connection_validation', 10, 3 );
function dt_storage_connection_validation( $response, $connection_type_api, $details ): bool {
    if ( isset( $details['access_key'], $details['secret_access_key'], $details['region'], $details['bucket'], $details['endpoint'] ) ) {
        switch ( $connection_type_api ) {
            case 's3':
                try {

                    require_once( 'vendor/autoload.php' );

                    // Instantiate required aws s3 client object.
                    $s3 = new Aws\S3\S3Client( [
                        'region' => $details['region'],
                        'version' => 'latest',
                        'credentials' => [
                            'key' => $details['access_key'],
                            'secret' => $details['secret_access_key']
                        ],
                        'endpoint' => $details['endpoint']
                    ] );

                    // A successful listing of buckets, shall constitute as a validated connection.
                    $buckets = $s3->listBuckets( [] );

                    $response = !is_wp_error( $buckets ) && !empty( $buckets['Buckets'] );

                }catch ( Exception $e ) {
                    $response = false;
                }
                break;
            default:
                break;
        }
    }

    return $response;
}

add_filter( 'dt_storage_connections_obj_upload', 'dt_storage_connections_obj_upload', 10, 5 );
function dt_storage_connections_obj_upload( $response, $storage_connection_id, $key_prefix = '', $upload = [], $args = [] ) {

    // If required, auto-generate key to be used for object upload storage, along with any prefixes & suffixes.
    if ( isset( $args['auto_generate_key'] ) && !$args['auto_generate_key'] && !empty( $args['default_key'] ) ) {
        $key = $args['default_key'];
    } else {
        $key = $key_prefix . Disciple_Tools_Storage_API::generate_random_string( 112 );
    }

    // If required, capture uploading file's extension.
    if ( isset( $args['include_extension'], $upload['full_path'] ) && $args['include_extension'] ) {
        $extension = pathinfo( $upload['full_path'] )['extension'] ?? '';
        if ( !empty( $extension ) ) {
            $key .= '.' . $extension;
        }
    }

    // Ensure required storage connection settings, are available.
    $storage_connection_types = apply_filters( 'dt_storage_connection_types', [] );
    $storage_connection_filter = apply_filters( 'dt_storage_connections_by_id', [], $storage_connection_id );
    $storage_connection = null;
    if ( !empty( $storage_connection_filter ) ) {
        $storage_connection = $storage_connection_filter[0];
    }

    if ( !empty( $storage_connection ) && isset( $storage_connection_types[$storage_connection['type']] ) ){
        $storage_connection_type = $storage_connection_types[$storage_connection['type']];

        // Handle file upload accordingly, based on associated connection api.
        switch ( $storage_connection_type['api'] ) {
            case 's3':
                $config = (array) $storage_connection[$storage_connection['type']];
                if ( isset( $config['access_key'], $config['secret_access_key'], $config['region'], $config['bucket'], $config['endpoint'] ) ) {

                    require_once( 'vendor/autoload.php' );

                    $s3 = null;
                    $response = true;
                    $bucket = $config['bucket'];

                    // Generate complete file key name to be used moving forward.
                    $key_name = ( isset( $storage_connection_type['prefix_bucket_name_to_obj_key'] ) && $storage_connection_type['prefix_bucket_name_to_obj_key'] ) ? ( $bucket .'/'. $key ) : $key;

                    // Ensure endpoint reference has the correct protocol schema.
                    $endpoint = Disciple_Tools_Storage_API::validate_url( $config['endpoint'] );

                    try {

                        // Instantiate required aws s3 client object.
                        $s3 = new Aws\S3\S3Client( [
                            'region' => $config['region'],
                            'version' => 'latest',
                            'credentials' => [
                                'key' => $config['access_key'],
                                'secret' => $config['secret_access_key']
                            ],
                            'endpoint' => $endpoint
                        ] );

                        // First, upload original file.
                        $uploaded_key = dt_storage_connections_obj_upload_s3( $s3, $bucket, $key_name, $upload );

                        // Next, if specified, generate and upload a corresponding thumbnail.
                        $uploaded_thumbnail_key = null;
                        if ( isset( $args['auto_generate_thumbnails'] ) && $args['auto_generate_thumbnails'] ) {
                            $thumbnail = Disciple_Tools_Storage_API::generate_image_thumbnail( $upload['tmp_name'], $upload['type'] ?? '', $args['thumbnails_desired_width'] ?? 32 );
                            if ( !empty( $thumbnail ) ) {

                                // Generate temp file to function as a reference point for generated thumbnail.
                                $tmp_image = tmpfile();
                                $tmp_image_metadata = stream_get_meta_data( $tmp_image );

                                // Next, populate temp file, accordingly, by image content type.
                                $thumbnail_tmp_name = null;
                                switch ( strtolower( trim( $upload['type'] ?? '' ) ) ) {
                                    case 'image/gif':
                                        if ( imagegif( $thumbnail, $tmp_image ) ) {
                                            $thumbnail_tmp_name = $tmp_image_metadata['uri'];
                                        }
                                        break;
                                    case 'image/jpeg':
                                        if ( imagejpeg( $thumbnail, $tmp_image ) ) {
                                            $thumbnail_tmp_name = $tmp_image_metadata['uri'];
                                        }
                                        break;
                                    case 'image/png':
                                        if ( imagepng( $thumbnail, $tmp_image ) ) {
                                            $thumbnail_tmp_name = $tmp_image_metadata['uri'];
                                        }
                                        break;
                                    default:
                                        break;
                                }

                                // If we have a valid thumbnail temp file, proceed with upload attempt.
                                if ( !empty( $thumbnail_tmp_name ) ) {

                                    // Adjust reference to temp file, for recently generated thumbnail.
                                    $upload['tmp_name'] = $thumbnail_tmp_name;

                                    // Fetch thumbnail sizing info, to be used to generate suffix for key_name.
                                    $thumbnail_size = getimagesize( $thumbnail_tmp_name );
                                    if ( !empty( $thumbnail_size ) ) {
                                        $thumbnail_key_name = $key_name . '.' . $thumbnail_size[0] . 'x' . $thumbnail_size[1];
                                        $uploaded_thumbnail_key = dt_storage_connections_obj_upload_s3( $s3, $bucket, $thumbnail_key_name, $upload );
                                    }
                                }
                            }
                        }

                        // Finally, capture valid uploaded keys.
                        $response = [
                            'uploaded_key' => ! empty( $uploaded_key ) ? $uploaded_key : null,
                            'uploaded_thumbnail_key' => ! empty( $uploaded_thumbnail_key ) ? $uploaded_thumbnail_key : null
                        ];
                    } catch ( Exception $e ) {
                        $response = false;
                    }
                }
                break;
            default:
                break;
        }
    }

    return $response;
}

function dt_storage_connections_obj_upload_s3( $s3, $bucket, $key_name, $upload ) {
    $response = null;
    $upload_id = null;

    try {

        // Upload file in parts, to better manage memory leaks.
        $result = $s3->createMultipartUpload( [
            'Bucket' => $bucket,
            'Key' => $key_name,
            // 'StorageClass' => 'REDUCED_REDUNDANCY', // NB: Currently not supported by Backblaze
            // 'ACL' => 'public-read', // NB: Currently not supported by Backblaze
            'ContentType' => $upload['type'] ?? '',
            'Metadata' => []
        ] );
        $upload_id = $result['UploadId'];

    } catch ( Exception $e ) {
        $response = false;
    }

    // Ensure no previous upload exceptions have been encountered.
    if ( $response !== false ) {
        $parts = [];

        try {

            // Start to upload file in partial chunks.
            $filename = $upload['tmp_name'] ?? '';
            $file = fopen( $filename, 'r' );
            $part_number = 1;
            while ( !feof( $file ) ) {
                $result = $s3->uploadPart( [
                    'Bucket' => $bucket,
                    'Key' => $key_name,
                    'UploadId' => $upload_id,
                    'PartNumber' => $part_number,
                    'Body' => fread( $file, 5 * 1024 * 1024 ),
                ] );

                $parts['Parts'][$part_number] = [
                    'PartNumber' => $part_number,
                    'ETag' => $result['ETag'],
                ];

                // Increment part count and force garbage collection, to better manage memory.
                $part_number++;
                gc_collect_cycles();
            }
            fclose( $file );

        } catch ( Exception $e ) {
            $response = false;
            $result = $s3->abortMultipartUpload( [
                'Bucket' => $bucket,
                'Key' => $key_name,
                'UploadId' => $upload_id
            ] );
        }

        // Ensure no previous upload exceptions have been encountered.
        if ( $response !== false ) {

            try {

                // Complete the multipart upload.
                $result = $s3->completeMultipartUpload( [
                    'Bucket' => $bucket,
                    'Key' => $key_name,
                    'UploadId' => $upload_id,
                    'MultipartUpload' => $parts,
                ] );
                $response = $result['Key'] ?? false;

            } catch ( Exception $e ) {
                $response = false;
            }
        }
    }

    return $response;
}

add_filter( 'dt_storage_connections_obj_url', 'dt_storage_connections_obj_url', 10, 4 );
function dt_storage_connections_obj_url( $url, $storage_connection_id, $key, $args = [] ): string {

    // Ensure required storage connection settings, are available.
    $storage_connection_types = apply_filters( 'dt_storage_connection_types', [] );
    $storage_connection_filter = apply_filters( 'dt_storage_connections_by_id', [], $storage_connection_id );
    $storage_connection = null;
    if ( !empty( $storage_connection_filter ) ) {
        $storage_connection = $storage_connection_filter[0];
    }

    if ( !empty( $storage_connection ) && isset( $storage_connection_types[$storage_connection['type']] ) ){
        $storage_connection_type = $storage_connection_types[$storage_connection['type']];

        // Retrieve file (by specified key) accordingly, based on associated connection api.
        switch ( $storage_connection_type['api'] ) {
            case 's3':
                $config = (array) $storage_connection[$storage_connection['type']];
                if ( isset( $config['access_key'], $config['secret_access_key'], $config['region'], $config['bucket'], $config['endpoint'] ) ) {

                    try {

                        require_once( 'vendor/autoload.php' );

                        // Ensure endpoint reference has the correct protocol schema.
                        $endpoint = Disciple_Tools_Storage_API::validate_url( $config['endpoint'] );

                        // Instantiate required aws s3 client object.
                        $s3 = new Aws\S3\S3Client( [
                            'region' => $config['region'],
                            'version' => 'latest',
                            'credentials' => [
                                'key' => $config['access_key'],
                                'secret' => $config['secret_access_key']
                            ],
                            'endpoint' => $endpoint
                        ] );
                        $bucket = $config['bucket'];

                        // Generate complete file key name to be used moving forward.
                        $key_name = ( isset( $storage_connection_type['prefix_bucket_name_to_obj_key'] ) && $storage_connection_type['prefix_bucket_name_to_obj_key'] ) ? ( $bucket .'/'. $key ) : $key;

                        // Obtain GetObject command handle.
                        $cmd = $s3->getCommand( 'GetObject', [
                            'Bucket' => $bucket,
                            'Key' => $key_name
                        ] );

                        // Create pre-signed url request and accordingly specify keep-alive duration time.
                        $request = $s3->createPresignedRequest( $cmd, $args['keep_alive'] ?? '+5 minutes' );

                        // Fetch pre-signed URL; which will be accessible for the duration of the keep_alive value.
                        $url = (string) $request->getUri();

                    } catch ( Exception $e ) {
                        $url = '';
                    }
                }
                break;

            default:
                break;
        }
    }

    return $url;
}

add_action( 'dt_storage_connections_obj_content', 'dt_storage_connections_obj_content', 10, 3 );
function dt_storage_connections_obj_content( $key, $storage_connection_id, $args = [
    'html_tag' => 'img',
    'size' => 150
] ): void {
    if ( apply_filters( 'dt_storage_connections_enabled', false, $storage_connection_id ) ) {
        $obj_url = apply_filters( 'dt_storage_connections_obj_url', null, $storage_connection_id, $key, [ 'keep_alive' => '+10 minutes' ] );
        if ( !empty( $obj_url ) ) {
            $size = $args['size'];

            // Determine html tag shape to be adopted.
            switch ( $args['html_tag'] ) {
                case 'img':
                    ?>
                    <img src="<?php echo esc_attr( $obj_url ); ?>" alt="" width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>" />
                    <?php
                    break;
                default:
                    break;
            }
        }
    }
}


