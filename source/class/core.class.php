<?PHP
/*******************************************************
 *
 *
 *
 *
 *
 *
 ******************************************************/

mb_internal_encoding('UTF-8');
mb_http_input('UTF-8');
mb_http_output('UTF-8');
define('IN_LAMOS', true);
define('DS', DIRECTORY_SEPARATOR);
define('LAMOS_ROOT', dirname(dirname(dirname(__FILE__))).DS);
define('LAMOS_CORE_DEBUG', true);
//define('DISCUZ_TABLE_EXTENDABLE', TRUE);
include(LAMOS_ROOT.'source/kohana/include.php');
//set_error_handler(array('core', 'handleError'));
//set_exception_handler(array('core', 'handleException'));
//register_shutdown_function(array('core', 'handleShutdown'));
spl_autoload_register(array('CORE', 'autoload'));

C::creatapp();


class CORE {

	private static $_tables;
	private static $_imports;
	private static $_app;
	private static $_memory;



	public static function app() {
		return self::$_app;
	}



	public static function creatapp() {
		if(!is_object(self::$_app)) {
			self::$_app = 1;//discuz_application::instance();
		}
		return self::$_app;
	}



	public static function t($name) {
		return self::_make_obj($name, 'table', DISCUZ_TABLE_EXTENDABLE);
	}



	public static function m($name) {
		$args = array();
		if(func_num_args() > 1) {
			$args = func_get_args();
			unset($args[0]);
		}
		return self::_make_obj($name, 'model', true, $args);
	}



	protected static function _make_obj($name, $type, $extendable = true, $p = array()) {
		$pluginid = null;
		if($name[0] === '#') {
			list(, $pluginid, $name) = explode('#', $name);
		}
		$cname = $type.'_'.$name;
		if(!isset(self::$_tables[$cname])) {
			if(!class_exists($cname, false)) {
				self::import(($pluginid ? 'plugin/'.$pluginid : 'class').'/'.$type.'/'.$name);
			}
			if($extendable) {
				self::$_tables[$cname] = new discuz_container();
				switch(count($p)) {
					case 0:
						self::$_tables[$cname]->obj = new $cname();
						break;
					case 1:
						self::$_tables[$cname]->obj = new $cname($p[1]);
						break;
					case 2:
						self::$_tables[$cname]->obj = new $cname($p[1], $p[2]);
						break;
					case 3:
						self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3]);
						break;
					case 4:
						self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3], $p[4]);
						break;
					case 5:
						self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3], $p[4], $p[5]);
						break;
					default:
						$ref                        = new ReflectionClass($cname);
						self::$_tables[$cname]->obj = $ref->newInstanceArgs($p);
						unset($ref);
						break;
				}
			} else {
				self::$_tables[$cname] = new $cname();
			}
		}
		return self::$_tables[$cname];
	}



	public static function memory() {
		if(!self::$_memory) {
			self::$_memory = new discuz_memory();
			self::$_memory->init(self::app()->config['memory']);
		}
		return self::$_memory;
	}



	public static function handleException($exception) {
		return vizto_exception::Exception_Handler($exception);
		discuz_error::exception_error($exception);
	}



	public static function handleError($errno, $errstr, $errfile, $errline) {
		$args = func_get_args();
		return call_user_func_array(array('vizto_exception', 'Error_Handler'), $args); //vizto_exception::Error_Handler($errno, $errstr, $errfile, $errline);
		if($errno & DISCUZ_CORE_DEBUG) {
			discuz_error::system_error($errstr, false, true, false);
		}
	}



	public static function handleShutdown() {
		return 1;//vizto_exception::Shutdown_Handler();
		if(($error = error_get_last()) && $error['type'] & DISCUZ_CORE_DEBUG) {
			discuz_error::system_error($error['message'], false, true, false);
		}
	}



	public static function analysisStart($name) {
		$key = 'other';
		if($name[0] === '#') {
			list(, $key, $name) = explode('#', $name);
		}
		if(!isset($_ENV['analysis'])) {
			$_ENV['analysis'] = array();
		}
		if(!isset($_ENV['analysis'][$key])) {
			$_ENV['analysis'][$key]        = array();
			$_ENV['analysis'][$key]['sum'] = 0;
		}
		$_ENV['analysis'][$key][$name]['start']                            = microtime(true);
		$_ENV['analysis'][$key][$name]['start_memory_get_usage']           = memory_get_usage();
		$_ENV['analysis'][$key][$name]['start_memory_get_real_usage']      = memory_get_usage(true);
		$_ENV['analysis'][$key][$name]['start_memory_get_peak_usage']      = memory_get_peak_usage();
		$_ENV['analysis'][$key][$name]['start_memory_get_peak_real_usage'] = memory_get_peak_usage(true);
	}



	public static function analysisStop($name) {
		$key = 'other';
		if($name[0] === '#') {
			list(, $key, $name) = explode('#', $name);
		}
		if(isset($_ENV['analysis'][$key][$name]['start'])) {
			$diff                                  = round((microtime(true) - $_ENV['analysis'][$key][$name]['start']) * 1000, 5);
			$_ENV['analysis'][$key][$name]['time'] = $diff;
			$_ENV['analysis'][$key]['sum']         = $_ENV['analysis'][$key]['sum'] + $diff;
			unset($_ENV['analysis'][$key][$name]['start']);
			$_ENV['analysis'][$key][$name]['stop_memory_get_usage']           = memory_get_usage();
			$_ENV['analysis'][$key][$name]['stop_memory_get_real_usage']      = memory_get_usage(true);
			$_ENV['analysis'][$key][$name]['stop_memory_get_peak_usage']      = memory_get_peak_usage();
			$_ENV['analysis'][$key][$name]['stop_memory_get_peak_real_usage'] = memory_get_peak_usage(true);
		}
		return $_ENV['analysis'][$key][$name];
	}



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