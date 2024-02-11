<?php

//
//	Grid HTML block
//
//	CSimpleGrid($name, $html_path, $db) constructor
//	onItem() called for each item after getting data but before parsing
//
//	regular example:
//
//	$list = new CSimpleGrid("searchlist", "html/searchlist.html", $db);
//	$list->m_sqlcount = "select count(*) as cnt from videos where userId=" . to_sql($userId, "Number");
//	$list->m_sql = "select login from videos where userId=" . to_sql($userId, "Number");
//
//	$list->m_fields["login"] = Array ("login", null);
//	$list->m_fields["img"] = Array ("img", "no.jpg");
//		"name", (null | "value") - if value != null then it's a value from this array
//		if value==null then it's from sql-result
//	$list->m_sort = "registered";
//	$list->m_dir = "desc";
//	$page->add($list);
//
//	more parameters:
//
//	$list->m_nPerPage			= 20;					// number items per page
//	$list->m_itemBlocks["vip"]	= 0;
//		if there is a block gridName_itemBlockName then
//			if it's true or 1 then parse it else set this internal item block to ""
//	$list->m_pageMode			= GRIDMODE_LASTPAGE;	// last page filled by items from prev. page
//	$list->m_lastPageByDefault	= GRIDMODE_LASTPAGE;	// by default grid views last page
//
//	$list->m_nCells	= 3;	// split whole grid into 3 cells (3 items per special row)
//							// here also used _begin, _end and _newline html blocks
//
//
//	grid blocks and tags:
//		{info} to show info about current page
//		gridName_pager
//			gridName_first
//				{url}, {page}
//			gridName_prev
//				{url}, {page}
//			gridName_page
//				{url}, {page}
//				gridName_link
//					{url}, {page}
//				gridName_curpage
//					{url}, {page}
//			gridName_next
//				{url}, {page}
//			gridName_last
//				{url}, {page}
//		gridName_sort_*fieldName* - one arrow sorter
//			gridName_asc_*fieldName*
//			gridName_desc_*fieldName*
//		gridName_sort2_*fieldName* - 2 arrows sorter
//			{urlAsc}
//			{urlDesc}
//			{asc} - "" or 2
//			{desc} - "" or 2
//		gridName_item
//			{*field*}
//			{n0} number of item from 0
//			{n1} number of item from 1
//			* internal items blocks *
//			gridName_middle		parsed for the item in the middle
//			gridName_even		parsed for items: 0,2,4,6,...
//			gridName_odd		parsed for items: 1,3,5,...
//			gridName_separator	parsed for all items except last one
//		gridName_noitems
//		{pager2} copy parsed pager block
//
//
//
//	2006-11-28
//



/**
	Interface of DataStorage for the Grid
	You should use one of its sub-class like CGrid or CSimpleGrid
*/
interface IGridData
{
	public function getCount(); // { return 0; }
	public function getItems($offset, $perPage, $sort, $dir = "asc"); // { return Array(); }
}


/*
class CGridData implements
{
	var $m_grid = false;

	function CGridData($grid = false)
	{
		$this->m_grid = $grid;
	}
}
*/

/**
	The grid without storage. You should create sub-class or provide grid-storage to use it.
	It's ready presentation layer and HTTP input interface
	Child of CGrid should implement IGridData or get it via constructor
*/
class CGrid extends CHtmlBlock
{
	const GRIDMODE_REGULAR		= 0;
	const GRIDMODE_LASTPAGE		= 1;

	private $m_storage = null;

	public $m_nPerPage = 30; // number items per page

	public $m_sort = "";			// default sort field
	public $m_dir = "";			// default sort dir. 'asc' or 'desc'

	// GRIDMODE_REGULAR		page by page strongly,
	// GRIDMODE_LASTPAGE	last page should be filled
	public $m_pageMode = CGrid::GRIDMODE_REGULAR;

	// GRIDMODE_REGULAR		view first page by default as regular grid,
	// GRIDMODE_LASTPAGE	view last page by default
	public $m_lastPageByDefault = CGrid::GRIDMODE_REGULAR;

	public $m_nCells = 0;	// split whole grid into several columns (cells)

	// the map of columns (fields)
	public $m_fields = Array();
	// "name", (null | "value") - if value != null then it's a value from this array
	// if value==null then it's from sql-result

	// blocks that we should parse/hide in each item
	public $m_itemBlocks = Array();


	function __construct($name, $html_path = "", $storage = false)
	{
		parent::__construct($name, $html_path);
		if ($storage)
		{
			$this->m_storage = $storage;
			//$this->m_storage->m_grid = $this;
		}
		else
			$this->m_storage = $this;
	}


	protected function getParamsArray(&$a)
	{
		$a[$this->m_name . "Sort"] = $this->m_sort;
		$a[$this->m_name . "Dir"] = $this->m_dir;
		parent::getParamsArray($a);
	}
	

	public function init()
	{
		parent::init();

		// try to get number of records per page
		$nPerPage = get_param($this->m_name . "Page", $this->m_nPerPage);
		if ($nPerPage > 0)
			$this->m_nPerPage = $nPerPage;
	
		// get sort and dir params
		$sortParam = $this->m_name . "Sort"; // http/cookie param of sorting
		//$sort = get_cookie($sortParam);	// get sorting from cookie (uncomment if need)
		//if (! $sort)
			$sort = $this->m_sort;
		$sort = get_param($sortParam, $sort); // get sort from http
		if ($sort != $this->m_sort && $sort != "")	// if it is but not default sorting
			$this->m_dir = "";		// then don't use default dir

		$dirParam = $this->m_name . "Dir"; // http/cookie param of dir
		//$dir = get_cookie($dirParam);	// get dir from cookie (uncomment if need)
		//if (! $dir)
			$dir = $this->m_dir;
		$dir = get_param($dirParam, $dir); // get dir from http
		if ($dir == "")
			$dir = $this->m_dir;
		if (! isset($this->m_fields[$sort]))	// if there is not such field to sort
			$sort = $this->m_sort;				// then sort by default
		else if ($this->m_fields[$sort][1] != null)	// or if this is not DB field
			$sort = $this->m_sort;					// then sort by default as well
		if ($sort != "" && $dir != "asc" && $dir != "desc") $dir = "asc"; // dir should be 'asc' or 'desc'
		//set_cookie($sortParam, $sort);
		//set_cookie($dirParam, $dir);

		$this->m_sort = $sort;
		$this->m_dir = $dir;
	}


	protected function onParse()
	{
		global $g_templ;

		$sortParam = $this->m_name . "Sort"; // http/cookie param of sorting
		$dirParam = $this->m_name . "Dir"; // http/cookie param of dir
		$sort = $this->m_sort;
		$dir = $this->m_dir;

		// get the total number of items
		$n_n = $this->m_storage->getCount();

		if ($n_n > 0) // if there is some item(s)
		{
			if ($n_n == "") $n_n = 0; // ?

			if ($this->m_nPerPage > 0)
			{

				// number of pages
				$n_p = (int)(($n_n % $this->m_nPerPage > 0 ? 1 : 0) + ($n_n / $this->m_nPerPage));

				$nOffset = get_param($this->m_name . "Offset", ""); // get offset from http
				if ($this->m_lastPageByDefault == CGrid::GRIDMODE_LASTPAGE)	// if we need last page by default
					if ($nOffset === "")					// and if there is not offset yet
						$nOffset = $n_p - 1;			// then switch it to last page
				if ($nOffset < 0) $nOffset = 0;				// 0-first page
				if ($nOffset >= $n_p) $nOffset = $n_p - 1;	// last page


				// first item we are going to view
				if ($this->m_pageMode == CGrid::GRIDMODE_LASTPAGE)
				{
					$nFirst = $nOffset * $this->m_nPerPage;
					if ($nFirst > $n_n - $this->m_nPerPage)
						$nFirst = $n_n - $this->m_nPerPage;
					if ($nFirst < 0)
						$nFirst = 1;
					else
						$nFirst ++;
				} else {
					$nFirst = ($nOffset  * $this->m_nPerPage + 1);
				}
				// last item we are going to view
				if ($this->m_pageMode == CGrid::GRIDMODE_LASTPAGE)
				{
					$nLast = $nFirst + $this->m_nPerPage;
					if ($nLast > $n_n)
						$nLast = $n_n;
				} else {
					$nLast = ($nOffset + 1) * $this->m_nPerPage;
					if ($nLast > $n_n) $nLast = $n_n;
				}

				$blPager =& $this->getBlock("_pager");
				if ($blPager)
				{
					$sOffset = $this->m_name . "Offset";
					if ($nOffset >= 10)
					{
						$this->setVar("url", $_SERVER["PHP_SELF"] . "?" . correct_param($sOffset, "0"));
						$g_templ->parse($blPager, "_first");
						$this->setVar("url", $_SERVER["PHP_SELF"] . "?" . correct_param($sOffset, $nOffset - 10));
						$g_templ->parse($blPager, "_prev");
					} else {
						$g_templ->setBlockText($blPager, "_first", "");
						$g_templ->setBlockText($blPager, "_prev", "");
					}

					$blPage =& $g_templ->getBlock($blPager, "_page");
					if ($blPage)
					{
						$n = 10*(int)($nOffset / 10) + 10;
						if ($n > $n_p) $n = $n_p;
						for ($i = 10*(int)($nOffset / 10); $i < $n; $i++)
						{
								$this->setVar("url", $_SERVER["PHP_SELF"] . "?" . correct_param($sOffset, $i));
							$this->setVar("page", $i+1);
							if ($i != $nOffset)
							{
								$g_templ->parse($blPage, "_link", false);
								$g_templ->setBlockText($blPage, "_curpage", "");
							} else {
								$g_templ->parse($blPage, "_curpage", false);
								$g_templ->setBlockText($blPage, "_link", "");
							}
							$g_templ->parse($blPage, "", true);
						}
					}

					if ((int)($nOffset / 10) < (int)($n_p / 10))
					{
						$this->setVar("url", $_SERVER["PHP_SELF"] . "?" . correct_param($sOffset, ($nOffset + 10 > $n_p - 1) ? $n_p - 1 : $nOffset + 10 ));
						$g_templ->parse($blPager, "_next");
						$this->setVar("url", $_SERVER["PHP_SELF"] . "?" . correct_param($sOffset, $n_p - 1));
						$g_templ->parse($blPager, "_last");
					} else {
						$g_templ->setBlockText($blPager, "_next", "");
						$g_templ->setBlockText($blPager, "_last", "");
					}

					$g_templ->parse($blPager);

					$this->setBlockText("_pager2", $this->getBlockText("_pager"));
				}

				$rec_offset = 0;
				if ($nOffset > 0)
				{
					if ($this->m_pageMode == CGrid::GRIDMODE_LASTPAGE) // last page should be filled
					{
						$_i = $nOffset * $this->m_nPerPage;
						if ($_i > $n_n - $this->m_nPerPage)
							$_i = $n_n - $this->m_nPerPage;
						if ($_i > 0)
							$rec_offset = $_i;
					} else {
						$rec_offset = $nOffset * $this->m_nPerPage;
					}
				}

			} else {
				$nFirst = 1;
				$nLast = $n_n;
				$rec_offset = 0;
			}

			$this->setVar("_info", to_anum($nFirst) . " - " . to_anum($nLast) . " of " . to_anum($n_n));
			$this->setVar("_n", to_anum($n_n));
			$this->setVar("_first", to_anum($nFirst));
			$this->setVar("_last", to_anum($nLast));



			$this->resultset = $this->m_storage->getItems(
				$rec_offset,
				$this->m_nPerPage,
				$this->m_fields[$sort][0],
				$dir
			);

			if ($this->resultset)
			{
				$this->onQuery();

				$counter = 0;
				$n = $nLast - $nFirst + 1;

				if ($this->m_nCells > 0)
				{
					$html->parsesafe($this->m_name . "_begin", false);
					if ($n > $this->m_nCells && $n % $this->m_nCells > 0)
					{
						$this->setVar("lostCells", $this->m_nCells - ($n % $this->m_nCells));
						$html->parsesafe($this->m_name . "_close", false);
					}
					$html->parsesafe($this->m_name . "_end", false);
				}

				$this->m_blItem =& $this->getBlock("_item");
				if ($this->m_blItem)
				{
					foreach ($this->resultset as $row)
					{
						foreach ($this->m_fields as $fn => $field)
						{
							if ($field[1] == null)
							{
								if (isset($row[$field[0]]))
									$this->m_fields[$fn][2] = $row[$field[0]];
							} else {
								$this->m_fields[$fn][2] = $field[1];
							}
						}
						$this->onItem();
						foreach ($this->m_fields as $fn => $field)
						{
							if (isset($field[2]))
							{
								$this->setVar($fn, $field[2]);
								$this->m_fields[$fn][1] = null;
								$this->m_fields[$fn][2] = "";
							}
						}
						$this->setVar("n0", $counter);
						$this->setVar("n1", $counter + 1);
						foreach ($this->m_itemBlocks as $itemBlock => $b)
						{
								if ($b)
									$g_templ->parse($this->m_blItem, $itemBlock, false);
								else
									$g_templ->setBlockText($this->m_blItem, $itemBlock, "");
						}
						if ($counter == ceil($n / 2) - 1)
							$g_templ->parse($this->m_blItem, "_middle", false);
						else
							$g_templ->setBlockText($this->m_blItem, "_middle", "");
						if ($counter % 2 == 1)
							$g_templ->parse($this->m_blItem, "_odd", false);
						else
							$g_templ->setBlockText($this->m_blItem, "_odd", "");
						if ($counter % 2 == 0)
							$g_templ->parse($this->m_blItem, "_even", false);
						else
							$g_templ->setBlockText($this->m_blItem, "_even", "");

						if ($this->m_nCells > 0)
						{
							if (
								($counter + 1) % $this->m_nCells == 0
								&& $counter < $n - 1
							)
								$g_templ->parse($this->m_blItem, "_newline", false);
							else
								$g_templ->setBlockText($this->m_blItem, "_newline", "");
						}

						if ($counter < $n - 1)
							$g_templ->parse($this->m_blItem, "_separator", false);
						else
							$g_templ->setBlockText($this->m_blItem, "_separator", "");

						$g_templ->parse($this->m_blItem, "", true);

						$counter ++;
					}
				}

			} else {
				$n_n = 0;
			}

		}
		if ($n_n == 0)
		{
			$this->setVar("_info", "");
			$this->setVar("_n", "");
			$this->setVar("_first", "");
			$this->setVar("_last", "");
			$this->setBlockText("_pager", "");
			$this->setBlockText("_pager2", "");

			$this->parseBlock("_noitems");
		}

		foreach ($this->m_fields as $fname => $field)
		{
			global $g_page;

			$blSort =& $this->getBlock("sort_" . $fname);
			if ($blSort)
			{
				if ($sort == $fname && $dir == "asc")
					$this->setVar("url", $_SERVER["PHP_SELF"] . "?" . $g_page->getParamsCorrectParam2($sortParam, $fname, $dirParam, "desc"));
				else
					$this->setVar("url", $_SERVER["PHP_SELF"] . "?" . $g_page->getParamsCorrectParam2($sortParam, $fname, $dirParam, "asc"));
				if ($sort == $fname && $dir == "desc")
					$g_templ->parse($blSort, "desc");
				else				
					$g_templ->setBlockText($blSort, "desc", "");
				if ($sort == $fname && $dir == "asc")
					$g_templ->parse($blSort, "asc");
				else				
					$g_templ->setBlockText($blSort, "asc", "");
				$g_templ->parse($blSort);
			}

			$blSort =& $this->getBlock("sort2_" . $fname);
			if ($blSort)
			{
				$this->setVar("urlAsc", $_SERVER["PHP_SELF"] . "?" . $g_page->getParamsCorrectParam2($sortParam, $fname, $dirParam, "asc"));
				$this->setVar("urlDesc", $_SERVER["PHP_SELF"] . "?" . $g_page->getParamsCorrectParam2($sortParam, $fname, $dirParam, "desc"));
				if ($sort == $fname && $dir == "desc")
					$this->setVar("desc", "2");
				else				
					$this->setVar("desc", "");
				if ($sort == $fname && $dir == "asc")
					$this->setVar("asc", "2");
				else				
					$this->setVar("asc", "");
				$g_templ->parse($blSort);
			}
		}

		unset($this->resultset);

		parent::onParse();
	}




	/**
		called one time for each record right before parsing
	*/
	protected function onItem()
	{
	}	


	/**
		called one time for whole resultset after query but before parsing
	*/
	protected function onQuery()
	{
	}	


}










/**
	Simple grid ready to use

	$list = new CSimpleMysqlGrid("searchlist", "html/searchlist.html", $db);
	$list->m_sqlcount = "select count(*) as cnt from videos where userId=" . to_sql($userId, "Number");
	$list->m_sql = "select login from videos where userId=" . to_sql($userId, "Number");

	$list->m_fields["login"] = Array ("login", null);
	$list->m_fields["img"] = Array ("img", "no.jpg");
		"name", (null | "value") - if value != null then it's a value from this array
		if value==null then it's from sql-result
	$list->m_sort = "registered";
	$list->m_dir = "desc";
	$page->add($list);
	
*/


class CSimpleMysqlGrid extends CGrid implements IGridData
{
	protected $m_sql = null;			// sql to get items for this page
	protected $m_sqlcount = null;		// count sql to get whole number of items

	// constructor
	function __construct($name, $html_path)
	{
		parent::__construct($name, $html_path, $this);
	}


	// IGridData
	public function getCount()
	{
		global $g_db;
		return $g_db->DLookUp($this->m_sqlcount);
	}

	public function getItems($offset, $perPage, $sort, $dir = "asc")
	{
		global $g_db;
		$sql = $this->m_sql;

		if ($sort != "")

			$sql .= " ORDER BY " . $this->m_fields[$sort][0] . " " . $dir;
		
		if ($perPage > 0)
		{
			$sql .= " LIMIT ";
			if ($offset > 0)
			{
				$sql .= $offset . ",";
			}
			$sql .= $perPage;
		}

//echo "$sql";

		return $g_db->queryAll($sql);
	}

}



















?>