<?php
$list = isset($list) ? $list : '';
$activeClass = isset($activeClass) ? $activeClass : '';
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


        $maxArray = [];
        if (!empty($max)) {
            foreach (explode('||', $max) as $maxList) {
                $listResp = explode('==', $maxList);
                $maxArray[$listResp[0]] = $listResp[1];
            }
        }
//       if(is_array(explode('')))
//       var_dump();

        $jsConfig = '
        <script>
        var compareOptions = {
         list: "' . $list . '",
         activeClass: "' . $activeClass . '",
         max: '.json_encode($maxArray).',
         alertTemplate:"' . $alertTemplate . '",
         lang:{
             max: "' . $lang['max'] . '",
             maxInCategory: "' . $lang['maxInCategory'] . '",
         },
	    };
        </script>   
        ';
        $modx->regClientHTMLBlock($jsConfig);
        $modx->regClientScript('/assets/snippets/compare/html/js/compare.js?v=' . time());
        $modx->regClientCSS('/assets/snippets/compare/html/css/compare.css?v=' . time());

        break;
    case 'OnPageNotFound':
        switch ($_GET['q']) {
            case 'compare_parent':
                $data = $_GET['data'];
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
                        $new[$el] = ['parent' => $parent, 'title' => $title];
                    }
                }
                echo json_encode($new);
                die();
                break;
        }

        break;


}