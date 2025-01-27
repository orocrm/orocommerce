<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\ShoppingListCheckoutProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListEventPostTransfer;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Listener to handle checkout processing when the source shopping list changes after login.
 *
 * This listener ensures that the checkout is properly updated or removed if the source shopping list changes
 * upon user login or shopping list transfer.
 */
class ProcessCheckoutOnSourceChange implements LoggerAwareInterface
{
    use FeatureCheckerHolderTrait;
    use LoggerAwareTrait;

    private ?ShoppingList $visitorShoppingList = null;
    private ?ShoppingList $currentShoppingList = null;

    public function __construct(
        private ShoppingListCheckoutProvider $checkoutProvider,
        private ActionGroupRegistry $actionGroupRegistry,
        private WebsiteManager $websiteManager,
        private ManagerRegistry $managerRegistry
    ) {
    }

    public function onShoppingListPostTransfer(ShoppingListEventPostTransfer $event): self
    {
        $this->visitorShoppingList = $event->getShoppingList();
        $this->currentShoppingList = $event->getCurrentShoppingList();

        return $this;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if ($this->shouldSkipProcessing($event->getAuthenticationToken()->getUser())) {
            return;
        }

        $visitorCheckout = $this->checkoutProvider->getCheckout($this->visitorShoppingList);
        if (!$visitorCheckout) {
            return;
        }
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->beginTransaction();
        try {
            $this->processCheckout($entityManager, $visitorCheckout);
            $entityManager->commit();
        } catch (\Exception $exception) {
            $entityManager->rollback();
            $this->logger?->error('An error occurred while processing the checkout:' . $exception->getMessage());
        }
    }

    private function processCheckout(ObjectManager $entityManager, Checkout $visitorCheckout): void
    {
        $this->removeCurrentCheckout($entityManager);
        $this->unlinkShoppingList($entityManager, $visitorCheckout);
        $this->removeVisitorShoppingList($entityManager);
        $this->actualizeCheckout($visitorCheckout);
    }

    private function actualizeCheckout(Checkout $checkout): void
    {
        $checkout->getSource()->setShoppingList($this->currentShoppingList);
        $website = $this->websiteManager->getCurrentWebsite();
        $actionData = new ActionData([
            'checkout' => $checkout,
            'currentWebsite' => $website,
            'sourceCriteria' => ['shoppingList' => $this->currentShoppingList]
        ]);
        $this->actionGroupRegistry->findByName('actualize_checkout')?->execute($actionData);
    }

    private function unlinkShoppingList(ObjectManager $entityManager, Checkout $checkout): void
    {
        $source = $checkout->getSource();
        $source->setShoppingList(null);
        $entityManager->flush($source);
    }

    private function removeVisitorShoppingList(ObjectManager $entityManager): void
    {
        $entityManager->remove($this->visitorShoppingList);
        $entityManager->flush();
    }

    private function removeCurrentCheckout(ObjectManager $entityManager): void
    {
        $checkout = $this->checkoutProvider->getCheckout($this->currentShoppingList);
        if ($checkout) {
            $entityManager->remove($checkout);
            $entityManager->flush();
        }
    }

    private function shouldSkipProcessing(mixed $user): bool
    {
        return $this->isFeaturesEnabled()
            || !$user instanceof CustomerUser
            || !$this->currentShoppingList
            || !$this->visitorShoppingList
            || $this->visitorShoppingList === $this->currentShoppingList;
    }
}
