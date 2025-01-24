<?php

namespace Ecomni\Webp\Model\Converter;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface as EntryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;

class ProductConverter
{
    public function __construct(
        protected \Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface $galleryManagement,
        protected \Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory $entryFactory,
        protected \Magento\Framework\Api\Data\ImageContentInterfaceFactory $imageContentFactory,
        protected \Ecomni\Webp\Model\WebpConverter $webpConverter,
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
        $webp = $this->webpConverter->convert($mediaPath);
        if ($webp) {
            $imageContent = $this->imageContentFactory->create();
            $imageContent->setBase64EncodedData(base64_encode(file_get_contents($webp['full_path'])));
            $imageContent->setType('image/webp');
            $imageContent->setName($webp['basename']);

            $newEntry = $this->entryFactory->create();
            $newEntry->setDisabled(false)
                ->setLabel($entry->getLabel())
                ->setPosition($entry->getPosition())
                ->setTypes($entry->getTypes())
                ->setContent($imageContent)
                ->setFile($webp['path'])
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
}
