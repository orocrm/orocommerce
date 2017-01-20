<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleType;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistry;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodsConfigsRuleTypeTest extends FormIntegrationTestCase
{
    const PAYMENT_TYPE = 'code1';
    const ADMIN_LABEL = 'admin_label1';

    /**
     * @var PaymentMethodsConfigsRuleType
     */
    protected $formType;

    /**
     * @var PaymentMethodProvidersRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodProvidersRegistry;

    /**
     * @var PaymentMethodViewProvidersRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewProvidersRegistry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->createMocks();
        $this->formType = new PaymentMethodsConfigsRuleType(
            $this->paymentMethodProvidersRegistry,
            $this->paymentMethodViewProvidersRegistry
        );
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PaymentMethodsConfigsRuleType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);
        $options = $form->getConfig()->getOptions();
        $this->assertContains('data_class', $options);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|null $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create($this->formType, $data);

        $this->assertEquals($data, $form->getData());

        $form->submit([
            'methodConfigs' => [['type' => self::PAYMENT_TYPE, 'options' => []]],
            'destinations'  => [['country' => 'US']],
            'currency'      => 'USD',
            'rule'          => [
                'name'    => 'rule2',
                'enabled' => 'true',
            ],
        ]);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            (new PaymentMethodsConfigsRule())
                ->setCurrency('USD')
                ->setRule((new Rule())->setName('rule2'))
                ->addDestination((new PaymentMethodsConfigsRuleDestination())->setCountry(new Country('US')))
                ->addMethodConfig((new PaymentMethodConfig())->setType(self::PAYMENT_TYPE)),
            $form->getData()
        );
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [null],
            [
                (new PaymentMethodsConfigsRule())
                    ->setRule((new Rule())->setName('rule1'))
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD']);

        $country = new Country('US');
        $choices = [
            'OroAddressBundle:Country' => ['US' => $country],
            'OroAddressBundle:Region'  => ['US-AL' => (new Region('US-AL'))],
        ];

        $translatableEntity->expects($this->any())->method('setDefaultOptions')->will(
            $this->returnCallback(
                function (OptionsResolver $resolver) use ($choices) {
                    $choiceList = function (Options $options) use ($choices) {
                        $className = $options->offsetGet('class');
                        if (array_key_exists($className, $choices)) {
                            return new ArrayChoiceList(
                                $choices[$className],
                                function ($item) {
                                    if ($item instanceof Country) {
                                        return $item->getIso2Code();
                                    }

                                    if ($item instanceof Region) {
                                        return $item->getCombinedCode();
                                    }

                                    return $item . uniqid('form', true);
                                }
                            );
                        }

                        return new ArrayChoiceList([]);
                    };

                    $resolver->setDefault('choice_list', $choiceList);
                }
            )
        );
        $this->createMocks();

        $subscriber = new RuleMethodConfigCollectionSubscriber($this->paymentMethodProvidersRegistry);

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                           => new CollectionType(),
                    RuleType::BLOCK_PREFIX                         => new RuleType(),
                    PaymentMethodConfigType::NAME                  =>
                        new PaymentMethodConfigType(
                            $this->paymentMethodProvidersRegistry,
                            $this->paymentMethodViewProvidersRegistry
                        ),
                    PaymentMethodsConfigsRuleDestinationType::NAME =>
                        new PaymentMethodsConfigsRuleDestinationType(new AddressCountryAndRegionSubscriberStub()),
                    PaymentMethodConfigCollectionType::class       =>
                        new PaymentMethodConfigCollectionType($subscriber),
                    CurrencySelectionType::NAME                    => new CurrencySelectionType(
                        $currencyProvider,
                        $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock(),
                        $this->getMockBuilder(CurrencyNameHelper::class)->disableOriginalConstructor()->getMock()
                    ),
                    'oro_country'                                  => new CountryType(),
                    'genemu_jqueryselect2_translatable_entity'     => new Select2Type('translatable_entity'),
                    'translatable_entity'                          => $translatableEntity,
                    'oro_region'                                   => new RegionType(),
                ],
                ['form' => [new AdditionalAttrExtension()]]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    protected function createMocks()
    {

        $this->paymentMethodViewProvidersRegistry = $this
            ->getMockBuilder(PaymentMethodViewProvidersRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodProvidersRegistry = $this->getMockBuilder(PaymentMethodProvidersRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMockBuilder(PaymentMethodInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethod->expects(static::any())->method('getIdentifier')->willReturn(self::PAYMENT_TYPE);

        /** @var PaymentMethodViewInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethodView */
        $paymentMethodView = $this->getMockBuilder(PaymentMethodViewInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodView->expects(static::any())->method('getAdminLabel')->willReturn(self::ADMIN_LABEL);

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodProvider
            ->expects(static::any())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);
        $paymentMethodProvider
            ->expects(static::any())
            ->method('hasPaymentMethod')
            ->with(self::PAYMENT_TYPE)
            ->willReturn(true);
        $paymentMethodProvider
            ->expects(static::any())
            ->method('getPaymentMethod')
            ->with(self::PAYMENT_TYPE)
            ->willReturn($paymentMethod);

        $this->paymentMethodProvidersRegistry->expects(static::any())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);
        $this->paymentMethodViewProvidersRegistry->expects(static::any())
            ->method('getPaymentMethodView')
            ->with(self::PAYMENT_TYPE)
            ->willReturn($paymentMethodView);
    }
}
