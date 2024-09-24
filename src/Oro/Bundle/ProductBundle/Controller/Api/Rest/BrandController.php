<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for Brand entity
 */
class BrandController extends RestController
{
    /**
     * @param int $id Brand id
     *
     * @ApiDoc(
     *     description="Get sissue",
     *     resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_product_brand_view')]
    public function getAction(int $id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Delete brand
     *
     * @param int $id Brand id
     *
     * @ApiDoc(
     *      description="Delete brand",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     *
     * @return Response
     */
    #[Acl(id: 'oro_product_brand_delete', type: 'entity', class: Brand::class, permission: 'DELETE')]
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_product.brand.manager.api');
    }

    /**
     * @return ApiFormHandler
     */
    #[\Override]
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * @return ApiFormHandler
     */
    #[\Override]
    public function getFormHandler()
    {
        throw new \BadMethodCallException('Form handler is not available.');
    }
}
