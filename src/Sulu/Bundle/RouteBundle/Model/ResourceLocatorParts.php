<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Model;

/**
 * @extends \ArrayObject<string, mixed>
 */
class ResourceLocatorParts extends \ArrayObject
{
    public function __get(string $name): mixed
    {
        return $this->offsetGet($name);
    }

    /**
     * @param mixed[] $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->offsetGet(\lcfirst(\substr($name, 3)));
    }
}
