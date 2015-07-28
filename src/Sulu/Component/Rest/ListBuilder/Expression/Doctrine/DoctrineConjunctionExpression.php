<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\Expression\Exception\InsufficientExpressionsException;

/**
 * This class is used as base class for the conjunctions expressions AND and OR
 */
class DoctrineConjunctionExpression extends AbstractDoctrineExpression
{
    /**
     * @var $conjunction string
     */
    protected $conjunction;

    /**
     * @var $expressions AbstractDoctrineExpression[]
     */
    protected $expressions;

    /**
     * DoctrineAndExpression constructor.
     *
     * @param string $conjunction
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @throws InsufficientExpressionsException
     */
    public function __construct($conjunction, array $expressions)
    {
        if (count($expressions) < 2) {
            throw new InsufficientExpressionsException($expressions);
        }

        $this->$expressions = $expressions;
        $this->conjunction = $conjunction;
    }

    /**
     *  Returns a statement for an expression
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return string
     */
    public function getStatement(QueryBuilder $queryBuilder)
    {
        $statements = [];
        foreach ($this->expressions as $expression) {
            /** @var AbstractDoctrineExpression $expression */
            $statements[] = $expression->getStatement($queryBuilder);
        }

        return ' (' . implode(' ' . $this->conjunction . ' ', $statements) . ') ';
    }
}
