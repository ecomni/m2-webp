<?php

namespace Ecomni\Webp\Model\Config;

class Config
{
    public const MAX_PRODUCTS_PER_RUN = 50;

    public const QUALITY = 80;

    public function __construct(
        protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        protected \Magento\Store\Model\StoreManagerInterface $storeManager,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag('ecomni_webp/general/enabled');
    }

    public function getMaxProductsPerRun(): int
    {
        $maxProducts = $this->scopeConfig->getValue('ecomni_webp/general/max_products_per_run');
        if (!$maxProducts || (int)$maxProducts === 0) {
            $maxProducts = self::MAX_PRODUCTS_PER_RUN;
        }
        return (int)$maxProducts;
    }

    public function getQuality(): int
    {
        $quality = $this->scopeConfig->getValue('ecomni_webp/general/quality');
        if (!$quality || (int)$quality === 0) {
            $quality = self::QUALITY;
        }
        if ($quality < 0) {
            $quality = abs($quality);
        }
        if ($quality > 100) {
            $quality = 100;
        }
        return (int)$quality;
    }
}
