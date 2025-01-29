<?php

namespace Ecomni\Webp\Cron;

class ConvertProductGalleryImages
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        protected \Ecomni\Webp\Model\Converter\ProductConverter $converter,
    ) {
    }

    public function execute(): void
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
        $productIds = $connection->fetchCol($select);
        if (empty($productIds)) {
            return;
        }

        $convertedCount = 0;
        foreach ($productIds as $productId) {
            // We need a loaded product to get the media gallery entries.
            $product = $this->productRepository->getById($productId);
            foreach ($product->getMediaGalleryEntries() as $entry) {
                try {
                    $this->converter->convert($product, $entry);
                    $convertedCount++;
                } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                    $this->logger->critical($exception->getMessage());
                }
            }
        }
        $this->logger->info(sprintf('Products: Converted %d images', $convertedCount));
    }
}
