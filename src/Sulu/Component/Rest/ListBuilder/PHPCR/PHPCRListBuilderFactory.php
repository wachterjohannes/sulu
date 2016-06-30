<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\PHPCR;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;

/**
 * Factory for SearchListBuilders.
 */
class PHPCRListBuilderFactory
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @param SearchManagerInterface $searchManager
     */
    public function __construct(SearchManagerInterface $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    /**
     * Create a new list-builder instance.
     *
     * @param string $indexName
     * @param string $locale
     *
     * @return PHPCRListBuilder
     */
    public function create($indexName, $locale)
    {
        return new PHPCRListBuilder($indexName, $locale, $this->searchManager);
    }
}
