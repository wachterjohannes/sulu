<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader;

use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Webspace\Loader\Exception\ExpectedDefaultTemplatesNotFound;
use Sulu\Component\Webspace\Webspace;

/**
 * This file loader is responsible for loading webspace configuration files using the xml format with the webspace
 * schema version 1.1.
 */
class XmlFileLoader11 extends XmlFileLoader10
{
    const SCHEMA_LOCATION = '/schema/webspace/webspace-1.1.xsd';

    const SCHEMA_URI = 'http://schemas.sulu.io/webspace/webspace-1.1.xsd';

    /**
     * @var PropertiesXmlParser
     */
    private $propertiesXmlParser;

    protected function parseXml($file)
    {
        $webspace = parent::parseXml($file);

        $propertiesNode = $this->xpath->query('/x:webspace/x:properties')->item(0);
        if (null !== $propertiesNode) {
            $webspace->setProperties($this->propertiesXmlParser->load(
                $tags,
                $this->xpath,
                $propertiesNode
            ));
        }

        $strategyNode = $this->xpath->query('/x:webspace/x:resource-locator/x:strategy')->item(0);
        if (null !== $strategyNode) {
            $webspace->setResourceLocatorStrategy($strategyNode->nodeValue);
        } else {
            $webspace->setResourceLocatorStrategy('tree_leaf_edit');
        }

        $this->generateExcludedTemplates($webspace);


        return $webspace;
    }

    protected function generateDefaultTemplates(Webspace $webspace)
    {
        $expected = ['page', 'home'];

        foreach ($this->xpath->query('/x:webspace/x:default-templates/x:default-template') as $node) {
            /* @var \DOMNode $node */
            $template = $node->nodeValue;
            $type = $node->attributes->getNamedItem('type')->nodeValue;

            $webspace->addDefaultTemplate($type, $template);
            if ('homepage' === $type) {
                $webspace->addDefaultTemplate('home', $template);
            }
        }

        $found = \array_keys($webspace->getDefaultTemplates());
        foreach ($expected as $item) {
            if (!\in_array($item, $found)) {
                throw new ExpectedDefaultTemplatesNotFound($this->webspace->getKey(), $expected, $found);
            }
        }

        return $webspace;
    }

    /**
     * Adds the template for the given types as described in the XML document.
     *
     * The types can be arbitrary, so that another bundle can easily add a new type and use the information from the
     * webspace.
     *
     * @return Webspace
     */
    protected function generateTemplates(Webspace $webspace)
    {
        foreach ($this->xpath->query('/x:webspace/x:templates/x:template') as $templateNode) {
            /* @var \DOMNode $templateNode */
            $template = $templateNode->nodeValue;
            $type = $templateNode->attributes->getNamedItem('type')->nodeValue;
            $webspace->addTemplate($type, $template);
        }

        return $webspace;
    }

    /**
     * Adds the excluded-templates as described in the XML document.
     *
     * @return Webspace
     */
    protected function generateExcludedTemplates(Webspace $webspace)
    {
        foreach ($this->xpath->query('/x:webspace/x:excluded-templates/x:excluded-template') as $excludedTemplateNode) {
            /* @var \DOMNode $excludedTemplateNode */
            $excludedTemplate = $excludedTemplateNode->nodeValue;
            $webspace->addExcludedTemplate($excludedTemplate);
        }

        return $webspace;
    }
}
