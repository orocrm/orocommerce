<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class StartCheckoutForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    public function testStartCheckout(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout']
        );
        $expectedData = $this->updateResponseContent(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => 'new',
                    'attributes' => [
                        'poNumber' => null,
                        'shippingMethod' => null,
                        'shippingMethodType' => null,
                        'paymentMethod' => null,
                        'shipUntil' => null,
                        'customerNotes' => null,
                        'currency' => 'USD',
                        'completed' => false,
                        'totalValue' => '190.4400',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '190.4400']
                        ]
                    ],
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                ['type' => 'checkoutlineitems', 'id' => 'new'],
                                ['type' => 'checkoutlineitems', 'id' => 'new'],
                                ['type' => 'checkoutlineitems', 'id' => 'new']
                            ]
                        ],
                        'customerUser' => [
                            'data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']
                        ],
                        'customer' => [
                            'data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']
                        ],
                        'billingAddress' => ['data' => null],
                        'shippingAddress' => ['data' => null],
                        'source' => [
                            'data' => ['type' => 'orders', 'id' => '<toString(@order4->id)>']
                        ],
                        'order' => ['data' => null]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
    }
}
