<?php

namespace Oro\Bundle\RedirectBundle\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\RememberMe\ResponseListener;

/**
 * Decorates a slug request factory to handle "Remember Me" login functionality data.
 */
class RememberMeSlugRequestFactory implements SlugRequestFactoryInterface
{
    /** @var SlugRequestFactoryInterface */
    private $innerFactory;

    public function __construct(SlugRequestFactoryInterface $innerFactory)
    {
        $this->innerFactory = $innerFactory;
    }

    #[\Override]
    public function createSlugRequest(Request $request): Request
    {
        $slugRequest = $this->innerFactory->createSlugRequest($request);
        $rememberMeCookie = $request->attributes->get(ResponseListener::COOKIE_ATTR_NAME);
        if ($rememberMeCookie instanceof Cookie) {
            $slugRequest->attributes->set(ResponseListener::COOKIE_ATTR_NAME, $rememberMeCookie);
            $slugRequest->cookies->set($rememberMeCookie->getName(), $rememberMeCookie->getValue());
        }

        return $slugRequest;
    }

    #[\Override]
    public function updateMainRequest(Request $request, Request $slugRequest): void
    {
        $this->innerFactory->updateMainRequest($request, $slugRequest);
        $rememberMeCookie = $slugRequest->attributes->get(ResponseListener::COOKIE_ATTR_NAME);
        if ($rememberMeCookie instanceof Cookie) {
            $request->attributes->set(ResponseListener::COOKIE_ATTR_NAME, $rememberMeCookie);
            $request->cookies->set($rememberMeCookie->getName(), $rememberMeCookie->getValue());
        }
    }
}
