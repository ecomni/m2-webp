<?php

namespace Ecomni\Webp\Model\Processor;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface as EntryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;

class ProductProcessor
{
    public function __construct(
        protected \Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface $galleryManagement,
        protected \Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory $entryFactory,
        protected \Magento\Framework\Api\Data\ImageContentInterfaceFactory $imageContentFactory,
        protected \Ecomni\Webp\Model\ConverterPool $converterPool,
        protected \Magento\Framework\Filesystem\DirectoryList $directoryList,
    ) {
    }

    /**
     * @param ProductInterface $product
     * @param EntryInterface $entry
     * @throws \Exception
     * @return bool
     */
    public function process(ProductInterface $product, EntryInterface $entry): void
    {
        chdir($this->directoryList->getRoot());
        $mediaPath = $product->getMediaConfig()->getMediaPath($entry->getFile());

        $webpPath = $this->converterPool->convert($mediaPath);

        $imageContent = $this->imageContentFactory->create();
        $imageContent->setBase64EncodedData(base64_encode(file_get_contents($webpPath)));
        $imageContent->setType('image/webp');
        $imageContent->setName(basename($webpPath));

        $newEntry = $this->entryFactory->create();
        $newEntry->setDisabled(false)
            ->setLabel($entry->getLabel())
            ->setPosition($entry->getPosition())
            ->setTypes($entry->getTypes())
            ->setContent($imageContent)
            ->setFile($webpPath)
            ->setMediaType(ImageEntryConverter::MEDIA_TYPE_CODE);

        /**
         * GalleryManagement also offers an `update` method, but that doesn't update the
         * `catalog_product_entity_media_gallery` table, so we have to remove the old entry
         * and create a new one. Only remove $entry when the $newEntry is successfully created, though.
         */
        if ($this->galleryManagement->create($product->getSku(), $newEntry)) {
            $this->galleryManagement->remove($product->getSku(), $entry->getId());
        }
    }
}
