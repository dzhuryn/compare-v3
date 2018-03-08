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

        'layoutType' => 'horizontal', //тип версти  (horizontal|vertical)
        'list' => 'compare', //ключ списка товаров
        'tvConfigType' => 'all', //Способ получения списка тв параметров (all|category|tv|list)
        'tvConfigValue' => '', // значение параметра для tvConfigType
        'tvGroup'=>'0', //групировка тв параметров,
        'tvGroupShowEmpty'=>'1', //Показывать групу без категории,
        'prepare'=>'', // prepare для шаблонов
        'lang'=>'ru',// язык
        'api'=>0,// язык
        'minItem'=>0, //минимальное количество
        'tvHideEmpty'=>1, //скрывать пустие тв параметры
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
        'paramNameTpl' => '@CODE:<td>[+caption+]</td>',
        'paramTpl' => '@CODE:<td>[+value+]</td>',
        'groupTpl' => '@CODE:<tr class="[+class+]"><td colspan="[+count+]"><b>[+name+]</b></td></tr>',  //шаблон обертка строки з названием групы
    ];

    public $vertical_config = [
        'layoutOwnerTpl'=>'@CODE:<div class="vertical-layout">[+wrap+]</div>',  //главная обертка
        'blockOuter'=>'@CODE:<div class="compare-item">[+item+][+tvs+]</div>', //обертка блока с товаром и эго свойствами
        'itemTpl'=> '@CODE:<div class="compare-item-info">[+pagetitle+]<br> <img src="[[phpthumb? &input=`[+image+]` &options=`w=100,h=100,far=C,bg=ffff`]]"><br><a class="compare-remove-item" data-id="[+id+]" href="[~[*id*]~]?delete=[+id+]">Удалить</a> </div>', //блок сверху с информацией об товаре
        'paramBlockOuter'=>'@CODE:<ul class="compare-values-outer">[+wrap+]</ul>',// обертка блока с списком ив полей
        'paramTpl'=>'@CODE:<li><p>[+caption+]</p><p>[+value+]</p></li>',// Блок из значением тв поля
        'groupTpl' => '@CODE:<div class="param-group-outer">[+name+] <div class="group-tvs">[+tvs+]</div> </div>',  //шаблон обертка строки з названием групы и параметрами


    ];

    /* масив из языковымы надписямы */
    public $lang;
    public $itemsData;
    public $categoryTVConfig;
    public $categoriesCaption;

    /* Последняя категория */
    public $lastRenderCategory;

    /** Масив из сформированными списком товаров и списком ттв параметров **/
    public $listData;
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
        $this->getList();
        if($this->config['api'] == 1){
            $data = [
                'data'=>$this->itemsData,
                'config'=>$this->categoryTVConfig
            ];
            return json_encode($data,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $output = $this->render($this->itemsData);

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

            foreach ($tvConfig as $tvCategory) {
                foreach ($tvCategory['tv'] as $tvElem) {

                    $tvs[] = $tvElem['name'];
                    $tvsIds[$tvElem['id']] = $tvElem['id'];
                }
            }

            $defaultTvValue = $this->getDefaultTVValues($tvsIds);
            if (!empty($this->config['dl_tvList'])) {
                $tvs[] = $this->config['dl_tvList'];
            }
            $tvs = implode(',', $tvs);


            // пользовательские параметры для DocLister по префиксу
            $docLiterUserParams = [];
            foreach ($this->config as $key=>$value) {
                $exp = explode('_',$key);
                if($exp[0]!='dl') {continue;}
                if($exp[1]=='tvList') {continue;}
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
            foreach ($tvConfig as $categoryKey=> $tvCategory) {
                foreach ($tvCategory['tv'] as $key=>$tvElem){

                    $tvName = $tvElem['name'];
                    $tvId = $tvElem['id'];
                    $values = [];
                    foreach ($itemsData as  $elem) {
                        $tvValue = $elem[$tvName];
                        $value = !empty($defaultTvValue[$tvId][$tvValue])?$defaultTvValue[$tvId][$tvValue]:$tvValue;
                        $values[] = $value;
                    }


                    if($this->checkUniqueValue($values) === false){
                        unset($tvConfig[$categoryKey]["tv"][$key]);
                    }

                    if($this->checkEmptyValues($values) === true){
                        unset($tvConfig[$categoryKey]["tv"][$key]);
                    }
                    //если в категории не осталось параметров
                    if(empty($tvConfig[$categoryKey]["tv"])){
                        unset($tvConfig[$categoryKey]);
                    }

                }
            }


            //глобальный список конфигов по категориям
            $this->categoryTVConfig[$category] = $tvConfig;

            $groupItems = [];
            foreach ($itemsData as $key=> $elem) {
                $itemTV = [];
                foreach ($tvConfig as $tvCategory) {
                    foreach ($tvCategory['tv'] as $tvElem) {
                        $tvId = $tvElem['id'];
                        $tvName = $tvElem['name'];
                        $tvValue = $elem[$tvName];
                        $value = !empty($defaultTvValue[$tvId][$tvValue])?$defaultTvValue[$tvId][$tvValue]:$tvValue;



                        $itemTV[$tvId] = [
                            'id'=>$tvElem['id'],
                            'name'=>$tvElem['name'],
                            'category'=>$tvElem['category'],
                            'value'=>$value
                        ];
                    }
                }

                $groupItems[] = [
                    'item'=>$elem,
                    'tv'=>$itemTV
                ];
            }

            $compareData[$category] = [
                'categoryId'=>$category,
                'categoryName'=>$categoryName,
                'items'=>$groupItems
            ];

        }
        $this->itemsData = $compareData;


        $prepare = $this->config['prepare'];
        if($prepare){
            //prepare для конфигурации тв
            $this->categoryTVConfig = $this->callPrepare($prepare, array(
                'data'=>[
                    'placeholders' => $this->categoryTVConfig
                ],
                'templateName' => 'configPrepare',
                'compare' => $this,

            ));
            //prepare для данных категорий и товаров
            $this->itemsData = $this->callPrepare($prepare, array(
                'data'=>[
                    'placeholders' => $this->itemsData
                ],
                'templateName' => 'itemsPrepare',
                'compare' => $this,

            ));
        }

    }

    public function prepareTvName($id,$name,$caption){
        if(!empty($this->modx->getConfig('__c_tv_'.$name))){
            $caption = $this->modx->getConfig('__c_tv_'.$name);
        }
        return $caption;
    }
    public function getListFromStorage()
    {
        $data =  json_decode($_COOKIE[$this->config['list'] . 'list'], true);
        if(empty($data) || !is_array($data)){
            $data = [];
        }
        return $data;
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
        $tvConfig = [];
        foreach ($tvList as $key => $tvElem) {

            $tvElem['caption'] = $this->prepareTvName($tvElem['id'],$tvElem['name'],$tvElem['caption']);

            //групировка по группам
            if($this->config['tvGroup'] == 1){
                $category = $tvElem['category'];
                $categoryTitle = $this->prepareTvCategoryCaption($tvElem['category']);
            }
            else{
                $category = 0;
                $categoryTitle = '';
            }
            $tvConfig[$category]['title'] = $categoryTitle;
            $tvConfig[$category]['tv'][] = $tvElem;
        }
        return $tvConfig;

    }

    public function render($data)
    {

        //prepare
        $groupOutput = '';
        foreach ($data as $category) {
            $this->lastRenderCategory = $category['categoryId'];
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
        $ind = 0;

        $phl = ['paramCaption' => $this->lang['paramCaption']];
        $tdStr .= $this->parseChunk('paramsFirstBlockTpl', $phl);
        foreach ($items as $item) {
            $tdStr .= $this->parseChunk('itemTpl', $item['item']);
        }
        $phl = ['wrap' => $tdStr];
        $trStr .= $this->parseChunk('firstRowTpl', $phl);
        unset($tdStr);

        $tvConfig = $this->categoryTVConfig[$categoryId];
        foreach ($tvConfig as $categoryId => $tvCategory) {
            $trStr .= $this->renderHorizontalGroup($categoryId,$tvCategory['title'],count($items));

            foreach ($tvCategory['tv'] as $tvElem) {
                $tdStr = '';
                $tvId = $tvElem['id'];
                //ячейка из названием тв параметра
                $phl = [
                    'caption' => $tvElem['caption'],
                    'name' => $tvElem['name'],
                    'id' => $tvElem['id'],
                ];
                $tdStr .= $this->parseChunk('paramNameTpl', $phl);
                foreach ($items as $item) {
                    $tvValue = $item['tv'][$tvId]['value'];
                    //ячейка из значением параметра
                    $phl = [
                        'id' => $tvElem['id'],
                        'name' => $tvElem['name'],
                        'value' => $tvValue,
                    ];
                    $tdStr .= $this->parseChunk('paramTpl', $phl);
                }
                $phl = [
                    'class'=>$ind % 2==1?'even':'odd',
                    'wrap' => $tdStr,
                ];
                $ind++;
                $trStr .= $this->parseChunk('rowTpl', $phl);
            }
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
            $categoryStr = '';
            foreach ($tvConfig as $tvCategoryId => $tvCategory) {
                foreach ($tvCategory['tv'] as  $tvElem) {
                    $tvId = $tvElem['id'];
                    $tvValue = $item['tv'][$tvId]['value'];
                    $phl = [
                        'id' => $tvElem['id'],
                        'name' => $tvElem['name'],
                        'caption' => $tvElem['caption'],
                        'value' => $tvValue,
                    ];
                    $tvsStr .= $this->parseChunk('paramTpl', $phl);
                }
                if ($this->config['tvGroup'] == 1) { //вклчена групировка
                    $categoryStr .= $this->renderVerticalGroup($tvCategoryId,$tvCategory['title'],$tvsStr);;
                }
                else{
                    $categoryStr .= $tvsStr;
                }
                unset($tvsStr);
            }
            $phl = [
                'wrap' => $categoryStr
            ];
            $paramBlockOuter = $this->parseChunk('paramBlockOuter', $phl);
            $phl = [
                'item' => $itemStr,
                'tvs' => $paramBlockOuter
            ];
            $itemsStr .= $this->parseChunk('blockOuter', $phl);

        }

        $phl = [
            'wrap' => $itemsStr,
        ];
        $output = $this->parseChunk('layoutOwnerTpl', $phl);

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
        return $out;
    }

    private function getCategoryName($category)
    {



        if (empty($category)) {
            return '';
        } elseif (is_numeric($category)) {
            $categoryId = intval($category);
            $langTitle = $this->modx->runSnippet('DocInfo',['field'=>'pagetitle_'.$this->config['lang'],'docid'=>$categoryId]);
            if(!empty($langTitle)){
                return $langTitle;
            }
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
        $categoryKey = '';
        if(!in_array($name,['ownerTpl','categoryOwnerTpl','categoryEmptyItems'])){
            $categoryKey = $this->lastRenderCategory;
        }

        $prepare = $this->config['prepare'];
        if($prepare){
            $data = $this->callPrepare($prepare, array(
                'data'=>[
                    'placeholders' => $data
                ],
                'templateName' => $name,
                'compare' => $this,
                'categoryId' => $categoryKey,
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
     * Проверка строки на пустоту
     * Если строка пустая и tvHideEmpty = 1 вернет true
     * */

    private function checkEmptyValues($values)
    {
        $allEmpty = true; //по умолчанию все пусто
        if ($this->config['tvHideEmpty'] == 0) {
            return false;
        }
        foreach ($values as $value){
            if(!empty($value)){
                $allEmpty = false;
            }
        }
        return $allEmpty;
    }


    /*
     * Проверка строки на уникальность данныъ
     * */
    private function checkUniqueValue($values)
    {
        if ($this->config['showUniqueValues'] == 0) {
            return true;
        }
        $searchUnique = false;
        for ($i = 1; $i < count($values); $i++) {
            $oldValue = $values[$i - 1];
            $value = $values[$i];
            if ($oldValue != $value) {
                $searchUnique = true;
            }
        }
        return $searchUnique;
    }

    private function prepareTvCategoryCaption($category)
    {
        if(empty($category)){
            return $this->lang['groupEmpty'];
        }
        if(!empty($this->modx->getConfig('__c_g_'.$this->categoriesCaption[$category]))){
            return $this->modx->getConfig('__c_g_'.$this->categoriesCaption[$category]);
        }

        if(!empty($this->modx->config['__c_g_'.$category])){
            return $this->modx->config['__c_g_'.$category];
        }

        return $this->categoriesCaption[$category];
    }


    /** Генерация строки из нзванием группи тв параметров для горизонтальной табличной верстки
     * @param $categoryId
     * @param $tvCategoryTitle
     * @param $countItems
     * @return string
     */
    private function renderHorizontalGroup($categoryId, $tvCategoryTitle, $countItems)
    {
        if ($this->config['tvGroup'] == 1) { //вклчена групировка
            /** Обертка ячеек из групой тв параметров **/
            $phl = [
                'class' => 'tv-group-wrap',
                'count' => $countItems + 1,
                'name' => $tvCategoryTitle
            ];

            if ($categoryId > 0 || $this->config['tvGroupShowEmpty'] == 1) {
                return $this->parseChunk('groupTpl', $phl);
            }
        }
    }

    /**
     * @param $tvCategoryId
     * @param $title
     * @param $tvsStr
     * @return string
     */
    private function renderVerticalGroup($tvCategoryId, $title, $tvsStr)
    {
        $phl = [
            'id' => $tvCategoryId,
            'name' => $title,
            'tvs' => $tvsStr,
        ];
        return $this->parseChunk('groupTpl', $phl);

    }


}