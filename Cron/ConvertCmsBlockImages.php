<?php

namespace Ecomni\Webp\Cron;

class ConvertCmsBlockImages
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Cms\Api\BlockRepositoryInterface $blockRepository,
        protected \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        protected \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        protected \Magento\Framework\Api\FilterBuilder $filterBuilder,
        protected \Ecomni\Webp\Model\Converter\PageBuilderBackgroundImageConverter $pageBuilderConverter,
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $jpgFilter = $this->filterBuilder
            ->setField(\Magento\Cms\Api\Data\BlockInterface::CONTENT)
            ->setValue('%.jpg%')
            ->setConditionType('like')
            ->create();

        $jpegFilter = $this->filterBuilder
            ->setField(\Magento\Cms\Api\Data\BlockInterface::CONTENT)
            ->setValue('%.jpeg%')
            ->setConditionType('like')
            ->create();

        $pngFilter = $this->filterBuilder
            ->setField(\Magento\Cms\Api\Data\BlockInterface::CONTENT)
            ->setValue('%.png%')
            ->setConditionType('like')
            ->create();

        $imageTypeFilter = $this->filterGroupBuilder
            ->addFilter($jpgFilter)
            ->addFilter($jpegFilter)
            ->addFilter($pngFilter)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Cms\Api\Data\BlockInterface::CONTENT, '%data-background-images%', 'like')
            ->setFilterGroups([$imageTypeFilter])
            ->create();

        $blocks = $this->blockRepository->getList($searchCriteria)->getItems();
        if (empty($blocks)) {
            return;
        }

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
