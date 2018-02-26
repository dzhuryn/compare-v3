<?php

require_once (MODX_BASE_PATH. "assets/snippets/DocLister/lib/DLTemplate.class.php");

class compare
{

    /* @var DocumentParser */
    public $modx;
    public $R, $TVR, $TV, $TT; // таблицы бд


    /**
     * Шаблонизатор чанков
     * @var DLTemplate
     * @access protected
     */
    protected $DLTemplate = null;


    public $product_templates_id; //шаблоны товаров
    public $config = [
        'showUniqueValues' => 0,//показывать только уникальние значения параметров
        'requiredTV' => '', // Обезательние тв поля для выборки DocLister (картинка товара и т.д.)
        'tvList' => '',//список тв для сравнения
        'layoutType' => 'horizontal', //тип версти  (horizontal|vertical)
        'list' => 'compare', //ключ списка товаров
        'tvConfigType' => 'all', //Способ получения списка тв параметров (all|category|tv|list)
        'tvConfigValue' => '', // значение параметра для tvConfigType
        'tvGroup'=>'0', //групировка тв параметров,
        'tvGroupShowEmpty'=>'1', //Показывать групу без категории,
        'prepare'=>'', // prepare для шаблонов
        'lang'=>'ru',// язык
        'minItem'=>2 //минимальное количество
    ];


    public $theme_config = [
        'ownerTpl' => '@CODE:<div class="compare-wrapper" data-list="[+list+]">[+wrap+]</div>',
        'categoryOwnerTpl' => '@CODE:<div class="compare-group" data-id="[+categoryId+]" data-list="[+list+]">
    <div class="actions-wrap[+actions-class+]">
    <a href="#" class="compare-remove-category" data-id="[+categoryId+]" >[+lang_groupRemove+]</a>
    <a href="#" class="compare-unique-value" data-status="[+unique+]" data-group="[+categoryId+]">[+unique-title+]</a>
</div>
<div class="categoryTitle">[+categoryName+]</div>
[+wrap+]</div>',
        'categoryEmptyItems'=>'@CODE:<div class="compare-empty-message">[+message+]</div>'
    ];

    public $horizontal_config = [
        'layoutOwnerTpl' => '@CODE:<table class="horizontal-layout">[+wrap+]</table>',
        'firstRowTpl' => '@CODE:<tr class="first">[+wrap+]</tr>',
        'paramsFirstBlockTpl' => '@CODE:<td>[+paramCaption+]</td>',
        'itemTpl' => '@CODE:<td>[+pagetitle+]<br> <img src="[[phpthumb? &input=`[+image+]` &options=`w=100,h=100,far=C,bg=ffff`]]"><br><a class="compare-remove-item" data-id="[+id+]" href="[~[*id*]~]?delete=[+id+]">Удалить</a> </td>',
        'rowTpl' => '@CODE:<tr class="[+class+]">[+wrap+]</tr>',
        'paramNameTpl' => '@CODE:<td>[+name+]</td>',
        'paramTpl' => '@CODE:<td>[+value+]</td>',
        'groupOuterTpl' => '@CODE:<tr class="[+class+]">[+wrap+]</tr>',  //шаблон обертка строки з названием групы
        'groupRowTpl' => '@CODE:<td colspan="[+count+]"><b>[+name+]</b></td>',  //шаблон ячейки з названием групы
    ];

    public $vertical_config = [
        'layoutOwnerTpl'=>'@CODE:<div class="vertical-layout">[+wrap+]</div>',  //главная обертка
        'blockOuter'=>'@CODE:<div class="compare-item">[+item+][+tvs+]</div>', //обертка блока с товаром и эго свойствами
        'itemTpl'=> '@CODE:<div class="compare-item-info">[+pagetitle+]<br> <img src="[[phpthumb? &input=`[+image+]` &options=`w=100,h=100,far=C,bg=ffff`]]"><br><a class="compare-remove-item" data-id="[+id+]" href="[~[*id*]~]?delete=[+id+]">Удалить</a> </div>', //блок сверху с информацией об товаре
        'paramBlockOuter'=>'@CODE:<ul class="compare-values-outer">[+wrap+]</ul>',// обертка блока с списком ив полей
        'paramTpl'=>'@CODE:<li><p>[+name+]</p><p>[+value+]</p></li>',// Блок из значением тв поля
    ];

    /* масив из языковымы надписямы */
    public $lang;

    public $categoryTVConfig;

    public $categoriesCaption;
    public function __construct($modx, $params)
    {
        $this->modx = $modx;
        $this->R = $this->modx->getFullTableName('site_content');
        $this->TT = $this->modx->getFullTableName('site_tmplvar_templates');
        $this->T = $this->modx->getFullTableName('site_tmplvars');

        $this->config = array_merge($this->config,  $this->theme_config);
        $layoutType = !empty($params['layoutType'])?$params['layoutType']:$this->config['layoutType'];
        switch ($layoutType) {
            case 'horizontal':
                $this->config = array_merge($this->config,  $this->horizontal_config);
                break;
            case 'vertical':
                $this->config = array_merge($this->config,  $this->vertical_config);
                break;
        }
        $this->config = array_merge($this->config,  $params);

        $this->DLTemplate = DLTemplate::getInstance($modx);

        $lang = [];
        $langFile = MODX_BASE_PATH . 'assets/snippets/compare/lang/' . $this->config['lang'] . '.php';
        if (file_exists($langFile)) {
            require $langFile;
        }
        $this->lang = $lang;

    }

    public function run()
    {
        $data = $this->getList();
        $output = $this->render($data);

        return $output;
    }

    /*
     * Получаэм данние и готовим до рендера html
     * */

    /**
     * @return array
     */
    public function getList()
    {
        global $modx;
        $compareList = $this->getListFromStorage();

        $compareData = [];
        //проходимся по категориям
        foreach ($compareList as $category => $items) {
            $categoryName = $this->getCategoryName($category);
            $tvConfig = $this->getTvConfig($items);

            $ids = [];
            foreach ($items as $id => $bool) {
                $ids[] = $id;
            }
            if (empty($ids)) {
                continue;
            };
            $ids = implode(',', $ids);
            $tvs = [];
            $tvsIds = [];

            foreach ($tvConfig as $tvElem) {
                $tvs[] = $tvElem['name'];
                $tvsIds[$tvElem['id']] = $tvElem['id'];
            }

            $defaultTvValue = $this->getDefaultTVValues($tvsIds);
            if (!empty($this->config['requiredTV'])) {
                $tvs[] = $this->config['requiredTV'];
            }
            $tvs = implode(',', $tvs);


            // пользовательские параметры для DocLister по префиксу
            $docLiterUserParams = [];
            foreach ($this->config as $key=>$value) {
                $exp = explode('_',$key);
                if($exp[0]!='dl') {continue;}
                $docLiterUserParams[$exp[1]] = $value;
            }
            $defaultParams = [
                'idType' => 'documents',
                'documents' => $ids,
                'tvList' => $tvs,
                'tvPrefix' => '',
                'api'=>1,
                'selectFields'=>'id,pagetitle,longtitle,description,introtext',
            ];
            $params = array_merge($defaultParams,$docLiterUserParams);
            $itemsData = $modx->runSnippet('DocLister', $params);
            $itemsData = json_decode($itemsData,true);

            //проверка на уникальность
            foreach ($tvConfig as $key=> $tvElem) {
                $tvName = $tvElem['name'];
                $tvId = $tvElem['id'];
                $values = [];
                foreach ($itemsData as  $elem) {
                    $tvValue = $elem[$tvName];
                    $value = !empty($defaultTvValue[$tvId][$tvValue])?$defaultTvValue[$tvId][$tvValue]:$tvValue;
                    $values[] = $value;
                }
                if($this->checkUniqueValue($values) === false){
                    unset($tvConfig[$key]);
                }
            }

            //глобальный список конфигов по категориям
            $this->categoryTVConfig[$category] = $tvConfig;



            $groupItems = [];
            foreach ($itemsData as $key=> $elem) {
                $itemTV = [];
                foreach ($tvConfig as $tvElem) {
                    $tvId = $tvElem['id'];
                    $tvCaption = $this->prepareTvName($tvId,$tvElem['name'],$tvElem['caption']);
                    $tvName = $tvElem['name'];
                    $tvValue = $elem[$tvName];
                    $value = !empty($defaultTvValue[$tvId][$tvValue])?$defaultTvValue[$tvId][$tvValue]:$tvValue;
                    $itemTV[$tvId] = [
                        'name'=>$tvName,
                        'category'=>$tvElem['category'],
                        'caption'=>$tvCaption,
                        'value'=>$value
                    ];
                }
                $groupItems[] = [
                    'item'=>$elem,
                    'tv'=>$itemTV
                ];
            }

            $compareData[] = [
                'categoryId'=>$category,
                'categoryName'=>$categoryName,
                'items'=>$groupItems
            ];

        }
        return $compareData;
    }

    public function prepareTvName($id,$name,$caption){
        return $caption;
    }
    public function getListFromStorage()
    {
        return json_decode($_COOKIE[$this->config['list'] . 'list'], true);
    }

    public function getTvConfig($items)
    {

        //список категорий
        $sql = "select id,category from ".$this->modx->getFullTableName('categories');
        $q = $this->modx->db->query($sql);
        $categoryResponse = $this->modx->db->makeArray($q);

        foreach ($categoryResponse as $category) {
            $this->categoriesCaption[$category['id']] = $category['category'];
        }

        $firstItemId = key($items);
        if (!empty($firstItemId)) {
            $itemTemplate = (int)$this->modx->runSnippet('DocInfo', ['field' => 'template', 'docid' => $firstItemId]);
            $this->product_templates_id[] = $itemTemplate;
        }
        $groupOrderBy = '';
        if($this->config['tvGroup'] == 1){
            $groupOrderBy = 'category asc,';
        }

        switch ($this->config['tvConfigType']) {
            case 'all': //все тв
                if (empty($itemTemplate)) {
                    return [];
                }
                $sql = "select T.id,T.name,T.caption,T.category from $this->T as T,$this->TT as TT where TT.tmplvarid = T.id and TT.templateid = $itemTemplate order by $groupOrderBy TT.rank ";
                $tvList = $this->modx->db->makeArray($this->modx->db->query($sql));
                break;
            case 'list':
                $tvConfigValue = $this->config['tvConfigValue'];
                if (empty($tvConfigValue)) {
                    return [];
                }
                $tvConfigValue = explode(',', $tvConfigValue);
                $tvConfigValueArray = [];
                foreach ($tvConfigValue as $item) {
                    $tvConfigValueArray[] = '"' . $this->modx->db->escape($item) . '"';
                }
                if (empty($tvConfigValueArray)) {
                    return [];
                }
                $tvConfigValueInDb = implode(',', $tvConfigValueArray);

                $sql = "select T.id,T.name,T.caption,T.category from $this->T as T where T.name in ($tvConfigValueInDb) ORDER BY $groupOrderBy FIELD(name, $tvConfigValueInDb)";
                $tvList = $this->modx->db->makeArray($this->modx->db->query($sql));
                break;
            case 'category':
                if (empty($itemTemplate) || empty($this->config['tvConfigValue'])) {
                    return [];
                }
                $categoryId = intval($this->config['tvConfigValue']);
                $sql = "select T.id,T.name,T.caption,T.category from $this->T as T,$this->TT as TT where TT.tmplvarid = T.id and TT.templateid = $itemTemplate and T.category = $categoryId order by $groupOrderBy TT.rank ";
                $tvList = $this->modx->db->makeArray($this->modx->db->query($sql));


                break;
            case 'tv':
                if (empty($firstItemId)) {
                    return [];
                }
                $config = [];
                $id = $firstItemId;
                while (1) {
                    $compare = $this->modx->runSnippet('DocInfo', ['field' => 'compare', 'docid' => $id]);
                    if (!empty($compare)) {
                        $compare = json_decode($compare, true)['fieldValue'];
                        if (!empty($compare)) {
                            foreach ($compare as $name) {
                                $config[] = intval($name['dropdown']);
                            };
                        }
                    }
                    $parent = $this->modx->runSnippet('DocInfo', ['field' => 'parent', 'docid' => $id]);
                    if ($parent == 0) {
                        break;
                    }
                    $id = $parent;
                }
                if (empty($config)) {
                    return [];
                } else {
                    $config = implode(',', $config);
                }
                $sql = "select T.id,T.name,T.caption,T.category from $this->T as T where T.id in ($config) ORDER BY $groupOrderBy FIELD(name, $config)";
                $tvList = $this->modx->db->makeArray($this->modx->db->query($sql));
                break;

            default:
                return [];
        }
        return $tvList;

    }

    public function render($data)
    {
        //prepare
        $groupOutput = '';
        foreach ($data as $category) {

            switch ($this->config['layoutType']){
                case 'horizontal':
                    $out = $this->renderHorizontal($category['categoryId'],$category['items']);
                    break;
                case 'vertical':
                    $out = $out = $this->renderVertical($category['categoryId'],$category['items']);;
                    break;
                default:
                    $out = '';
            }

            $minItem = intval($this->config['minItem']);
            $minStatus = false;
            if($minItem>0 && count($category['items'])<$minItem){
                $minStatus = true;
                $plh = [
                    'message'=>str_replace(['[+min+]'],[$minItem],$this->lang['minItem'])
                ];
                $out = $this->parseChunk('categoryEmptyItems', $plh);
            }
            $uniqueTitle = $this->config['showUniqueValues'] ==1?$this->lang['uniqueAllTitle']:$this->lang['uniqueOnlyTitle'];
            //рендер обертки группы
            $plh = [
                'categoryId'=>$category['categoryId'],
                'categoryName'=>$category['categoryName'],
                'wrap'=>$out,
                'unique'=>$this->config['showUniqueValues'],
                'unique-title'=>$uniqueTitle,
                'list'=>$this->config['list'],
                'actions-class'=> $minStatus ==true?' hide':''
            ];
            $groupOutput .= $this->parseChunk('categoryOwnerTpl', $plh);
        }
        if(empty($groupOutput)){
            $plh = [
                'message'=>$this->lang['compareEmpty']
            ];
            $groupOutput = $this->parseChunk('categoryEmptyItems', $plh);
        }

        //рендер основной обертки для всего
        $plh = [
            'list'=>$this->config['list'],
            'wrap'=>$groupOutput
        ];
        $out = $this->parseChunk('ownerTpl', $plh);
        return $out;
    }

    /*Получаэм список тв для категории*/

    public function renderHorizontal($categoryId,$items)
    {
        $trStr = '';
        $tdStr = '';


        $phl = ['paramCaption' => $this->lang['paramCaption']];
        $tdStr .= $this->parseChunk('paramsFirstBlockTpl', $phl);
        foreach ($items as $item) {
            $tdStr .= $this->parseChunk('itemTpl', $item['item']);
        }

        $phl = ['wrap' => $tdStr];
        unset($tdStr);
        $trStr .= $this->parseChunk('firstRowTpl', $phl);


        $tvCategoryId = -1;
        $tvConfig = $this->categoryTVConfig[$categoryId];
        foreach ($tvConfig as $tvElem) {
            $tdStr = '';
            $tvId = $tvElem['id'];
            //ячейка из названием тв параметра
            $phl = ['name' => $tvElem['caption']];
            $tdStr .= $this->parseChunk('paramNameTpl', $phl);
            foreach ($items as $item) {
                $tvValue = $item['tv'][$tvId]['value'];
                //ячейка из значением параметра
                $phl = ['value' => $tvValue];
                $tdStr .= $this->parseChunk('paramTpl', $phl);
            }
            if($this->config['tvGroup'] == 1 && $tvCategoryId != $tvElem['category']){ //вклчена групировка и у нас новый параметр

                /** Строка из групой тв параметров **/
                $phl = [
                    'count'=>count($items) +1,
                    'name' => $this->prepareTvCategoryCaption($tvElem['category'])
                ];
                $gorupTD = $this->parseChunk('groupRowTpl', $phl);


                /** Обертка ячеек из групой тв параметров **/
                $phl = [
                    'class'=>'tv-group-wrap',
                    'wrap' => $gorupTD
                ];
                if($tvElem['category'] > 0 || $this->config['tvGroupShowEmpty'] == 1){
                    $trStr .= $this->parseChunk('groupOuterTpl', $phl);
                }

                $tvCategoryId = $tvElem['category'];
            }


            $phl = [
                'class' => '',
                'wrap' => $tdStr,
            ];
            $trStr .= $this->parseChunk('rowTpl', $phl);
        }


        $phl = ['wrap' => $trStr];
        $table = $this->parseChunk('layoutOwnerTpl', $phl);
        return $table;

    }

    private function renderVertical($categoryId, $items)
    {
        $tvConfig = $this->categoryTVConfig[$categoryId];

        $itemsStr = '';
        foreach ($items as $item) {
            $itemStr = $this->parseChunk('itemTpl', $item['item']);
            $tvsStr = '';
            foreach ($tvConfig as $tvElem) {
                $tvId = $tvElem['id'];


                $tvName = $tvElem['name'];
                $tvValue = $item['tv'][$tvId]['value'];
                $phl = [
                    'name' => $this->prepareTvName($tvId, $tvName, $tvElem['caption']),
                    'value' => $tvValue
                ];
                $tvsStr .= $this->parseChunk('paramTpl', $phl);


            }
            $phl = [
                'wrap'=>$tvsStr
            ];
            $paramBlockOuter =  $this->parseChunk('paramBlockOuter', $phl);
            $phl = [
                'item'=>$itemStr,
                'tvs'=>$paramBlockOuter
            ];
            $itemsStr .=  $this->parseChunk('blockOuter', $phl);

        }

        $phl = [
            'wrap'=>$itemsStr,
        ];
        $output =  $this->parseChunk('layoutOwnerTpl', $phl);

        return $output;

    }


    public function getTVNames ($tv_ids = '', $field = 'name')
    {
        $tv_names = array();
        if ($tv_ids != '' && !empty($this->product_templates_id)) {
            $product_templates_id = implode(',',$this->product_templates_id);

            $q = $this->modx->db->query("SELECT `a`.`id`, `a`.`".$field."` FROM " . $this->modx->getFullTableName('site_tmplvars') . " as `a`, " . $this->modx->getFullTableName('site_tmplvar_templates') . " as `b` WHERE `a`.`id` IN (" . $tv_ids . ") AND `a`.`id` = `b`.`tmplvarid` AND `b`.`templateid` IN(" . $product_templates_id . ") ORDER BY `b`.`rank` ASC, `a`.`$field` ASC");



            while ($row = $this->modx->db->getRow($q)){
                if (!isset($tv_names[$row['id']])) {
                    $tv_names[$row['id']] = $row[$field];
                }
            }
        }
        return $tv_names;
    }

    public function getDefaultTVValues($array = array())
    {
        $out = array();
        $tvs = implode(",", array_keys($array));

        if ($tvs != '') {
            $elements = $this->getTVNames($tv_ids = $tvs, $field = 'elements');
            foreach ($elements as $tv_id => $element) {
                if (stristr($element, "@EVAL")) {
                    $element = trim(substr($element, 6));
                    $element = str_replace("\$modx->", "\$this->modx->", $element);
                    $element = eval($element);
                }
                if (stristr($element, "@SELECT")) {
                    $element = str_replace(['@SELECT','[+PREFIX+]'],['SELECT',$this->modx->db->config['table_prefix']],$element);
                    $resp = $this->modx->db->makeArray($this->modx->db->query($element));
                    $respData = [];
                    foreach ($resp as $el) {
                        $keys = array_keys($el);
                        $respData[] = $el[$keys[0]].'=='.$el[$keys[1]];
                    }
                    $element = implode('||',$respData);
                }
                if ($element != '') {
                    $tmp = explode("||", $element);
                    foreach ($tmp as $v) {
                        $tmp2 = explode("==", $v);
                        $key = isset($tmp2[1]) && $tmp2[1] != '' ? $tmp2[1] : $tmp2[0];
                        $value = $tmp2[0];
                        if ($key != '') {
                            $out[$tv_id][$key] = $value;
                        }
                    }
                }
            }
        }
        $this->modx->ef_elements_name = $out;
        return $out;
    }

    private function getCategoryName($category)
    {
        if (empty($category)) {
            return '';
        } elseif (is_numeric($category)) {
            $categoryId = intval($category);
            return $this->modx->runSnippet('DocInfo', ['docid' => $categoryId]);
        } else {
            return $category;
        }
    }

    public function callPrepare($name, array $params)
    {
        $params['compare'] = $this;
        $out = null;
        if (empty($name)) {
            return $params['data'];
        }
        if ((is_object($name) && ($name instanceof Closure)) || is_callable($name)) {
            $data = call_user_func($name, $params['data']);
        } else {
            $data = $this->modx->runSnippet($name, $params);
        }
        switch (true) {
            case is_array($data):
                $out = $data;
                break;
            case ($data === '' || (is_bool($data) && $data === false)):
                $out = false;
                break;
            case is_string($data):
                if ($data[0] == '[' || $data[0] == '{') {
                    $out = json_decode($data,true);
                } else {
                    $out = unserialize($data);
                }
        }

        return is_null($out) ? $params['data'] : $out;
    }

    public function parseChunk($name, $data, $parseDocumentSource = false)
    {
        $prepare = $this->config['prepare'];
        if($prepare){
            $data = $this->callPrepare($prepare, array(
                'data'=>[
                    'placeholders' => $data
                ],
                'templateName' => $name,
            ));
        }
//добавляем $lang
        foreach ($this->lang as $key => $val) {
            $data['lang_'.$key] = $val;
        }
        $out = $this->DLTemplate->parseChunk($this->config[$name], $data, $parseDocumentSource);
        return $out;
    }

    /*
     * Проверка строки на уникальность данныъ
     * */
    private function checkUniqueValue($values)
    {
        if ($this->config['showUniqueValues'] == 0) {
            return true;
        }
        $status = true;
        for ($i = 1; $i < count($values); $i++) {
            $oldValue = $values[$i - 1];
            $value = $values[$i];
            if ($oldValue == $value) {
                $status = false;
            }
        }
        return $status;
    }

    private function prepareTvCategoryCaption($category)
    {
        if(empty($category)){
            return $this->lang['groupEmpty'];
        }
        return $this->categoriesCaption[$category];
    }



}