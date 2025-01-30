<?php

namespace Ecomni\Webp\Model\Converter;

use Magento\Framework\App\Filesystem\DirectoryList;

class PageBuilderBackgroundImageConverter
{
    public function __construct(
        protected \Magento\Framework\Serialize\Serializer\Json $json,
        protected \Ecomni\Webp\Model\Util\LocalUrl $localUrl,
        protected \Ecomni\Webp\Model\WebpConverter $webpConverter,
        protected \Magento\MediaGallerySynchronizationApi\Model\CreateAssetFromFileInterface $createAssetFromFile,
        protected \Magento\MediaGalleryApi\Api\SaveAssetsInterface $saveAssets,
        protected \Psr\Log\LoggerInterface $logger,
    ) {
    }

    public function convert(string $html, int &$convertedCount): string
    {
        $document = new \DOMDocument();
        $document->loadHTML($html);

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//*[@data-background-images]');

        /** @var \DOMNode $node */
        foreach ($nodes as $node) {
            $backgroundImages = $node->attributes->getNamedItem('data-background-images');
            if (empty(trim($backgroundImages->nodeValue))) {
                continue;
            }
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $images = $this->json->unserialize(stripslashes($backgroundImages->nodeValue));
            if (empty($images)) {
                continue;
            }

            $hasChanges = false;
            $assets = [];

            if ($this->isConvertibleImage($images['desktop_image'] ?? '')) {
                $desktopImageUrl = $this->normalizeFilePath($images['desktop_image']);
                if ($webp = $this->processImage($desktopImageUrl)) {
                    /** @var \Magento\MediaGalleryApi\Api\Data\AssetInterface $asset */
                    $images['desktop_image'] = $this->encodeFilePath($webp['path']);
                    try {
                        $assets[] = $this->saveAsset($webp['path']);
                        $hasChanges = true;
                        $convertedCount++;
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                        continue;
                    }
                }
            }

            if ($this->isConvertibleImage($images['mobile_image'] ?? '')) {
                $mobileImageUrl = $this->normalizeFilePath($images['mobile_image']);
                if ($webp = $this->processImage($mobileImageUrl)) {
                    $images['mobile_image'] = $this->encodeFilePath($webp['path']);
                    try {
                        $assets[] = $this->saveAsset($webp['path']);
                        $hasChanges = true;
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                        continue;
                    }
                }
            }

            if ($hasChanges) {
                $this->saveAssets->execute($assets);
                $images = json_encode($images, JSON_UNESCAPED_SLASHES);
                $backgroundImages->nodeValue = $images;
                $node->setAttribute('data-background-images', $backgroundImages->nodeValue);
                $html = $document->saveHTML();
            }
        }
        return $html;
    }

    protected function saveAsset(string $path): \Magento\MediaGalleryApi\Api\Data\AssetInterface
    {
        $assetPath = ltrim(str_replace([DirectoryList::PUB, DirectoryList::MEDIA], '', $path), '/');
        $asset = $this->createAssetFromFile->execute($assetPath);
        return $asset;
    }

    protected function processImage(string $imageUrl): ?array
    {
        if (!$this->isAllowedByImageUrl($imageUrl)) {
            return null;
        }
        $webp = $this->webpConverter->convert($imageUrl);
        if (!$webp) {
            return null;
        }
        return $webp;
    }

    protected function isAllowedByImageUrl(string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }
        if (!preg_match('/\.(jpg|jpeg|png)/i', $imageUrl)) {
            return false;
        }
        if (str_starts_with('data:', $imageUrl)) {
            return false;
        }
        if (!$this->localUrl->isLocal($imageUrl)) {
            return false;
        }
        if (str_contains($imageUrl, '/media/captcha/')) {
            return false;
        }
        return true;
    }

    /**
     * Convert {{media url=xxx}} into media/xxx.
     *
     * @param string $filePath
     * @return string
     */
    protected function normalizeFilePath(string $filePath): string
    {
        $filePath = str_replace(
            [
                DirectoryList::PUB,
                DirectoryList::MEDIA,
                '{{',
                'url=',
                '}}'
            ],
            '',
            $filePath
        );
        $filePath = ltrim($filePath);
        return sprintf('/%s/%s', DirectoryList::MEDIA, $filePath);
    }

    /**
     * Convert media/xxx into {{media url=xxx}}.
     *
     * @param string $filePath
     * @return string
     */
    protected function encodeFilePath(string $filePath): string
    {
        $filePath = ltrim(str_replace('media', '', $filePath), '/');
        return sprintf('{{media url=%s}}', $filePath);
    }

    protected function isConvertibleImage(string $path): bool
    {
        return str_contains($path, '.png') || str_contains($path, '.jpg') || str_contains($path, '.jpeg');
    }
}
