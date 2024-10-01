<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\ProductPriceReference;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\DomCrawler\Form;

class AjaxProductPriceControllerTest extends AbstractAjaxProductPriceControllerTest
{
    use ProductPriceReference;

    protected string $pricesByCustomerActionUrl = 'oro_pricing_price_by_customer';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadCombinedProductPrices::class,
            LoadProductPrices::class,
            LoadPriceListRelations::class
        ]);
    }

    public function testUpdate()
    {
        $this->loadFixtures([LoadProductPrices::class]);
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReferenceRepository()->getReferences()[LoadProductPrices::PRODUCT_PRICE_3];
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');

        $url = $this->getUrl(
            'oro_product_price_update_widget',
            [
                'id' => $productPrice->getId(),
                'priceList' => $productPrice->getPriceList()->getId(),
                '_widgetContainer' => 'dialog',
                '_wid' => 'test-uuid'
            ]
        );
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_pricing_price_list_product_price[quantity]' => 10,
                'oro_pricing_price_list_product_price[unit]' => $unit->getCode(),
                'oro_pricing_price_list_product_price[price][value]' => 20,
                'oro_pricing_price_list_product_price[price][currency]' => 'USD'
            ]
        );

        $this->assertSaved($form);
    }

    public function testUpdateDuplicateEntry()
    {
        $this->loadFixtures([LoadProductPrices::class]);

        $productPrice = $this->getPriceByReference(LoadProductPrices::PRODUCT_PRICE_1);
        $productPriceEUR = $this->getPriceByReference(LoadProductPrices::PRODUCT_PRICE_2);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_price_update_widget',
                [
                    'id' => $productPriceEUR->getId(),
                    'priceList' => $productPriceEUR->getPriceList()->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_pricing_price_list_product_price[quantity]' => $productPrice->getQuantity(),
                'oro_pricing_price_list_product_price[unit]' => $productPrice->getUnit()->getCode(),
                'oro_pricing_price_list_product_price[price][value]' => $productPrice->getPrice()->getValue(),
                'oro_pricing_price_list_product_price[price][currency]' => $productPrice->getPrice()->getCurrency(),
            ]
        );

        $this->assertSubmitError($form, 'oro.pricing.validators.product_price.unique_entity.message');
    }

    protected function assertSubmitError(Form $form, string $message): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertMatchesRegularExpression('/"savedId":[\s\d-]*/i', $html);
        $error = self::getContainer()->get('translator')
            ->trans($message, [], 'validators');
        self::assertStringContainsString($error, $html);
    }

    protected function assertSaved(Form $form): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertMatchesRegularExpression('/"savedId":"[\w\d-]+"/i', $html);
    }

    #[\Override]
    public function getProductPricesByCustomerActionDataProvider(): array
    {
        return [
            'with customer and website' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => '1.1000', 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                    ['price' => '1.2000', 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                ],
                'currency' => null,
                'customer' => 'customer.level_1.1',
                'website' => LoadWebsiteData::WEBSITE1
            ],
            'default, without customer and website' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => '13.1000', 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                    ['price' => '10.0000', 'currency' => 'USD', 'quantity' => 1, 'unit' => 'liter'],
                    ['price' => '12.2000', 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                ]
            ],
        ];
    }
}
