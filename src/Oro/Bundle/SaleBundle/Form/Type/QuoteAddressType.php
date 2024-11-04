<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for QuoteAddress entity.
 */
class QuoteAddressType extends AbstractType
{
    private AddressFormatter $addressFormatter;
    private QuoteAddressManager $quoteAddressManager;
    private QuoteAddressSecurityProvider $quoteAddressSecurityProvider;
    private Serializer $serializer;

    public function __construct(
        AddressFormatter $addressFormatter,
        QuoteAddressManager $quoteAddressManager,
        QuoteAddressSecurityProvider $quoteAddressSecurityProvider,
        Serializer $serializer
    ) {
        $this->addressFormatter = $addressFormatter;
        $this->quoteAddressManager = $quoteAddressManager;
        $this->quoteAddressSecurityProvider = $quoteAddressSecurityProvider;
        $this->serializer = $serializer;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $type = $options['addressType'];
        $quote = $options['quote'];

        $isManualEditGranted = $this->quoteAddressSecurityProvider->isManualEditGranted($type);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($quote, $type, $isManualEditGranted) {
                $form = $event->getForm();

                $addressCollection = $this->quoteAddressManager->getGroupedAddresses($quote, $type, 'oro.sale.quote.');
                $addresses = $addressCollection->toArray();

                $customerAddressOptions = [
                    'label' => false,
                    'required' => false,
                    'mapped' => false,
                    'choices' => $this->getChoices($addresses),
                    'configs' => ['placeholder' => 'oro.sale.quote.form.address.choose'],
                    'attr' => [
                        'data-addresses' => json_encode($this->getPlainData($addresses)),
                        'data-default' => $addressCollection->getDefaultAddressKey(),
                    ],
                ];

                if ($isManualEditGranted) {
                    $customerAddressOptions['choices'] = array_merge(
                        $customerAddressOptions['choices'],
                        ['oro.sale.quote.form.address.manual' => 0]
                    );
                    $customerAddressOptions['configs']['placeholder'] = 'oro.sale.quote.form.address.choose_or_create';
                }

                $form->add('customerAddress', Select2ChoiceType::class, $customerAddressOptions);
            }
        );

        $builder->add('phone', TextType::class, [StripTagsExtension::OPTION_NAME => true]);

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($isManualEditGranted) {
                if (!$isManualEditGranted) {
                    $event->setData(null);
                }

                $form = $event->getForm();
                if (!$form->has('customerAddress')) {
                    return;
                }

                $identifier = $form->get('customerAddress')->getData();
                if ($identifier === null) {
                    return;
                }

                //Enter manually or Customer/CustomerUser address
                $quoteAddress = $event->getData();

                $address = null;
                if ($identifier) {
                    $address = $this->quoteAddressManager->getEntityByIdentifier($identifier);
                }

                if ($quoteAddress || $address) {
                    $event->setData($this->quoteAddressManager->updateFromAbstract($address, $quoteAddress));
                }
            },
            -10
        );
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $isManualEditGranted = $this->quoteAddressSecurityProvider->isManualEditGranted($options['addressType']);
        $exceptKey = 'phone';

        foreach ($view->children as $key => $child) {
            if ($key === $exceptKey) {
                continue;
            }

            $child->vars['disabled'] = !$isManualEditGranted;
            $child->vars['required'] = false;
            unset(
                $child->vars['attr']['data-validation'],
                $child->vars['attr']['data-required'],
                $child->vars['label_attr']['data-required']
            );
        }

        if ($view->offsetExists('customerAddress')) {
            $view->offsetGet('customerAddress')->vars['disabled'] = false;
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['quote', 'addressType'])
            ->setDefaults(['data_class' => QuoteAddress::class])
            ->setAllowedValues('addressType', [AddressTypeEntity::TYPE_SHIPPING])
            ->setAllowedTypes('quote', Quote::class);
    }

    #[\Override]
    public function getParent(): string
    {
        return AddressType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_quote_address_type';
    }

    private function getChoices(array $addresses = []): array
    {
        foreach ($addresses as $group => $groupAddresses) {
            array_walk(
                $groupAddresses,
                function (&$item) {
                    if ($item instanceof AbstractAddress) {
                        $item = $this->addressFormatter->format($item, null, ', ');
                    }
                }
            );
            $addresses[$group] = array_flip($groupAddresses);
        }

        return $addresses;
    }

    private function getPlainData(array $addresses = []): array
    {
        $data = [];

        array_walk_recursive(
            $addresses,
            function ($item, $key) use (&$data) {
                if ($item instanceof AbstractAddress) {
                    $data[$key] = $this->serializer->normalize($item);
                }
            }
        );

        return $data;
    }
}
