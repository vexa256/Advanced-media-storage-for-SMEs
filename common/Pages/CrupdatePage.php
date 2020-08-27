<?php

namespace Common\Pages;

use Auth;
use Illuminate\Support\Arr;

class CrupdatePage
{
    /**
     * @var CustomPage
     */
    private $page;

    /**
     * @param CustomPage $page
     */
    public function __construct(CustomPage $page)
    {
        $this->page = $page;
    }

    /**
     * @param array $data
     * @param CustomPage $page
     * @return CustomPage
     */
    public function execute($data, $page = null)
    {
        if ( ! $page) {
            $page = $this->page->newInstance();
        }

        $attributes = [
            'title' => $data['title'],
            'body' => $data['body'],
            'slug' => Arr::get($data, 'slug') ?: Arr::get($data, 'title'),
            'type' => Arr::get($data, 'type') ?: CustomPage::DEFAULT_PAGE_TYPE,
            'user_id' => Auth::id(),
        ];

        $page->fill($attributes)->save();

        return $page;
    }
}