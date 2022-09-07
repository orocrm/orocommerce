<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Oro\Bundle\PricingBundle\Async\Topic\RebuildCombinedPriceListsTopic;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RebuildCombinedPriceListsTopicTest extends AbstractTopicTestCase
{
    use EntityTrait;

    private ManagerRegistry $registry;

    protected function getTopic(): TopicInterface
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        // The registry will return repository mock which is configured to return entities for even IDs
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnCallback(function ($className) {
                $repo = $this->createMock(EntityRepository::class);
                $repo->expects($this->any())
                    ->method('find')
                    ->willReturnCallback(function ($id) use ($className) {
                        if ($id % 2 === 0) {
                            return $this->getEntity($className, ['id' => $id]);
                        }

                        return null;
                    });

                return $repo;
            });

        return new RebuildCombinedPriceListsTopic($this->registry);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function validBodyDataProvider(): array
    {
        return [
            'empty message' => [
                'rawBody' => [],
                'expectedMessage' => [
                    'force' => false,
                    'website' => null,
                    'customerGroup' => null,
                    'customer' => null,
                    'assignments' => [
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null,
                        ]
                    ]
                ]
            ],

            'just force' => [
                'rawBody' => ['force' => true],
                'expectedMessage' => [
                    'force' => true,
                    'website' => null,
                    'customerGroup' => null,
                    'customer' => null,
                    'assignments' => [
                        [
                            'force' => true,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null,
                        ]
                    ]
                ]
            ],

            'website' => [
                'rawBody' => ['website' => 2],
                'expectedMessage' => [
                    'force' => false,
                    'website' => $this->getEntity(Website::class, ['id' => 2]),
                    'customerGroup' => null,
                    'customer' => null,
                    'assignments' => [
                        [
                            'force' => false,
                            'website' => $this->getEntity(Website::class, ['id' => 2]),
                            'customer' => null,
                            'customerGroup' => null,
                        ]
                    ]
                ]
            ],

            'customer group' => [
                'rawBody' => ['force' => true, 'website' => 2, 'customerGroup' => 4],
                'expectedMessage' => [
                    'force' => true,
                    'website' => $this->getEntity(Website::class, ['id' => 2]),
                    'customerGroup' => $this->getEntity(CustomerGroup::class, ['id' => 4]),
                    'customer' => null,
                    'assignments' => [
                        [
                            'force' => true,
                            'website' => $this->getEntity(Website::class, ['id' => 2]),
                            'customer' => null,
                            'customerGroup' => $this->getEntity(CustomerGroup::class, ['id' => 4]),
                        ]
                    ]
                ]
            ],

            'customer' => [
                'rawBody' => ['website' => 2, 'customer' => 4],
                'expectedMessage' => [
                    'force' => false,
                    'website' => $this->getEntity(Website::class, ['id' => 2]),
                    'customerGroup' => null,
                    'customer' => $this->getEntity(Customer::class, ['id' => 4]),
                    'assignments' => [
                        [
                            'force' => false,
                            'website' => $this->getEntity(Website::class, ['id' => 2]),
                            'customer' => $this->getEntity(Customer::class, ['id' => 4]),
                            'customerGroup' => null,
                        ]
                    ]
                ]
            ],

            'assignments' => [
                'rawBody' => [
                    'website' => 2,
                    'customer' => 4,
                    'assignments' => [
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null,
                        ]
                    ]
                ],
                'expectedMessage' => [
                    'force' => false,
                    'website' => $this->getEntity(Website::class, ['id' => 2]),
                    'customerGroup' => null,
                    'customer' => $this->getEntity(Customer::class, ['id' => 4]),
                    'assignments' => [
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null,
                        ],
                        // If assignments exist, ignore other message data.
                    ]
                ]
            ],
            [
                'rawBody' => [
                    'assignments' => [
                        [],
                        [
                            'force' => true,
                        ],
                        [
                            'website' => 1,
                        ],
                        [
                            'customer' => 1,
                        ],
                        [
                            'customerGroup' => 1,
                        ],
                        [
                            'force' => true,
                            'website' => 1,
                            'customer' => 2,
                            'customerGroup' => 3
                        ]
                    ],
                ],
                'expectedMessage' => [
                    'force' => false,
                    'website' => null,
                    'customerGroup' => null,
                    'customer' => null,
                    'assignments' => [
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null
                        ],
                        [
                            'force' => true,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null
                        ],
                        [
                            'force' => false,
                            'website' => 1,
                            'customer' => null,
                            'customerGroup' => null
                        ],
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => 1,
                            'customerGroup' => null
                        ],
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => 1
                        ],
                        [
                            'force' => true,
                            'website' => 1,
                            'customer' => 2,
                            'customerGroup' => 3
                        ]
                    ],
                ]
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'invalid force' => [
                'body' => ['force' => 1],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "force" with value 1 is expected to be of type "bool", but is of type "integer"./',
            ],

            'invalid website' => [
                'body' => ['website' => [1, 2]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "website" with value array is expected to be of type "null" or "int" or "string", ' .
                    'but is of type "array"/',
            ],

            'not found website' => [
                'body' => ['website' => 1],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/Website was not found./',
            ],

            'customer group without website' => [
                'body' => ['customerGroup' => 2],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The "website" option is required when "customerGroup" is set./',
            ],

            'customer without website' => [
                'body' => ['customer' => 2],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The "website" option is required when "customer" is set./',
            ],

            'not found customer group' => [
                'body' => ['website' => 2, 'customerGroup' => 1],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/Customer Group was not found./',
            ],

            'not found customer' => [
                'body' => ['website' => 2, 'customer' => 1],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/Customer was not found./',
            ]
        ];
    }
}
