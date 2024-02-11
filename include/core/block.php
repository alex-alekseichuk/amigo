<?php

//
//	2 main classes of the Framework
//
//
//	example of regular framework page:
//	
//	include_once("include/core.php");
//	include_once("include/db.php");
//	include_once("include/lang.php");
//	include_once("include/html.php");
//	include_once("include/params.php");
//	include_once("include/block.php");
//	include_once("include/grid.php");
//	include_once("include/record.php");
//	include_once("include/common.php");
//	
//	$db = new CDB();
//	$db->connect();
//	
//	$page = new CHtmlBlockPage("", "html/1.html");
//	$page->add(new CHtmlBlock("iHeader", "html/header.html"));
//	$page->add(new CHtmlBlock("iFooter", "html/footer.html"));
//	
//	$page->init();
//	$page->action();
//	$page->parse(null);
//	
//
//	2006-11-28


//
//	parent for any block
//		used to create hierarchy of the blocks
//		The page is a block; usually it's root block of hierarcy
//		Block may have several sub-blocks
//
//	CBlock($name)	constructor
//		$name maybe ""
//	add($block)		add $block as a child to this block 
//	init()			init the block recursively
//	action()		do the action recursively
//		

abstract class CBlock
{
	protected $m_blocks = Array();	// child blocks
	protected $m_name = "";			// name of the block
	protected $m_parent = null;		// ref. to parent block

	function __construct($name)
	{
		$this->m_name = $name;
	}

	public function init()
	{
		foreach ($this->m_blocks as $n => $b)
		{
			$this->m_blocks[$n]->init();
		}
	}

	public function action()
	{
		foreach ($this->m_blocks as $n => $b)
		{
			$this->m_blocks[$n]->action();
		}
	}

	public function add(&$b)
	{
		if ($b->m_name == "")
			return;
		if (isset($this->m_blocks[$b->m_name]))
			return;
		$this->m_blocks[$b->m_name] =& $b;
		$b->m_parent =& $this;
	}

	public function getRoot()
	{
		$root = $this;
		while (! is_null($root->m_parent))
		{
			$root = $root->m_parent;
		}
		return $root;
	}

	protected function getParamsArray(&$a)
	{
		foreach ($this->m_blocks as $n => $b)
		{
			$this->m_blocks[$n]->getParamsArray($a);
		}
	}
}


//	parent for any HTML block
//
//	$name of CHtmlBlock should be "" or have appropriate block in HTML template
//
//	$g_images - global var.; relative path to folder with images
//		'img' by default
//
//	CHtmlBlock($name, $html_path) constructor
//		loads HTML template and set 2 tags
//			{img}		- url path to images, js and css
//			{curscript}	- current page URL
//			{params}	- saved parameters for current page
//			{curpage}	- current page URL + saved parameters
//		$name maybe ""
//		$html_path path to HTML template or null if it's just a part of the page
//
//	parseBlock(&$html)
//		parse only this block
//

class CHtmlBlock extends CBlock
{
	private $m_html = null;					// ref. to specified block-node in the template tree

	private $m_html_path = "";				// path of the template file for this block or null for sub-block using

	private $m_bDisabled = false;
	public function disable()
	{
		$this->m_bDisabled = true;
	}
	public function disableBlock($blockName)
	{
		foreach ($this->m_blocks as $n => $b)
		{
			if ($this->m_blocks[$n]->m_name == $blockName)
				$this->m_blocks[$n]->disable();
		}
	}

	protected $m_sMessage = "";				// message for this block
	public function addMessage($str)
	{
		if ($this->m_sMessage != "")
			$this->m_sMessage .= "<br />";
		$this->m_sMessage .= $str;
	}

	function __construct($name, $html_path = "")
	{
		parent::__construct($name);
		$this->m_html_path = $html_path;
//		$this->m_html = CTemplate::$NOBLOCK;
	}


	public function init()
	{
		global $g_page;
		global $g_templ;
		if ($this->m_parent == null)
		{
			$this->m_html =& $g_templ->getMainBlock();
		} else {
			$this->m_html =& $g_templ->getBlock($this->m_parent->m_html, $this->m_name);
		}

		if ($this->m_html_path)
		{
			if ($this->m_html)
			{
				if (! $g_templ->loadTo($this->m_html_path, $this->m_html))
					echo "Can't load " . $this->m_html_path . " file<br />";
			}
		}

		parent::init();

		$this->m_sMessage = get_param($this->m_name . "Mes", $this->m_sMessage);
	}

	public function action()
	{
		parent::action();
	}


	// parse only this block
	// don't call this method
	protected function onParse()
	{
		if ($this->m_sMessage != "")
		{
			$this->setVar("sMessage", $this->m_sMessage);
			$this->parseBlock("bMessage");
		}
		$this->setVar("_block", $this->m_name);
	}

	// parse the block in blocks tree
	// call this method
	public function parse()
	{
		global $g_page;
		global $g_templ;

		if ($this->m_bDisabled)
			return;

		foreach ($this->m_blocks as $name => $b)
		{
			$this->m_blocks[$name]->parse();
		}

		if ($this->m_html != null)
		{
			$this->onParse();
			$g_templ->parse($this->m_html);
		}

	}


	public function parseBlock($blockName, $bAdd = false)
	{
		global $g_templ;
		$g_templ->parse($this->m_html, $blockName, $bAdd);
	}

	public function setVar($name, $value)
	{
		global $g_templ;
		$g_templ->setVar($name, $value);
	}
	public function setBlockText($blockName, $value)
	{
		global $g_templ;
		$g_templ->setBlockText($this->m_html, $blockName, $value);
	}
	public function getBlockText($blockName)
	{
		global $g_templ;
		return $g_templ->getBlockText($this->m_html, $blockName);
	}
	public function &getBlock($blockName)
	{
		global $g_templ;
		return $g_templ->getBlock($this->m_html, $blockName);
	}

	
}

/*
// TODO provide refactoring for this class
class CPathBlock extends CHtmlBlock
{

	function parsePath($path, $label)
	{
		return $label;
	}
	
	function parseBlock(&$html)
	{
		global $g_page_block;
		global $g_path_scheme;
		global $g_paths;

		$block = $g_page_block;

		$paths = Array();

		while (isset($g_paths[$block]))
		{
			$paths[sizeof($paths)] = $block;
			if (isset($g_path_scheme[$block]))
				$block = $g_path_scheme[$block];
			else
				break;
		}

		$n = sizeof($paths);
		for ($i=$n-1; $i>=0; $i--)
		{
			$path = $paths[$i];
			$s = $g_paths[$path][0];
			$s = $this->parsePath($path, $s);

			$html->setvar("label", $s);
			$html->setvar("url", $g_paths[$path][1]);
			$p = Array();
			foreach ($g_paths[$path][2] as $pname)
			{
				$p[$pname] = get_param_value($pname);
			}
			$html->setvar("p", correct_params($p));

			if ($i == 0)
			{
				$html->parse("path_label");
			} else {
				$html->parse("path_link");
			}
		}
		

		parent::parseBlock($html);
	}	
}
*/

?>