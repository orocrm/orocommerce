<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to quick order process
 */
class DataStorageAwareComponentProcessor implements ComponentProcessorInterface
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var ProductDataStorage */
    protected $storage;

    /** @var ComponentProcessorFilter */
    protected $componentProcessorFilter;

    /** @var string */
    protected $name;

    /** @var string */
    protected $redirectRouteName;

    /** @var bool */
    protected $validationRequired = true;

    /** @var string */
    protected $acl;

    /** @var string */
    protected $scope;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var Session */
    protected $session;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ProductMapperInterface */
    protected $productMapper;

    public function __construct(
        UrlGeneratorInterface $router,
        ProductDataStorage $storage,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        Session $session,
        TranslatorInterface $translator
    ) {
        $this->router = $router;
        $this->storage = $storage;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * @param ComponentProcessorFilterInterface $filter
     * @return ComponentProcessorInterface
     */
    public function setComponentProcessorFilter(ComponentProcessorFilterInterface $filter)
    {
        $this->componentProcessorFilter = $filter;

        return $this;
    }

    /**
     * @param string $name
     * @return ComponentProcessorInterface
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $acl
     */
    public function setAcl($acl)
    {
        $this->acl = $acl;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        if (!$this->acl) {
            return true;
        }

        return $this->tokenAccessor->hasUser() && $this->authorizationChecker->isGranted($this->acl);
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @param string $redirectRouteName
     */
    public function setRedirectRouteName($redirectRouteName)
    {
        $this->redirectRouteName = $redirectRouteName;
    }

    /**
     * @param bool $validationRequired
     * @return ComponentProcessorInterface
     */
    public function setValidationRequired($validationRequired)
    {
        $this->validationRequired = (bool)$validationRequired;

        return $this;
    }

    /**
     * @param ProductMapperInterface $productMapper
     */
    public function setProductMapper($productMapper)
    {
        $this->productMapper = $productMapper;
    }

    /**
     * @return bool
     */
    public function isValidationRequired()
    {
        return $this->validationRequired;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $data, Request $request)
    {
        $inputProductSkus = $this->getProductSkus($data);
        $data = $this->filterData($data);
        $allowedProductSkus = $this->getProductSkus($data);
        $this->checkNotAllowedProducts($inputProductSkus, $allowedProductSkus);
        $allowRedirect = !empty($allowedProductSkus);

        $this->storage->set($data);

        if ($allowRedirect) {
            return $this->getResponse();
        }

        return null;
    }

    /**
     * @return null|RedirectResponse
     */
    protected function getResponse()
    {
        if ($this->redirectRouteName) {
            return new RedirectResponse($this->getUrl($this->redirectRouteName));
        }

        return null;
    }

    /**
     * @param string $routeName
     * @return string
     */
    protected function getUrl($routeName)
    {
        return $this->router->generate($routeName, [ProductDataStorage::STORAGE_KEY => true]);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getProductSkus(array $data)
    {
        $skus = [];
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $item) {
            $skus[] = $item[ProductDataStorage::PRODUCT_SKU_KEY];
        }

        return array_values(array_unique($skus));
    }

    protected function checkNotAllowedProducts(array $inputProductSkus, array $allowedProductSkus)
    {
        $notAllowedProductSkus = array_diff($inputProductSkus, $allowedProductSkus);

        if (!empty($notAllowedProductSkus)) {
            $this->addFlashMessage($notAllowedProductSkus);
        }
    }

    protected function addFlashMessage(array $skus)
    {
        $skus = array_unique($skus);

        $message = $this->translator->trans(
            'oro.product.frontend.quick_add.messages.not_added_products',
            ['%count%' => count($skus),'%sku%' => implode(', ', $skus)]
        );
        $this->session->getFlashBag()->add('warning', $message);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function filterData(array $data)
    {
        if (empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])) {
            return $data;
        }

        if ($this->productMapper) {
            $items = new ArrayCollection();
            foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataItem) {
                $items->add(new \ArrayObject($dataItem));
            }

            $this->productMapper->mapProducts($items);

            $updatedData = [];
            /** @var \ArrayObject $item */
            foreach ($items as $item) {
                $updatedData[] = $item->getArrayCopy();
            }
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] = $updatedData;
        } elseif ($this->componentProcessorFilter) {
            $filterParameters = [];
            if ($this->scope) {
                $filterParameters['scope'] = $this->scope;
            }
            $data = $this->componentProcessorFilter->filterData($data, $filterParameters);
        }

        return $data;
    }
}
