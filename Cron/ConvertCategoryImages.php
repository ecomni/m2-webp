<?php

namespace Ecomni\Webp\Cron;

use Magento\Catalog\Model\Category\FileInfo;
use Magento\Framework\App\Filesystem\DirectoryList;

class ConvertCategoryImages
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        protected \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        protected \Ecomni\Webp\Model\WebpConverter $webpConverter,
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
            $category = $this->categoryRepository->get($categoryId);
            $imagePath = $this->normalizeFilePath($category->getData('image'));
            if (!$imagePath) {
                continue;
            }
            try {
                $webp = $this->webpConverter->convert($imagePath);
                if (!$webp) {
                    continue;
                }
                $category->setData('image', $webp['basename']);
                $this->categoryRepository->save($category);
                $convertedCount++;
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }
        $this->logger->info(sprintf('Categories: Converted %d images', $convertedCount));
    }

    /**
     * Files can be stored as `{image}` or as `catalog/category/{image}` in the database.
     * Here we make sure we always work with the `catalog/category/{image}` format.
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
                FileInfo::ENTITY_MEDIA_PATH,
            ],
            '',
            $filePath
        );
        $filePath = ltrim($filePath, '/');
        return sprintf('%s/%s', FileInfo::ENTITY_MEDIA_PATH, $filePath);
    }
}
