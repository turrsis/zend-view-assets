# Assets

The `Assets` helper allow to add various resourses to html page, such as .css, .js, etc.
It can be configure in config files.
For page performance optimisation assets can be placed in top or bottom of page (or elsewhere).
Allow to build several link by one alias.
Also can possible change layout decoration 'on fly'.

## Basic Usage

```php
<?php
// setting links in a view script:
$this->assets()
        ->add('http://com.com/foo.css')
        ->add('/bar.css')
        ->add('/bat.js');

// rendering the links from the layout:
echo $this->assets();
?>
```

Output:

```html
<link href="http://com.com/foo.css" type="text/css">
<link href="/bar.css" type="text/css">
<script type="application/javascript" src="/bat.js"></script>
```

## Add assets to different positions on page

```php
<?php
// setting links in a view script:
$this->assets()
        ->addHeader('/foo.css')
        ->addFooter('/bar.css');

// rendering the links from the layout:
echo '<body>';
    echo '<header>';
    echo $this->assets()->renderHeader();
    echo '</header>';
    echo '<article> some content </article>';
    echo '<footer>';
    echo $this->assets()->renderFooter();
    echo '</footer>';
echo '</body>';
?>
```

Output:

```html
<body>
    <header>
    <link href="/foo.css" type="text/css">
    </header>
    <article> some content </article>
    <footer>
    <link href="/bar.css" type="text/css">
    </footer>
</body>
```

## Using configurable assets

```php
<?php
// section in config file:
'assets_manager' => [
    'collections' => [
        'default' => [
            'external' => [
                'http://com.com/foo.css' => [],
            ],
            'foo.css' => [],
            'several_assets' => [
                'assets' => [
                    'external'
                    'bar.css' => [],
                    'bat.js' => [
                        'attributes' => [
                            'charset' => 'UTF-8',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
?>
```

```php
<?php
// setting links in a view script:
$this->assets()
        ->add('foo.css')
        ->add('several_assets');

// rendering the links from the layout:
echo $this->assets();
?>
```

Output:

```html
<link href="/foo.css" type="text/css">
<link href="http://com.com/foo.css" type="text/css">
<link href="/bar.css" type="text/css">
<script type="application/javascript" charset="UTF-8" src="/bat.js"></script>
```

## change layout decoration 'on fly'

```php
<?php
// section in config file:
'assets_manager' => [
    'collections' => [
        'default' => [
            'layout.css' => ['source' => 'foo.css'],
        ],
        'my-theme' => [
            'layout.css' => ['source' => 'bar.css'],
        ],
    ],
];
?>
```

```php
<?php
// setting links in a view script:
$this->assets()->add('layout.css');
if ('good weather') {
  $this->assets()->getAssetsManager()->setCurrentGroup('my-theme');
} else {
  $this->assets()->getAssetsManager()->setCurrentGroup('default');
}
// rendering the links from the layout:
echo $this->assets();
?>
```

Default Output:

```html
<link href="/foo.css" type="text/css">
```

Output if good weather:

```html
<link href="/bar.css" type="text/css">
```