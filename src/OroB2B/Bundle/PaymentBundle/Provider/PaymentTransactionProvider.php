<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentTransactionProvider
{
    use LoggerAwareTrait;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $paymentTransactionClass;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TokenStorageInterface $tokenStorage
     * @param string $paymentTransactionClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenStorageInterface $tokenStorage,
        $paymentTransactionClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionClass = $paymentTransactionClass;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param object $object
     * @param array $filter
     * @param array $orderBy
     * @return PaymentTransaction|null
     */
    public function getPaymentTransaction($object, array $filter = [], array $orderBy = [])
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findOneBy(
            array_merge(
                [
                    'frontendOwner' => $this->getAccountUser()
                ],
                $filter,
                [
                    'entityClass' => $className,
                    'entityIdentifier' => $identifier,
                ]
            ),
            array_merge(['id' => Criteria::DESC], $orderBy)
        );
    }

    /**
     * @param object|null $object
     * @return AccountUser|null
     */
    protected function getAccountUser($object = null)
    {
        if ($object instanceof AccountOwnerAwareInterface) {
            return $object->getAccountUser();
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if ($user instanceof AccountUser) {
            return $user;
        }

        return null;
    }

    /**
     * @param object $object
     * @param array $filter
     * @return PaymentTransaction[]
     */
    public function getPaymentTransactions($object, array $filter = [])
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findBy(
            array_merge(
                [
                    'frontendOwner' => $this->getAccountUser()
                ],
                $filter,
                [
                    'entityClass' => $className,
                    'entityIdentifier' => $identifier,
                ]
            )
        );
    }

    /**
     * @param object $object
     * @return PaymentTransaction|null
     */
    public function getActiveAuthorizePaymentTransaction($object)
    {
        return $this->getPaymentTransaction(
            $object,
            [
                'active' => true,
                'successful' => true,
                'action' => PaymentMethodInterface::AUTHORIZE,
                'frontendOwner' => $this->getAccountUser($object)
            ]
        );
    }

    /**
     * @param object $object
     * @return PaymentTransaction
     */
    public function getActiveValidatePaymentTransaction($object)
    {
        return $this->getPaymentTransaction(
            $object,
            [
                'active' => true,
                'successful' => true,
                'action' => PaymentMethodInterface::VALIDATE,
                'frontendOwner' => $this->getAccountUser()
            ]
        );
    }

    /**
     * @param string $paymentMethod
     * @param string $type
     * @param object $object
     * @return PaymentTransaction
     */
    public function createPaymentTransaction($paymentMethod, $type, $object)
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = new $this->paymentTransactionClass;
        $paymentTransaction
            ->setPaymentMethod($paymentMethod)
            ->setAction($type)
            ->setEntityClass($className)
            ->setEntityIdentifier($identifier)
            ->setFrontendOwner($this->getAccountUser());

        return $paymentTransaction;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function savePaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $em = $this->doctrineHelper->getEntityManager($paymentTransaction);
        try {
            $em->transactional(
                function (EntityManagerInterface $em) use ($paymentTransaction) {
                    if (!$paymentTransaction->getId()) {
                        $em->persist($paymentTransaction);
                    }
                }
            );
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }
        }
    }
}
