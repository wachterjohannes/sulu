<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

use Doctrine\Common\Cache\Cache;
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provider functionality to render and update preview instances.
 */
class Preview implements PreviewInterface
{
    /**
     * @var int
     */
    private $cacheLifeTime;

    /**
     * @var PreviewObjectProviderInterface[]
     */
    private $objectProviders;

    /**
     * @var Cache
     */
    private $dataCache;

    /**
     * @var PreviewRendererInterface
     */
    private $renderer;

    public function __construct(
        array $objectProvidersForClass,
        array $objectProvidersForKey,
        Cache $dataCache,
        PreviewRendererInterface $renderer,
        int $cacheLifeTime = 3600
    ) {
        $this->objectProviders = array_merge($objectProvidersForClass, $objectProvidersForKey);
        $this->dataCache = $dataCache;
        $this->renderer = $renderer;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function start($providerKey, $id, $userId, $webspaceKey, $locale, array $data = [])
    {
        $provider = $this->getProvider($providerKey);
        $object = $provider->getObject($id, $locale);
        $token = md5(sprintf('%s.%s.%s.%s', $providerKey, $id, $locale, $userId));

        if (0 !== count($data)) {
            $provider->setValues($object, $locale, $data);
        }

        $this->save($token, $object);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function stop($token)
    {
        if (!$this->exists($token)) {
            return;
        }

        $this->dataCache->delete($token);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($token)
    {
        return $this->dataCache->contains($token);
    }

    /**
     * {@inheritdoc}
     */
    public function update($token, $webspaceKey, $locale, array $data, $targetGroupId = null)
    {
        if (0 === count($data)) {
            return [];
        }

        $object = $this->fetch($token);
        $provider = $this->getProvider(get_class($object));
        $provider->setValues($object, $locale, $data);
        $this->save($token, $object);

        $id = $provider->getId($object);
        $partialHtml = $this->renderer->render($object, $id, $webspaceKey, $locale, true, $targetGroupId);
        $html = $this->fetchHtml($token);

        return str_replace('<!-- CONTENT-REPLACER -->', $partialHtml, $html);
    }

    /**
     * {@inheritdoc}
     */
    public function updateContext($token, $webspaceKey, $locale, array $context, array $data, $targetGroupId = null)
    {
        $object = $this->fetch($token);
        $provider = $this->getProvider(get_class($object));
        if (0 === count($context)) {
            $id = $provider->getId($object);

            return $this->renderer->render($object, $id, $webspaceKey, $locale, false, $targetGroupId);
        }

        // context
        $object = $provider->setContext($object, $locale, $context);
        $id = $provider->getId($object);

        if (0 < count($data)) {
            // data
            $provider->setValues($object, $locale, $data);
        }

        $this->save($token, $object);

        return $this->doRender($token, $object, $id, $webspaceKey, $locale, false, $targetGroupId);
    }

    /**
     * {@inheritdoc}
     */
    public function render($token, $webspaceKey, $locale, $targetGroupId = null)
    {
        $object = $this->fetch($token);
        $id = $this->getProvider(get_class($object))->getId($object);

        return $this->doRender($token, $object, $id, $webspaceKey, $locale, false, $targetGroupId);
    }

    protected function doRender($token, $object, $id, $webspaceKey, $locale, $partial = false, $targetGroupId = null)
    {
        $html = $this->renderer->render($object, $id, $webspaceKey, $locale, $partial, $targetGroupId);
        $crawler = new Crawler($html);

        $this->saveHtml($token, str_replace($crawler->filter('#content')->html(), '<!-- CONTENT-REPLACER -->', $html));

        return $html;
    }

    protected function getProvider(string $providerKey): PreviewObjectProviderInterface
    {
        if (!array_key_exists($providerKey, $this->objectProviders)) {
            throw new ProviderNotFoundException($providerKey);
        }

        return $this->objectProviders[$providerKey];
    }

    protected function save(string $token, $object): void
    {
        $data = $this->getProvider(get_class($object))->serialize($object);
        $data = sprintf("%s\n%s", get_class($object), $data);

        $this->dataCache->save($token, $data, $this->cacheLifeTime);
    }

    protected function fetch(string $token)
    {
        if (!$this->exists($token)) {
            throw new TokenNotFoundException($token);
        }

        $cacheEntry = explode("\n", $this->dataCache->fetch($token), 2);

        return $this->getProvider($cacheEntry[0])->deserialize($cacheEntry[1], $cacheEntry[0]);
    }

    protected function saveHtml(string $token, string $html): void
    {
        $this->dataCache->save(sprintf('%s.html', $token), $html, $this->cacheLifeTime);
    }

    protected function fetchHtml(string $token): string
    {
        $cacheId = sprintf('%s.html', $token);
        if (!$this->exists($cacheId)) {
            throw new TokenNotFoundException($token);
        }

        return $this->dataCache->fetch($cacheId);
    }
}
