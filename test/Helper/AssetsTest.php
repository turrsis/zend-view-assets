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
use Zend\View\Exception;
use Zend\View\Renderer\PhpRenderer as Renderer;
use Zend\View\HelperPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zend\Router\RouteStackInterface;

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
        $renderer = new Renderer();
        $renderer->setHelperPluginManager(new HelperPluginManager(new ServiceManager));

        $router = $this->getMockBuilder(RouteStackInterface::class)
                         ->setMethods(['assemble'])
                         ->getMockForAbstractClass();
        $router->method('assemble')->will($this->returnCallback(function ($params) {
            $result = 'assets-cache';
            if (isset($params['alias'])) {
                $result .= '/alias-' . $params['alias'];
            }
            if (isset($params['prefix'])) {
                $result .= '/prefix-' . $params['prefix'];
            }
            return $result . '/' . ltrim($params['asset'], '/');
        }));
        $renderer->plugin('url')->setRouter($router);

        $this->assetsManager = new AssetsManager();
        $this->assetsManager->setMimeResolver(new MimeResolver());
        $this->helper = new Helper\Assets();
        $this->helper
                ->setAssetsManager($this->assetsManager)
                ->setView($renderer)
                ->setRouteName('someroute')
                ->setMimeAttributes('text/css', []);
    }

    public function testCallUndefinedMethod()
    {
        $this->setExpectedException(Exception\BadMethodCallException::class, 'Method "Undefined" does not exist');
        $this->helper->Undefined('');
    }

    public function testInvalidRouteName()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'Using modules assets require the valid "routeName"');
        $this->helper
                ->setRouteName(null)
                ->add('Module1::/css/foo.css')
                ->render();
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
            '<link href="/assets-cache/prefix-module-1/foo.css" type="text/css">',
            '<link href="/bar.css" type="text/css">',
            '<link href="/alias0" type="text/plain">',
        ], $rendered);
    }

    public function testRenderWithRefferences()
    {
        $this->assetsManager
                ->set('foo', [
                    'assets' => [
                        'foo1.css',
                        'foo2.css',
                     ],
                ])
                ->set('bar', [
                    'assets' => [
                        'bar1.css',
                        'foo',
                     ],
                ]);
        $this->helper->add('bar');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link href="/assets-cache/alias-bar/bar1.css" type="text/css">',
            '<link href="/assets-cache/alias-foo/foo1.css" type="text/css">',
            '<link href="/assets-cache/alias-foo/foo2.css" type="text/css">',
        ], $rendered);
    }

    public function testRenderWithRename()
    {
        $this->assetsManager
                ->set('foo', [
                    'assets' => [
                        'style1.css' => [
                            'source' => 'style1.less'
                        ],
                        'foo::style2.css' => [
                            'source' => 'foo::style2.less'
                        ],
                        'style3.css' => [
                            'source' => 'foo::style3.less'
                        ],
                     ],
                ])
                ->set('bar.css', [
                    'source' => 'bar.less',
                ])
                ->set('bat.css', [
                    'source' => 'bat.less',
                ]);
        $this->helper->add('foo');
        $this->helper->add('bar.css');
        $this->helper->add('bat.css');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link href="/assets-cache/alias-foo/style1.css" type="text/css">',
            '<link href="/assets-cache/alias-foo/prefix-foo/style2.css" type="text/css">',
            '<link href="/assets-cache/alias-foo/style3.css" type="text/css">',
            '<link href="/bar.css" type="text/css">',
            '<link href="/bat.css" type="text/css">',
        ], $rendered);
    }

    public function testRenderWithAssetsManager()
    {
        $this->assetsManager
                ->set('external.css', [
                    'source' => 'http://com.com/foo.css',
                ])
                ->set('/foo.css', [
                    'source' => '/foo.less',
                ])
                ->set('foo', [
                    'assets' => [
                        '/bar.css',
                        '/bat.css',
                     ],
                ])
                ->set('module-1::/foo.css', [])
                ->set('alias', [
                    'assets' => [
                        'module-2::/baz.css',
                     ],
                ]);
        $this->helper
                ->add('external.css')
                ->add('foo.css')
                ->add('foo')
                ->add('module-1::/foo.css')
                ->add('alias');

        $rendered = explode("\n", $this->helper->render(), -1);
        $this->assertEquals([
            '<link href="/external.css" type="text/css">',
            '<link href="/foo.css" type="text/css">',
            '<link href="/assets-cache/alias-foo/bar.css" type="text/css">',
            '<link href="/assets-cache/alias-foo/bat.css" type="text/css">',
            '<link href="/assets-cache/prefix-module-1/foo.css" type="text/css">',
            '<link href="/assets-cache/alias-alias/prefix-module-2/baz.css" type="text/css">',
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

    public function testRenderWithAttributes()
    {
        $this->assetsManager
                ->set('bas.css', [
                    'source' => '/css/bas.css',
                    'attributes' => [
                        'charset' => 'UTF-3',
                    ],
                ])
                ->set('bat.css', [
                    'source' => '/css/bat.css',
                    'attributes' => [
                        'charset' => 'UTF-4',
                    ],
                ])
                ->set('list', [
                    'assets' => [
                        '/css/baz.css' => [
                            'attributes' => [
                                'charset' => 'UTF-6',
                            ],
                        ]
                    ],
                    'attributes' => [
                        'charset' => 'UTF-7',
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
                '<link charset="UTF-3" href="/bas.css" hreflang="UK-1" type="text/css">',
                '<link charset="UTF-5" href="/bat.css" hreflang="UK-2" type="text/css">',
                '<link charset="UTF-7" href="/assets-cache/alias-list/css/baz.css" type="text/css">',
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

    public function testRenderSameNameInDifferentGroups()
    {
        $this->assetsManager
                ->set('foo.css', ['attributes' => ['charset' => 'UTF-1']])
                ->set('bar.css', ['attributes' => ['charset' => 'UTF-2']]);
        $this->assetsManager->setCurrentGroup('group2')
                ->set('foo.css', ['attributes' => ['charset' => 'UTF-3']])
                ->set('bar.css', ['attributes' => ['charset' => 'UTF-4']]);

        $this->helper
                ->add('foo.css')
                ->add('bar.css');

        $this->assetsManager->setCurrentGroup('default');

        $this->assertEquals([
                '<link charset="UTF-1" href="/foo.css" type="text/css">',
                '<link charset="UTF-2" href="/bar.css" type="text/css">',
            ],
            explode("\n", $this->helper->render(), -1)
        );

        $rendered = new \ReflectionProperty($this->helper, 'rendered');
        $rendered->setAccessible(true);
        $rendered->setValue($this->helper, []);

        $this->assetsManager->setCurrentGroup('group2');

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
}
