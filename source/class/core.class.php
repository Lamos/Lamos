<?PHP
/*******************************************************
 *
 *
 *
 *
 *
 *
 ******************************************************/
define('IN_LAMOS', true);
define('LAMOS_ROOT', dirname(dirname(dirname(__FILE__))));

spl_autoload_register(array('CORE', 'autoload'));


class CORE {

	private static $_imports;



	public static function import($name, $folder = '', $force = true) {
		$key = $folder.$name;
		if(!isset(self::$_imports[$key])) {
			$path = LAMOS_ROOT.'/source/'.$folder;
			if(strpos($name, '/') !== false) {
				$pre = basename(dirname($name));
				if($pre === 'lamos') {
					$filename = dirname($name).'/lamos'.basename($name).'.php';
				} else {
					$filename = dirname($name).'/'.basename($name).'.'.$pre.'.php';
				}
			} else {
				$filename = $name.'.php';
			}
			if(is_file($path.'/'.$filename)) {
				self::$_imports[$key] = true;
				$rt                   = include $path.'/'.$filename;
				return $rt;
			} elseif(!$force) {
				return false;
			} else {
				throw new Exception('Oops! System file lost: '.$filename);
			}
		}
		return true;
	}



	public static function autoload($class) {
		$class = strtolower($class);
		if(strpos($class, 'lamos') === 0) {
			$file = 'class/lamos/'.substr($class, 5);
		} elseif(strpos($class, '_') !== false) {
			list($folder) = explode('_', $class);
			$file = 'class/'.$folder.'/'.substr($class, strlen($folder) + 1);
		} else {
			$file = 'class/'.$class;
		}
		try {
			self::import($file);
			return true;
		} catch(Exception $exc) {
			$trace = $exc->getTrace();
			foreach($trace as $log) {
				if(empty($log['class']) && $log['function'] == 'class_exists') {
					return false;
				}
			}
			LamosError::exception_error($exc);
		}
	}
}

class C extends CORE {

}

class DB extends LamosDatabase {

}