<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderAddressForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'orderaddresses'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'orderaddresses'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'orderaddresses'],
            ['filter' => ['id' => '<toString(@order1_billing_address->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToGetSubresourceForCountry()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForCountry()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForCountry()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForRegion()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForRegion()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForRegion()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForCustomerAddress()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForCustomerAddress()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForCustomerAddress()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForCustomerUserAddress()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForCustomerUserAddress()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForCustomerUserAddress()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
