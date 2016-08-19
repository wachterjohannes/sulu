<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\WebsiteBundle\Locale\DefaultLocaleProviderInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class ContentRouteProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var DefaultLocaleProviderInterface
     */
    private $defaultLocaleProvider;

    /**
     * @var ReplacerInterface
     */
    private $urlReplacer;

    /**
     * @var ContentRouteProvider
     */
    private $contentRouteProvider;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->rlpStrategy = $this->prophesize(RlpStrategyInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $this->urlReplacer = $this->prophesize(ReplacerInterface::class);

        $this->contentRouteProvider = new ContentRouteProvider(
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->rlpStrategy->reveal(),
            $this->structureManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->defaultLocaleProvider->reveal(),
            $this->urlReplacer->reveal()
        );
    }

    public function testStateTest()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('');

        $this->rlpStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);
        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequest()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->rlpStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $pageBridge->getController()->willReturn('::Controller');
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals($document->reveal(), $defaults['document']);
        $this->assertEquals(false, $defaults['partial']);
    }

    public function testGetCollectionForRequestWithPartialFlag()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->rlpStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $pageBridge->getController()->willReturn('::Controller');
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request(['partial' => 'true'], [], [], [], [], ['REQUEST_URI' => '/']);

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals($document->reveal(), $defaults['document']);
        $this->assertEquals(true, $defaults['partial']);
    }

    public function testGetCollectionForRequestNoLocalization()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequestNoLocalizationPartialMatch()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $this->requestAnalyzer->getRedirect()->willReturn(null);
        $this->requestAnalyzer->getPortalUrl()->willReturn(null);

        $localization = new Localization();
        $localization->setLanguage('de');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(
            'SuluWebsiteBundle:Redirect:redirectWebspace',
            array_values(iterator_to_array($routes->getIterator()))[0]->getDefaults()['_controller']
        );
    }

    public function testGetCollectionForRequestNoLocalizationRedirect()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->requestAnalyzer->getResourceLocator()->willReturn('');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $this->requestAnalyzer->getRedirect()->willReturn(null);
        $this->requestAnalyzer->getPortalUrl()->willReturn(null);

        $localization = new Localization();
        $localization->setLanguage('de');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(
            'SuluWebsiteBundle:Redirect:redirectWebspace',
            array_values(iterator_to_array($routes->getIterator()))[0]->getDefaults()['_controller']
        );
    }

    public function testGetCollectionForRequestSlashOnly()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $this->requestAnalyzer->getRedirect()->willReturn('sulu.lo/de');
        $this->requestAnalyzer->getPortalUrl()->willReturn('sulu.lo/de/');

        $localization = new Localization();
        $localization->setLanguage('de');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo/de/', $route->getDefaults()['url']);
        $this->assertEquals('sulu.lo/de', $route->getDefaults()['redirect']);
    }

    public function testGetCollectionForSingleLanguageRequestSlashOnly()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('');

        $this->rlpStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $pageBridge->getController()->willReturn('::Controller');
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $routes->getIterator()->current()->getDefaults()['structure']);
        $this->assertEquals($document->reveal(), $routes->getIterator()->current()->getDefaults()['document']);
    }

    public function testGetCollectionForPartialMatch()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $this->requestAnalyzer->getPortalUrl()->willReturn('sulu.lo');
        $this->requestAnalyzer->getRedirect()->willReturn('sulu.lo/{localization}');

        $localization = new Localization('de', 'at');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->urlReplacer->replaceCountry('sulu.lo/{localization}', 'at')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/{localization}');
        $this->urlReplacer->replaceLanguage('sulu.lo/{localization}', 'de')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/{localization}');
        $this->urlReplacer->replaceLocalization('sulu.lo/{localization}', 'de-at')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/de-at');

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['url']);
        $this->assertEquals('sulu.lo/de-at', $route->getDefaults()['redirect']);
    }

    public function testGetCollectionForNotExistingRequest()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->rlpStrategy->loadByResourceLocator('', 'webspace', 'de')
            ->willThrow(ResourceLocatorNotFoundException::class);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRedirect()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $this->requestAnalyzer->getPortalUrl()->willReturn('sulu-redirect.lo');
        $this->requestAnalyzer->getRedirect()->willReturn('sulu.lo');

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));

        $this->urlReplacer->replaceCountry('sulu.lo', 'at')->shouldBeCalled()->willReturn('sulu.lo');
        $this->urlReplacer->replaceLanguage('sulu.lo', 'de')->shouldBeCalled()->willReturn('sulu.lo');
        $this->urlReplacer->replaceLocalization('sulu.lo', 'de-at')->shouldBeCalled()->willReturn('sulu.lo');

        $this->rlpStrategy->loadByResourceLocator(Argument::cetera())->shouldNotBeCalled();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu-redirect.lo', $route->getDefaults()['url']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['redirect']);
    }

    public function testGetRedirectForInternalLink()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/test');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->rlpStrategy->loadByResourceLocator('/test', 'webspace', 'de')->willReturn('some-uuid');

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/test']);

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/other-test', $route->getDefaults()['url']);
    }

    public function testGetRedirectForInternalLinkWithQueryString()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/test');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->rlpStrategy->loadByResourceLocator('/test', 'webspace', 'de')->willReturn('some-uuid');

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/test', 'QUERY_STRING' => 'test1=value1']);

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/other-test?test1=value1', $route->getDefaults()['url']);
    }

    public function testGetRedirectForExternalLink()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/test');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->rlpStrategy->loadByResourceLocator('/test', 'webspace', 'de')->willReturn('some-uuid');

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::EXTERNAL);
        $document->getRedirectExternal()->willReturn('http://www.example.org');
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/test']);

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('http://www.example.org', $route->getDefaults()['url']);
    }

    public function testGetCollectionEndingSlash()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $localization = new Localization('de', 'at');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $this->requestAnalyzer->getResourceLocator()->willReturn('/qwertz/');
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('de');
        $this->requestAnalyzer->getRedirect()->willReturn('sulu.lo/de-at');
        $this->requestAnalyzer->getPortalUrl()->willReturn('sulu.lo');

        $this->rlpStrategy->loadByResourceLocator('/qwertz', 'webspace', 'de_at')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $this->documentManager->find('some-uuid', 'de_at', ['load_ghost_content' => false])->willReturn($document->reveal());

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/qwertz/']);
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['url']);
    }

    public function testGetCollectionEndingSlashForHomepage()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $localization = new Localization('de', 'at');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $this->requestAnalyzer->getPortalUrl()->willReturn('sulu.lo');
        $this->requestAnalyzer->getRedirect()->willReturn('sulu.lo/de-at');

        $this->rlpStrategy->loadByResourceLocator('', 'webspace', 'de_at')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $this->documentManager->find('some-uuid', 'de_at', ['load_ghost_content' => false])->willReturn($document->reveal());

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['url']);
    }

    public function testGetCollectionMovedResourceLocator()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $localization = new Localization('de', 'at');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);

        $this->requestAnalyzer->getResourceLocator()->willReturn('/qwertz/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->rlpStrategy->loadByResourceLocator('/qwertz', 'webspace', 'de_at')
            ->willThrow(new ResourceLocatorMovedException('/new-test', '123-123-123'));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/qwertz/']);

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/new-test', $route->getDefaults()['url']);
    }
}
