<?php

class Navbar {

    /**
     * @var array
     */
    private $_elements = [];

    public function __construct (array $data) {
        $this->_elements = $this->_parseSimpleData($data);
    }

    /**
     * @return array
     */
    public function getElements () {
        return $this->_elements;
    }

    /**
     * @param array $simpleData Nav elements in format text=>link
     * @return array
     */
    private function _parseSimpleData (array $simpleData) {
        $result = [];

        foreach ($simpleData as $simpleElement) {
            $elements = [
                'subelements' => []
            ];

            foreach ($simpleElement as $text => $url) {
                if (is_array($url)) {
                    foreach ($url as $text => $subUrl) {
                        $elements['subelements'][] = [
                            'link' => $subUrl,
                            'text' => trim($text)
                        ];
                    }
                } else {
                    $elements = [
                        'link' => $url,
                        'text' => trim($text)
                    ];
                }
            }

            $result[] = $elements;
        }

        return $result;
    }

    /**
     * @return string|null
     */
    public function getIndexUrn () {
        if (!count($this->getElements())) {
            return null;
        }

        return $this->getElements()[0]['link'];
    }

    /**
     * @param string $requestUrl
     */
    public function initForUrl ($requestUrl) {
        foreach ($this->_elements as & $element) {
            if (empty($element['subelements'])) {
                $element['subelements'] = [];
            }

            $active = false;

            if (strpos($element['link'], $requestUrl) === 0) {
                $active = true;
            }

            foreach ($element['subelements'] as & $subElement) {
                if (strpos($subElement['link'], $requestUrl) === 0) {
                    $subElement['active'] = true;
                    $active = true;
                } else {
                    $subElement['active'] = false;
                }
            }

            $element['active'] = $active;
        }
    }

    public function renderHtml ($caption, $logoUrl = '/') {
        $html = [];

        foreach ($this->getElements() as $navElement) {
            $liClasses = '';
            $aClasses = '';
            $dropDownAttrs = '';
            $dropDownCaret = '';
            if ($navElement['active']) {
                $liClasses .= ' active';
            }
            if (!empty($navElement['subelements'])) {
                $liClasses .= ' dropdown';
                $aClasses .= ' dropdown-toggle';
                $dropDownAttrs = ' data-toggle="dropdown" role="button" aria-expanded="false"';
                $dropDownCaret = ' <span class="caret"></span>';
            }

            $html[] = <<<HTML
                <li class="{$liClasses}">
                    <a href="{$navElement['link']}" class="{$aClasses}"{$dropDownAttrs}>
                        {$navElement['text']}{$dropDownCaret}
                    </a>
HTML;

            if (!empty($navElement['subelements'])) {
                $html[] = '<ul class="dropdown-menu inverse-dropdown" role="menu">';

                foreach ($navElement['subelements'] as $navSubElement) {
                    $subLiClasses = '';
                    if ($navSubElement['active']) {
                        $subLiClasses .= ' active';
                    }

                    $html[] = <<<HTML
                        <li class="{$subLiClasses}"><a href="{$navSubElement['link']}">
                                {$navSubElement['text']}
                            </a></li>
HTML;
                }

                $html[] = '</ul>';
            }

            $html[] = '</li>';
        }

        $html = implode('', $html);

        return <<<HTML
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{$logoUrl}">{$caption}</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">{$html}</ul>
        </div>
    </div>
</nav>
HTML;
    }
}
