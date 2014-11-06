<?php
$project_base = realpath(dirname(__FILE__).'/../../../').'/';

// Path constants
define('BASEPATH', $project_base.'codeigniter/system/');
define('APPPATH',  $project_base.'expressionengine/');

define('LD', '{');
define('RD', '}');

define('IS_CORE', FALSE);
define('DEBUG', 1);

// Minor CI annoyance
function log_message() {}
function ee()
{
	static $EE;
	if ( ! $EE)	$EE = new stdClass();
	return $EE;
}

function &get_config($replace = array())
{
	static $_config;

	if (isset($_config))
	{
		return $_config[0];
	}

	// Fetch the config file
	if ( ! file_exists(APPPATH.'config/config.php'))
	{
		set_status_header(503);
		exit('The configuration file does not exist.');
	}
	else
	{
		require(APPPATH.'config/config.php');
	}

	// Does the $config array exist in the file?
	if ( ! isset($config) OR ! is_array($config))
	{
		set_status_header(503);
		exit('Your config file does not appear to be formatted correctly.');
	}

	// Are any values being dynamically replaced?
	if (count($replace) > 0)
	{
		foreach ($replace as $key => $val)
		{
			if (isset($config[$key]))
			{
				$config[$key] = $val;
			}
		}
	}

	return $_config[0] =& $config;
}

function config_item($item)
{
	static $_config_item = array();

	if ( ! isset($_config_item[$item]))
	{
		$config =& get_config();

		if ( ! isset($config[$item]))
		{
			return FALSE;
		}
		$_config_item[$item] = $config[$item];
	}

	return $_config_item[$item];
}

function is_loaded($class = '')
{
	static $_is_loaded = array();

	if ($class != '')
	{
		$_is_loaded[strtolower($class)] = $class;
	}

	return $_is_loaded;
}

function &load_class($class, $directory = 'libraries', $prefix = 'CI_')
{
	static $_classes = array();

	// Does the class exist?  If so, we're done...
	if (isset($_classes[$class]))
	{
		return $_classes[$class];
	}

	$name = FALSE;

	// Look for the class first in the native system/libraries folder
	// thenin the local application/libraries folder
	foreach (array(BASEPATH, APPPATH) as $path)
	{
		if (file_exists($path.$directory.'/'.$class.'.php'))
		{
			$name = $prefix.$class;

			if (class_exists($name) === FALSE)
			{
				require($path.$directory.'/'.$class.'.php');
			}

			break;
		}
	}

	// Is the request a class extension?  If so we load it too
	if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
	{
		$name = config_item('subclass_prefix').$class;

		if (class_exists($name) === FALSE)
		{
			require(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php');
		}
	}

	// Did we find the class?
	if ($name === FALSE)
	{
		// Note: We use exit() rather then show_error() in order to avoid a
		// self-referencing loop with the Excptions class
		set_status_header(503);
		exit('Unable to locate the specified class: '.$class.'.php');
	}

	// Keep track of what we just loaded
	is_loaded($class);

	$_classes[$class] = new $name();
	return $_classes[$class];
}

function get_instance()
{
	return ee();
}

function set_status_header($id) {}

// DB Stuff
require_once(BASEPATH.'database/DB.php');
ee()->db = DB('', NULL);

require $project_base."EllisLab/ExpressionEngine/Service/Autoloader.php";

$autoloader = new \EllisLab\ExpressionEngine\Service\Autoloader();
$autoloader->register();

// Setup Dependency Injection Container
ee()->di = new \EllisLab\ExpressionEngine\Service\DependencyInjectionContainer();

ee()->di->register('Model', function($di)
{
	$model_alias_path = APPPATH . 'config/model_aliases.php';
	$model_alias_service = new \EllisLab\ExpressionEngine\Service\AliasService('Model', $model_alias_path);

	return $di->singleton(function($di) use ($model_alias_service)
	{
        return new \EllisLab\ExpressionEngine\Service\Model\Factory(
            $model_alias_service,
            $di->make('Validation')
        );
	});
});

ee()->di->register('Validation', function($di)
{
	return $di->singleton(function($di)
	{
        return new \EllisLab\ExpressionEngine\Service\Validation\Factory();
	});
});

$api = ee()->di->make('Model');