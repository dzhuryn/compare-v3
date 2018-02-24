<?php
require_once MODX_BASE_PATH.'assets/snippets/compare/class.compare.php';

$layoutType = isset($layoutType)?$layoutType:'horizontal'; //тип верстки
$list = isset($list)?$list:'compare'; //ключ списка
$tvConfigType = isset($tvConfigType)?$tvConfigType:'list'; //ключ списка

$compare = new compare();