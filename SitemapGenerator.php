<?php

namespace pendalf89\sitemap;

use Yii;
use yii\base\Component;

/**
 * Class SitemapGenerator
 * Класс предназначен для создания карты сайта
 *
 * Пример использования:
 * ```
 *  use pendalf89/sitemap/SitemapGenerator;
 *
 *	Yii::$app->urlManager->baseUrl = 'http://site.com'; // base url use in sitemap urls creation
 *	
 *	$sitemap = new ArticlesSitemap(); // must implement a SitemapInterface
 *	$sitemapGenerator = new SitemapGenerator([
 *	 	'sitemaps' => [$sitemap],
 *	 	'dir' => '@webRoot',
 *	]);
 *	$sitemapGenerator->generate();
 * ```
 *
 * @package common\components
 */
class SitemapGenerator extends Component
{
	/**
	 * @var string директория для записи файлов карты сайта. Допускается использование алиасов.
	 */
	public $dir = '';

	/**
	 * @var string название индексного файла карты сайта
	 */
	public $indexFilename = 'sitemap.xml';

	/**
	 * @var string формат записи последнего изменеия страницы
	 */
	public $lastmodFormat = 'Y-m-d';

	/**
	 * @var SitemapInterface[] набор объектов карты сайта
	 */
	public $sitemaps = [];

	/**
	 * @var int максимальное количество адресов в одной карте.
	 * Если в карте сайта количество адресов больше чем заданное значение,
	 * то карта сайта разобьётся на несколько карт сайта таким образом,
	 * чтобы в каждой было не больше адресов, чем заданное значение.
	 * Если стоит "0", то карты не будут разбиваться на несколько и в одной карте может быть
	 * неограниченное количество адресов.
	 */
	public $maxUrlsCount = 45000;

	/**
	 * @var array хранит информацию о созданных карт сайта
	 */
	protected $createdSitemaps = [];

	/**
	 * Создаёт карты сайта
	 */
	public function generate()
	{
		foreach ($this->sitemaps as $sitemap) {
			$this->createSitemap($sitemap);
		}
		$this->createIndexSitemap();
	}

	/**
	 * Создаёт индексную карту сайта
	 *
	 * @return string
	 */
	protected function createIndexSitemap()
	{
		$sitemapIndex = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$sitemapIndex .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
		$baseUrl = Yii::$app->urlManager->baseUrl;

		$sitemaps = $this->createdSitemaps;
		self::sortByLastmod($sitemaps);

		foreach ($sitemaps as $sitemap) {
			$sitemapIndex .= '    <sitemap>' . PHP_EOL;
			$sitemapIndex .= "        <loc>$baseUrl/$sitemap[loc]</loc>" . PHP_EOL;

			if (!empty($sitemap['lastmodTimestamp'])) {
				$lastmod = date($this->lastmodFormat, $sitemap['lastmodTimestamp']);
				$sitemapIndex .= "        <lastmod>$lastmod</lastmod>" . PHP_EOL;
			}

			$sitemapIndex .= '    </sitemap>' . PHP_EOL;
		}

		$sitemapIndex .= '</sitemapindex>';
		$this->createSitemapFile($this->indexFilename, $sitemapIndex);

		return $sitemapIndex;
	}

	/**
	 * Создаёт карту сайта из объекта $sitemap и записывает информацию о созданной карте сайта
	 * в массив $this->createdSitemaps
	 *
	 * @param SitemapInterface $sitemap
	 *
	 * @return boolean
	 */
	protected function createSitemap(SitemapInterface $sitemap)
	{
		if (!$urls = $sitemap->getUrls()) {
			return false;
		}

		self::sortByLastmod($urls);
		$chunkUrls           = $this->chunkUrls($urls);
		$multipleSitemapFlag = count($chunkUrls) > 1;
		$i                   = 1;

		foreach ($chunkUrls as $urlsData) {
			$freshTimestamp = 0;
			$urlset         = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
			$urlset .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

			foreach ($urlsData as $url) {
				$urlset .= '    <url>' . PHP_EOL;
				$urlset .= "        <loc>$url[url]</loc>" . PHP_EOL;

				if (!empty($url['lastmodTimestamp'])) {
					$date = date($this->lastmodFormat, $url['lastmodTimestamp']);
					$urlset .= "        <lastmod>$date</lastmod>" . PHP_EOL;

					if ($freshTimestamp < $url['lastmodTimestamp']) {
						$freshTimestamp = $url['lastmodTimestamp'];
					}
				}

				$urlset .= '    </url>' . PHP_EOL;
			}

			$urlset .= '</urlset>';
			$currentSitemapFilename = $multipleSitemapFlag ? "{$sitemap->getName()}-{$i}.xml" : "{$sitemap->getName()}.xml";

			$this->createdSitemaps[] = [
				'loc'              => $currentSitemapFilename,
				'lastmodTimestamp' => $freshTimestamp,
			];
			if (!$this->createSitemapFile($currentSitemapFilename, $urlset)) {
				return false;
			}
			$i++;
		}

		return true;
	}

	/**
	 * Разбивает массив урлов в соответствии с $this->maxUrlsCount.
	 * Обёртка для функции array_chunk().
	 *
	 * @param array $urls
	 *
	 * @return array
	 */
	protected function chunkUrls(array $urls)
	{
		if (empty($this->maxUrlsCount)) {
			$result[] = $urls;

			return $result;
		}

		return array_chunk($urls, $this->maxUrlsCount);
	}

	/**
	 * Создаёт файл карты сайта
	 *
	 * @param $filename
	 * @param $data
	 *
	 * @return int
	 */
	protected function createSitemapFile($filename, $data)
	{
		$fullFilename = Yii::getAlias($this->dir) . '/' . $filename;

		return file_put_contents($fullFilename, $data);
	}

	/**
	 * Сортирует урлы по lastmod в убывающем порядке
	 *
	 * @param array $urls
	 */
	protected static function sortByLastmod(array &$urls)
	{
		$lastmod = [];

		foreach ($urls as $key => $row) {
			$lastmod[$key] = !empty($row['lastmodTimestamp']) ? $row['lastmodTimestamp'] : 0;
		}

		array_multisort($lastmod, SORT_DESC, $urls);
	}
}
