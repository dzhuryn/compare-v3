/**
 * compare
 * Сравнение
 * 
 * @category    plugin
 * @version    0.1a
 * @internal @modx_category    compare
 * @internal @properties    {
  "list": [
    {
      "label": "Списки:",
      "type": "text",
      "value": "compare,desire",
      "default": "compare,desire",
      "desc": ""
    }
  ],
  "listNames": [
    {
      "label": "Название списков для сообщений:",
      "type": "text",
      "value": "compare==сравнения||desire==список желаний",
      "default": "compare==сравнения||desire==список желаний",
      "desc": ""
    }
  ],
  "activeClass": [
    {
      "label": "Клас активного елемента:",
      "type": "text",
      "value": "active",
      "default": "active",
      "desc": ""
    }
  ],
  "lang": [
    {
      "label": "Язык:",
      "type": "text",
      "value": "ru",
      "default": "ru",
      "desc": ""
    }
  ],
  "max": [
    {
      "label": "Максимальное количество елементов в категории для списка :",
      "type": "text",
      "value": "compare==3||desire==0",
      "default": "compare==3,desire==0",
      "desc": ""
    }
  ],
  "listSetParent": [
    {
      "label": "Устанавливать data-parent на основании категорий каталога",
      "type": "text",
      "value": "compare==1||desire==0",
      "default": "compare==1,desire==1",
      "desc": ""
    }
  ],
  "listParent": [
    {
      "label": "Групировать список по категориям",
      "type": "text",
      "value": "compare==1||desire==0",
      "default": "compare==1,desire==0",
      "desc": ""
    }
  ],
  "categoryTemplate": [
    {
      "label": "Список id шаблонов категорий товаров:",
      "type": "text",
      "value": "4",
      "default": "",
      "desc": ""
    }
  ],
  "alertTemplate": [
    {
      "label": "Шаблон сообщения об ограничение количества, добавление и удаление елементов:",
      "type": "text",
      "value": "<div class='compare-message'>(text)</div>",
      "default": "<div class='compare-message'>(text)</div>",
      "desc": ""
    }
  ],
  "maxShowMessage": [
    {
      "label": "Показать сообщения о превышении количества элементов (1|0)",
      "type": "text",
      "value": "compare==1||desire==0",
      "default": "compare==1||desire==0",
      "desc": ""
    }
  ],
  "addShowMessage": [
    {
      "label": "Показать сообщения о добавление элемента (1|0)",
      "type": "text",
      "value": "compare==1||desire==0",
      "default": "compare==1||desire==0",
      "desc": ""
    }
  ],
  "removeShowMessage": [
    {
      "label": "Показать сообщения о удаление элемента (1|0)",
      "type": "text",
      "value": "compare==1||desire==0",
      "default": "compare==1||desire==0",
      "desc": ""
    }
  ],
  "css": [
    {
      "label": "Подключать css (1|0)",
      "type": "text",
      "value": "1",
      "default": "1",
      "desc": ""
    }
  ],
  "js": [
    {
      "label": "Подключать js (1|0)",
      "type": "text",
      "value": "1",
      "default": "1",
      "desc": ""
    }
  ],
  "log": [
    {
      "label": "Вывод лога в console",
      "type": "text",
      "value": "1",
      "default": "0",
      "desc": ""
    }
  ]
}
 * @internal @events    OnWebPageInit,OnPageNotFound
 */

require 'assets/snippets/compare/plugin.compare.php';