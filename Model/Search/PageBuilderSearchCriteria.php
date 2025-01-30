<?php

namespace Ecomni\Webp\Model\Search;

class PageBuilderSearchCriteria
{
    protected const FIELD_CONTENT = 'content';

    public function __construct(
        protected \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        protected \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        protected \Magento\Framework\Api\FilterBuilder $filterBuilder,
    ) {
    }

    /**
     * This builds a SearchCriteria to be used in repositories with the following conditions:
     * `content` LIKE `%data-background-images%` AND (`%.jpg%` OR `%.jpeg%` OR `%.png%`)
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     */
    public function build(): \Magento\Framework\Api\SearchCriteriaInterface
    {
        $jpgFilter = $this->filterBuilder
            ->setField(self::FIELD_CONTENT)
            ->setValue('%.jpg%')
            ->setConditionType('like')
            ->create();

        $jpegFilter = $this->filterBuilder
            ->setField(self::FIELD_CONTENT)
            ->setValue('%.jpeg%')
            ->setConditionType('like')
            ->create();

        $pngFilter = $this->filterBuilder
            ->setField(self::FIELD_CONTENT)
            ->setValue('%.png%')
            ->setConditionType('like')
            ->create();

        $imageTypeFilter = $this->filterGroupBuilder
            ->addFilter($jpgFilter)
            ->addFilter($jpegFilter)
            ->addFilter($pngFilter)
            ->create();

        return $this->searchCriteriaBuilder
            ->addFilter(self::FIELD_CONTENT, '%data-background-images%', 'like')
            ->setFilterGroups([$imageTypeFilter])
            ->create();
    }
}
