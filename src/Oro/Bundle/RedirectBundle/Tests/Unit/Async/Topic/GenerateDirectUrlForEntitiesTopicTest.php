<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\RedirectBundle\Async\Topic\DirectUrlTopicHelper;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenerateDirectUrlForEntitiesTopicTest extends \PHPUnit\Framework\TestCase
{
    private DirectUrlTopicHelper|\PHPUnit\Framework\MockObject\MockObject $directUrlTopicHelper;

    private GenerateDirectUrlForEntitiesTopic $topic;

    #[\Override]
    protected function setUp(): void
    {
        $this->directUrlTopicHelper = $this->createMock(DirectUrlTopicHelper::class);

        $this->topic = new GenerateDirectUrlForEntitiesTopic($this->directUrlTopicHelper);
    }

    public function testConfigureMessageBody(): void
    {
        $resolver = new OptionsResolver();
        $this->directUrlTopicHelper
            ->expects(self::once())
            ->method('configureIdOption')
            ->with($resolver);

        $this->directUrlTopicHelper
            ->expects(self::once())
            ->method('configureEntityClassOption')
            ->with($resolver);

        $this->directUrlTopicHelper
            ->expects(self::once())
            ->method('configureRedirectOption')
            ->with($resolver);

        $this->topic->configureMessageBody($resolver);
    }
}
