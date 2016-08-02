<?php

namespace pendalf89\sitemap;

/**
 * Interface SitemapInterface
 * Интерфейс класса карты сайта.
 */
interface SitemapInterface
{
	/**
	 * Возвращает название карты сайта (соответствует имени файла без разрешения).
	 *
	 * Например: 'sitemap-articles', 'sitemap-news' и т.д.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Возвращает список урлов карты сайта.
	 * Ключ 'lastmodTimestamp' не обязателен.
	 *
	 * Например:
	 * ```
	 *  [
	 *      ['url'=> '/1', 'lastmodTimestamp' => 12312312312],
	 *      ['url'=> '/2', 'lastmodTimestamp' => 12312312342],
	 *      ['url'=> '/3'],
	 *  ]
	 * ```
	 *
	 * @return array
	 */
	public function getUrls();
}
