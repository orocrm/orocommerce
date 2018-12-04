<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\FillConsentContextEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Extension\FrontendCustomerEditExtension;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendCustomerEditExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var CustomerConsentsEventSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $saveConsentAcceptanceSubscriber;

    /** @var FillConsentContextEventSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $fillConsentContextEventSubscriber;

    /** @var PopulateFieldCustomerConsentsSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $populateFieldCustomerConsentsSubscriber;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $formBuilder;

    /** @var FrontendCustomerEditExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->saveConsentAcceptanceSubscriber = $this->createMock(CustomerConsentsEventSubscriber::class);
        $this->fillConsentContextEventSubscriber = $this->createMock(FillConsentContextEventSubscriber::class);
        $this->populateFieldCustomerConsentsSubscriber = $this->createMock(
            PopulateFieldCustomerConsentsSubscriber::class
        );
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->extension = new FrontendCustomerEditExtension(
            $this->saveConsentAcceptanceSubscriber,
            $this->fillConsentContextEventSubscriber,
            $this->populateFieldCustomerConsentsSubscriber,
            $this->tokenStorage
        );
        $this->extension->setFeatureChecker($this->featureChecker);
    }

    public function testGetExtendedType()
    {
        $this->assertNull($this->extension->getExtendedType());

        $this->extension->setExtendedType('extendedType');
        $this->assertEquals('extendedType', $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $this->extension->addFeature('feature');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->formBuilder->expects($this->exactly(3))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->saveConsentAcceptanceSubscriber],
                [$this->fillConsentContextEventSubscriber],
                [$this->populateFieldCustomerConsentsSubscriber]
            );

        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::POST_SET_DATA,
                [$this->extension, 'addCustomerConsentsField'],
                2000
            );

        $this->extension->buildForm($this->formBuilder, []);
    }

    public function testBuildFormFeatureDisabled()
    {
        $this->extension->addFeature('feature');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->formBuilder->expects($this->never())
            ->method('addEventSubscriber');

        $this->formBuilder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($this->formBuilder, []);
    }

    public function testAddCustomerConsentsField()
    {
        $form = $this->createMock(FormInterface::class);
        $data = $this->getEntity(CustomerUser::class, ['id' => 35]);
        $event = new FormEvent($form, $data);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($data);

        $form->expects($this->once())
            ->method('add')
            ->with(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class
            );

        $this->extension->addCustomerConsentsField($event);
    }

    public function testAddCustomerConsentsFieldNoCustomerUserInEventData()
    {
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, []);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $token->expects($this->never())
            ->method('getUser');

        $form->expects($this->never())
            ->method('add');

        $this->extension->addCustomerConsentsField($event);
    }

    public function testAddCustomerConsentsFieldNoToken()
    {
        $form = $this->createMock(FormInterface::class);
        $data = $this->getEntity(CustomerUser::class, ['id' => 35]);
        $event = new FormEvent($form, $data);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $otherCustomerUser = $this->getEntity(CustomerUser::class, ['id' => 72]);
        $token->expects($this->never())
            ->method('getUser');

        $form->expects($this->never())
            ->method('add');

        $this->extension->addCustomerConsentsField($event);
    }

    public function testAddCustomerConsentsFieldWrongCustomerUserInEventData()
    {
        $form = $this->createMock(FormInterface::class);
        $data = $this->getEntity(CustomerUser::class, ['id' => 35]);
        $event = new FormEvent($form, $data);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $otherCustomerUser = $this->getEntity(CustomerUser::class, ['id' => 72]);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($otherCustomerUser);

        $form->expects($this->never())
            ->method('add');

        $this->extension->addCustomerConsentsField($event);
    }
}
