<?php

namespace Ecomni\Webp\Cron;

class ConvertCmsBlockContent
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Ecomni\Webp\Model\Search\PageBuilderSearchCriteria $pageBuilderSearchCriteria,
        protected \Ecomni\Webp\Model\Converter\PageBuilderBackgroundImageConverter $pageBuilderConverter,
        protected \Magento\Cms\Api\BlockRepositoryInterface $blockRepository,
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $searchCriteria = $this->pageBuilderSearchCriteria->build();
        $items = $this->blockRepository->getList($searchCriteria)->getItems();
        if (empty($items)) {
            return;
        }

        $convertedCount = 0;
        foreach ($items as $item) {
            try {
                $html = $this->pageBuilderConverter->convert($item->getContent(), $convertedCount);
                if ($html !== $item->getContent()) {
                    $item->setContent($html);
                    $this->blockRepository->save($item);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                continue;
            }
        }
        $this->logger->info(sprintf('CMS Blocks: Converted %d images', $convertedCount));
    }
}
