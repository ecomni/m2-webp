<?php

namespace Ecomni\Webp\Cron;

class ConvertCategoryImages
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        protected \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        protected \Ecomni\Webp\Model\ConverterPool $converterPool,
        protected \Ecomni\Webp\Model\Util\NormalizeUrl $normalizeUrl,
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['ccev' => $this->resourceConnection->getTableName('catalog_category_entity_varchar')],
                ['ccev.entity_id']
            )
            ->join(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'ea.attribute_code = "image"',
                []
            )
            ->where('ccev.attribute_id = ea.attribute_id')
            ->where('ccev.value NOT LIKE "%.webp"');

        $categoryIds = $connection->fetchCol($select);
        if (empty($categoryIds)) {
            return;
        }

        $convertedCount = 0;
        foreach ($categoryIds as $categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
                $imagePath = $this->normalizeUrl->normalizeCatalogFilePath($category->getData('image'));
                if (!$imagePath) {
                    continue;
                }
                $webpPath = $this->converterPool->convert($imagePath);
                if (!$webpPath) {
                    continue;
                }
                $category->setData('image', basename($webpPath));
                $this->categoryRepository->save($category);
                $convertedCount++;
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }
        $this->logger->info(sprintf('Categories: Converted %d images', $convertedCount));
    }
}
