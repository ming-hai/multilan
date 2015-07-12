<?php

/*
* e107 website system
* Multiple Languages Plugin for e107.
* Copyright (C) 2015 e107 Inc (e107.org)
* Released under the terms and conditions of the
* GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
*/


require_once('../../class2.php');
if (!getperms('P') || !e107::isInstalled('multilan'))
{
	header('location:'.e_BASE.'index.php');
	exit;
}

if(!empty($_GET['iframe']))
{
	define('e_IFRAME', true);
}

define('ADMIN_BING_ICON', "<img src='".e_PLUGIN."multilan/images/bing_16.png' alt='auto-translated' />");





class multilan_adminArea extends e_admin_dispatcher
{

	protected $modes = array(
		'news'		=> array(
			'controller' 	=> 'status_admin_ui',
			'path' 			=> null,
			'ui' 			=> 'status_form_ui',
			'uipath' 		=> null,
			'perm'          => null
		),
		'page'		=> array(
			'controller' 	=> 'status_admin_ui',
			'path' 			=> null,
			'ui' 			=> 'status_form_ui',
			'uipath' 		=> null,
			'perm'          => null
		),
		'faqs'		=> array(
			'controller' 	=> 'status_admin_ui',
			'path' 			=> null,
			'ui' 			=> 'status_form_ui',
			'uipath' 		=> null,
			'perm'          => null
		),
		'main'		=> array(
			'controller' 	=> 'status_admin_ui',
			'path' 			=> null,
			'ui' 			=> 'status_form_ui',
			'uipath' 		=> null,
			'perm'          => null
		),
	);


	protected $adminMenu = array(



		'news/list'			=> array('caption'=> 'News', 'perm' => 'P'),
		'page/list' 		=> array('caption'=> 'Page', 'perm' => 'P'),
		'faqs/list' 		=> array('caption'=> 'FAQs', 'perm' => 'P'),
		'option3'           => array('divider'=>true),
		'main/core'         => array('caption'=>'Core Translator', 'perm'=>'0'),
		'main/editor'         => array('caption'=>'Core Editor', 'perm'=>'0'),
		'option2'           => array('divider'=>true),
		'main/prefs' 	    => array('caption'=> LAN_PREFS, 'perm' => '0'), // Preferences
		'main/tools'       =>array('caption'=>'Tools', 'perm'=>'0'),
		'main/tables'       => array()
	);


	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'				
	);	
	
	protected $menuTitle = 'Multiple Languages';


	function init()
	{
		$this->adminMenu['main/tables'] = array('caption'=>'Tables', 'modal-caption'=>'Database Tables', 'perm'=>0, 'modal'=>true, 'uri'=>e_ADMIN.'language.php?mode=main&action=db&iframe=1');

		e107::css('inline', " #etrigger-batch { width: 300px } ");

		$sitelanguage = e107::getPref('sitelanguage');
		if(e_LANGUAGE != $sitelanguage)
		{

			e107::getMessage()->addWarning("Please switch to ".$sitelanguage." to view.");
			$this->adminMenu = array();

			return false;
		}

		if(e_AJAX_REQUEST)
		{
			$this->handleAjax();
		}

	}


	private function handleAjax()
	{


		if(!empty($_GET['itemid']) && !empty($_GET['language']) &&  !empty($_GET['type']) && !empty($_GET['table']))
		{
			switch($_GET['type'])
			{
				case "copy":
					if($this->copyItem($_GET['table'], $_GET['language'], $_GET['itemid']))
					{
						echo ADMIN_FALSE_ICON;
					}
					else
					{
						echo ADMIN_WARNING_ICON;
					}
					break;

				case "delete":
					if($this->deleteItem($_GET['table'], $_GET['language'], $_GET['itemid']))
					{
						echo '&middot;';
					}
					else
					{
						echo ADMIN_WARNING_ICON;
					}
					break;

				case "bing":
					if(!$this->copyItem($_GET['table'], $_GET['language'], $_GET['itemid']))
					{
						//echo ADMIN_WARNING_ICON;
						//exit;

					}

					if($this->translateItem($_GET['table'], $_GET['language'], $_GET['itemid']))
					{
						echo ADMIN_BING_ICON;
					}
					else
					{
						echo ADMIN_WARNING_ICON;
					}
					break;

			}


		}


		if(!empty($_GET['lanid']) && !empty($_GET['language']) )
		{
			if($this->translateFile($_GET['lanid'],$_GET['language']))
			{
				echo ADMIN_TRUE_ICON; // e107::getParser()->toGlyph('fa-check');
			}
			else
			{
				echo ADMIN_WARNING_ICON;
			}
		}

		exit;

	}



	private function deleteItem($type, $lan, $id)
	{

		$table = '';
		$pid = '';

		switch($type)
		{
			case "news":
				$table = 'news';
				$pid = 'news_id';

				break;

			case "page":
				$table = 'page';
				$pid = 'page_id';

				break;

			case "faqs":
				$table = 'faqs';
				$pid = 'faq_id';


				break;
		}

		if(empty($table) || empty($pid))
		{
			return false; // "Invalid";
		}

		$lanTable = "lan_".strtolower($lan)."_".$table;

		if(e107::getDb()->delete($lanTable, $pid. ' = '.intval($id))) // already exists.
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param $type
	 * @param $lan
	 * @param $id
	 * @return bool
	 */
	private function translateItem($type, $lan, $id)
	{


		$table = '';
		$fields = '';
		$pid = '';

		switch($type)
		{
			case "news":
				$table = 'news';
				$pid = 'news_id';
				$fields = array('news_title', 'news_body', 'news_extended', 'news_meta_description', 'news_summary'); // translatable fields.
				$ucfield = 'news_class';
				break;

			case "page":
				$table = 'page';
				$pid = 'page_id';
				$fields = array('page_title', 'page_text', 'menu_title', 'menu_text');
				$ucfield = 'page_class';
				break;

			case "faqs":
				$table = 'faqs';
				$pid = 'faq_id';
				$fields = array('faq_question', 'faq_answer');
			//	$ucfield = 'page_class';

				break;
		}

		if(empty($fields) || empty($pid))
		{
			return false; // "Invalid";
		}

		$sql            = e107::getDb();
		$bng            = e107::getSingleton('bingTranslate', e_PLUGIN."multilan/bing.class.php");
		$languageCode   = e107::getLanguage()->convert($lan);
		$tp = e107::getParser();
		$row = $sql->retrieve($table, implode(",",$fields), $pid. ' = '.intval($id));

		$update = array();

		foreach($row as $field=>$value)
		{
			if(!empty($value))
			{
				$html = false;
			//	$newValue = $bng->getTranslation('en', $languageCode, e107::getParser()->toHtml($value,true));
				if($tp->isHtml($value))
				{
					$value = str_replace(array("[html]","[/html]"), "", $value);
					$html = true;
				}

				$newValue = $bng->getTranslation('en', $languageCode, $value);

				if($html == true)
				{
					$update[$field] = "[html]".$newValue."[/html]";
				}
				else
				{
					$update[$field] = $newValue;
				}
			}
		}

		if(empty($update))
		{
			return false;
		}

		$autoClass = e107::pref('multilan', 'autotranslatedClass');
		if(!empty($ucfield) && !empty($autoClass))
		{
			$update[$ucfield] = $autoClass;
		}

		$update['WHERE'] = $pid.' = '.intval($id). ' LIMIT 1';

		$lanTable = "lan_".strtolower($lan)."_".$table;

		if($sql->update($lanTable, $update))
		{
			return true;
		}

		return false;
	}


	/**
	 * @param $type
	 * @param $lan
	 * @param $id
	 * @return bool|string
	 */
	private function copyItem($type, $lan, $id)
	{

		if(empty($type))
		{
			return "Type not set";
		}

		if(empty($lan))
		{
			return "Language not set";
		}

		if(empty($id))
		{
			return "Item ID not set";
		}

		$pid = '';
		$method = '';
		$table = '';

		switch($type)
		{
			case "news":
				$table = 'news';
				$pid = 'news_id';
				$method = 'syncNews';
				break;

			case "page":
				$table = 'page';
				$pid = 'page_id';
				$method = 'syncPage';
				break;

			case "faqs":
				$table = 'faqs';
				$pid = 'faq_id';
				$method = 'syncFAQs';
				break;
		}

		if(empty($pid) || empty($method) || empty($table))
		{
			return false;
		}

		$lanTable = "lan_".strtolower($lan)."_".$table;

		if(e107::getDb()->select($lanTable,'*', $pid. ' = '.intval($id))) // already exists.
		{
			// echo "Already exists";
			return false;
		}


		$mlan = new multilan_copymodule;
		$data = array();
		$data['newData'] = array($pid=>$id);
		$languages = array($lan);

	//	return print_a($data,true);


		$mlan->$method($data, null,  $languages); // eg syncNews.

		e107::getMessage()->resetSession();

		//TODO The Bing translation part.

		return true;
	}


	private function translateFile($key, $lan)
	{
		$bng = e107::getSingleton('bingTranslate', e_PLUGIN."multilan/bing.class.php");
		$lng = e107::getLanguage();

		$id             = $_GET['lanid'];
		$languageCode   = e107::getParser()->filter($_GET['language'], 'w');
		$language       = $lng->convert($languageCode);
		$newFile        = str_replace(array('-core-','-plugin-','English'), array(e_LANGUAGEDIR.'English/', e_PLUGIN, $language), $_SESSION['multilan_lanfilelist'][$id]);


		$srch = array('en', 'GB', 'US');
		$repl = array($languageCode, strtoupper($languageCode), strtoupper($languageCode));

		foreach($_SESSION['multilan_lanfiledata'][$id] as $k=>$v)
		{

			if($k == 'LC_ALL' || $k == 'CORE_LC' || $k == 'CORE_LC2')
			{
				$translation = str_replace($srch,$repl, $v);
			}
			else
			{
				$translation = $bng->getTranslation('en', $languageCode, $v);
			}

			$this->writeFile($newFile, $k, $translation);
		}


		return true;


	}




	private function writeFile($file, $key,$value)
	{
		$output = '';

		$dir =  dirname($file);

		if(!is_dir($dir))
		{
			mkdir($dir, 0755);
		}


		if(!file_exists($file))
		{
			$output .= chr(60)."?php\n\n";
			$output .= "// Bing-Translated Language file \n";
			$output .= "// Generated for e107 v2.x by the Multi-Language Plugin\n";
			$output .= "// https://github.com/e107inc/multilan\n\n";

		}
		else
		{
			return false;
		}

		$output .= 'define("'.$key.'", "'.$value.'");';
		$output .= "\n";

		file_put_contents($file, $output, FILE_APPEND);

	}



}

class status_admin_ui extends e_admin_ui
{
		protected $pluginTitle	= 'Multiple Languages'; // "News"
		protected $pluginName	= 'multilan';
	//	protected $table 		= "";
	//	protected $pid			= null;
		protected $perPage      = 10; //no limit
		protected $batchDelete  = false;
		protected $batchCopy    = false;
		protected $batchOptions = array();
	//	protected $listOrder	= null;

		protected $fields       = array();
		protected $fieldpref    = array();

		public $langData        = array();
		public $statusField     = null;  // field name.
		public $statusLink      = null;
		public $statusTitle     = null; // fieldName

		protected $preftabs        = array("Data Sync", "Offline", "Bing" );

		protected $prefs = array(
			'syncLanguages'         => array('title'=> "Sync Table Content",  'tab'=>0, 'type'=>'method', 'data'=>'str'),
			'untranslatedClass'	    => array('title'=> "Untranslated Class", 'tab'=>0, 'type'=>'userclass', 'writeParms'=>array('default'=>'TRANSLATE_ME')),
			'autotranslatedClass'	    => array('title'=> "Auto-Translated Class", 'tab'=>0, 'type'=>'userclass', 'writeParms'=>array('default'=>'REVIEW_ME')),


			'offline_languages'     => array('title' => "Offline", 'tab'=>1, 'type'=>'method', 'data'=>'str'),
			'offline_excludeadmins' => array('title'=>'Exclude Admins from redirect', 'tab'=>1, 'type'=>'boolean'),
			'language_navigation'    => array('title'=>"Language Navigation", 'type'=>'method', 'tab'=>1),
			'bing_translator'       => array('title' => 'Frontend Auto-Translator', 'type'=>'dropdown', 'tab'=>2,'writeParms'=>array(0=>'Off', 'auto'=>'Auto', 'notify'=>'Notify')),

			'bing_exclude_installed'=>  array('title' => 'Exclude installed languages', 'type'=>'boolean', 'tab'=>2, 'help'=>"If enabled, will exclude languages currently installed in e107 from the available bing translations."),
			'bing_client_id'    => array('title'=>"Client ID", 'type'=>'text', 'data'=>'str',  'tab'=>2,  'writeParms'=>array('rightCellClass'=>'form-inline','post'=>" <a class='btn btn-primary btn-mini btn-xs' target='_blank' href='https://msdn.microsoft.com/en-us/library/mt146806.aspx'>More Info.</a>")),
			'bing_client_secret'    => array('title'=>"Client Secret", 'type'=>'text', 'data'=>'str', 'tab'=>2, 'writeParms'=>array('size'=>'xxlarge')),
		//	'retain sefurls'	  => array('title'=> "Untranslated Class", 'tab'=>0, 'type'=>'userclass' ),
		);


		protected $languageTables = array();
		protected $totalCharCount  = 0;

		function init()
		{

			$this->languageTables = e107::getDb()->db_IsLang(array('news','page','faqs'),true);

			if(e107::isInstalled("faqs"))
			{
				$this->initFaqsPrefs();
			}

			if(!empty($_POST['generate_lanlinks']))
			{
				$this->generateSystemLANS();
			}

			if($this->getMode()== 'main')
			{
				return;
			}

			$js = <<<JS
				$('#e--execute-batch').on('click', function(){

					tmp = $('#etrigger-batch').val().split('_');


    				$( "#uiAlert" ).html("<div class='alert fade in alert-success'>Processing</div>").show();
						// .fadeIn({ duration: 3000,  queue: false })

					var type = tmp[0];
					var table = tmp[1];
					var lancode = tmp[2];
					var handler = window.location.href;

					if(lancode == '')
					{
						alert("No Language Selected");
						return false;
					}

					$('#plugin-multilan-list-form').find('.lanfile').each(function(e){

						var indicator = $(this).attr('id');
						tmp = indicator.split('-');

						var language = tmp[1];
						var id = tmp[2];

						if(language != lancode)
						{
							return;
						}

						var cbox = '#multiselect-'+id+'-'+id;

						if($(cbox).is(":not(:checked)")){

	                        return;
	                    }

						$('#'+indicator).html('<i class="fa fa-spin fa-spinner"></i>');



					    $.ajax({
						type: 'get',
						async: false,
						url: handler,
						data: { itemid: id, language: lancode, table: table, type: type},
						success: function(data)
							{
									 // 	console.log(data);
								//	 alert('Done:'+ theid);
								$('#'+indicator).html(data);
								$(cbox).removeAttr('checked');

							//	$('tr#row-'+id);
							//	 $('#status-'+theid).html(data);

								//	$('#uiAlert').notify({
								//		type: 'success',
				                //        message: { text: 'Completed' },
				                //        fadeOut: { enabled: true, delay: 2000 }
				                //    }).show();

							 }
						});



					});

					 $('#uiAlert').fadeOut(2000);



					return false;
				});


JS;

			e107::js('footer-inline', $js);



			if($this->initAll() === false)
			{
				return false;
			}



		}

/*
 *      //XXX No longer used - using ajax now.
		function handleListBatch($selected, $value)
		{

		//	print_a($selected);
		//	echo "Val: ".print_a($value,true);
		//	e107::getMessage()->addInfo("Translating...");
		//	e107::getMessage()->addInfo(print_a($selected,true));
		//	e107::getMessage()->addInfo(print_a($value,true));


			list($mode,$type, $language) = explode("_",$value);


			if($mode == 'copy')
			{

				$mode = $this->getMode();
				$pid = '';
				$method = '';

				switch($mode)
				{
					case "news":
						$pid = 'news_id';
						$method = 'syncNews';
					break;

					case "page":
						$pid = 'page_id';
						$method = 'syncPage';
						break;

					case "faqs":
						$pid = 'faq_id';
						$method = 'syncFAQs';
						break;
				}

				if(empty($pid) || empty($method))
				{
					return false;
				}

				$mlan = new multilan_copymodule;
				$data = array();
				foreach($selected as $id)
				{
					$data['newData'] = array($pid=>$id);
					$languages = array($language);
					$mlan->$method($data, null,  $languages); // eg syncNews.
				}

				e107::getRedirect()->go(e_REQUEST_URI);
			}
		}
*/


		/**
		 * Initial all Language-Sync Options for the current mode.
		 */
		private function initAll()
		{
			$lng = e107::getLanguage();

			$this->fields['checkboxes'] =  array('title'=> '',	'type' => null, 'width' =>'5%', 'forced'=> true, 'thclass'=>'center', 'class'=>'center');

			$languages = $lng->installed();

			sort($languages);

			$sitelanguage = e107::getPref('sitelanguage');

			if(e_LANGUAGE != $sitelanguage)
			{
				$this->pid                  = 'news_id';
				return false;
			}

			$mode =$this->getMode();
			$initType = 'init'.ucfirst($mode);

			$this->$initType(); // eg. initNews();

			$this->langData = $this->getLangData($languages);

			foreach($languages as $k=>$v)
			{
				if($v == $sitelanguage)
				{
					continue;
				}

				$key = $lng->convert($v);


				$this->fields[$key] = array('title'=> $key,	'type' => 'method', 	'data' => 'str',  'method'=>'findTranslations',	'width' => '100px',	'thclass' => 'center', 'class'=>'center', 'readonly'=>FALSE,	'batch' => FALSE, 'filter'=>FALSE);
			}

			foreach($languages as $v)
			{
				$lowerLang = strtolower($v);
				if($v == $sitelanguage || !isset($this->languageTables[$lowerLang][MPREFIX.$mode ]))
				{
					continue;
				}

				$this->batchOptions['delete_'.$mode.'_'.$v] = "Delete from ".$v.' table';
			}


			foreach($languages as $v)
			{
				$lowerLang = strtolower($v);
				if($v == $sitelanguage || !isset($this->languageTables[$lowerLang][MPREFIX.$mode ]))
				{
					continue;
				}

				$this->batchOptions['copy_'.$mode.'_'.$v] = "Copy ".$sitelanguage." into ".$v.' table';
			}

			$bingClient = e107::pref('multilan', 'bing_client_id');
			if(!empty($bingClient))
			{
				foreach($languages as $v)
				{
					$lowerLang = strtolower($v);
					if($v == $sitelanguage || !isset($this->languageTables[$lowerLang][MPREFIX.$mode ]))
					{
						continue;
					}

					$this->batchOptions['bing_'.$mode."_".$v] = "Translate into ".$v." table";
				}
			}



			$this->fields['options']    = array('title'=> 'Status',			'type' => 'method',		'nolist'=>true,		'width' => '10%', 'forced'=>TRUE, 'thclass' => 'center last', 'class' => 'center');
			$this->fieldpref = array_keys($this->fields);


		}


		public function initNews()
		{
			$this->pid                  = 'news_id';
			$this->table                = 'news';
			$this->listOrder            = 'news_id DESC';
			$this->statusField          = 'news_class';
			$this->statusLink           = "{e_BASE}news.php?item.{ID}"; // (no SEFs)
			$this->statusTitle          = "news_title";

			$this->fields['news_id']        = array('title'=> LAN_ID,			'type' => 'number',			'width' =>'5%', 'forced'=> TRUE, 'readonly'=>TRUE);
			$this->fields['news_title']     = array('title'=> LAN_TITLE,		'type' => 'text', 			'data' => 'str',		'width' => 'auto',	'thclass' => 'left', 'class'=>'left',  'readonly'=>FALSE,	'batch' => FALSE, 'filter'=>FALSE);
			$this->fields['news_datestamp'] = array('title'=> LAN_DATESTAMP,	'type' => 'datestamp', 			'data' => 'str',		'width' => 'auto',	'thclass' => 'left', 'class'=>'left',  'readonly'=>FALSE,	'batch' => FALSE, 'filter'=>FALSE);
			$this->fields['news_class']     = array( 'nolist'=>true ); // to retrieve it for comparison.
		}


		public function initPage()
		{
			$this->pid                  = 'page_id';
			$this->table                = 'page';
			$this->listOrder            = 'page_id DESC';
			$this->statusField          = 'page_class';
			$this->statusLink           = "{e_BASE}page.php?id={ID}"; // (no SEFs)
			$this->statusTitle          = "page_title";

			$this->fields['page_id']        = array('title'=> LAN_ID,			'type' => 'number',			'width' =>'5%', 'forced'=> TRUE, 'readonly'=>TRUE);
			$this->fields['page_title']     = array('title'=> LAN_TITLE,		'type' => 'text', 			'data' => 'str',		'width' => 'auto',	'thclass' => 'left', 'class'=>'left',  'readonly'=>FALSE,	'batch' => FALSE, 'filter'=>FALSE);
			$this->fields['page_datestamp'] = array('title'=> LAN_DATESTAMP,	'type' => 'datestamp', 			'data' => 'str',		'width' => 'auto',	'thclass' => 'left', 'class'=>'left',  'readonly'=>FALSE,	'batch' => FALSE, 'filter'=>FALSE);
			$this->fields['page_class']     = array( 'nolist'=>true ); // to retrieve it for comparison.

		}


		public function initFaqs()
		{
			$this->pid                  = 'faq_id';
			$this->table                = 'faqs';
			$this->listOrder            = 'faq_id DESC';
			$this->statusField          = 'faq_parent';
			$this->statusLink           = "{e_PLUGIN}faqs/faqs.php?id={ID}"; // (no SEFs)
			$this->statusTitle          = "faq_question";

			$this->fields['faq_id']        = array('title'=> LAN_ID,			'type' => 'number',			'width' =>'5%', 'forced'=> TRUE, 'readonly'=>TRUE);
			$this->fields['faq_question']     = array('title'=> LAN_TITLE,		'type' => 'text', 			'data' => 'str',		'width' => 'auto',	'thclass' => 'left', 'class'=>'left',  'readonly'=>FALSE,	'batch' => FALSE, 'filter'=>FALSE);
			$this->fields['faq_datestamp'] = array('title'=> LAN_DATESTAMP,	'type' => 'datestamp', 			'data' => 'str',		'width' => 'auto',	'thclass' => 'left', 'class'=>'left',  'readonly'=>FALSE,	'batch' => FALSE, 'filter'=>FALSE);
			$this->fields['faq_parent']     = array( 'nolist'=>true ); // to retrieve it for comparison.


		}



		private function initFaqsPrefs()
		{
			$sql = e107::getDb();
			$faqCats = array();
			$sql->select('faqs_info', 'faq_info_id,faq_info_title');
			while($row = $sql->fetch())
			{
				$id = $row['faq_info_id'];
				$faqCats[$id] = 	$row['faq_info_title'];
			}

			$this->prefs['untranslatedFAQCat'] = array('title'=> "Untranslated FAQ Category", 'tab'=>0, 'type'=>'dropdown' );
			$this->prefs['untranslatedFAQCat']['writeParms']['optArray'] = $faqCats;

		}



		private function generateSystemLANS()
		{
			$sql = e107::getDb();
			$frm = e107::getForm();
			$rows = $sql->retrieve('links','*','',true);

			$writeFile = e_SYSTEM."lans/English_custom.php";

			$text = '<?php';

			$text .= "\n// e107 Custom Language File \n\n";
			$update = array();

			foreach($rows as $row)
			{
				if(empty($row['link_name']))
				{
					continue;
				}

				$name = str_replace('-','_',$frm->name2id($row['link_name']));
				$key = "CUSTLAN_".strtoupper($name);
				$text .= 'define("'.$key.'", "'.$row['link_name'].'");';
				$text .= "\n";
				$id = $row['link_id'];
				$update[$id] = $key;

				// $sql->update('links', 'link_name= "'.$key.'" WHERE link_id = '.$row['link_id'].' LIMIT 1');
			}

			if(!is_dir(e_SYSTEM."lans"))
			{
				mkdir(e_SYSTEM.'lans',0755);
			}

			if(file_exists($writeFile))
			{
				e107::getMessage()->addWarning("File already exists");
				e107::getMessage()->addWarning(print_a($text,true));
				return;
			}


			if(file_put_contents($writeFile, $text))
			{
				foreach($update as $id=>$val)
				{
					$sql->update('links', 'link_name= "'.$val.'" WHERE link_id = '.$id.' LIMIT 1'); //TODO add a checkbox preference for this.
				}

				e107::getMessage()->addSuccess(LAN_CREATED);
			}
			else
			{
				e107::getMessage()->addError(LAN_CREATED);
			}


		}



		/**
		 * @param array $languages
		 * @return array
		 */
		function getLangData($languages)
		{
			$sql2 = e107::getDb('sql2');
			$lng = e107::getLanguage();

			$fields = $this->fields;

			$sitelanguage = e107::getPref('sitelanguage');

			unset($fields['checkboxes'], $fields['options']);

			$selectFields = implode(",", array_keys($fields));

			$from = $this->getQuery('from',0);

			$query = "SELECT ".$selectFields." FROM #lan_{LANGUAGE}_".$this->table." WHERE ".$this->pid." !='' ORDER BY ".$this->listOrder; // ."  LIMIT ".$from.", ".$this->perPage;

			$langData = array();
			foreach($languages as $langu)
			{
				if($langu == $sitelanguage || !$sql2->isTable($this->table, $langu))
				{
					continue;
				}

				$lg = strtolower($langu);
				$qry = str_replace("{LANGUAGE}",$lg,$query);
				$key = $lng->convert($langu);
				$res =$sql2->gen($qry);
				while($row = $sql2->fetch())
				{
					$langData[$key][] = $row;
				}
				if($res === 0) // table empty but not missing.
				{
					$langData[$key][] = array();
				}
			}
			// print_a($langData['fr']);

			return $langData;
		}





		public function corePage()
		{
			$frm = e107::getForm();
			$lng = e107::getLanguage();
			$bng = e107::getSingleton('bingTranslate', e_PLUGIN."multilan/bing.class.php");

			if(!empty($_GET['lanlanguage']))
			{
				$title = $lng->convert($_GET['lanlanguage']);
			}
			else
			{
				$title = "Choose Language";
			}
			$this->addTitle($title);

			$_SESSION['multilan_lanfilelist'] = array();

			$languageList = $bng->supportedLanguages();

			unset($languageList['en']);

			require_once(e_ADMIN."lancheck.php");
			$lck = new lancheck;
			$lck->thirdPartyPlugins(false);


			$text = $frm->open('corePage', 'get');

			$text .= "<div class='alert-block'>";
			$text .= $frm->select('lanlanguage', $languageList, varset($_GET['lanlanguage']), array('class'=>'filter'), 'Select Language');

			if(!empty($_GET['lanlanguage']))
			{
				$text .= "<button type='button' data-loading='".e_IMAGE."generic/loading_32.gif' class='btn btn-primary e-ajax-post' value='Download and Install' data-src='".e_SELF."' ><span>Bing Translate</span></button>";
			}
			$text .= "</div>";

			$text2 = '';

			if(!empty($_GET['lanlanguage']))
			{
				$tmp = $lck->get_comp_lan_phrases(e_LANGUAGEDIR."English/","English",1);
				unset($tmp['bom']);
				$text2 .= $this->renderTable($tmp, 'core');

				$tmp2 = $lck->get_comp_lan_phrases(e_PLUGIN,"English",3);
				unset($tmp2['bom']);
				$text2 .= $this->renderTable($tmp2, 'plugin');
			}

			$text2 .= $frm->close();


			$js = <<<JS

				$('.e-ajax-post').on('click', function(){

		            var form		= $(this).closest('form').attr('id');
		            var target 		= $(this).attr('data-target'); // support for input buttons etc.
		            var loading 	= $(this).attr('data-loading'); // image to show loading.
		            var handler		= $(this).attr('data-src');
		     		 // var data 	= $('#'+form).serialize();

					var lancode = $('#lanlanguage').val();

					if(lancode == '')
					{
						alert("No Language Selected");
						return false;
					}

					$('#' + form).find('.lanfile').each(function(e){
						val = $(this).text();
						theid = $(this).attr('id');

						if($('#check-'+theid).is(":not(:checked)")){

	                     //   alert("Checkbox is not checked."+theid);
	                        return;
	                    }

					//	$('#status-'+theid).html("<img src='"+loading+"' alt='' />");

						$('#status-'+theid).html('<i class="fa fa-spin fa-spinner"></i>');

				//		 alert(theid);
					    $.ajax({
						type: 'get',
						async: false,
						url: handler,
						data: { lanid: theid, language: lancode},
						success: function(data)
							{
									 // 	console.log(data);
								//	 alert('Done:'+ theid);
								 $('#status-'+theid).html(data);
							 }
						});



					});

					alert('Complete');
					return false;

				});
JS;





			e107::js('footer-inline', $js);


			$count = ($this->totalCharCount) ? "<div class='right' style='margin-top: -40px; padding: 10px;'><small>Total Chars: ".number_format($this->totalCharCount)."</small></div>" : '';

			return  $text . $count . $text2;

		// 	print_a($tmp);

		}

		private function renderTable($data, $mode)
		{
			$frm = e107::getForm();
			$lng = e107::getLanguage();
			$languageCode   = e107::getParser()->filter($_GET['lanlanguage'], 'w');
			$language       = $lng->convert($languageCode);

			if($mode == 'core')
			{
				$toggleButton= $frm->checkbox_toggle('tog', 'lancheckbox');
			}
			else
			{
				$toggleButton= '';
			}

		//	$toggleButton = '<input name="e-column-toggle" value="jstarget:lancheckbox" class="btn btn-small checkbox toggle-all" type="button" />';


			$text = "<table class='table table-striped adminlist'>
				<colgroup>
					<col />
					<col style='width:10%' />
					<col style='width:50%' />
				</colgroup>
				<thead>
				<tr class='first'>
				<th>".$toggleButton." <span style='vertical-align: bottom;'>Language File</span></th>
				<th class='right' style='padding-right:40px'>Character Count</th>
				<th>".LAN_STATUS."</th></tr>
				</thead>";



			foreach($data as $file => $lans)
			{
				$id = $frm->name2id($file);
				$status = '-';

				$_SESSION['multilan_lanfilelist'][$id] = '-'.$mode.'-'.$file;
				$_SESSION['multilan_lanfiledata'][$id] = $lans;

				$charCount = $this->countChars($lans);


				if(!empty($language))
				{

					$newFile  = str_replace(array('-core-','-plugin-','English'), array(e_LANGUAGEDIR.'English/', e_PLUGIN, $language), $_SESSION['multilan_lanfilelist'][$id]);

					if(file_exists($newFile))
					{
						$status = ADMIN_TRUE_ICON; // e107::getParser()->toGlyph('fa-check');
					}
				}

				$text .= "
				<tr>

					<td id='".$id."' class='lanfile'>
						<label class='checkbox'><input name='lancheckbox[]' value='1' id='check-".$id."' type='checkbox'>".$file."</label>
					</td>
					<td class='right' style='padding-right:40px'>".$charCount."</td>
					<td id='status-".$id."'>".$status."</td>
				</tr>";
			}

			$text .= "</table>";

			return $text;
		}

		private function countChars($lans)
		{
			$count = 0;

			foreach($lans as $value)
			{
				$count += strlen($value);
			}

			$this->totalCharCount += $count;

			if($count > 1500)
			{
				return "<span title='high character count' class='label label-important'>".$count."</span>";
			}

			return $count;
		}


		public function editorPage()
		{

			$lck = e107::getSingleton('lancheck', e_ADMIN."lancheck.php");

			if($return = $lck->init())
			{
				if($return['file'])
				{
					$this->addTitle($return['file']);
				}

				return $return['text'];
			}


			// show_packs();

			return $lck->showLanguagePacks();

		}



		public function toolsPage()
		{

			$frm = e107::getForm();

			$text2 = $frm->open('multilan-links');
			$text2 .= "<table class='table table-bordered'><tr><td>
		This will generate LAN definitions for all your sitelinks and store them in a custom language file within your system folder and then update all your link names to use them.
		</td>
		<td>

			".$frm->admin_button('generate_lanlinks', 'no-value', 'delete', "Generate LANs")."
			</td></tr>
			</table>";

			$text2 .= $frm->close();

			return $text2;

		}

	}

	class status_form_ui extends e_admin_form_ui
	{


		function language_navigation($curVal,$mode)
		{

			$lng = e107::getLanguage();
			$frm = e107::getForm();
			$languages = $lng->installed();

			sort($languages);

			$text = "<table class='table table-striped table-bordered table-condensed'>
					<colgroup>
					<col style='width:20%' />
					<col style='width:80%' />
					</colgroup>

		        <tr>
			        <th>Language</th>
			        <th>Sitelink Status</th>
		        </tr>";

			foreach($languages as $v)
			{
				$text .= "<tr><td>".$v."</td><td>".$frm->radio_switch('language_navigation['.$v.']', varset($curVal[$v],1))."</td></tr>";
			}

			$text .= "</table>";

			return $text;

		}


		function syncLanguages($curVal) // preference.
		{

			$frm = e107::getForm();
			$sql = e107::getDb('sql2');
			$modeData = $this->getController()->getDispatcher()->getMenuData();

			$text2 = "<table class='table table-striped table-condensed table-bordered'>";

			$options = array();
			$tableInstalled = array();

			$opts = e107::getLanguage()->installed();

			foreach($opts as $v)
			{
				if($v == 'English')
				{
					continue;
				}

				$options[$v] = $v;

				foreach($modeData as $key=>$val)
				{
					list($mode,$action) = explode("/",$key);
					if($action != 'list')
					{
						continue;
					}

					$tableInstalled[$mode][$v] = $sql->db_Table_exists($mode,$v);
				}
			}


			foreach($modeData as $k=>$v)
			{
				list($mode,$action) = explode("/",$k);
				if($action != 'list')
				{
					continue;
				}

				$lanOpts = $options;

				foreach($lanOpts as $keyOpt=>$opt)
				{
					if(empty($tableInstalled[$mode][$opt]))
					{
						$lanOpts[$opt] .= ' (not installed)' ; // " <span class='label label-warning'>Not installed</span>";
					}
				}

				$text2 .= "<tr><td>".$v['caption']."</td><td>".$frm->checkboxes('syncLanguages['.$mode.'][]', $lanOpts, varset($curVal[$mode]), array('useKeyValues'=>1));

				$text2 .= "</td></tr>";
			}


		//	$text2 .= "<tr><td>Pages</td><td>".$frm->checkboxes('syncLangs[page][]', $options, $prefs['page'], array('useKeyValues'=>1))."</td></tr>";
		//	$text2 .= "<tr><td>FAQs</td><td>".$frm->checkboxes('syncLangs[faqs][]', $options, $prefs['faqs'], array('useKeyValues'=>1))."</td></tr>";



			$text2 .= "</table>";

			return $text2;
		}



		function findTranslations($curval,$mode,$att)
		{

			$langs = $att['field'];

			$lng = e107::getLanguage();
			$tp = e107::getParser();

			$langData       = $this->getController()->langData;
			$row            = $this->getController()->getListModel()->getData();
			$pid            = $this->getController()->getPrimaryName();
			$transField     = $this->getController()->statusField;
			$statusLink     = $this->getController()->statusLink;
			$statusTitle    = $this->getController()->statusTitle;

			$itemid             = $row[$pid];

		//	print_a($row);

			if(!isset($langData[$langs]))
			{
				return "&nbsp;";
			}

			$language = e107::getLanguage()->convert($langs);

			$text = "<b>&middot;</b>";

			foreach($langData[$langs] as $rw)
			{

				if(($rw[$pid]==$row[$pid]))
				{
				//	print_a('lang: '.$rw[$transField].' => orig:'.$row[$transField]);
				//	$icon = ($rw[$transField] == $row[$transField]) ?  ADMIN_TRUE_ICON : ADMIN_FALSE_ICON;
					$icon = $this->getStatusIcon($rw,$row);
					$link = $tp->replaceConstants(str_replace('{ID}', $rw[$pid], $statusLink),'full');
					$subUrl = $lng->subdomainUrl($langs, $link);

					$text =  "<a class='e-modal' href='".$subUrl."' title=\"".$rw[$statusTitle]."\">".$icon."</a></span>";
					break;
				}
			}

			return "<span id='status-".$language."-".$itemid ."' class='lanfile'>".$text."</span>";


		}

		function getStatusIcon($rw,$row)
		{
			$transField     = $this->getController()->statusField;
			$reviewField    = e107::pref('multilan','autotranslatedClass');

			if($rw[$transField] == $reviewField)
			{
				return ADMIN_BING_ICON;
			}

			if($rw[$transField] == $row[$transField])
			{
				return ADMIN_TRUE_ICON;
			}

			return ADMIN_FALSE_ICON;
		}



		function offline_languages($curval)
		{
			$lng = e107::getLanguage();


			$text = "<table class='table table-striped table-bordered table-condensed'>
					<colgroup>
					<col style='width:20%' />
					<col style='width:10%' />
					<col style='width:10%' />
					<col style='width:60%' />
					</colgroup>



";

			$text .="
		        <tr>
			        <th>Language</th>
			        <th class='center'>Visible</th>
			        <th class='center'>Maintenance</th>
			        <th>Redirect</th>
		        </tr>";

		//	$tmp = explode(",",e_LANLIST);

			$tmp = $lng->installed();

			sort($tmp);

			foreach($tmp as $lang)
			{
				// if($lang == $pref['sitelanguage']){ continue; }

				$checked_0 = (empty($curval[$lang])) ? "checked='checked'" : "";
				$checked_1 = (!empty($curval[$lang]) && $curval[$lang] == 1) ? "checked='checked'" : "";
				$checked_2 = (!empty($curval[$lang]) &&$curval[$lang] == 2) ? "checked='checked'" : "";


				if(!empty($curval[$lang]) && strlen($curval[$lang])>3)
				{
					$url_value = $curval[$lang];
					$checked_2 = "checked='checked'";
				}
				else
				{
					$url_value = '';
				}

				$fieldName = "offline_languages[".$lang."]";
				$fieldNameUrl = "offline_languages[".$lang."-url]";

				$text .="
		        <tr>
			        <td>{$lang} (".$lng->convert($lang).")</td>
			        <td class='center'>".$this->radio($fieldName, 0, $checked_0)."

			        </td>
			        <td class='center'>".$this->radio($fieldName, 1, $checked_1)."

			        </td>
			        <td class='form-inline'>
			            ".$this->radio($fieldName, 2, $checked_2)." ".
			            $this->text($fieldNameUrl, varset($curval[$lang."-url"],''), 255, array('size'=>'xxlarge'))."
			            <div class='field-help' data-placement='top'>eg. http://wherever.com or {e_PLUGIN}myplugin/myplugin.php</div>
			        </td>

				</tr>";
			}


			$text .= "
		    </table>
		  	 ";


			return $text;


		}



}

		
new multilan_adminArea();

require_once(e_ADMIN."auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN."footer.php");
exit;

?>