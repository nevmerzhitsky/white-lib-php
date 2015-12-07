<?php

class Pagination {

    /**
     * @var integer
     */
    private $_totalCount = 0;

    /**
     * @var integer
     */
    private $_onPage = 20;

    /**
     * @var integer
     */
    private $_totalPages = 0;

    /**
     * @param integer $totalCount
     * @param integer|null $onPage Count of element on page. -1 for all on one page.
     */
    public function __construct ($totalCount, $onPage) {
        $this->_totalCount = max(intval($totalCount), 0);

        if (-1 == $onPage) {
            $onPage = $this->_totalCount;
        }
        if (!is_null($onPage)) {
            $this->_onPage = max(intval($onPage), 1);
        }

        $this->_totalPages = intval(ceil($this->_totalCount / $this->_onPage));
    }

    /**
     * @return integer
     */
    public function getTotalCount () {
        return $this->_totalCount;
    }

    /**
     * @param integer $pageNumber
     * @return [integer, integer]
     */
    public function getLimitParams ($pageNumber) {
        $pageNumber = intval($pageNumber);

        if (0 == $this->_onPage) {
            return [
                $this->_totalCount,
                0
            ];
        }

        $pageNumber = min(max($pageNumber, 1), $this->_totalPages);
        $pageNumber--;
        $pageNumber = max($pageNumber, 0);

        return [
            $this->_onPage,
            $pageNumber * $this->_onPage
        ];
    }

    public function getSelectParams ($pageNumber) {
        $temp = $this->getLimitParams($pageNumber);

        return [
            'count' => $temp[0],
            'offset' => $temp[1]
        ];
    }

    public function sliceData (array $data, $pageNumber) {
        $temp = $this->getLimitParams($pageNumber);

        return array_slice($data, $temp[1], $temp[0]);
    }

    /**
     * @param integer $pageNumber
     * @param integer $neighbors
     * @param boolean $alwaysEdges
     * @return array
     */
    public function getNav ($pageNumber, $neighbors = 3, $alwaysEdges = true) {
        $pageNumber = intval($pageNumber);
        $neighbors = intval($neighbors);

        $pageNumber = min($pageNumber, $this->_totalPages);
        $neighbors = max($neighbors, 1);
        $totalElements = $neighbors * 2 + 1;
        $first = $pageNumber - $neighbors;
        $first = max($first, 1);
        $last = $pageNumber + $neighbors + abs(min($pageNumber - $neighbors - 1, 0));
        $last = min($last, $this->_totalPages);
        $last = max($last, $first);

        $result = [];
        if ($first != $last) {
            $elements = range($first, $last);
        } else {
            $elements = [];
        }

        if (!empty($alwaysEdges) && $this->_totalPages > 0) {
            $elements[] = 1;
            $elements[] = $this->_totalPages;
        }

        foreach ($elements as $tempNumber) {
            $result[$tempNumber] = [
                'number' => $tempNumber,
                'active' => $tempNumber == $pageNumber
            ];
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * @param array $nav return string
     */
    public function renderNav (array $nav, $urlPrefix) {
        $lis = [];
        $prevPage = null;

        foreach ($nav as $pageParams) {
            $liClasses = '';
            if ($pageParams['active']) {
                $liClasses .= ' active';
            }
            if (!is_null($prevPage) && $prevPage + 1 != $pageParams['number']) {
                $lis[] = '<li class="disabled"><a href="#">&hellip;</a></li>';
            }

            $lis[] = <<<HTML
<li class="{$liClasses}"><a href="{$urlPrefix}{$pageParams['number']}">{$pageParams['number']}</a></li>
HTML;

            $prevPage = $pageParams['number'];
        }

        return '<ul class="pagination">' . implode('', $lis) . '</ul>';
    }

    /**
     * @param integer $page
     * @param array $nav
     * @return array
     */
    public function getJSONNav ($page, array $nav) {
        return [
            'page' => intval($page),
            'total_pages' => $this->_totalPages,
            'total_count' => $this->_totalCount,
            'on_page' => $this->_onPage,
            'nav' => $nav
        ];
    }

    /**
     * @param integer $pageNumber
     * @param integer $neighbors
     * @param boolean $alwaysEdges
     * @return array
     */
    public function getNavForJson ($pageNumber, $neighbors = 3, $alwaysEdges = true) {
        $nav = $this->getNav($pageNumber, $neighbors, $alwaysEdges);

        return [
            'page' => intval($pageNumber),
            'total_pages' => $this->_totalPages,
            'total_count' => $this->_totalCount,
            'on_page' => $this->_onPage,
            'nav' => $nav
        ];
    }
}
