<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListKitItemForVisitorTest extends FrontendRestJsonApiTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroShoppingListBundle/Tests/Functional/ApiFrontendForVisitor/DataFixtures/shopping_list_for_visitor.yml'
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 3; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list%d', $i)),
                true
            );
        }
    }

    protected function getRequestDataFolderName(): string
    {
        return '../../ApiFrontend/RestJsonApi/requests';
    }

    private static function assertProductKitItemLineItem(
        ProductKitItemLineItem $kitItemLineItem,
        int $lineItemId,
        int $productKitItemId,
        int $productId,
        string $productUnitCode,
        float $quantity,
        int $sortOrder
    ): void {
        self::assertEquals($lineItemId, $kitItemLineItem->getLineItem()->getId());
        self::assertEquals($productKitItemId, $kitItemLineItem->getKitItem()->getId());
        self::assertEquals($productId, $kitItemLineItem->getProduct()->getId());
        self::assertEquals($productUnitCode, $kitItemLineItem->getProductUnitCode());
        self::assertEquals($quantity, $kitItemLineItem->getQuantity());
        self::assertEquals($sortOrder, $kitItemLineItem->getSortOrder());
    }

    private function setGuestShoppingListFeatureStatus(bool $status = true): void
    {
        $configManager = self::getConfigManager('global');
        $configManager->set('oro_shopping_list.availability_for_guests', $status);
        $configManager->flush();
    }

    private function assertShoppingListTotal(
        ShoppingList $shoppingList,
        float $total,
        string $currency
    ): void {
        /** @var ShoppingListTotal[] $totals */
        $totals = $this->getEntityManager()
            ->getRepository(ShoppingListTotal::class)
            ->findBy(['shoppingList' => $shoppingList]);
        self::assertCount(1, $totals);
        $totalEntity = $totals[0];
        self::assertEquals($total, $totalEntity->getSubtotal()->getAmount());
        self::assertEquals($currency, $totalEntity->getCurrency());
    }

    public function testTryToGetListWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setGuestShoppingListFeatureStatus(false);
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(
            ['entity' => 'shoppinglistkititems'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetEmptyList(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor2'));

        $response = $this->cget(['entity' => 'shoppinglistkititems'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => []], $response);
        self::assertEquals(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetList(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(['entity' => 'shoppinglistkititems'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_kit_line_item_visitor.yml', $response);
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilteredByLineItem(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(
            ['entity' => 'shoppinglistkititems'],
            ['filter' => ['lineItem' => '<toString(@kit_line_item1->id)>']]
        );

        $this->assertResponseContains('cget_kit_line_item_filter_visitor.yml', $response);
    }

    public function testTryToGetWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setGuestShoppingListFeatureStatus(false);
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglistkititems', 'id' => '<toString(@product_kit_item1_line_item1->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetNotVisitorKitItemLineItem(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglistkititems', 'id' => '<toString(@product_kit_item1_line_item3->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGet(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglistkititems', 'id' => '<toString(@product_kit_item1_line_item1->id)>']
        );

        $this->assertResponseContains('get_kit_line_item_visitor.yml', $response);
    }

    public function testTryToCreateWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setGuestShoppingListFeatureStatus(false);
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            'create_kit_item_line_item_visitor.yml',
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testCreate(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 49.79, 'USD');

        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('kit_line_item1');
        $productKitItemId = $this->getReference('product_kit1_item3')->getId();
        $productId = $this->getReference('product3')->getId();
        $productUnitCode = $this->getReference('item')->getCode();

        self::assertCount(2, $lineItem->getKitItemLineItems());

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            'create_kit_item_line_item_visitor.yml'
        );

        $kitItemLineItemId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_kit_item_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNotNull($kitItemLineItem, 'ProductKitItemLineItem is not found');
        self::assertProductKitItemLineItem(
            $kitItemLineItem,
            $lineItem->getId(),
            $productKitItemId,
            $productId,
            $productUnitCode,
            5,
            1
        );
        self::assertCount(3, $kitItemLineItem->getLineItem()->getKitItemLineItems());

        $kitItemLineItemShoppingList = $kitItemLineItem->getLineItem()->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 81.89, 'USD');
    }

    public function testTryToUpdateWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setGuestShoppingListFeatureStatus(false);
        $this->setVisitorCookie($this->getReference('visitor1'));

        $kitItemLineItemId = (string) $this->getReference('product_kit_item1_line_item1')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems',
                'id' => $kitItemLineItemId,
                'attributes' => [
                    'quantity' => 10
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => $kitItemLineItemId],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateNotVisitorKitItemLineItem(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $kitItemLineItemId = (string) $this->getReference('product_kit_item1_line_item3')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems',
                'id' => $kitItemLineItemId,
                'attributes' => [
                    'quantity' => 10
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => $kitItemLineItemId],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNotNull($kitItemLineItem);
        self::assertEquals(2, $kitItemLineItem->getQuantity());
    }

    public function testUpdate(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 49.79, 'USD');

        $kitItemLineItemId = (string) $this->getReference('product_kit_item1_line_item1')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems',
                'id' => $kitItemLineItemId,
                'attributes' => [
                    'quantity' => 10
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => $kitItemLineItemId],
            $data
        );
        $this->assertResponseContains($data, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNotNull($kitItemLineItem);
        self::assertEquals(10, $kitItemLineItem->getQuantity());

        $kitItemLineItemShoppingList = $kitItemLineItem->getLineItem()->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 69.47, 'USD');
    }

    public function testTryToDeleteWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setGuestShoppingListFeatureStatus(false);
        $this->setVisitorCookie($this->getReference('visitor1'));

        $kitItemLineItemId = $this->getReference('product_kit_item1_line_item1')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglistkititems', 'id' => (string)$kitItemLineItemId],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToDeleteNotVisitorKitItemLineItem()
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        $kitItemLineItemId = $this->getReference('product_kit_item1_line_item3')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglistkititems', 'id' => (string)$kitItemLineItemId],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNotNull($kitItemLineItem);
    }

    public function testDelete(): void
    {
        $this->setGuestShoppingListFeatureStatus();
        $this->setVisitorCookie($this->getReference('visitor1'));

        /** @var ProductKitItemLineItem $kitItemLineItemReference */
        $kitItemLineItemReference = $this->getReference('product_kit_item1_line_item1');
        $kitItemLineItemId = $kitItemLineItemReference->getId();
        $lineItemId = $kitItemLineItemReference->getLineItem()->getId();
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 49.79, 'USD');

        $this->delete(
            ['entity' => 'shoppinglistkititems', 'id' => (string)$kitItemLineItemId]
        );

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNull($kitItemLineItem);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        self::assertCount(1, $lineItem->getKitItemLineItems());

        $lineItemShoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $lineItemShoppingList->getId());
        $this->assertShoppingListTotal($lineItemShoppingList, 44.87, 'USD');
    }

    public function testOptionsWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setGuestShoppingListFeatureStatus(false);
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'shoppinglistkititems']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }
}
