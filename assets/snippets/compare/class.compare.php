<?php
class compare
{

    public $modx;

    public $config = [
        'showUniqueValues'=>0,//показывать только уникальние значения параметров

        'requiredTV'=>'', // Обезательние тв поля для выборки DocLister (картинка товара и т.д.)
        'tvList'=>'',//список тв для сравнения
        'layoutType'=>'horizontal', //тип версти
        'list'=>'compare', //ключ списка товаров
    ];


    public $theme_config = [
        'ownerTpl'=>'@CODE:<div class="compare-wrapper">[+wrapper+]</div>'
    ];
 
    public $horizontal_config = [
        'categoryOwnerTpl'=>'@CODE:<table border="1px" style="border-collapse: collapse;padding: 10px;">[+wrapper+]</table>',
        'firstRowTpl'=>'@CODE:<tr class="first">[+wrapper+]</tr>',
        'paramsFirstBlockTpl'=>'@CODE:<td>[+paramCaption+]</td>',
        'itemTpl'=> '@CODE:<td>[+pagetitle+]<br> <img src="[[phpthumb? &input=`[+image+]` &options=`w=100,h=100,far=C,bg=ffff`]]"><br><a href="[~[*id*]~]?delete=[+id+]"><Удалить></Удалить></a> </td>',
        'rowTpl'=>'@CODE:<tr class="[+class+]">[+wrapper+]</tr>',
        'paramNameTpl'=>'@CODE:<td>[+name+]</td>',
        'paramTpl'=>'@CODE:<td>[+value+]</td>',
        'groupOuterTpl'=>'@CODE:<tr class="[+class+]">[+wrapper+]</tr>',  //шаблон обертка строки з названием групы
        'groupRowTpl'=>'@CODE:<td colspan="[+count+]"><b>[+name+]</b></td>',  //шаблон ячейки з названием групы
    ];

}