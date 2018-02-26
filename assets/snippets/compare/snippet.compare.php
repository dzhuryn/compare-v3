<?php
require_once MODX_BASE_PATH . 'assets/snippets/compare/class.compare.php';

if (isset($_REQUEST['unique'])) {
    $params['showUniqueValues'] = intval($_REQUEST['unique']);

}
$compare = new compare($modx, $params);
echo $compare->run();