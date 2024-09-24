<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\PayPal\Payflow\Client;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Client\ClientInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\User;
use Psr\Cache\CacheItemPoolInterface;

class NVPClientMock implements ClientInterface
{
    public const LINE_ITEM_NAME = 'L_NAME';

    public const LINE_ITEM_DESC = 'L_DESC';

    public const LINE_ITEM_CACHE_KEY = 'lineItems';

    private CacheItemPoolInterface $cache;

    /**
     * NVPClientMock constructor.
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    #[\Override]
    public function send($hostAddress, array $options = [], array $connectionOptions = [])
    {
        if (!$this->credentialsAreValid($options)) {
            return $this->getDeclinedResponse();
        }

        $filteredLineItems = array_values(array_filter($options, function ($item) {
            return false !== strpos($item, self::LINE_ITEM_NAME) || false !== strpos($item, self::LINE_ITEM_DESC);
        }, ARRAY_FILTER_USE_KEY));
        $cacheItem = $this->cache->getItem(self::LINE_ITEM_CACHE_KEY);
        if ($cacheItem->isHit()) {
            $filteredLineItems = array_merge($filteredLineItems, $cacheItem->get());
        }
        $cacheItem->set($filteredLineItems);
        $this->cache->save($cacheItem);

        return $this->getApprovedResponse($this->isOnCheckout($options) ? null : 1);
    }

    /**
     * @param int $reference
     *
     * @return array
     */
    private function getApprovedResponse($reference)
    {
        return [
            'RESULT' => '0',
            'RESPMSG' => 'Approved',
            'SECURETOKEN' => '8w0KDpDSXj0Wh9kLHh6VVfwiz',
            'SECURETOKENID' => '00ebe252-8910-45c1-8e89-32b2a74e800e',
            'TOKEN' => 'EC-0WP62848AP6323500', // required for express checkout
            'PNREF' => $reference,
        ];
    }

    /**
     * @return array
     */
    private function getDeclinedResponse()
    {
        return [
            'RESULT' => '12',
            'RESPMSG' => 'Declined',
            'SECURETOKEN' => 'ziwfVV6hHLk9hW0jXSDpDK0w8',
            'SECURETOKENID' => 'e008e47a2b23-98e8-1c54-0198-252ebe00',
            'PNREF' => 1,
        ];
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function isOnCheckout(array $options)
    {
        return array_key_exists(ReturnUrl::RETURNURL, $options);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function credentialsAreValid(array $options)
    {
        if (!array_key_exists(User::USER, $options)) {
            return true;
        }

        return $options['USER'] !== 'invalid';
    }
}
