<?php

namespace Ecomni\Webp\Model;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface as EntryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;
use Magento\Framework\App\Filesystem\DirectoryList;

class Converter
{
    public function __construct(
        protected \Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface $galleryManagement,
        protected \Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory $entryFactory,
        protected \Magento\Framework\Api\Data\ImageContentInterfaceFactory $imageContentFactory,
        protected \Magento\Framework\Filesystem\Io\File $file,
        protected \Ecomni\Webp\Model\Config\Config $config,
    ) {
    }

    /**
     * @param ProductInterface $product
     * @param EntryInterface $entry
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function convert(ProductInterface $product, EntryInterface $entry): bool
    {
        $mediaPath = $product->getMediaConfig()->getMediaPath($entry->getFile());
        $url = sprintf('%s/%s/%s', DirectoryList::PUB, DirectoryList::MEDIA, $mediaPath);
        if ($this->cWebP($url)) {
            $newPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $entry->getFile());
            $newUrl = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $url);
            $fileName = $this->file->getPathInfo($newPath)['basename'];

            $imageContent = $this->imageContentFactory->create();
            $imageContent->setBase64EncodedData(base64_encode(file_get_contents($newUrl)));
            $imageContent->setType('image/webp');
            $imageContent->setName($fileName);

            $newEntry = $this->entryFactory->create();
            $newEntry->setDisabled(false)
                ->setLabel($entry->getLabel())
                ->setPosition($entry->getPosition())
                ->setTypes($entry->getTypes())
                ->setContent($imageContent)
                ->setFile($newPath)
                ->setMediaType(ImageEntryConverter::MEDIA_TYPE_CODE);

            /**
             * GalleryManagement also offers an `update` method, but that doesn't update the
             * `catalog_product_entity_media_gallery` table, so we have to remove the old entry
             * and create a new one. Only remove $entry when the $newEntry is successfully created, though.
             */
            if ($this->galleryManagement->create($product->getSku(), $newEntry)) {
                return $this->galleryManagement->remove($product->getSku(), $entry->getId());
            }
        }
        return false;
    }

    protected function cWebP(string $url): bool
    {
        $quality = $this->config->getQuality();
        $newUrl = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $url);
        exec(sprintf('cwebp -q %d %s -o %s', (int)$quality, $url, $newUrl), $output, $resultCode);
        return $resultCode === 0;
    }
}
