<?PHP
include 'Smarty/Smarty.class.php';
$Smarty = new Smarty();
$Smarty->registerFilter('pre', 'smarty_prefilter_preCompile');
$Smarty->template_dir = DAZHE_ROOT.'tpl';
$Smarty->cache_dir    = DAZHE_ROOT.'data/cache';
$Smarty->compile_dir  = DAZHE_ROOT.'data/compile';


function smarty_prefilter_preCompile($source, Smarty_Internal_Template $template) {
	//	$file_type = strtolower(strrchr($this->_current_file, '.'));
	//	$tmp_dir   = 'templates/'.$GLOBALS['config']['site_template'].'/';
	if(strpos($source, "\xEF\xBB\xBF") !== false) {
		$source = str_replace("\xEF\xBB\xBF", '', $source);
	}
	$pattern = array(
		'/<!--[^>|\n]*?({.+?})[^<|{|\n]*?-->/',
		'/<!--[^<|>|{|\n]*?-->/',
		'/(href=["|\'])\.\.\/(.*?)(["|\'])/i',
		'/([\'|"])\.\.\//is',
	);
	$replace = array(
		'\1',
		'',
		'\1\2\3',
		'\1'
	);
	return preg_replace($pattern, $replace, $source);
}