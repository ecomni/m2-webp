<?php

namespace Ecomni\Webp\Cron;

use Magento\Catalog\Model\ProductRepository;
use Zend_Db_Select;

class ConvertCatalogProductGalleryImages
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        protected \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        protected ProductRepository $productRepository,
        protected \Ecomni\Webp\Model\Converter $converter,
    ) {
    }

    public function execute()
    {
        if (!$this->config->isEnabled()) {
            return;
        }
        $limit = $this->config->getMaxProductsPerRun();
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['cpemg' => $this->resourceConnection->getTableName('catalog_product_entity_media_gallery')],
                []
            )
            ->join(
                ['cpemgv' => $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value')],
                'cpemg.value_id = cpemgv.value_id',
                ['cpemgv.entity_id']
            )
            ->where('cpemg.value NOT LIKE "%.webp"')
            ->limit($limit);
        $productsIds = $connection->fetchCol($select);

        /** @var \Magento\Catalog\Model\Product[] $products */
        $collection = $this->productCollectionFactory->create();
        // We only need the entity_id, for some reason `removeAllFieldsFromSelect()` does nothing.
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('e.entity_id');
        $products = $collection
            ->addAttributeToSelect('e.entity_id')
            ->addIdFilter($productsIds)
            ->getItems();

        $convertedCount = 0;
        foreach ($products as $product) {
            // We need a loaded product to get the media gallery entries.
            $product = $this->productRepository->getById($product->getId());
            foreach ($product->getMediaGalleryEntries() as $entry) {
                try {
                    $this->converter->convert($product, $entry);
                    $convertedCount++;
                } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                    $this->logger->critical($exception->getMessage());
                }
            }
        }
        $this->logger->info(sprintf('Converted %d images', $convertedCount));
    }
}
