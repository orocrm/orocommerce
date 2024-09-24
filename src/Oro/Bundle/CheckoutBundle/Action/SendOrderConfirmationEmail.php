<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate;
use Twig\Error\Error;

/**
 * A wrapper over @send_email_template
 * Currently it ignores email template rendering exceptions
 * (no email will be sent though).
 */
class SendOrderConfirmationEmail extends SendEmailTemplate
{
    #[\Override]
    protected function executeAction($context): void
    {
        try {
            parent::executeAction($context);
        } catch (Error | EmailTemplateCompilationException $exception) {
            $this->logger->error(
                'Twig exception in @send_order_confirmation_email action',
                ['exception' => $exception]
            );
        } catch (EntityNotFoundException $exception) {
            $this->logger->error(
                'Cannot find the specified email template in @send_order_confirmation_email action',
                ['exception' => $exception]
            );
        }
    }
}
