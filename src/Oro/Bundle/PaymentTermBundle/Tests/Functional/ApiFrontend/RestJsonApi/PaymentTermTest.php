<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

class PaymentTermTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadPaymentTermData::class
        ]);
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'paymentterms'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'paymentterms', 'id' => '<toString(@payment_term_test_data_net 10->id)>'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'paymentterms'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'paymentterms', 'id' => '<toString(payment_term_test_data_net 10->id)>'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'paymentterms', 'id' => '<toString(payment_term_test_data_net 10->id)>'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'paymentterms'],
            ['filter' => ['id' => '<toString(payment_term_test_data_net 10->id)>']],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }
}
