<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is an service helper for ListResources accessed
 * by an REST-API. It contains a few getters, which
 * deliver some values needed by the inheriting controller.
 * These values are calculated from the request paramaters.
 */
class ListRestHelper implements ListRestHelperInterface
{
    /**
     * The current request object.
     *
     * @var Request
     */
    protected $requestStack;

    /**
     * The constructor takes the request stack as an argument, which
     * is injected by the service container.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Returns the current Request.
     *
     * @return Request
     */
    protected function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Returns an array of ids to which the response should be restricted.
     * If null is returned, entities in the response should not be restricted by their id.
     *
     * @return array
     */
    public function getIds()
    {
        $idsString = $this->getRequest()->get('ids');

        return (null !== $idsString) ? array_filter(explode(',', $idsString)) : null;
    }

    /**
     * Returns an array of ids which should be excluded from the response.
     *
     * @return array
     */
    public function getExcludedIds()
    {
        $excludedIdsString = $this->getRequest()->get('excludedIds');

        return (null !== $excludedIdsString) ? array_filter(explode(',', $excludedIdsString)) : [];
    }

    /**
     * Returns the desired sort column.
     *
     * @return string
     */
    public function getSortColumn()
    {
        return $this->getRequest()->get('sortBy', null);
    }

    /**
     * Returns desired sort order.
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->getRequest()->get('sortOrder', 'asc');
    }

    /**
     * Returns the maximum number of elements in a single response.
     *
     * @return int
     */
    public function getLimit()
    {
        $default = 10;
        if ('csv' === $this->getRequest()->getRequestFormat()) {
            $default = null;
        }

        // set default limit to count of ids if result is restricted to specific ids
        $ids = $this->getIds();
        if (null != $ids) {
            $default = count($ids);
        }

        return $this->getRequest()->get('limit', $default);
    }

    /**
     * Returns the calculated value for the starting position based
     * on the page and limit values.
     *
     * @return int|null
     */
    public function getOffset()
    {
        $page = $this->getRequest()->get('page', 1);
        $limit = $this->getLimit();

        return (null != $limit) ? $limit * ($page - 1) : null;
    }

    /**
     * returns the current page.
     *
     * @return mixed
     */
    public function getPage()
    {
        return $this->getRequest()->get('page', 1);
    }

    /**
     * Returns an array with all the fields, which should be contained in the response.
     * If null is returned every field should be contained.
     *
     * @return array|null
     */
    public function getFields()
    {
        $fields = $this->getRequest()->get('fields');

        return (null != $fields) ? explode(',', $fields) : null;
    }

    /**
     * Returns the pattern of the search.
     *
     * @return mixed
     */
    public function getSearchPattern()
    {
        return $this->getRequest()->get('search');
    }

    /**
     * Returns an array with all the fields the search pattern should be executed on.
     *
     * @return array|null
     */
    public function getSearchFields()
    {
        $searchFields = $this->getRequest()->get('searchFields');

        return (null != $searchFields) ? explode(',', $searchFields) : [];
    }
}
