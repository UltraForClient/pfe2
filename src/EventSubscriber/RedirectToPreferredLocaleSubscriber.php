<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectToPreferredLocaleSubscriber implements EventSubscriberInterface
{
    private $urlGenerator;
    private $locales;
    private $defaultLocale;
    public function __construct(UrlGeneratorInterface $urlGenerator, string $locales, string $defaultLocale = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->locales = explode('|', trim($locales));

        if (empty($this->locales)) throw new \UnexpectedValueException('The list of supported locales must not be empty.');

        $this->defaultLocale = $defaultLocale ?: $this->locales[0];

        if (!in_array($this->defaultLocale, $this->locales, true)) throw new \UnexpectedValueException(sprintf('The default locale ("%s") must be one of "%s".', $this->defaultLocale, $locales));

        array_unshift($this->locales, $this->defaultLocale);
        $this->locales = array_unique($this->locales);
    }
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest',];
    }
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest() || '/' !== $request->getPathInfo()) return;


        if (0 === mb_stripos($request->headers->get('referer'), $request->getSchemeAndHttpHost())) return;

        $preferredLanguage = $request->getPreferredLanguage($this->locales);
        if ($preferredLanguage !== $this->defaultLocale) {
            $response = new RedirectResponse($this->urlGenerator->generate('homepage', ['_locale' => $preferredLanguage]));
            $event->setResponse($response);
        }
    }
}