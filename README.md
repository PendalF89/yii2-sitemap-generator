# yii2-sitemap-generator
Yii2 Sitemap Generator component

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pendalf89/yii2-sitemap-generator "*"
```

or add

```
"pendalf89/yii2-sitemap-generator": "*"
```

to the require section of your `composer.json` file.

Usage
------------
$sitemap = new ArticlesSitemap(); // must implement a SitemapInterface
$sitemapGenerator = new SitemapGenerator([
  'sitemaps' => [$sitemap],
  'host' => 'http://site.com',
  'dir' => '@webRoot',
]);
$sitemapGenerator->generate();
