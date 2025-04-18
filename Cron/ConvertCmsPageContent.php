<?php

namespace Ecomni\Webp\Cron;

class ConvertCmsPageContent
{
    public function __construct(
        protected \Ecomni\Webp\Model\Config\Config $config,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Ecomni\Webp\Model\Search\PageBuilderSearchCriteria $pageBuilderSearchCriteria,
        protected \Ecomni\Webp\Model\Converter\PageBuilderBackgroundImageConverter $pageBuilderConverter,
        protected \Magento\Cms\Api\PageRepositoryInterface $pageRepository,
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $searchCriteria = $this->pageBuilderSearchCriteria->build();
        $items = $this->pageRepository->getList($searchCriteria)->getItems();
        if (empty($items)) {
            return;
        }

        $convertedCount = 0;
        foreach ($items as $item) {
            try {
                $html = $this->pageBuilderConverter->convert($item->getContent(), $convertedCount);
                if ($html !== $item->getContent()) {
                    $item->setContent($html);
                    $this->pageRepository->save($item);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                continue;
            }
        }
        $this->logger->info(sprintf('CMS Page: Converted %d images', $convertedCount));
    }
}
