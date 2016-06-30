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

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\PHPCR\SessionManager\SessionManager;
use Sulu\Component\Rest\ListBuilder\AbstractListBuilder;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\PHPCR\FieldDescriptor\PHPCRFieldDescriptor;
use Sulu\Exception\FeatureNotImplementedException;

/**
 * The list-builder implementation for massive-search.
 */
class PHPCRListBuilder extends AbstractListBuilder
{
    /**
     * @var PHPCRFieldDescriptor[]
     */
    protected $selectFields = [];

    /**
     * @var PHPCRFieldDescriptor[]
     */
    protected $searchFields = [];

    /**
     * @var PHPCRFieldDescriptor[]
     */
    protected $sortFields = [];
    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var QueryObjectModelFactoryInterface
     */
    private $qomFactory;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string[]
     */
    private $locales;

    public function __construct($locale, $locales, SessionManager $sessionManager, PropertyEncoder $propertyEncoder)
    {
        $this->sessionManager = $sessionManager;

        $this->locale = $locale;
        $this->locales = $locales;
        $this->session = $sessionManager->getSession();
        $this->qomFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $queryBuilder = $this->getQueryBuilder($this->locale, $this->locales);
    }

    /**
     * Returns QueryBuilder with basic select and where statements.
     *
     * @param string $locale
     * @param string[] $locales
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder($locale, $locales)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder->select('node', 'jcr:uuid', 'uuid');

        return $queryBuilder;
    }

    /**
     * Append mapping selects for a single property to given query-builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $propertyName
     * @param string[] $locales
     */
    private function appendSingleMapping(QueryBuilder $queryBuilder, $propertyName, $locales)
    {
        foreach ($locales as $locale) {
            $alias = sprintf('%s%s', $locale, str_replace('-', '_', ucfirst($propertyName)));

            $queryBuilder->addSelect(
                'node',
                $this->propertyEncoder->localizedContentName($propertyName, $locale),
                $alias
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createBetweenExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createInExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createWhereExpression(FieldDescriptorInterface $fieldDescriptor, $value, $comparator)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createAndExpression(array $expressions)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createOrExpression(array $expressions)
    {
        throw new FeatureNotImplementedException();
    }
}
