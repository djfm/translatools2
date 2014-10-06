<?php

require_once dirname(__FILE__).'/parsing/PrestaShopTranslatableStringParser.php';
require_once dirname(__FILE__).'/TranslatableStringList.php';
require_once dirname(__FILE__).'/Translatools2FilesLister.php';

class PrestaShopTranslatableStringsExtractor
{
	public function __construct()
	{
	}

	public function reset()
	{
		$this->lists = array();
	}

	private function getLocatorsFor($path)
	{
		$locators = array();

		if (0 === strpos($path, 'modules/emailgenerator/templates/'))
		{
			$locators[] = array('type' => 'emailContent');
			return $locators;
		}

		if (pathinfo($path, PATHINFO_EXTENSION) === 'php')
			$locators[] = array('type' => 'emailSubject');

		$path = str_replace('\\', '/', $path);

		$admin_dirname = basename($this->getAdminDir());

		if (0 === strpos($path, 'classes/pdf/') ||
			0 === strpos($path, 'override/classes/pdf/') ||
			0 === strpos($path, 'pdf/') ||
			preg_match('#^themes/(?:[^/]+)/pdf/$#', $path)
		)
		{
			$locators[] = array('type' => 'pdf');
			return $locators;
		}



		$regular_admin_prefixes = array(
			'controllers/admin/',
			'override/controllers/admin/',
			'classes/helper',
			'classes/controller/AdminController.php',
			'classes/PaymentModule.php',
			$admin_dirname.'/themes/'
		);
		
		foreach ($regular_admin_prefixes as $prefix)
		{
			if (0 === strpos($path, $prefix))
			{
				$locators[] = array('type' => 'bo', 'data_for_key' => 'regular');
				return $locators;
			}
		}

		$specific_admin_files = array(
			'header.inc.php',
			'footer.inc.php',
			'index.php',
			'functions.php'
		);

		foreach ($specific_admin_files as $file)
		{
			if (0 === strpos($path, $admin_dirname.'/'.$file))
			{
				$locators[] = array('type' => 'bo', 'data_for_key' => 'specific');
				return $locators;
			}
		}

		$m = array();
		if (preg_match('#^modules/([^/]+)/#', $path, $m))
		{
			$locators[] = array('type' => 'modules', 'data_for_key' => null, 'module' => $m[1]);
		}
		elseif (preg_match('#^themes/([^/]+)/modules/([^/]+)/#', $path, $m))
		{
			$locators[] = array('type' => 'modules', 'data_for_key' => $m[1], 'module' => $m[2]);
		}
		elseif (preg_match('#^themes/([^/]+)/.*\.tpl$#', $path, $m))
		{
			$locators[] = array('type' => 'fo', 'theme' => $m[1]);
		}

		return $locators;
	}

	private function getParserFor($locator, $ext)
	{
		if ($ext === 'tpl')
			return new PrestaShopTranslatableStringParser('smarty');
		elseif ($ext === 'php')
		{
			if ($locator['type'] === 'bo')
			{
				if ($locator['data_for_key'] === 'regular')
					return new PrestaShopTranslatableStringParser('/\$this\s*->\s*l\s*\(\s*/');
				elseif ($locator['data_for_key'] === 'specific')
					return new PrestaShopTranslatableStringParser('/Translate\s*::\s*getAdminTranslation\s*\(\s*/');
			}
			elseif ($locator['type'] === 'modules')
			{
				return new PrestaShopTranslatableStringParser('/->\s*l\s*\(\s*/');
			}
			elseif ($locator['type'] === 'pdf')
			{
				return new PrestaShopTranslatableStringParser('/HTMLTemplate\w*\s*::\s*l\s*\(\s*/');
			}
			elseif ($locator['type'] === 'emailSubject')
			{
				return new PrestaShopTranslatableStringParser('/Mail\s*::\s*l\s*\(\s*/');
			}
			elseif ($locator['type'] === 'emailContent')
			{
				return new PrestaShopTranslatableStringParser('/\bt\s*\(\s*/');
			}
			
			throw new Exception("Could not find adequate parser.");
		}
		else
			throw new Exception("Invalid extension `$ext` for parser.");
	}

	private function getKeyFor($locator, $data_for_key, $file, $ext, $string)
	{
		if ($locator['type'] === 'bo')
		{
			if ($ext === 'php')
			{
				if ($data_for_key === 'regular')
				{
					$prefix_key = basename($file);
					if (strpos($file, 'Controller.php') !== false)
						$prefix_key = basename(Tools::substr($file, 0, -14));
					else if (strpos($file, 'Helper') !== false)
						$prefix_key = 'Helper';

					if ($prefix_key == 'Admin')
						$prefix_key = 'AdminController';

					if ($prefix_key == 'PaymentModule.php')
						$prefix_key = 'PaymentModule';

					return $prefix_key.md5($string);
				}
				elseif ($data_for_key === 'specific')
				{
					return 'index'.md5($string);
				}
			}
			elseif ($ext === 'tpl')
			{
				// get controller name instead of file name
				$prefix_key = Tools::toCamelCase(str_replace(basename($this->getAdminDir()).'/themes', '', $file), true);
				$pos = strrpos($prefix_key, '/');
				$tmp = Tools::substr($prefix_key, 0, $pos);

				if (preg_match('#controllers#', $tmp))
				{
					$parent_class = explode('/', $tmp);
					$override = array_search('override', $parent_class);
					if ($override !== false)
						// case override/controllers/admin/templates/controller_name
						$prefix_key = 'Admin'.Tools::ucfirst($parent_class[$override + 4]);
					else
					{
						// case admin_name/themes/theme_name/template/controllers/controller_name
						$key = array_search('controllers', $parent_class);
						$prefix_key = 'Admin'.Tools::ucfirst($parent_class[$key + 1]);
					}
				}
				else
					$prefix_key = 'Admin'.Tools::ucfirst(Tools::substr($tmp, strrpos($tmp, '/') + 1, $pos));

				// Adding list, form, option in Helper Translations
				$list_prefix_key = array('AdminHelpers', 'AdminList', 'AdminView', 'AdminOptions', 'AdminForm', 'AdminHelpAccess', 'AdminCalendar', 'AdminTree', 'AdminUploader', 'AdminDataviz', 'AdminKpi', 'AdminModule_list', 'AdminModulesList');
				if (in_array($prefix_key, $list_prefix_key))
					$prefix_key = 'Helper';

				// Adding the folder backup/download/ in AdminBackup Translations
				if ($prefix_key == 'AdminDownload')
					$prefix_key = 'AdminBackup';

				// use the prefix "AdminController" (like old php files 'header', 'footer.inc', 'index', 'login', 'password', 'functions'
				if ($prefix_key == 'Admin' || $prefix_key == 'AdminTemplate')
					$prefix_key = 'AdminController';

				return $prefix_key.md5($string);
			}
		}
		elseif ($locator['type'] === 'fo')
		{
			return Tools::substr(basename($file), 0, -4).'_'.md5($string);
		}
		elseif ($locator['type'] === 'modules')
		{
			return Tools::strtolower(
				'<{'.$locator['module'].'}prestashop>'.Tools::substr(basename($file), 0, -4).'_'.md5($string)
			);
		}
		elseif ($locator['type'] === 'pdf')
		{
			return 'PDF'.md5($string);
		}
		elseif ($locator['type'] === 'emailSubject')
		{
			return $string;
		}
		elseif ($locator['type'] === 'emailContent')
		{
			return $string;
		}

		throw new Exception("Could not compute key.");
	}

	public function extract()
	{
		$this->reset();

		$root = $this->getRootDir();

		$files = Translatools2FilesLister::recListFiles($root, '/\.(?:php|tpl)$/');

		foreach ($files as $path)
		{
			$ext = pathinfo($path, PATHINFO_EXTENSION);

			$locators = $this->getLocatorsFor($this->getPathRelativeToRoot($path));

			foreach ($locators as $locator)
			{
				$parser = $this->getParserFor($locator, $ext);
				$data_for_key = null;
				if (array_key_exists('data_for_key', $locator))
				{
					$data_for_key = $locator['data_for_key'];
					unset($locator['data_for_key']);
				}

				$parser->setFile($path);
				foreach ($parser->parse() as $string)
				{
					$this->recordString($locator, $this->getKeyFor($locator, $data_for_key, $path, $ext, $string), $string);
				}
			}
		}

		$this->extractFields();
		$this->extractTabs();

		return $this->lists;
	}

	private function extractFields()
	{
		$src = dirname(__FILE__).'/../data/fields';
		if (file_exists($src) && ($data = file_get_contents($src)))
		{
			$dictionary = array();
			foreach (preg_split('#\n+#', $data) as $line)
			{
				$left_right = explode(':', $line);
				if (count($left_right) === 2)
				{
					$classes = array_map('trim', explode(',', $left_right[0]));
					$fields = array_map('trim', explode(',', $left_right[1]));

					foreach ($classes as $class)
					{
						foreach ($fields as $field)
						{
							$this->recordString(
								['type' => 'field'],
								$class.'_'.md5($field),
								$field
							);
						}
					}
				}
			}
		}
		else
		{
			throw new Exception("Could not find fields file!");
		}
	}

	private function extractTabs()
	{

	}

	private function recordString($file_identifier_array, $key, $string)
	{
		$id = TranslatableStringList::makeID($file_identifier_array);
		if (!isset($this->lists[$id]))
			$this->lists[$id] = new TranslatableStringList($file_identifier_array);

		$this->lists[$id]->addString($key, $string);
	}

	public function getRootDir()
	{
		if (isset($this->root_dir))
			return $this->root_dir;
		elseif (defined('_PS_ROOT_DIR_'))
			return _PS_ROOT_DIR_;
		else
			throw new \Exception("No root dir defined.");
	}

	public function getAdminDir()
	{
		if (isset($this->admin_dir))
			return $this->admin_dir;
		if (defined('_PS_ADMIN_DIR_'))
			return _PS_ADMIN_DIR_;
		else
		{
			$root = $this->getRootDir();
			foreach (scandir($root) as $entry)
			{
				$candidate = "$root/$entry/ajax-tab.php";
				if (file_exists($candidate))
				{
					$this->admin_dir = $candidate;
				}
			}
			return $this->admin_dir;
		}

		throw new \Exception("Could not find admin dir.");
	}

	public function getPathRelativeToRoot($path)
	{
		return substr(realpath($path), strlen(realpath($this->getRootDir())) + 1);
	}
}