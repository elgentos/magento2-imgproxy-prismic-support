<?php

declare(strict_types=1);

namespace Elgentos\ImgproxyPrismicSupport\Plugin;

use Elgentos\Imgproxy\Model\Config;
use Elgentos\Imgproxy\Model\Image as ImgproxyImage;
use Elgentos\PrismicIO\Block\Dom\Image as PrismicImage;
use Psr\Log\LoggerInterface;

class PrismicImageUrlTransformer
{
    public function __construct(
        private readonly Config $config,
        private readonly ImgproxyImage $imgproxyImage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Transform Prismic image URLs through imgproxy before rendering HTML
     *
     * Intercepts the fetchDocumentView method BEFORE execution to modify
     * the context URL. Since context is a stdClass object, modifications
     * are applied by reference and persist to the original method.
     *
     * @param PrismicImage $subject
     * @return void
     */
    public function beforeFetchDocumentView(PrismicImage $subject): void
    {
        // Early return if imgproxy is disabled
        if (!$this->config->isEnabled()) {
            return;
        }

        // Check if block has context before attempting to retrieve it
        if (!$subject->hasContext()) {
            return;
        }

        try {
            // Retrieve the context object (stdClass with url, alt, dimensions properties)
            $context = $subject->getContext();

            // Validate required properties exist
            if (!isset($context->url) || !isset($context->dimensions->width) || !isset($context->dimensions->height)) {
                $this->logger->warning(
                    '[IMGPROXY-PRISMIC] Context missing required properties for image transformation.',
                    [
                        'has_url' => isset($context->url),
                        'has_width' => isset($context->dimensions->width),
                        'has_height' => isset($context->dimensions->height),
                    ]
                );
                return;
            }

            // Extract dimensions as integers
            $width = (int) $context->dimensions->width;
            $height = (int) $context->dimensions->height;

            // Transform URL through imgproxy
            $transformedUrl = $this->imgproxyImage->getCustomUrl(
                $context->url,
                $width,
                $height
            );

            // Modify context URL in-place (stdClass is passed by reference)
            $context->url = $transformedUrl;

        } catch (\Throwable $e) {
            // Log any errors but don't break rendering
            $this->logger->error(
                '[IMGPROXY-PRISMIC] Failed to transform Prismic image URL through imgproxy.',
                [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }
}
