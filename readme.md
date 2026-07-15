# AIO Media Library Manager

A WordPress media library folders plugin that lets you organize your media library into unlimited nested folders with drag-and-drop, then offload uploads to AWS S3 or Cloudflare R2 with automatic CDN URL rewriting.

## Overview

The default WordPress media library dumps every image, video, PDF, and document into one long, unsorted grid. As a site grows, finding a specific file becomes a scavenger hunt. **AIO Media Library Manager** solves this by adding a real folder structure to the media library — a drag and drop media organizer built on top of WordPress's native attachment taxonomy, so folders behave like first-class citizens across the admin, the block editor, and the REST API.

Beyond organization, the plugin also works as a WordPress CDN offload plugin: connect an AWS S3 or Cloudflare R2 bucket and new uploads (including all generated image sizes) are pushed to cloud storage automatically, with attachment URLs and `srcset` rewritten to serve from your CDN. That means less storage used on your web host and faster image delivery for visitors.

It's built for bloggers who want a folder per post, photographers organizing shoots by client and date, ecommerce/WooCommerce store owners who need product images sorted by category and brand, agencies managing media for multiple clients in one install, and any WordPress site with a media library that has grown out of control.

## Key Features

- **Drag-and-drop folder UI** — move files between folders, or drag a whole folder to reorganize your hierarchy, right inside `wp-admin`.
- **Unlimited nested media folders** — build a multi-level structure (e.g. Clients → Client Name → Shoot Type) with no depth limit, powered by a custom attachment taxonomy.
- **Folder management tools** — create, rename, delete (including multi-select bulk delete), copy/paste, and cut/paste folders.
- **AWS S3 connector** — connect an S3 bucket, store credentials securely, and automatically upload new attachments (originals and every generated size) to your bucket.
- **Cloudflare R2 connector** — the same automatic offload workflow for R2's S3-compatible storage, using your Cloudflare account ID and bucket.
- **Automatic CDN upload with URL and srcset rewriting** — once a provider is connected, `wp_get_attachment_url` and responsive `srcset` output are rewritten to point at your CDN/public URL instead of your server.
- **Media Library REST API and AJAX filtering** — folder-aware filtering is wired into `ajax_query_attachments_args`, `rest_media_query`, and a custom `aioml/v1/media/remove-from-folder` REST route, so folder filtering works in the classic media modal, the block editor media picker, and custom REST clients.
- **React-based folder interface** — a modern, embedded React UI for browsing and managing folders alongside the standard media grid and list views.
- **Page builder and post editor integration** — browse and filter folders from within the post/page editor and popular page builders, not just the Media Library screen.
- **Encrypted credential storage** — S3/R2 secret keys are encrypted at rest using your site's WordPress auth keys.

## Use Cases

- **Bloggers** — keep a dedicated folder for each post's images and documents instead of hunting through hundreds of unsorted uploads.
- **Photographers** — organize thousands of images into folders by shoot type (weddings, portraits, landscapes) with client and date subfolders, so any photo can be found in seconds.
- **Ecommerce / WooCommerce sites** — sort product images by category and sub-categorize by brand for fast, confusion-free product image management.
- **Agencies and multi-client sites** — separate client media into isolated folder trees within a single WordPress install.
- **High-traffic or media-heavy sites** — offload uploads to S3 or Cloudflare R2 to reduce server storage usage and bandwidth, and serve images from a CDN for faster page loads.

## Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 5.2.4 or higher (PHP 7.4+ recommended for the S3/R2 connectors)
- **Other requirements:** An AWS S3 or Cloudflare R2 account with a bucket and access credentials is only needed if you want to use the cloud storage / CDN offload features — folder organization works with no external service.

## Installation

### Install from WordPress

1. Download the plugin ZIP file.
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin.
3. Upload the ZIP, click **Install Now**, then **Activate**.

### Manual Installation

1. Download or clone this repository.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate **AIO Media Library Manager** from WordPress Admin → Plugins.

## Configuration / Setup

1. After activation, open **Media → AIOML Settings** in the WordPress admin.
2. To organize media, go to the **Media Library** screen — a folder sidebar appears alongside the existing media grid. Use **Create Folder** to add your first folder, and drag files or folders to reorganize.
3. To enable cloud storage/CDN offload, open the **AWS S3** or **Cloudflare R2** card on the AIOML Settings page and click **Connect**.
   - For **AWS S3**: enter your Access Key ID, Secret Access Key, Bucket Name, and Region.
   - For **Cloudflare R2**: enter your Access Key ID, Secret Access Key, Bucket Name, and Cloudflare Account ID.
4. Optionally set a **Public URL / Custom Domain** so rewritten attachment and `srcset` URLs point to your own CDN domain instead of the default bucket endpoint.
5. Click **Test Connection** to verify the bucket is reachable, then **Save Credentials**. New uploads (and their generated image sizes) will be pushed to the connected provider automatically.

## Usage

Once folders are set up, the Media Library screen shows a folder tree on one side and the standard media grid/list on the other. Select a folder to filter the grid to just that folder's files, drag and drop uploads between folders, or use bulk actions to move, copy/paste, cut/paste, or multi-select delete folders. The same folder filtering is available from the media picker in the block editor and post editor, and programmatically through the WordPress REST API (`wp/v2/media` with folder-aware query filtering, plus the plugin's own `aioml/v1/media/remove-from-folder` endpoint) — useful if you're building a custom media browser or extending the media library UI.

## Supported Integrations

- WordPress Media Library (Grid and List views)
- WordPress Block Editor (Gutenberg) media picker
- WordPress REST API (`wp/v2/media`)
- Popular WordPress page builders (folder browsing within the page builder media interface)
- AWS S3
- Cloudflare R2

## Screenshots / Demo

![AIO Media Library Manager Dashboard](assets/plugin-dashboard.png)

*All-In-One Media Library Manager view — folder sidebar with drag-and-drop media organization.*

## Documentation

For setup guidance and support resources, visit [smackcoders.com](https://www.smackcoders.com/wordpress.html) or see the Configuration/Setup and Usage sections above.

## Frequently Asked Questions

### What is AIO Media Library Manager?
AIO Media Library Manager is a WordPress plugin that helps you organize your media files — images, videos, documents, and more — within the WordPress media library by letting you create folders, making it much easier to find what you need.

### Can I create nested folders?
Yes. The plugin supports unlimited nested media folders, so you can build multi-level structures like Client → Shoot Type → Date without any depth limit.

### Does it support Cloudflare R2?
Yes. Connect a Cloudflare R2 bucket with your account ID and credentials, and new uploads are automatically pushed to R2 with attachment URLs rewritten to your CDN.

### Does it support AWS S3?
Yes. Connect an AWS S3 bucket with your access key, secret key, region, and bucket name to enable automatic upload and CDN offload.

### Will offloading break my existing image URLs?
No. URL rewriting only applies to attachments that have been uploaded to the connected provider; if a public URL/custom domain isn't configured or an object hasn't been offloaded, the plugin falls back to the original WordPress-hosted URL.

### Can I filter media by folder in the post editor?
Yes. Folder-aware filtering is wired into both the classic AJAX media query and the REST-powered block editor media picker, so you can browse folders directly from the post/page editor.

### Does it support unlimited folders?
Yes, there's no limit on the number of folders or nesting depth you can create.

## Roadmap

- Additional CDN/cloud storage provider connectors
- Expanded page builder integrations
- Folder-level access/permission controls

## Changelog

### 1.0.0
- Initial release.
- Drag-and-drop folder-based media organization with unlimited nested folders.
- Folder create, rename, delete, bulk delete, copy/paste, and cut/paste.
- Media Library REST API and AJAX query filtering by folder.
- AWS S3 and Cloudflare R2 connectors with automatic upload and CDN URL/srcset rewriting.
- Page builder and block editor media picker integration.

## Security

If you discover a security vulnerability in AIO Media Library Manager, please do not disclose it publicly via GitHub Issues. Instead, report it directly to the Smackcoders team at [smackcoders.com/contact-us.html](https://www.smackcoders.com/contact-us.html) so it can be investigated and patched responsibly. S3/R2 secret keys are encrypted at rest and never displayed in plaintext once saved.

## Contributing

Bug reports, feature suggestions, and pull requests are welcome. Please open a GitHub Issue describing the bug or feature request with enough detail (WordPress/PHP version, steps to reproduce) for it to be investigated, and submit pull requests against this repository for code changes.

## Support

For help, bug reports, or feature requests, open an issue in this repository's GitHub Issues, or reach out via the [Smackcoders contact page](https://www.smackcoders.com/contact-us.html).

## License

Licensed under the GNU General Public License v2 (or later). See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html) for full license text.

## Disclaimer

AWS S3 and Cloudflare R2 are trademarks of their respective owners (Amazon Web Services, Inc. and Cloudflare, Inc.). AIO Media Library Manager is an independent plugin that integrates with these third-party services and is not officially affiliated with, endorsed by, or sponsored by Amazon or Cloudflare. Use of these services is subject to their own terms and pricing.

## Author / Maintainer

Developed and maintained by [Smackcoders](https://www.smackcoders.com/wordpress.html).
