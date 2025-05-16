<?php

namespace Ecomni\Webp\Model\Util;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class LocalUrl
{
    public function __construct(
        protected StoreManagerInterface $storeManager,
    ) {
    }

    public function isLocal(string $url): bool
    {
        if (!preg_match('#^http(s?)://#', $url)) {
            return true;
        }

        foreach ($this->storeManager->getStores() as $store) {
            $storeBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            if (str_contains($url, $storeBaseUrl)) {
                return true;
            }

            $storeMediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            if (str_contains($url, $storeMediaUrl)) {
                return true;
            }

            $storeStaticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
            if (str_contains($url, $storeStaticUrl)) {
                return true;
            }
        }
        return false;
    }
}
