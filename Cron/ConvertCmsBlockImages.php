<?php

namespace Ecomni\Webp\Cron;

class ConvertCmsBlockImages
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        protected \Magento\Cms\Api\BlockRepositoryInterface $blockRepository,
        protected \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        protected \Ecomni\Webp\Model\Converter\PageBuilderBackgroundImageConverter $pageBuilderConverter,
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Cms\Api\Data\BlockInterface::CONTENT, '%data-background-images%', 'like')
            ->addFilter(\Magento\Cms\Api\Data\BlockInterface::CONTENT, '%.jpg%', 'like')
            ->create();
        $blocks = $this->blockRepository->getList($searchCriteria)->getItems();

        $convertedCount = 0;
        foreach ($blocks as $block) {
            try {
                $html = $this->pageBuilderConverter->convert($block);
                if ($html !== $block->getContent()) {
                    $block->setContent($html);
                    $this->blockRepository->save($block);
                }
            } catch (\Exception $e) {
                $this->logger->debug('Exception: ' . $e->getMessage());
                continue;
            }
        }
        $this->logger->info(sprintf('CMS Blocks: Converted %d images', $convertedCount));
    }
}
