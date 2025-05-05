![Build Status](https://github.com/DiscipleTools/disciple-tools-storage/actions/workflows/ci.yml/badge.svg?branch=master)

# Disciple.Tools - Storage

Disciple.Tools - Storage is intended to help manage connections with remote object storage services, such as AWS S3, Backblaze, etc.

## Community

Need help or have a question? Join the D.T Forum: https://community.disciple.tools/category/17/d-t-storage

## Purpose

Provide the ability to store/retrieve all storage content within 3rd party object storage services; offering greater security.

## Security
Keep your files in a private S3 Bucket, protected from being findable from the web. This integration with Disciple.Tools creates short lived links (24 hours) to display images.

## API

See the [API Documentation](https://github.com/DiscipleTools/disciple-tools-storage/wiki/API) for more information.
```php
DT_Storage::get_file_url( string $key = '' )
```
```php
DT_Storage::upload_file( string $key_prefix = '', array $upload = [], string $existing_key = '', array $args = [] )
```

## Setup

- Once D.T Storage Plugin has been installed, create a new connection. Go to WP Admin > Extensions (D.T) > Storage.

![1](/documentation/readme/imgs/1.png)

- The following connection types (3rd Party Object Storage Services) are currently supported:
  - [BackBlaze](https://www.backblaze.com/) (Recommened) - See our [BackBlaze setup notes](https://disciple.tools/docs/storage/#backblaze)
  - [AWS S3](https://aws.amazon.com/s3/) - See AWS S3 setup instructions [here](/SETUP_AWS_S3.md)
  - [MinIO](https://min.io/)


- Enter required connection details; ensuring specified bucket has already been created within 3rd party object storage service.

![2](/documentation/readme/imgs/2.png)

> If no endpoint protocol scheme is specified; then https:// will be used.


- Once new connection has been validated and saved, navigate to Storage Settings section within D.T General Settings and select connection to be used for the default media storage within D.T.

![6](/documentation/readme/imgs/6.png)


## Requirements

- Disciple.Tools Theme installed on a Wordpress Server.
- Ensure PHP v8.1 or greater, has been installed.

## Installing

- Install as a standard Disciple.Tools/Wordpress plugin in the system Admin/Plugins area.
- Requires the user role of Administrator.

## Contribution

Contributions welcome. You can report issues and bugs in the
[Issues](https://github.com/DiscipleTools/disciple-tools-storage/issues) section of the repo. You can present ideas
in the [Discussions](https://github.com/DiscipleTools/disciple-tools-storage/discussions) section of the repo. And
code contributions are welcome using the [Pull Request](https://github.com/DiscipleTools/disciple-tools-storage/pulls)
system for git. For a more details on contribution see the
[contribution guidelines](https://github.com/DiscipleTools/disciple-tools-storage/blob/master/CONTRIBUTING.md).
