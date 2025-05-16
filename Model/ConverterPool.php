<?php

namespace Ecomni\Webp\Model;

class ConverterPool
{
    public function __construct(
        protected Config\Config $config,
        protected \Ecomni\Webp\Model\Util\NormalizeUrl $normalizeUrl,
        protected array $converters = [],
    ) {
    }

    /**
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    public function convert(string $filePath): string
    {
        $filePath = $this->normalizeUrl->normalizeFilePath($filePath);
        $options = [
            'quality' => 'auto',
            'encoding' => 'auto',
            'max-quality' => $this->config->getQuality(),
        ];
        foreach ($this->converters as $converter) {
            $options['converter'] = $converter;
            try {
                $destPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $filePath);
                \WebPConvert\WebPConvert::convert($filePath, $destPath, $options);
                return $destPath;
            } catch (\Exception $e) {
                continue;
            }
        }
        throw new \Exception(sprintf('Could not convert %s', $filePath));
    }
}
