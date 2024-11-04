<?php

namespace Oro\Bundle\SaleBundle\Tests\Performance\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadQuoteDataForPerformance extends AbstractFixture implements DependentFixtureInterface
{
    /** Total quotes will be NUMBER_OF_QUOTE_GROUPS * count(LoadQuoteData::$items) */
    public const NUMBER_OF_QUOTE_GROUPS = 10000;

    public const QUOTES_TO_EXPIRE = 10000;

    private int $quotesToExpire = self::QUOTES_TO_EXPIRE;

    private static array $quoteUpdateFields = [
        'user_owner_id',
        'qid',
        'organization_id',
        'ship_until',
        'po_number',
        'created_at',
        'updated_at',
        'expired',
        'valid_until'
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
            LoadCustomerUserAddresses::class,
            LoadCustomerAddresses::class,
            LoadProductUnitPrecisions::class,
            LoadUser::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $insertQuoteBaseSql = $this->getUpdateQuotesBaseSql();

        $params = [];

        // generate sprintf string for insert values
        foreach (self::$quoteUpdateFields as $field) {
            $params[] = "'%s'";
        }
        $valueSprintf = '(' . implode(', ', $params) . '),';
        $UTC = new \DateTimeZone('UTC');

        for ($i = 1; $i <= static::NUMBER_OF_QUOTE_GROUPS; $i++) {
            $quoteSql = $insertQuoteBaseSql;
            foreach (LoadQuoteData::$items as $item) {
                $poNumber = 'CA' . random_int(1000, 9999) . 'USD';

                // generate VALUES sql
                $quoteSql .= sprintf(
                    $valueSprintf,
                    $user->getId(),
                    $item['qid'],
                    $user->getOrganization()->getId(),
                    (new \DateTime('+10 day', $UTC))->format('Y-m-d'),
                    $poNumber,
                    (new \DateTime('now', $UTC))->format('Y-m-d'),
                    (new \DateTime('now', $UTC))->format('Y-m-d'),
                    $this->getExpiredValue(),
                    $this->getValidUntilValue($UTC)
                );
            }
            $quoteSql = substr($quoteSql, 0, -1) . ';';
            $manager->getConnection()->exec($quoteSql);
        }
    }

    private function getUpdateQuotesBaseSql(): string
    {
        $sql = 'INSERT INTO oro_sale_quote (';
        foreach (self::$quoteUpdateFields as $field) {
            $sql .= $field . ', ';
        }
        $sql = substr($sql, 0, -2) . ') VALUES ';

        return $sql;
    }

    private function getExpiredValue(): int
    {
        if ($this->quotesToExpire >= 0) {
            $this->quotesToExpire--;

            return 0;
        }

        return 1;
    }

    private function getValidUntilValue(string $timezone): string
    {
        return $this->quotesToExpire >= 0
            ? (new \DateTime('-1days', $timezone))->format('Y-m-d H:i:s')
            : (new \DateTime('+1days', $timezone))->format('Y-m-d H:i:s');
    }
}
