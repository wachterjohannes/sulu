<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Controller;

use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PreviewController
{
    use RequestParametersTrait;

    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(PreviewInterface $preview, TokenStorageInterface $tokenStorage)
    {
        $this->preview = $preview;
        $this->tokenStorage = $tokenStorage;
    }

    public function startAction(Request $request): Response
    {
        $id = $this->getRequestParameter($request, 'id', true);
        $provider = $this->getRequestParameter($request, 'provider', true);
        $webspace = $this->getRequestParameter($request, 'webspace', false, null);
        $locale = $this->getRequestParameter($request, 'locale', false, null);

        return new JsonResponse(
            [
                'token' => $this->preview->start($provider, $id, $this->getUserId(), $webspace, $locale),
            ]
        );
    }

    public function renderAction(Request $request): Response
    {
        $token = $this->getRequestParameter($request, 'token', true);
        $webspace = $this->getRequestParameter($request, 'webspace', false, null);
        $locale = $this->getRequestParameter($request, 'locale', false, null);

        $content = $this->preview->render($token, $webspace, $locale);

        return new Response($this->prepareHtml($content), 200 , ['Content-Type' => 'text/html']);
    }

    public function updateAction(Request $request): Response
    {
        $token = $this->getRequestParameter($request, 'token', true);
        $data = $this->getRequestParameter($request, 'data', true);
        $webspace = $this->getRequestParameter($request, 'webspace', false, null);
        $locale = $this->getRequestParameter($request, 'locale', false, null);
        $targetGroup = $this->getRequestParameter($request, 'target-group', false, null);

        $content = $this->preview->update($token, $webspace, $locale, $data, $targetGroup);

        return new JsonResponse(['content' => $this->prepareHtml($content)]);
    }

    public function updateContextAction(Request $request): Response
    {
        $token = $this->getRequestParameter($request, 'token', true);
        $context = $this->getRequestParameter($request, 'context', true);
        $data = $this->getRequestParameter($request, 'data', true);
        $webspace = $this->getRequestParameter($request, 'webspace', false, null);
        $locale = $this->getRequestParameter($request, 'locale', false, null);
        $targetGroup = $this->getRequestParameter($request, 'target-group', false, null);

        $content = $this->preview->updateContext($token, $webspace, $locale, $context, $data, $targetGroup);

        return new JsonResponse(['content' => $this->prepareHtml($content)]);
    }

    public function stopAction(Request $request): Response
    {
        $this->preview->stop($this->getRequestParameter($request, 'token', true));

        return new JsonResponse();
    }

    protected function prepareHtml(string $html)
    {
        return preg_replace('/(href|action)="[^"]*"/', '$1="#"', $html);
    }

    protected function getUserId(): ?int
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if (!$token) {
            return null;
        }

        return $user->getId();
    }
}
