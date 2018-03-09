<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle;

use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Integrates preview into symfony.
 */
class SuluPreviewBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // TODO remove when removing old UI
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass('sulu_preview.preview', 'sulu_preview.object_provider', 0, 'class')
        );

        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_preview.preview',
                'sulu_preview.object_provider',
                1,
                'provider-key'
            )
        );
    }
}
