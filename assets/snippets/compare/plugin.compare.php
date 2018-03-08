<?php
$list = isset($list) ? $list : '';
$activeClass = isset($activeClass) ? $activeClass : '';
$alertTemplate = isset($alertTemplate) ? $alertTemplate : '';
$language = isset($_SESSION['lang']) ? $_SESSION['lang'] : $lang;


global $categories;
if (!function_exists('compare_parent')) {
    function compare_parent($el)
    {
        global $categories;
        $id = $el;
        // echo $id;
        $parent = $categories[$el];
        //     echo $parent['id'];
        if ($parent['parent'] == 0 || $parent['compare_top'] == 'yes') {
            return $parent['id'];
        } else {
            return compare_parent($parent['parent']);
        }
    }
}

$e = $modx->event;
switch ($e->name) {
    case 'OnWebPageInit':
        $lang = [];
        $langFile = MODX_BASE_PATH . 'assets/snippets/compare/lang/' . $language . '.php';
        if (file_exists($langFile)) {
            require $langFile;
        }

        $config = [
            'list' => $list,
            'activeClass' => $activeClass,
            'alertTemplate' => $alertTemplate,
        ];

        //Сборные параметры
        $jsonFields = ['max', 'listSetParent','listNames', 'maxShowMessage', 'addShowMessage', 'removeShowMessage'];
        foreach ($jsonFields as $key) {
            if (!empty($params[$key])) {
                $respArray = [];
                foreach (explode('||', $params[$key]) as $list) {
                    $resp = explode('==', $list);
                    $respArray[$resp[0]] = $resp[1];
                }
                $config[$key] = $respArray;
            }
        }
        foreach ($lang as $key =>$langValue) {
            if(!in_array($key,['max','maxInCategory','add','remove'])){
                continue;
            }
            $config['lang'][$key] = $langValue;
        }
        $config = json_encode($config,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        //для уобства


        $jsConfig = '
        <script>
        var compareOptions = '.$config.';
        </script>   
        ';
        $modx->regClientHTMLBlock($jsConfig);
        if($js == 1){
            $file = '/assets/snippets/compare/html/js/compare.js';
            $fileFull = MODX_BASE_PATH.$file;
            if(file_exists($fileFull)){
                $file .= '?v='.filemtime($fileFull);
            }
            $modx->regClientScript($file);
        }
        if($css == 1){
            $file = '/assets/snippets/compare/html/css/compare.css';
            $fileFull = MODX_BASE_PATH.$file;
            if(file_exists($fileFull)){
                $file .= '?v='.filemtime($fileFull);
            }
            $modx->regClientCSS($file);
        }


        break;
    case 'OnPageNotFound':
        switch ($_GET['q']) {
            case 'compare_parent':
                $data = $_REQUEST['data'];
                $list = $_REQUEST['list'];
                $listParentCheck = [];
                $listParent = explode('||', $listParent);
                foreach ($listParent as $elem) {
                    $elem = explode('==', $elem);
                    $listParentCheck[$elem[0]] = $elem[1];
                };
                //получаем все перенты
                if (!empty($categoryTemplate)) {
                    $categories = $modx->runSnippet('DocLister', [
                        'api' => 1,
                        'depth' => 4,
                        'selectFields' => 'id,parent',
                        'parents' => '0',
                        'addWhereList' => 'template in(' . $categoryTemplate . ')',
                        'showParent' => 1,
                        'tvList' => 'compare_top,compare_group_name',
                        'tvPrefix' => ''
                    ]);
                    $categories = json_decode($categories, true);
                }

                $new = [];
                if (is_array($data)) {
                    foreach ($data as $el) {
                        $parent = 0;
                        $title = '';
                        $elParent = $modx->runSnippet('DocInfo', ['field' => 'parent', 'docid' => $el]);
                        $parentResp = compare_parent($elParent);
                        if (!empty($parentResp)) {
                            $parent = $parentResp;
                        }
                        if (!empty($categories[$parent]['compare_group_name'])) {
                            $title = $categories[$parent]['compare_group_name'];
                        }
                        if ($listParentCheck[$list] == 0) {
                            $parent = 0;
                            $title = '';
                        }
                        $new[$el] = ['parent' => $parent, 'title' => $title];
                    }
                }
                echo json_encode($new);
                die();
                break;
        }

        break;


}