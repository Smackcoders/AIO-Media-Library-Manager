# AIO Media Library Manager

Organize the WordPress media library into nested drag-and-drop folders, then offload uploads to AWS S3 or Cloudflare R2 with automatic CDN rewriting.

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-777bb4.svg)](https://www.php.net/)

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Use Cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Supported Integrations](#supported-integrations)
- [Screenshots](#screenshots)
- [Documentation](#documentation)
- [FAQ](#faq)
- [Changelog](#changelog)
- [Security](#security)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)
- [Disclaimer](#disclaimer)
- [Author](#author)

## Overview

The default WordPress media library drops every image, video, PDF, and document into a single flat grid. On a small site that is fine. On a site with thousands of uploads, finding one file turns into scrolling and guessing.

AIO Media Library Manager adds a real folder structure on top of the library. Folders are built on a hierarchical WordPress taxonomy (`attachment_category`), so they behave consistently across the Media Library grid, the list view, the block editor media picker, and the REST API rather than living in a disconnected sidebar. You create folders, drag files into them, and filter the grid down to a single folder in one click.

The plugin also doubles as a cloud offload tool. Connect an AWS S3 or Cloudflare R2 bucket and every new upload, including all generated image sizes, is pushed to that bucket automatically. Attachment URLs and responsive `srcset` output are rewritten to serve from your bucket or custom CDN domain, which lightens storage on your web host and speeds up image delivery. The connectors use the AWS SDK for PHP when it is available and fall back to native, signed (AWS Signature V4) HTTP requests when it is not, so offloading works on a wide range of hosts.

## Key Features

- **Drag-and-drop organization** — move files into folders, or drag a folder to restructure the hierarchy, directly inside `wp-admin`.
- **Unlimited nested folders** — build multi-level structures such as Clients to Client Name to Shoot Type, with no depth limit, backed by a hierarchical attachment taxonomy.
- **Full folder management** — create, rename, delete, multi-select bulk delete, copy/paste (which duplicates the folder subtree and its media assignments), and cut/paste to move folders.
- **AWS S3 connector** — store bucket credentials, test the connection, and upload new attachments (originals and every generated size) to S3 automatically.
- **Cloudflare R2 connector** — the same offload workflow against R2's S3-compatible API, using your Cloudflare Account ID and bucket.
- **Automatic URL and srcset rewriting** — once a provider is active, `wp_get_attachment_url` and responsive `srcset` output point at your bucket or custom CDN domain instead of your server.
- **Folder-aware filtering everywhere** — folder filters are wired into the classic media modal (`ajax_query_attachments_args`), the Media Library list view (`pre_get_posts`), the block editor picker (`rest_media_query`), and a dedicated `aioml/v1/media/remove-from-folder` REST route.
- **React folder interface** — an embedded React UI for browsing and managing folders alongside the standard media grid and list views.
- **Post editor and page builder access** — browse and filter folders from the post/page editor and the WordPress customizer, not only the Media Library screen.
- **Encrypted credential storage** — S3 and R2 secret keys are encrypted at rest with AES-256-CBC, keyed from your site's WordPress authentication salts, and are never returned to the browser once saved.

## Use Cases

- **Bloggers** — keep one folder per post for its images and documents instead of scrolling through hundreds of unsorted uploads.
- **Photographers** — file thousands of images by shoot type, then subdivide by client and date, so any photo is a click away.
- **WooCommerce and ecommerce stores** — sort product images by category and by brand for clean, fast product image management.
- **Agencies and multi-client sites** — isolate each client's media in its own folder tree within a single WordPress install.
- **High-traffic and media-heavy sites** — offload uploads to S3 or R2 to cut server storage and bandwidth, and serve images from a CDN for faster page loads.

## Requirements

| Requirement | Version |
| --- | --- |
| WordPress | 6.0 or higher |
| PHP | 7.0 or higher (7.2.3+ recommended for the S3/R2 connectors) |
| Cloud storage account | AWS S3 or Cloudflare R2 (optional) |

Folder organization works with no external service. An AWS S3 or Cloudflare R2 account with a bucket and access credentials is only required if you want to use the cloud storage and CDN offload features.

## Installation

### Install from WordPress

1. Download the plugin ZIP file.
2. In the WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP, click **Install Now**, then **Activate**.

### Manual Installation

1. Download or clone this repository.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Go to **Plugins** in the WordPress admin and activate **AIO Media Library Manager**.

To use the S3 or R2 connectors with the AWS SDK for PHP, install Composer dependencies from the plugin directory:

```bash
composer install
```

If the SDK is not present, the connectors fall back to native signed HTTP requests, so this step is optional.

## Configuration

1. After activation, open **Media → AIOML Settings** in the WordPress admin.
2. To organize media, open the **Media Library** screen. A folder panel appears next to the media grid. Use it to create your first folder, then drag files or folders to arrange them.
3. To enable cloud offload, open the **Connectors** page (Media → AIOML Settings) and click **Connect** on the **AWS S3** or **Cloudflare R2** card.
   - For **AWS S3**: enter your Access Key ID, Secret Access Key, Bucket Name, and Region.
   - For **Cloudflare R2**: enter your Access Key ID, Secret Access Key, Account ID, and Bucket Name (region is fixed to `auto`).
4. Optionally set a **Public URL / Custom Domain** so rewritten attachment and `srcset` URLs point to your own CDN domain instead of the default bucket endpoint. R2 requires a public URL, since it has no default public bucket endpoint.
5. Click **Test Connection** to confirm the bucket is reachable, then **Save Credentials**. From then on, new uploads and their generated image sizes are pushed to the active provider automatically.

## Usage

With folders in place, the Media Library shows a folder tree on one side and the standard grid or list on the other. Select a folder to filter the view to that folder's files. Drag uploads between folders, or use folder actions to rename, copy/paste, cut/paste, or bulk-delete.

The same folder filtering is available from the media picker in the block editor and post editor, and programmatically through the WordPress REST API. Request `wp/v2/media` with an `attachment_category` term ID to return only that folder's attachments, or call the plugin's `aioml/v1/media/remove-from-folder` endpoint (POST, with `media_id` and `term_id`) to detach a file from a folder. This is useful when building a custom media browser or extending the media library UI.

## Supported Integrations

- WordPress Media Library (grid and list views)
- WordPress block editor (Gutenberg) media picker
- WordPress REST API (`wp/v2/media` with folder-aware filtering)
- WordPress post/page editor and customizer
- AWS S3
- Cloudflare R2

## Screenshots

This repository does not bundle screenshot images. A live view of the folder sidebar and drag-and-drop organization is available on the [plugin page](https://www.smackcoders.com/wordpress.html) and the [GitHub repository](https://github.com/Smackcoders/AIO-Media-Library-Manager).

## Documentation

For setup guidance and support resources, visit [smackcoders.com](https://www.smackcoders.com/wordpress.html), or follow the Configuration and Usage sections above.

## FAQ

### What is AIO Media Library Manager?

It is a WordPress plugin that organizes your media files, including images, videos, and documents, into folders inside the media library so you can find what you need without scrolling through an unsorted grid.

### Can I create nested folders?

Yes. Folders use a hierarchical taxonomy, so you can nest them as deeply as you like, for example Client to Shoot Type to Date, with no depth limit.

### Does it support AWS S3 and Cloudflare R2?

Yes. Connect an S3 bucket with your access key, secret key, region, and bucket name, or an R2 bucket with your access key, secret key, Account ID, and bucket name. New uploads are then pushed to the provider automatically, with URLs rewritten to your CDN.

### Will offloading break my existing image URLs?

No. URL rewriting applies only to attachments that have actually been offloaded to the connected provider. If an object has not been uploaded, or no public URL is configured for R2, the plugin serves the original WordPress-hosted URL.

### Can I filter media by folder in the post editor?

Yes. Folder filtering is wired into both the classic AJAX media query and the REST-powered block editor picker, so you can browse folders directly from the post or page editor.

### Are my cloud credentials stored securely?

Secret keys are encrypted at rest with AES-256-CBC, keyed from your site's WordPress authentication salts, and are never sent back to the browser after they are saved.

## Changelog

### 1.0.0

- Initial release.
- Drag-and-drop folder organization with unlimited nested folders.
- Folder create, rename, delete, bulk delete, copy/paste, and cut/paste.
- Folder-aware filtering across the media grid, list view, block editor picker, and REST API.
- AWS S3 and Cloudflare R2 connectors with automatic upload and CDN URL/srcset rewriting.
- Post/page editor and customizer media picker integration.

## Security

If you discover a security vulnerability, please do not disclose it publicly through GitHub Issues. Report it directly to the Smackcoders team through the [contact page](https://www.smackcoders.com/contact-us.html) so it can be investigated and patched responsibly. Cloud credentials are encrypted at rest and are never displayed in plaintext once saved.

## Contributing

Bug reports, feature suggestions, and pull requests are welcome. Open a [GitHub issue](https://github.com/Smackcoders/AIO-Media-Library-Manager/issues) with enough detail to reproduce the problem, including your WordPress and PHP versions and the steps you took, and submit pull requests against this repository for code changes.

## Support

For help, bug reports, or feature requests, open an issue on the [GitHub repository](https://github.com/Smackcoders/AIO-Media-Library-Manager/issues) or reach out through the [Smackcoders contact page](https://www.smackcoders.com/contact-us.html).

## License

Licensed under the GNU General Public License v2 (or later). See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html) for the full license text.

## Disclaimer

AWS S3 and Cloudflare R2 are trademarks of their respective owners (Amazon Web Services, Inc. and Cloudflare, Inc.). AIO Media Library Manager is an independent plugin that integrates with these third-party services and is not affiliated with, endorsed by, or sponsored by Amazon or Cloudflare. Use of these services is subject to their own terms and pricing.

## Author

Developed and maintained by [Smackcoders](https://www.smackcoders.com/wordpress.html).
