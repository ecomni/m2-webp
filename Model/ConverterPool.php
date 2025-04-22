<?php

namespace Ecomni\Webp\Model;

class ConverterPool implements ConverterInterface
{
    /**
     * @param ConverterInterface[] $converters
     */
    public function __construct(
        protected array $converters = [],
    ) {
    }

    /**
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function convert(string $filePath): array
    {
        foreach ($this->converters as $converter) {
            try {
                return $converter->convert($filePath);
            } catch (\Exception $e) {
                continue;
            }
        }
        throw new \Exception(sprintf('Could not convert %s', $filePath));
    }
}
