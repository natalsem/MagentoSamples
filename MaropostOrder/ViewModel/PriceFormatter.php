<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\ViewModel;

use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Magento\Framework\Locale\Format;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class PriceFormatter
 */
class PriceFormatter implements ArgumentInterface
{
    /**
     * @param Format $localeFormat
     * @param LoggerInterface $logger
     * @param Json $jsonEncoder
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        private readonly Format $localeFormat,
        private readonly LoggerInterface $logger,
        private readonly Json $jsonEncoder,
        private readonly ResolverInterface $localeResolver,
    ) {
    }

    /**
     * Get serialized string
     *
     * @return bool|string
     */
    public function getPriceFormatJson(): bool|string
    {
        $result = '';
        try {
            $result = $this->jsonEncoder->serialize($this->localeFormat->getPriceFormat($this->localeResolver->getLocale()));
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
