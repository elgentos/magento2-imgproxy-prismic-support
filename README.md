# elgentos/magento2-imgproxy-prismic-support

Integration module that connects the [elgentos/magento2-imgproxy](https://github.com/elgentos/magento2-imgproxy) and the [elgentos/magento2-prismicio](https://github.com/elgentos/magento2-prismicio) extensions for Magento 2.

## What it does

Automatically transforms Prismic CDN image URLs through imgproxy before rendering, with the main goal of Prismic API usage reduction.

These are also benefits, but these are also handled by Prismic (which uses Imgix internally);
- On-the-fly image optimization
- Responsive image sizing
- Format conversion (WebP, AVIF)
- Bandwidth savings

## Requirements

- PHP 8.3+
- Magento 2.4+
- `elgentos/magento2-imgproxy` - installed and configured
- `elgentos/module-prismicio` - installed and configured

## Installation

```bash
composer require elgentos/magento2-imgproxy-prismic-support
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

No additional configuration needed. The module:
1. Respects imgproxy enable/disable setting
2. Automatically detects Prismic image blocks
3. Transforms URLs before rendering

## Scope

**Supported:**
- Standalone Prismic Image blocks (`Elgentos\PrismicIO\Block\Dom\Image`)

**Not supported:**
- Images embedded in RichText blocks (different rendering pipeline)

## How it works

The module uses a BEFORE plugin that:

1. Intercepts `Elgentos\PrismicIO\Block\Dom\Image::fetchDocumentView()` before it renders the HTML
2. Checks if imgproxy is enabled
3. Retrieves the image context (URL, width, height)
4. Transforms the URL through imgproxy's `getCustomUrl()` method
5. Modifies the context URL in-place
6. Allows the original method to proceed with the transformed URL

This ensures all standalone Prismic images are optimized through imgproxy without any template modifications.

## Troubleshooting

### Images not transforming

1. Verify imgproxy module is enabled and configured:
   ```bash
   bin/magento module:status | grep Imgproxy
   ```

2. Check if imgproxy is enabled in system configuration:
   - Admin > Stores > Configuration > Imgproxy > General > Enabled

3. Check logs for transformation errors:
   ```bash
   tail -f var/log/system.log | grep IMGPROXY-PRISMIC
   ```

### Inspect rendered HTML

To verify URL transformation is working:

```html
<!-- Before (original Prismic URL): -->
<img src="https://images.prismic.io/example/image.jpg" alt="..." width="800" height="600" />

<!-- After (imgproxy transformed URL): -->
<img src="https://imgproxy.example.com/[signature]/resize:fit:800:600/plain/https://images.prismic.io/example/image.jpg" alt="..." width="800" height="600" />
```

### Enable debug logging

The module logs all transformations and errors with the `[IMGPROXY-PRISMIC]` prefix. Errors are logged at the ERROR level, so check:

```bash
grep IMGPROXY-PRISMIC var/log/system.log
```

## Architecture

- **Plugin Type:** BEFORE
- **Target:** `Elgentos\PrismicIO\Block\Dom\Image::fetchDocumentView()`
- **Dependencies:** Config and Image model from `elgentos/magento2-imgproxy`

The BEFORE plugin intercepts the method before HTML rendering, allowing clean URL transformation by reference modification of the context object.

## License

MIT

## Support

For issues or feature requests, please contact info@elgentos.nl
