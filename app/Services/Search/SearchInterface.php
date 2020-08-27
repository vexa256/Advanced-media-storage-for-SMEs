<?php namespace App\Services\Search;

interface SearchInterface {

    /**
     * Search database using given params.
     *
     * @param string $q
     * @param int $limit
     * @param array $modelTypes
     * @return array
     */
    public function search($q, $limit, $modelTypes);
}
