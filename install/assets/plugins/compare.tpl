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
      "label": "Шаблон окна об ограничение количества:",
      "type": "text",
      "value": "<div class='compare-message'>(text)</div>",
      "default": "<div class='compare-message'>(text)</div>",
      "desc": ""
    }
  ]
}
 * @internal @events    OnWebPageInit,OnPageNotFound
 */

require 'assets/snippets/compare/plugin.compare.php';