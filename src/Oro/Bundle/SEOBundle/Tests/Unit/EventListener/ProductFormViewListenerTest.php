<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\SEOBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class ProductFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var ProductFormViewListener */
    private $listener;

    /** @var FieldAclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldAclHelper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldAclHelper = $this->createMock(FieldAclHelper::class);
        $this->fieldAclHelper
            ->expects($this->any())
            ->method('isFieldAvailable')
            ->willReturn(true);
        $this->fieldAclHelper
            ->expects($this->any())
            ->method('isFieldViewGranted')
            ->willReturn(true);

        $this->listener = new ProductFormViewListener($this->translator, $this->fieldAclHelper);
    }

    public function testOnProductView()
    {
        $product = new Product();

        $env = $this->getEnvironmentForView($product, $this->listener->getMetaFieldLabelPrefix());
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($env, $scrollData, $product);

        $this->listener->onProductView($event);
    }

    public function testOnProductEdit()
    {
        $product = new Product();

        $env = $this->getEnvironmentForEdit();
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($env, $scrollData, $product, new FormView());

        $this->listener->onProductEdit($event);
    }

    #[\Override]
    protected function getEnvironmentForView(object $entityObject, string $labelPrefix): Environment
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->exactly(4))
            ->method('render')
            ->willReturnMap([
                [
                    '@OroSEO/SEO/title_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix,
                    ],
                    '',
                ],                [
                    '@OroSEO/SEO/description_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix,
                    ],
                    '',
                ],
                [
                    '@OroSEO/SEO/keywords_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix,
                    ],
                    '',
                ],
                [
                    '@OroRedirect/entitySlugs.html.twig',
                    [
                        'entitySlugs' => $entityObject->getSlugs(),
                    ],
                    '',
                ],
            ]);

        return $env;
    }
}
