<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller;

use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShoppingListControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadShoppingLists::class]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_shopping_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_view', ['id' => $shoppingList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString($shoppingList->getLabel(), $crawler->html());
    }
}
