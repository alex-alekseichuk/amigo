<?php

interface IWebApp
{
	public function createPage($pageName);
	public function getPages();
}

abstract class CWebApp implements IWebApp
{
	protected $m_pages = Array ();

	protected function createPageInstance($pageName, $pageAttrs, $path = "")
	{
		$page = null;
		if (isset($pageAttrs["file"]))
			include_once($path . $pageAttrs["file"]);
		eval("\$page = new " . $pageAttrs["class"] . "(\$pageName, \$pageAttrs);");
		return $page;
	}

	public function getPages()
	{
		$pages = Array();
		foreach ($this->m_pages as $name => $page)
		{
			if (isset($page["map"]))
				$pages[$name] = $page["title"];
		}
		return $pages;
	}
}


// TODO add more params methods here
class CPage extends CHtmlBlock
{
	protected $m_pageAttrs = Array();

	protected $m_templ = null;
	public function getTemplate() {return $this->m_templ;}

	public function __construct($html_path, $pageAttrs = null)
	{
		global $g_p;
		global $g_templ;
		global $g_images;

		parent::__construct($g_p, $html_path);

		$this->m_templ = new CTemplate();
		$g_templ = $this->m_templ;

		if ($pageAttrs)
		{
			$this->m_pageAttrs = $pageAttrs;
		}
	}

	public function init()
	{
		global $g_images;
		global $g_page;

		parent::init();

		$this->m_sMessage = get_param($this->m_name . "Mes", $this->m_sMessage);

		$this->setVar("_img" , $g_images);
		$this->setVar("_params", $g_page->getParams());
		$this->setVar("_curscript", getenv("SCRIPT_NAME"));
		$this->setVar("_curpage", getenv("SCRIPT_NAME") . "?" . $g_page->getParams());
	}

	public function setStyle($css_style)
	{
		$this->m_pageAttrs["style"] = $css_style;
		//$this->m_css_style = $css_style;
	}
	public function setTitle($title)
	{
		$this->m_pageAttrs["title"] = $title;
		//$this->m_title = $title;
	}

	public function parse()
	{
		parent::parse();
		echo $this->m_templ->getMainBlockText();
	}


	protected function onParse()
	{
		foreach ($this->m_pageAttrs as $name => $value)
		{
			if ($name == "style")
				$this->setVar("page_style", "@import url(" . $value . ");\n");
			else
				$this->setVar("page_" . $name, $value);
		}

		parent::onParse();
	}

	protected function getParamsArray(&$a)
	{
		global $g_p;

		$a["p"] = $g_p;
		parent::getParamsArray($a);
	}

	public function getParams()
	{
		global $g_page;
		//$r = $this->getRoot();
		$r = $g_page;
		$a = Array();
		$r->getParamsArray($a);
		return CPage::composeParamsString($a);
	}
	public function getParamsCorrectParam($name, $new_value)
	{
		global $g_page;
		//$r = $this->getRoot();
		$r = $g_page;
		$a = Array();
		$r->getParamsArray($a);
		$a[$name] = $new_value;
		return CPage::composeParamsString($a);
	}	
	public function getParamsCorrectParam2($name, $new_value, $name2, $new_value2)
	{
		global $g_page;
		//$r = $this->getRoot();
		$r = $g_page;
		$a = Array();
		$r->getParamsArray($a);
		$a[$name] = $new_value;
		$a[$name2] = $new_value2;
		return CPage::composeParamsString($a);
	}	

	public static function composeParamsString(&$a)
	{
		$s = "";
		foreach ($a as $name => $value)
		{
			if ($s != "")
				$s .= "&";
			$s .= $name . "=" . $value;
		}
		return $s;
	}
}

class CSiteMap extends CHtmlBlock
{
	protected function onParse()
	{
		global $g_app;

		$pages = $g_app->getPages();

		foreach ($pages as $name => $title)
		{
			$this->setVar("name", $name);
			$this->setVar("title", $title);
			$this->parseBlock("bPage", true);
		}

		parent::onParse();
	}
	
}


?>