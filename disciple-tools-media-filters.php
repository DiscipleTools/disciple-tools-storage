<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


add_filter( 'dt_media_connection_types', 'dt_media_connection_types', 10, 1 );
function dt_media_connection_types( $connection_types ) {
    foreach ( Disciple_Tools_Media_API::list_supported_connection_types() as $key => $type ) {
        if ( $type['enabled'] ) {
            $connection_types[ $key ] = $type;
        }
    }

    return $connection_types;
}

add_filter( 'dt_media_connections', 'dt_media_connections', 10, 1 );
function dt_media_connections( $connections ) {
    foreach ( Disciple_Tools_Media_API::fetch_option_connection_objs() as $id => $connection ) {
        if ( $connection->enabled ) {
            $connections[] = (array) $connection;
        }
    }

    return $connections;
}

add_filter( 'dt_media_connections_by_id', 'dt_media_connections_by_id', 10, 2 );
function dt_media_connections_by_id( $connections, $id ) {
    $connection = Disciple_Tools_Media_API::fetch_option_connection_obj( $id );

    if ( !empty( $connection ) && isset( $connection->enabled ) && $connection->enabled ) {
        $connections[] = (array) $connection;
    }

    return $connections;
}

add_filter( 'dt_media_connections_enabled', 'dt_media_connections_enabled', 10, 2 );
function dt_media_connections_enabled( $response, $id ): bool {
    return !empty( apply_filters( 'dt_media_connections_by_id', [], $id ) );
}

add_filter( 'dt_media_connections_obj_upload', 'dt_media_connections_obj_upload', 10, 4 );
function dt_media_connections_obj_upload( $response, $media_connection_id, $key, $upload = [] ) {
    dt_write_log( $media_connection_id );
    dt_write_log( $key );
    dt_write_log( $upload );

    // Ensure required media connection settings, are available.
    $media_connection_types = apply_filters( 'dt_media_connection_types', [] );
    $media_connection_filter = apply_filters( 'dt_media_connections_by_id', [], $media_connection_id );
    $media_connection = null;
    if ( !empty( $media_connection_filter ) ) {
        $media_connection = $media_connection_filter[0];
    }

    if ( !empty( $media_connection ) && isset( $media_connection_types[$media_connection['type']] ) ){
        $media_connection_type = $media_connection_types[$media_connection['type']];

        // Handle file upload accordingly, based on associated connection api.
        switch ( $media_connection_type['api'] ) {
            case 's3':
                dt_write_log( $media_connection );

                $config = (array) $media_connection[$media_connection['type']];
                if ( isset( $config['access_key'], $config['secret_access_key'], $config['region'], $config['bucket'], $config['endpoint'] ) ) {

                    require_once( 'vendor/autoload.php' );

                    // Instantiate required aws s3 client object.
                    $s3 = new Aws\S3\S3Client( [
                        'region' => $config['region'],
                        'version' => 'latest',
                        'credentials' => [
                            'key' => $config['access_key'],
                            'secret' => $config['secret_access_key']
                        ],
                        'endpoint' => $config['endpoint']
                    ] );
                    $bucket = $config['bucket'];

                    // Generate complete file key name to be used moving forward; prefixing with bucket id.
                    $key_name = $bucket . $key;

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

                            dt_write_log( "Uploading part {$part_number} of {$filename}." );
                        }
                        fclose( $file );

                    } catch ( Exception $e ) {
                        $result = $s3->abortMultipartUpload( [
                            'Bucket' => $bucket,
                            'Key' => $key_name,
                            'UploadId' => $upload_id
                        ] );

                        dt_write_log( "Upload of {$filename} failed." );
                        dt_write_log( $e );
                    }

                    // Complete the multipart upload.
                    $result = $s3->completeMultipartUpload( [
                        'Bucket' => $bucket,
                        'Key' => $key_name,
                        'UploadId' => $upload_id,
                        'MultipartUpload' => $parts,
                    ] );
                    $url = $result['Location'];

                    dt_write_log( "Uploaded {$filename} to {$url}." );
                    dt_write_log( $result );

                    $response = $result;
                }
                break;
            default:
                break;
        }
    }

    return $response;
}

add_filter( 'dt_media_connections_obj_url', 'dt_media_connections_obj_url', 10, 4 );
function dt_media_connections_obj_url( $url, $media_connection_id, $key, $args = [] ): string {
    dt_write_log( $media_connection_id );
    dt_write_log( $key );
    dt_write_log( $args );

    // Ensure required media connection settings, are available.
    $media_connection_types = apply_filters( 'dt_media_connection_types', [] );
    $media_connection_filter = apply_filters( 'dt_media_connections_by_id', [], $media_connection_id );
    $media_connection = null;
    if ( !empty( $media_connection_filter ) ) {
        $media_connection = $media_connection_filter[0];
    }

    if ( !empty( $media_connection ) && isset( $media_connection_types[$media_connection['type']] ) ){
        $media_connection_type = $media_connection_types[$media_connection['type']];

        // Retrieve file (by specified key) accordingly, based on associated connection api.
        switch ( $media_connection_type['api'] ) {
            case 's3':
                dt_write_log( $media_connection );

                $config = (array) $media_connection[$media_connection['type']];
                if ( isset( $config['access_key'], $config['secret_access_key'], $config['region'], $config['bucket'], $config['endpoint'] ) ) {

                    try {

                        require_once( 'vendor/autoload.php' );

                        // Instantiate required aws s3 client object.
                        $s3 = new Aws\S3\S3Client( [
                            'region' => $config['region'],
                            'version' => 'latest',
                            'credentials' => [
                                'key' => $config['access_key'],
                                'secret' => $config['secret_access_key']
                            ],
                            'endpoint' => $config['endpoint']
                        ] );
                        $bucket = $config['bucket'];

                        // Generate complete file key name to be used moving forward; prefixing with bucket id.
                        $key_name = $bucket . $key;

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
                        dt_write_log( $e );
                        $url = null;
                    }
                }
                break;

            default:
                break;
        }
    }

    dt_write_log( $url );

    return $url;
}


