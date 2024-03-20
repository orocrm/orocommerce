<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class SystemPageForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
    }

    public function testGetFrontendPage()
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => 'oro_product_frontend_product_index']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'systempages',
                    'id'         => 'oro_product_frontend_product_index',
                    'attributes' => [
                        'url' => '/product/'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetBackendPage()
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => 'oro_product_index'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
