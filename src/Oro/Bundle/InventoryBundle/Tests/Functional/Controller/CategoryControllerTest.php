<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Tests\Functional\Helper\FallbackTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    use FallbackTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryData::class]);
    }

    public function testAddQuantityToOrder()
    {
        $categoryId = $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId();
        $crawler = $this->updateCategory($categoryId, '123', '321', null, null);
        $form = $crawler->selectButton('Save')->form();

        static::assertStringNotContainsString(
            'The CSRF token is invalid. Please try to resubmit the form.',
            $crawler->html()
        );
        $this->assertEquals(
            '123.00',
            $form['oro_catalog_category[minimumQuantityToOrder][scalarValue]']->getValue()
        );
        $this->assertEquals(
            '321.00',
            $form['oro_catalog_category[maximumQuantityToOrder][scalarValue]']->getValue()
        );
    }

    public function testFallbackQuantity()
    {
        $categoryId = $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId();
        $crawler = $this->updateCategory($categoryId, null, null, 'systemConfig', 'systemConfig');
        $form = $crawler->selectButton('Save')->form();

        static::assertStringNotContainsString(
            'The CSRF token is invalid. Please try to resubmit the form.',
            $crawler->html()
        );
        $this->assertEquals(
            'systemConfig',
            $form['oro_catalog_category[minimumQuantityToOrder][fallback]']->getValue()
        );
        $this->assertEquals(
            'systemConfig',
            $form['oro_catalog_category[maximumQuantityToOrder][fallback]']->getValue()
        );
    }

    /**
     * @param integer $categoryId
     * @param mixed $minScalar
     * @param mixed $maxScalar
     * @param string $minFallback
     * @param string $maxFallback
     * @return null|\Symfony\Component\DomCrawler\Crawler
     */
    protected function updateCategory($categoryId, $minScalar, $maxScalar, $minFallback, $maxFallback)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_update', ['id' => $categoryId]));
        $form = $crawler->selectButton('Save')->form();
        $this->updateFallbackField($form, $minScalar, $minFallback, 'oro_catalog_category', 'minimumQuantityToOrder');
        $this->updateFallbackField($form, $maxScalar, $maxFallback, 'oro_catalog_category', 'maximumQuantityToOrder');
        $form['input_action'] = $crawler->selectButton('Save')->attr('data-action');
        $values = $form->getPhpValues();
        $values['oro_catalog_category']['_token'] = $this->getCsrfToken('category')->getValue();

        $this->client->followRedirects();

        return $this->client->request($form->getMethod(), $form->getUri(), $values);
    }
}
