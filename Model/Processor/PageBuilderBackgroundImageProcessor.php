<?php

namespace Ecomni\Webp\Model\Processor;

use Magento\Framework\App\Filesystem\DirectoryList;

class PageBuilderBackgroundImageProcessor
{
    public function __construct(
        protected \Magento\Framework\Serialize\Serializer\Json $json,
        protected \Ecomni\Webp\Model\ConverterPool $converterPool,
        protected \Magento\MediaGallerySynchronizationApi\Model\CreateAssetFromFileInterface $createAssetFromFile,
        protected \Magento\MediaGalleryApi\Api\SaveAssetsInterface $saveAssets,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\Filesystem\DirectoryList $directoryList,
        protected \Ecomni\Webp\Model\Util\IsConvertible $isConvertible,
        protected \Ecomni\Webp\Model\Util\NormalizeUrl $normalizeUrl,
    ) {
    }

    public function process(string $html, int &$convertedCount): string
    {
        chdir($this->directoryList->getRoot());
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

            $assets = [];

            if (isset($images['desktop_image'])) {
                if ($desktopPath = $this->processImage($images['desktop_image'], $assets)) {
                    $images['desktop_image'] = $desktopPath;
                }
            }

            if (isset($images['mobile_image'])) {
                if ($mobilePath = $this->processImage($images['mobile_image'], $assets)) {
                    $images['mobile_image'] = $mobilePath;
                }
            }

            if (count($assets)) {
                $this->saveAssets->execute($assets);
                $images = json_encode($images, JSON_UNESCAPED_SLASHES);
                $backgroundImages->nodeValue = $images;
                $node->setAttribute('data-background-images', $backgroundImages->nodeValue);
                $html = $document->saveHTML();
                $convertedCount += count($assets);
            }
        }
        return $html;
    }

    protected function processImage(string $filePath, array &$assets): ?string
    {
        if (!$this->isConvertible->isAllowedByImageUrl($filePath)) {
            return null;
        }
        if (!$this->isConvertible->isConvertibleImage($filePath)) {
            return null;
        }

        try {
            $filePath = $this->normalizeUrl->normalizeInlineFilePath($filePath);
            $webpPath = $this->converterPool->convert($filePath);
            $webpPath = $this->normalizeUrl->encodeFilePath($webpPath);
            $assets[] = $this->saveAsset($webpPath);
            return $webpPath;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        return null;
    }

    protected function saveAsset(string $path): \Magento\MediaGalleryApi\Api\Data\AssetInterface
    {
        $assetPath = ltrim(str_replace([DirectoryList::PUB, DirectoryList::MEDIA], '', $path), '/');
        return $this->createAssetFromFile->execute($assetPath);
    }
}
