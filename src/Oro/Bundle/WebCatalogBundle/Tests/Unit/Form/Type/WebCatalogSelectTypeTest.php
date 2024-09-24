<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebCatalogSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var WebCatalogSelectType
     */
    protected $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new WebCatalogSelectType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) use ($resolver) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals(WebCatalogType::class, $options['autocomplete_alias']);
                    $this->assertEquals('oro_web_catalog_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'placeholder' => 'oro.webcatalog.form.choose'
                        ],
                        $options['configs']
                    );

                    return $resolver;
                }
            );

        $this->formType->configureOptions($resolver);
    }
}
