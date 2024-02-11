<?

/*
Engine to parse HTML blocks tree.


Example:

<!--begin_bMessage-->
<hr />{sMessage}<hr />
<!--begin_bInternalEmpty-->
<!--end_bMessage-->


$html = new CTemplate();
$html->load("question.html");

$htmlMain =& $html->getMainBlock();


$html->setVar("sMessage", "Hello!");
$html->parse($htmlMain, "bMessage");

echo $html->parseBlock($htmlMain);

$html->setVar("sMessage", "Yes!");
$html->parse($htmlMain, "bMessage", true);

$html->parse($htmlMain);
echo $html->getMainBlockText();

//echo $html->parseBlock($htmlMain);
	
*/


class CTemplate
{
	/**
		Used is block is not found by name.
	*/
	public static $NOBLOCK = null;

	/**
		Main block node of the tree.
	*/
	protected $m_block = Array("", Array());

	/**
		The set of vars (tags) to parse.
	*/
	protected $m_vars = Array();

	/**
		Special chars used in loading.
	*/
	private $delimiter;
	private $tag_sign;
	private $begin_block;
	private $end_block;


	public function __construct()
	{
		$this->delimiter      = chr(27);
		$this->tag_sign       = chr(15);
		$this->begin_block    = chr(16);
		$this->end_block      = chr(17);
		$this->block_block    = chr(18);
	}


	public function setVar($name, $value)
	{
		$this->m_vars[$name] = $value;
	}

	/**
		Load blocks tree into main node from specified file
		@return true if loaded non-empty file
	*/
	public function load($path)
	{
		return $this->loadTo($path, $this->m_block, "");
	}

	/**
		Load the tree from the string
		@return true if loaded non-empty content
	*/
	public function loadContent($file_content)
	{
		return $this->loadContentTo($file_content, $this->m_block, "");
	}

	/**
		Load blocks tree into main node from specified file
		@return true if loaded non-empty file
	*/
	public function loadTo($path, &$block, $blockName = "")
	{
		if (file_exists($path))
		{
			$fh = fopen($path, "rb");
			if (filesize($path))
				$file_content = fread($fh, filesize($path));
			fclose($fh);

			return $this->loadContentTo($file_content, $block, $blockName); // php5: $file_content, php4: &$file_content
		}
		return false;
	}

	/**
		Load the tree from the string
		@return true if loaded non-empty content
	*/
	public function loadContentTo(&$file_content, &$block, $blockName = "") // php5: &$file_content, php4: $file_content
	{
		if ($file_content)
		{
			$delimiter = $this->delimiter;
			$tag_sign = $this->tag_sign;
			$block_block = $this->block_block;
			$begin_block = $this->begin_block;
			$end_block = $this->end_block;

			// preparing file content for parsing
			$file_content = preg_replace("/<!\-\-\s*block_\s*([\w\s]*\w+)\s*\-\->/is",  $delimiter . $block_block . $delimiter . "\\1" . $delimiter, $file_content);
			$file_content = preg_replace("/<!\-\-\s*begin_\s*([\w\s]*\w+)\s*\-\->/is",  $delimiter . $begin_block . $delimiter . "\\1" . $delimiter, $file_content);
			$file_content = preg_replace("/<!\-\-\s*end_\s*([\w\s]*\w+)\s*\-\->/is",  $delimiter . $end_block . $delimiter . "\\1" . $delimiter, $file_content);
			$file_content = preg_replace("/\{(\w+)\}/is", $delimiter . $tag_sign . $delimiter . "\\1" . $delimiter, $file_content);
			$parse_array = explode($delimiter, $file_content);
			$pos = 0;

			//if ($block == CTemplate::$NOBLOCK)
			//{
			//	if ($blockName)
			//	{
			//		$b =& $this->getBlock($this->m_block, $blockName);
			//		if ($b == CTemplate::$NOBLOCK)
			//			return false;
			//	} else {
			//		$b =& $this->m_block;
			//	}
			//} else {
				if ($blockName)
				{
					$b =& $this->getBlock($block, $blockName);
					if (! $b) //$b == CTemplate::$NOBLOCK)
						return false;
				} else {
					$b =& $block;
				}
			//}

			return $this->loading($b, $parse_array, $pos);
		}
		return false;
	}

	/**
		Loading block-node recursively
		@param &$block block we are loading into
		@param &$parse_array array of tokens
		@param &$pos index of current token
		@param $blockName the name of the block we are parsing or empty string for main node
	*/
	protected function loading(&$block, &$parse_array, &$pos, $blockName = "")
	{
		while ($pos < sizeof($parse_array))
		{
			if ($parse_array[$pos] == $this->begin_block)
			{
				$blockName2 = $parse_array[$pos + 1];
				$block[1]["b" . $blockName2] = Array("", Array());
				$pos += 2;
				if (! $this->loading($block[1]["b" . $blockName2], $parse_array, $pos, $blockName2))
					return false;
			} elseif ($parse_array[$pos] == $this->block_block) {
				$block[1]["b" . $parse_array[$pos + 1]] = Array("", Array());
				$pos += 2;
			} elseif ($parse_array[$pos] == $this->tag_sign) {
				$tagName = $parse_array[$pos + 1];
				$n = sizeof($block[1]);
				$block[1]["v" . $n] = $tagName;
				$pos += 2;
			} elseif ($parse_array[$pos] == $this->end_block) {
				$pos += 2;
				if ($parse_array[$pos - 1] == $blockName)
				{
					return true;
				}
			} else {
				$n = sizeof($block[1]);
				$block[1]["t" . $n] = $parse_array[$pos];
				$pos ++;
			}
		}

		// if don't find end-block token and there is main node loading then it's ok
		if ($blockName == "")
			return true;

		// it's not main node loadin, so, return false
		echo "Can't find the end of block " . $blockName;
		return false;
	}



	/**
		@return ref to root node
	*/
	public function &getMainBlock()
	{
		return $this->m_block;
	}
	public function getMainBlockText()
	{
		return $this->m_block[0];
	}

	/**
		@return ref to node by $blockName from parent node &$block
	*/
	public function &getBlock(&$block, $blockName)
	{
		if (isset($block[1]["b" . $blockName]))
		{
			return $block[1]["b" . $blockName];
		} else {
			return CTemplate::$NOBLOCK;
		}
	}
	public function getBlockText(&$block, $blockName = "")
	{
		if ($blockName)
		{
			$b =& $this->getBlock($block, $blockName);
			if ($b) // != CTemplate::$NOBLOCK)
			{
				return $b[0];
			}
			return "";
		} else {
				return $block[0];
		}
	}
	public function setBlockText(&$block, $blockName = "", $text = "")
	{
		if ($blockName)
		{
			$b =& $this->getBlock($block, $blockName);
			if ($b) // != CTemplate::$NOBLOCK)
			{
				$b[0] = $text;
			}
		} else {
				$block[0] = $text;
		}
	}

	/**
		Parse specified node and return result text
		@return result text
	*/
	protected function collect($block) // php5: $block, php4: &$block
	{
		$s = "";
		foreach ($block[1] as $name => $value)
		{
			switch ($name[0])
			{
				case "t":
					$s .= $value;
					break;
				case "v":
					if (isset($this->m_vars[$value]))
						$s .= $this->m_vars[$value];
					break;
				case "b":
					$s .= $value[0];
					break;
			}
		}
		return $s;
	}

	/**
		Parse specified block and return result text
		@param &$block block to parse or to parse in
		@param $blockName block to parse. if $blockName = "" then parse &$block block
		@return result text
	*/
	public function parseBlock(&$block, $blockName = "")
	{
		if ($blockName)
		{
			$b =& $this->getBlock($block, $blockName);
			if ($b) // != CTemplate::$NOBLOCK)
				return $this->collect($b); // php5: $b, php4: &$b
		} else {
			return $this->collect($block); // php5: $block, php4: &$block
		}
		return "";
	}


	/**
		Parse specified block and return result text
		@param &$block block to parse or to parse in
		@param $blockName block to parse. if $blockName = "" then parse &$block block
		@return result text
	*/
	public function parse(&$block, $blockName = "", $bAdd = false)
	{
		if ($blockName)
		{
			$b =& $this->getBlock($block, $blockName);
			if ($b) // != CTemplate::$NOBLOCK)
			{
				if ($bAdd)
					$b[0] .= $this->collect($b);
				else
					$b[0] = $this->collect($b);
			}
		} else {
			if ($bAdd)
				$block[0] .= $this->collect($block);
			else
				$block[0] = $this->collect($block);
		}
	}
	public function parseTo($toBlockName, &$block, $blockName = "", $bAdd = false)
	{
		$bTo =& $this->getBlock($block, $toBlockName);
		if ($bTo) // != CTemplate::$NOBLOCK)
		{
			if ($blockName)
			{
				$b =& $this->getBlock($block, $blockName);
				if ($b) // != CTemplate::$NOBLOCK)
				{
					if ($bAdd)
						$bTo[0] .= $this->collect($b);
					else
						$bTo[0] = $this->collect($b);
				}
			} else {
				if ($bAdd)
					$bTo[0] .= $this->collect($block);
				else
					$bTo[0] = $this->collect($block);
			}
		}
	}

	public function test(&$b = null, $sTab = "")
	{
		if ($b == null)
			$b =& $this->getMainBlock();
		foreach ($b[1] as $name => $value)
		{
			if ($name[0] == "v")
				echo $sTab . "v: " . $value . "<br>";
			if ($name[0] == "t")
				echo $sTab . "t: " . $name . "<br>";
			if ($name[0] == "b")
			{
				echo $sTab . "b: " . substr($name, 1) . "<br>";
				$this->test($value, $sTab . "&nbsp;&nbsp;&nbsp;&nbsp;");
			}
		}
		echo "<hr>" . $b[0] . "<hr>";
	}

}


?>