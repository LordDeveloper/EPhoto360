<?php

namespace JupiterAPI;

use DOMDocument;
use DOMXPath;
use Throwable;

class EPhoto360
{

    /**
     * @return array
     * @throws Throwable
     */
    public static function getCategories(): array
    {
        $contents = Request::fetch('/');

        $xpath = static::getXPath($contents);

        $anchors = $xpath->query('//div[contains(@class, "menu-right")][not(.//img)]/a');
        if($anchors->count() === 0)
            return [
                'success' => false,
            ];

        $promises = [];
        foreach ($anchors as $anchor)
            $promises[] = Request::fetchAsync($anchor->getAttribute('href'));

        $responses = Request::unwrap($promises);

        return [
            'success' => true,
            'categories' => array_column(array_map([static::class, 'getCategory'], $responses), 'category'),
        ];
    }

    /**
     * @param $contents
     * @param int $page
     * @return array
     */
    public static function getCategory($contents, int $page = 1): array
    {
        $contents = is_numeric($contents)
            ? Request::fetch('/-c'. $contents .'-p'. $page, method: 'GET')
            : (filter_var($contents, FILTER_VALIDATE_URL) ? Request::fetch($contents, method: 'GET') : $contents);

        $xpath = static::getXPath($contents);

        if($xpath->query('//div[@class="error-page"]')->count() === 1 || empty($contents))
            return ['success' => false];

        $category = $xpath->query('//ol[@class="breadcrumb"]/li[2]/span/span/a[@itemprop="url"]')[0];
        $title = $xpath->query('.//span[@itemprop="name"]', $category)[0]->nodeValue;
        $url = $category->getAttribute('href');
        $id = static::getIdFromUrl($url);
        $effects = [];

        foreach ($xpath->query('//div[contains(@class, "effect-box")]/div[contains(@class, "lazyload")]/a') as $effect)
            $effects[] = [
                'url' => $href  = $effect->getAttribute('href'),
                'id' => static::getIdFromUrl($href),
                'thumbnail' => $xpath->query('.//img', $effect)[0]->getAttribute('src'),
                'title' => $xpath->query('.//div[@class="title-effect-home"]', $effect)[0]->nodeValue,
            ];

        return [
            'success' => true,
            'category' => compact('id', 'title', 'url', 'page', 'effects'),
        ];

    }

    /**
     * @return array
     * @throws Throwable
     */
    public static function getTopEffects(): array
    {
        $contents = Request::fetch('/');

        $xpath = static::getXPath($contents);

        $anchors = $xpath->query('//div[contains(@class, "menu-right")][.//img]/a');
        if($anchors->count() === 0)
            return [
                'success' => false,
            ];

        $promises = [];
        foreach ($anchors as $anchor)
            $promises[] = Request::fetchAsync($anchor->getAttribute('href'));

        $responses = Request::unwrap($promises);

        return [
            'success' => true,
            'effects' => array_column(array_map([static::class, 'getEffect'], $responses), 'effect'),
        ];
    }

    /**
     * @param $contents
     * @return array
     */
    public static function getEffect($contents, array $fromData = []): array
    {
        $contents = is_numeric($contents)
            ? Request::fetch('/-'. $contents .'.html', method: 'GET')
            : (filter_var($contents, FILTER_VALIDATE_URL) ? Request::fetch($contents, method: 'GET') : $contents);

        $xpath = static::getXPath($contents);
        if($xpath->query('//div[@class="error-page"]')->count() === 1 || empty($contents))
            return ['success' => false];

        $form_data = empty($fromData) ? [] : static::formData($xpath, $fromData);
        $url = $xpath->query('//link[@rel="canonical"]')[0]->getAttribute('href');
        $id = static::getIdFromUrl($url);
        $title = $xpath->query('//title')[0]->nodeValue;
        $thumbnail = $xpath->query('//a[@class="example_picture"]')[0]->getAttribute('href');
        $category = $xpath->query('//ol[@class="breadcrumb"]/li[2]/span/span/a[@itemprop="url"]')[0];
        $category = [
            'title' => $xpath->query('.//span[@itemprop="name"]', $category)[0]->nodeValue,
            'url' => $href = $category->getAttribute('href'),
            'id' => static::getIdFromUrl($href)
        ];

        $filters = [];
        foreach ($xpath->query('//div[contains(@class, "select_option_wrapper")]/*/input') as $input)
            $filters[] = [
                'id' => $input->getAttribute('value'),
                'title' => $input->getAttribute('data-title'),
                'thumbnail' => $input->getAttribute('data-thumb'),
            ];

        $relative_effects = [];
        foreach ($xpath->query('//div[contains(@class, "effect-box")]/div[contains(@class, "lazyload")]') as $effect)
            $relative_effects[] = [
                'url' => $href = $xpath->query('.//a', $effect)[0]->getAttribute('href'),
                'id' => static::getIdFromUrl($href),
                'thumbnail' => $xpath->query('.//img')[0]->getAttribute('src'),
            ];

        return [
            'success' => true,
            'effect' => compact('id', 'url', 'title', 'thumbnail', 'form_data', 'category', 'filters', 'relative_effects')
        ];

    }

    /**
     * @param $effectId
     * @param array $data
     * @return mixed
     */
    public static function create($effectId, array $data = []): mixed
    {
        $data = static::getEffect($effectId, $data);

        if($data['success'] === false)
            return $data;

        $fromData = $data['effect']['form_data'];

        if($fromData['success'] === false)
            return $fromData;

        $created = Request::fetch('/effect/create-image', $data['effect']['form_data']);

        if($created['success'] === false)
            return $created;

        return array_merge($created, [
            'image' => $fromData['build_server'] . $created['image'],
            'filters' => $data['effect']['filters'],
        ]);
    }

    /**
     * @param $file
     * @param $buildServer
     * @param array $extra
     * @return mixed
     */
    private static function upload($file, $buildServer, array $extra = []): mixed
    {
        $uploaded = Request::fetch($buildServer .'/upload', [
            'file' => $file,
        ]);

        $default = array_merge([
            'x' => 0,
            'y' => 0,
            'rotate' => 0,
            'scaleX' => 0,
            'scaleY' => 0,
        ], $extra);

        if($uploaded['success'] === true) {
            [$width, $height] = getimagesize($file);

            $default = array_merge($default, [
                'image' => $uploaded['uploaded_file'],
                'image_thumb' => $uploaded['thumb_file'],
                'icon_file' => $uploaded['icon_file'],
                'width' => $width,
                'height' => $height,
                'thumb_width' => $width,
            ]);
        }

        return json_encode($default);

    }

    /**
     * @param DOMXPath $xpath
     * @param array $extra
     * @return mixed
     */
    private static function formData(DOMXPath $xpath, array $extra = []): mixed
    {

        $submit = $xpath->query('//input[@name="submit"]')[0]->getAttribute('value');
        $buildServer = $xpath->query('//input[@name="build_server"]')[0]->getAttribute('value');
        $buildServerId = $xpath->query('//input[@name="build_server_id"]')[0]->getAttribute('value');
        $token = $xpath->query('//input[@name="token"]')[0]->getAttribute('value');
        $effectId = static::getIdFromUrl($xpath->query('//link[@rel="canonical"]')[0]->getAttribute('href'));

        array_walk_recursive($extra, function (&$item) use ($buildServer) {
            $item = is_file($item) && filesize($item) > 0
                ? static::upload($item, $buildServer)
                : $item;
        });

        $mergedData = array_merge($extra, [
            'id' => $effectId,
            'submit' => $submit,
            'build_server' => $buildServer,
            'build_server_id' => $buildServerId,
            'token' => $token,
        ]);

        $contents = Request::fetch('/-'. $effectId .'.html', $mergedData);
        $xpath = static::getXPath($contents);
        $form_value_input = $xpath->query('//input[@name="form_value_input"]');

        if($form_value_input->count() === 0) {
            $errors = [];

            foreach ($xpath->query('//ul[@class="errors"]') as $ul)
                foreach ($xpath->query('.//li', $ul) as $error) {
                    $input = $xpath->query('./preceding::input[1]', $ul)[0]->getAttribute('name');
                    parse_str($input, $name);
                    $errors[array_key_last((array) $name)][] = $error->nodeValue;
                }

            return [
                'success' => false,
                'errors' => $errors,
            ];
        }


        return array_merge(json_decode($xpath->query('//input[@name="form_value_input"]')[0]->getAttribute('value'), true), [
            'success' => true,
        ]);
    }

    /**
     * @param string $contents
     * @return DOMXPath
     */
    private static function getXPath(string $contents): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($contents);
        libxml_use_internal_errors(false);
        return new DOMXPath($dom);
    }

    /**
     * @param string $url
     * @return int
     */
    private static function getIdFromUrl(string $url): int
    {
        preg_match('/(?P<id>\d+)$/', pathinfo($url, PATHINFO_FILENAME), $matches);

        return (int) $matches['id'];
    }
}