<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Helper;

use Zend\View\Assets\Helper;
use Zend\View\Assets\AssetsManager;
use Zend\View\Assets\MimeResolver;
use Zend\View\Assets\Exception;
use Zend\View\Renderer\PhpRenderer as Renderer;
use Zend\View\HelperPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Assets\AssetsRouter;

/**
 * Test class for Zend\View\Helper\Assets.
 *
 * @group      Zend_View
 * @group      Zend_View_Helper
 */
class AssetsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Helper\Assets
     */
    protected $helper;

    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    public function setUp()
    {
        $view = new Renderer();
        $view->setHelperPluginManager(new HelperPluginManager(new ServiceManager));

        $serviceManager = new ServiceManager([
            'services' => [
                'config' => [
                    'assets_manager' => [],
                ],
            ],
        ]);
        $this->assetsManager = new AssetsManager($serviceManager);
        $this->assetsManager->setMimeResolver(new MimeResolver());
        $this->assetsManager->setAssetsRouter(new AssetsRouter());
        $this->assetsManager->getAssetsRouter()->setAssetsManager($this->assetsManager);
        $this->assetsManager->getAssetsRouter()->setPrefix('assets-cache');
        
        $factory = new \Zend\View\Assets\Service\AssetsResolverFactory;
        $resolver = $factory($serviceManager, 'AssetsResolver');
        $this->assetsManager->setAssetsResolver($resolver);
        
        $this->helper = new Helper\Assets();
        $this->helper->setAssetsManager($this->assetsManager);
        $this->helper->setView($view);
        $this->helper->setMimeAttributes('text/css', []);
    }

    public function testCallUndefinedMethod()
    {
        $this->setExpectedException(Exception\BadMethodCallException::class, 'Method "Undefined" does not exist');
        $this->helper->Undefined('');
    }

    public function testToString()
    {
        $this->helper->add('/css/baz.css');
        $this->assertEquals(
            '<link href="/css/baz.css" type="text/css">' . "\n",
            (string)$this->helper
        );
    }

    public function testRender()
    {
        $this->helper
                ->add('http://com.com/css/bat.css')
                ->add('module-1::/foo.css')
                ->add('/bar.css')
                ->add('alias0');
        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link href="http://com.com/css/bat.css" type="text/css">',
            '<link href="/assets-cache/ns-module-1/foo.css" type="text/css">',
            '<link href="/bar.css" type="text/css">',
            '<link href="/alias0" type="text/plain">',
        ], $rendered);
    }

    public function testRenderConditional()
    {
        $this->helper
                ->add('/foo.css', ['conditional' => 'c1'])
                ->add('/bar.js', ['conditional' => 'c2']);
        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<!--[if c1]><link href="/foo.css" type="text/css"><![endif]-->',
            '<!--[if c2]><script type="application/javascript" src="/bar.js"></script><![endif]-->',
        ], $rendered);
    }

    public function testRenderAttributes()
    {
        $this->helper
                ->add('/foo.css', ['charset' => 'foo'])
                ->add('/bar.js',  ['charset' => 'bar']);
        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link charset="foo" href="/foo.css" type="text/css">',
            '<script type="application/javascript" charset="bar" src="/bar.js"></script>',
        ], $rendered);
    }

    public function testRenderWithPriority()
    {
        $this->helper->add('/css/foo.css', 2);
        $this->helper->add('/css/bar.css', -5);
        $this->helper->add('/css/bat.css', 3);
        $this->helper->add('/css/baz.css', 4);

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
                '<link href="/css/baz.css" type="text/css">',
                '<link href="/css/bat.css" type="text/css">',
                '<link href="/css/foo.css" type="text/css">',
                '<link href="/css/bar.css" type="text/css">',
            ],
            $rendered
        );
    }

    public function testRenderWithDependences()
    {
        $this->assetsManager->getCollection()
                ->setAsset('foo', [
                    'assets' => [
                        'foo1.css' => [],
                        'foo2.css' => [],
                     ],
                ])
                ->setAsset('bar', [
                    'assets' => [
                        'bar1.css' => [],
                        'foo',
                        'notExistLink',
                     ],
                ]);
        $this->helper->add('bar');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link href="/assets-cache/collection-bar/bar1.css" type="text/css">',
            '<link href="/assets-cache/collection-foo/foo1.css" type="text/css">',
            '<link href="/assets-cache/collection-foo/foo2.css" type="text/css">',
        ], $rendered);
    }

    public function testRenderWithAssetsManager()
    {
        $this->assetsManager->getCollection()
                ->setFilters(['public_filtered.css' => ['someFilter']])
                ->setAsset('external.css', [
                    'source' => 'http://com.com/foo.css',
                ])
                ->setAsset('public_filtered.css', [])
                ->setAsset('public_not_filtered.css', [])
                ->setAsset('foo', [
                    'assets' => [
                        '/bar.css' => [],
                        '/bat.css' => [],
                     ],
                ])
                ->setAsset('module-1::/foo.css', [])
                ->setAsset('alias', [
                    'assets' => [
                        'module-2::/baz.css' => [],
                     ],
                ]);
        $this->helper
                ->add('external.css')
                ->add('public_filtered.css')
                ->add('public_not_filtered.css')
                ->add('foo')
                ->add('module-1::/foo.css')
                ->add('alias');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link href="http://com.com/foo.css" type="text/css">',
            '<link href="/assets-cache/ns-public/public_filtered.css" type="text/css">',
            '<link href="/public_not_filtered.css" type="text/css">',
            '<link href="/assets-cache/collection-foo/bar.css" type="text/css">',
            '<link href="/assets-cache/collection-foo/bat.css" type="text/css">',
            '<link href="/assets-cache/ns-module-1/foo.css" type="text/css">',
            '<link href="/assets-cache/collection-alias/ns-module-2/baz.css" type="text/css">',
        ], $rendered);
    }

    public function testRenderWithAttributes()
    {
        $this->assetsManager->getCollection()
                ->setAsset('bas.css', [
                    'source' => '/css/bas.css',
                    'attributes' => [
                        'charset' => 'UTF-3',
                    ],
                ])
                ->setAsset('bat.css', [
                    'source' => '/css/bat.css',
                    'attributes' => [
                        'charset' => 'UTF-4',
                    ],
                ])
                ->setAsset('list', [
                    'assets' => [
                        '/css/baz.css' => [
                            'attributes' => [
                                'charset' => 'UTF-6',
                            ],
                        ]
                    ],
                ]);

        $this->helper
                ->add('/css/foo.css', ['charset' => 'UTF-1'])
                ->add('/css/bar.css',  ['charset' => 'UTF-2'])
                ->add('bas.css',  ['hreflang' => 'UK-1'])
                ->add('bat.css',  ['hreflang' => 'UK-2', 'charset' => 'UTF-5'])
                ->add('list');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
                '<link charset="UTF-1" href="/css/foo.css" type="text/css">',
                '<link charset="UTF-2" href="/css/bar.css" type="text/css">',
                '<link charset="UTF-3" href="/css/bas.css" hreflang="UK-1" type="text/css">',
                '<link charset="UTF-5" href="/css/bat.css" hreflang="UK-2" type="text/css">',
                '<link charset="UTF-6" href="/assets-cache/collection-list/css/baz.css" type="text/css">',
            ],
            $rendered
        );
    }

    public function testRenderGroups()
    {
        $this->helper->add('/css/baz.css');
        $this->helper->addFooter('/css/bar.css');

        $this->assertEquals(
            '<link href="/css/baz.css" type="text/css">' . "\n",
            $this->helper->render()
        );
        $this->assertEquals(
            '<link href="/css/bar.css" type="text/css">' . "\n",
            $this->helper->renderFooter()
        );
    }

    public function testRenderSameNameInDifferentCollection()
    {
        $this->assetsManager->setCollection('collection-1', ['assets' => [
            'foo.css' => ['attributes' => ['charset' => 'UTF-1']],
            'bar.css' => ['attributes' => ['charset' => 'UTF-2']],
        ]]);
        
        $this->assetsManager->setCollection('collection-2', ['assets' => [
            'foo.css' => ['attributes' => ['charset' => 'UTF-3']],
            'bar.css' => ['attributes' => ['charset' => 'UTF-4']],
        ]]);

        $this->helper
                ->add('foo.css')
                ->add('bar.css');

        $this->assetsManager->setCollectionName('collection-1');

        $this->assertEquals([
                '<link charset="UTF-1" href="/foo.css" type="text/css">',
                '<link charset="UTF-2" href="/bar.css" type="text/css">',
            ],
            explode("\n", $this->helper->render(), -1)
        );

        $rendered = new \ReflectionProperty($this->helper, 'rendered');
        $rendered->setAccessible(true);
        $rendered->setValue($this->helper, []);

        $this->assetsManager->setCollectionName('collection-2');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
                '<link charset="UTF-3" href="/foo.css" type="text/css">',
                '<link charset="UTF-4" href="/bar.css" type="text/css">',
            ],
            $rendered
        );
    }

    public function testRenderMimeDefaultAttributes()
    {
        $this->helper->setMimeAttributes('text/css', [
            'rel'   => 'stylesheet',
            'media' => 'screen',
        ]);

        $this->assertEquals(
            '<link href="/style1.css" media="screen" rel="stylesheet" type="text/css">' . "\n",
            (string)$this->helper->add('style1.css')
        );
    }

    public function testRenderDuplicates()
    {
        $this->helper->add('style1.css');
        $this->helper->add('style2.css');
        $this->helper->add('style1.css');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
                '<link href="/style1.css" type="text/css">',
                '<link href="/style2.css" type="text/css">',
            ],
            $rendered
        );
    }

    public function testRenderNotExistGroup()
    {
        $this->helper->addGroup1('style1.css');
        $this->assertEquals('', $this->helper->render());
    }

    public function testRenderJavaScript()
    {
        $this->helper->add('style1.js', ['charset' => 'UTF-1']);
        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
                '<script type="application/javascript" charset="UTF-1" src="/style1.js"></script>',
            ],
            $rendered
        );
    }

    public function testRenderAggregate()
    {
        $this->assetsManager->getCollection()
                ->setAsset('foo.css', [
                    'assets' => [
                        'bar.css' => [],
                        'bat.css' => [],
                    ],
                    'aggregate' => 'Aggregate',
                ]);
        $this->helper->add('foo.css');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link href="/assets-cache/collection-foo.css/foo.css" type="text/css">',
        ], $rendered);
    }
}
