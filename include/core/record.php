<?php


// returns the title of the field or field name
function rec_field_title($field, $name)
{
	if (isset($field["title"]))
	{
		return $field["title"];
	} else {
		return $name;
	}
}


class CForm extends CHtmlBlock
{
	protected $m_cmd;
	protected $m_fields = Array();


	function __construct($name, $html_path = "", $fields = null)
	{
		parent::__construct($name, $html_path);
		if ($fields)
			$this->m_fields = $fields;
	}



	public function init()
	{
		parent::init();
	}




	public function action()
	{
		// get the action to do
		$this->m_cmd = get_param($this->m_name . "Cmd", "");

		if ($this->m_cmd ==  "")
			return;

		$this->getHttp();
	}

	protected function onParse()
	{
		$this->parseValues();
		$this->setVar("checks", $this->getChecks());

		parent::onParse();
	}

	protected function onSave()
	{
		global $g_messages;
		global $_FILES;
		foreach ($this->m_fields as $name => $i)
		{
			if (
				isset($i["type"]) && $i["type"] == "file" &&
				isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]["tmp_name"])
			)
			{
				// trying to add uploaded file
				if (isset($i["file"]))
					$sFile = $i["file"];
				else
					$sFile = "{rand}_{name}";
				$sFile = ereg_replace("{rand}", substr(md5(uniqid(rand())), 1, 10), $sFile);
				$sFile = ereg_replace("{name}", $_FILES[$name]["name"], $sFile);
				if ($i["value"] != "")
					@unlink($i["dir"] . "/" . $i["value"]);
				if (! @move_uploaded_file($_FILES[$name]['tmp_name'],
					$i["dir"] . "/" . $sFile))
				{
					$this->addMessage(parseHash($g_messages["cantsave"], Array("file" => rec_field_title($i, $name))));
					$this->m_fields[$name]["value"] = "";
				} else {
					@chmod($i["dir"] . "/" . $sFile, 0622);
					$this->m_fields[$name]["value"] = $sFile;
				}
			}
		}
	}


	protected function getHttp()
	{
		global $g_messages;
		global $_FILES;

		foreach ($this->m_fields as $name => $f)
		{
			$type = "";
		
			if (isset($f["type"]))
				$type = $f["type"];
			if ($type == "file")
			{
				if (isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]["tmp_name"]))
				{
					// check file size
					if (isset($f["max"]) && $_FILES[$name]["size"] > $f["max"])
					{

						$this->addMessage(parseHash($g_messages["tobigsize"], 
							Array(
								"file" => rec_field_title($f, $name), 
								"bigsize" => to_anum($_FILES[$name]["size"]), 
								"size" => to_anum($f["max"])
							)));
					
					} else {

						// check file ext.
						if (isset($f["exts"]) && ! (preg_match("/" . $f["exts"] . "$/i", $_FILES[$name]["name"])))
						{
							$this->addMessage(parseHash($g_messages["badext"], 
								Array(
									"file" => rec_field_title($f, $name), 
									"exts" => ereg_replace("\|", ", ", $f["exts"])
								)));
//						} else {

						}
					}

				} else {
					if ((! isset($f["nocheck"])) && (! isset($f["optional"])))
					{
						if ($f["value"] == "")
							$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
					}
				}
			}
			else if (! isset($f["nohttp"]))
			{

/*
				if (isset($f["multiple"]) && (
					$type == "iselect" || $type == "sselect"
					)
				)
				{
					$v = get_param_array($name);
				} else {
					$v = get_param($name, $i["value"]);
				}
*/

				$v = get_param($name, $f["value"]);

				if (! isset($f["nocheck"]))
				{
					if ($type == "int" || $type == "float")
					{
						if ($v == "")
						{   
							if (! isset($i["optional"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {
							if ($type == "int") $v = (int)$v;
							if ($type == "float") $v = (double)$v;
							if (isset($f["min"]) && (0 + $v) < $f["min"])
							{
								$this->addMessage(parseHash($g_messages["imin"], Array("name"=>rec_field_title($f, $name), "min"=>$f["min"])));
							}
							if (isset($f["max"]) && (0 + $v) > $f["max"])
							{
								$this->addMessage(parseHash($g_messages["imax"], Array("name"=>rec_field_title($f, $name), "max"=>$f["max"])));
							}

//							if (isset($i["unique"]))
//							{
//								if ($db->DLookUp("SELECT count(*) FROM $_table WHERE " . $form . "Id<>" . to_sql($_id, "Number") . " AND " . $name . "=" . to_sql($v, "Number")) > 0)
//								{
//									$this->addMessage(parseHash($g_messages["unique"], Array("name"=>rec_field_title($f, $name))));
//								}
//							}


						}
					}

/*
					else if ($type == "iselect" || $type == "sselect")
					{
						$b = true;
						if (is_array($v))
							$b = (sizeof($v) == 0);
						else
							$b = ($v == "");
						if ($b)
						{
							if (! isset($f["optional"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {

// TODO check iselect, sselect for correct input values
//							if (isset($f["sqlcheck"]))	// TODO
//							{
//								if ($db->DLookUp($f["sqlcheck"] . to_sql($v, ($f["type"] == "iselect" ? "Number" : ""))) == 0)
//									$sRet = add_error($sRet, parseHash($g_messages["incorrect"], Array("name" => rec_field_title($i, $name))));
//							}

						}
					}
*/

					else if ($type == "ilov" || $type == "slov")
					{
						if ($v == "")
						{
							if (! isset($f["optional"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {
							if (isset($f["options"]))
							{
								if (! isset($f["options"][$v]))
									$this->addMessage(parseHash($g_messages["incorrect"], Array("name" => rec_field_title($f, $name))));
							}
						}
					}
					else if ($type == "ilovRadio" || $type == "slovRadio")
					{
						if ($v == "")
						{
							if (! isset($f["optional"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {
							if (isset($f["options"]))
							{
								if (! in_array($v, $f["options"]))
									$this->addMessage(parseHash($g_messages["incorrect"], Array("name" => rec_field_title($f, $name))));
							}
						}
					}
					else if ($type == "check")
					{
						if ($v == "")
						{
							if (isset($f["required"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {
							if ($f["value"] != 1)
								$f["value"] = 1;
						}
					}

/*
					else if ($type == "checks" || $type == "checksOn")
					{
						$v = get_checks_param($name);
						if ($v == 0)
						{
							if (! isset($i["optional"]))
								$sRet = add_error($sRet, parseHash($g_messages["required"], Array("name" => rec_field_title($i, $name))));
						}
					}
*/

					else if ($i["type"] == "date3")
					{
						$vY = get_param($name . "Year", "");
						$vM = get_param($name . "Month", "");
						$vD = get_param($name . "Day", "");
						if ($vD == "" || $vM == "" || $vY == "")
						{
							if (! isset($i["optional"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {
							if (
								(isset(f["min"]) && $vY < f["min"]) || (isset(f["max"]) && $vY > f["max"])
								|| $vM < 1 || $vM > 12 || $vD < 1 || $vD > 31)
								$this->addMessage(parseHash($g_messages["incorrect"], Array("name" => rec_field_title($f, $name))));
							else
								$v = $vY . "-" . $vM . "-" . $vD;
						}
					}
					else if ($i["type"] == "time2")
					{
						$vH = get_param($name . "Hour", "");
						$v = get_param($name . "Mins", "");
						if ($vH === "" || $vM === "")
						{
							if (! isset($i["optional"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {
							if ($vH < 0 || $vH > 23 || $vM < 0 || $vM > 59 )
								$this->addMessage(parseHash($g_messages["incorrect"], Array("name" => rec_field_title($f, $name))));
							else
								$v = $vH . ":" . $vM;
						}
					}


					else { // pure text field
						if ($v == "")
						{
							if (! isset($f["optional"]))
								$this->addMessage(parseHash($g_messages["required"], Array("name" => rec_field_title($f, $name))));
						} else {
							if (isset($f["min"]) && strlen($v) < $f["min"])
							{
								$this->addMessage(parseHash($g_messages["smin"], Array("name"=>rec_field_title($f, $name), "min"=>$f["min"])));
							}
							if (isset($f["max"]) && strlen($v) > $f["max"])
							{
								$this->addMessage(parseHash($g_messages["smax"], Array("name"=>rec_field_title($f, $name), "max"=>$f["max"])));
							}
						}
					}
				}

				$this->m_fields[$name]["value"] = $v;

			}
		}
	}





	protected function parseValues()
	{
		global $g_months;

		foreach ($this->m_fields as $name => $f)
		{
			if (! isset($f["value"]))
				continue;

			$type = "";
			if (isset($f["type"]))
				$type = $f["type"];

			if ($type == "file")
			{
				$this->setVar($name, $f["value"]);
			}
			else if ($type == "check")
			{
				$this->setVar($name, $f["value"] == 1 ? " checked" : "");
			}

//			else if ($type == "checks")
//			{
//				rec_parse_checks($name, $db, $html, $f["sql"], $f["value"]);
//			}
//			else if ($type == "checksOn")
//			{
//				rec_parse_checksOn($name, $db, $html, $f["sql"], $f["value"]);
//			}

			else if (($type == "ilov" || $type == "slov") && isset($f["options"]))
			{
				$this->setVar($name . "Options", HSelectOptions($f["options"], $f["value"])	);
			}
			else if (($type == "ilovRadio" || $type == "slovRadio") && isset($f["options"]))
			{
				foreach ($f["options"] as $val)
				{
					if ($f["value"] == $val)
						$this->setVar($name . "_" . $val, "checked");
				}
			}

//			else if (($type == "iselect" || $type == "sselect") && isset($f["storage"]))
//			{
//				$this->setVar($name . "Options", DSelectOptions($f["storage"], $f["value"]));
//			}

//			else if ($type == "idblookup")
//			{
//				$s = $db->DLookUp($f["sql"] . to_sql($f["value"], "Number"));
//				if ($s === 0) $s = "";
//				$this->setVar($name, $s);
//			}

			else if ($type == "lovlookup")
			{
				if (isset($f["options"][$f["value"]]))
					$this->setVar($name, $f["options"][$f["value"]]);
			}



			else if ($type == "date3")
			{
				$d = "";
				$m = "";
				$y = "";
				if ($f["value"] != "")
				{
					$aa = explode("-", $f["value"]);
					$d = $aa[2];
					$m = $aa[1];
					$y = $aa[0];
				}
				$this->setVar($name . "DayOptions", NSelectOptions(1, 31, $d));
				$this->setVar($name . "MonthOptions", HSelectOptions($g_months, $m));
				$yearMin = 1930;
				$yearMax = 2008;
				if (isset($f["min"]))
					$yearMin = $f["min"];
				if (isset($f["max"]))
					$yearMax = $f["max"];
				$this->setVar($name . "YearOptions", NSelectOptions($yearMin, $yearMax, $y));
			}
			else if ($type == "time2")
			{
				$h = "";
				$m = "";
				if ($f["value"] != "")
				{
					$aa = explode(":", $f["value"]);
					$h = $aa[0];
					$m = $aa[1];
				}
				$this->setVar($name . "Hour", $h);
				$this->setVar($name . "Mins", $m);
			}



			else {
				$this->setVar($name, $f["value"]);
			}
		}
	}



	// @return the set of Javascript checks
	protected function getChecks()
	{
		$ret = "";
		foreach ($this->m_fields as $name => $f)
		{
			if (isset($f["nocheck"]))
				continue;

			$type = "";
			if (isset($f["type"]))
				$type = $f["type"];

			if ((
				$type == "ilovRadio" ||
				$type == "slovRadio"
				) && (! isset($f["optional"])) )
			{
				$ret .= "sError += checkEmptyRadio(\"" .
					(isset($f["title"]) ? $f["title"] : $name) .
					"\", document.forms[\"" . $form . "\"]." . $name . 
					");";
			}

//				$type == "iselect" ||
//				$type == "sselect" ||
			else if ((
				$type == "ilov" ||
				$type == "slov"
				) )
			{
				if (! isset($f["optional"]))
				{
					$ret .= "sError += checkEmptyValue(\"" .
						(isset($f["title"]) ? $f["title"] : $name) .
						"\", document.forms[\"" . $form . "\"]." . $name . ".options[document.forms[\"" . $form . "\"]." . $name . ".selectedIndex].value" .
						");";
				}
			}
			else if ($type == "file")
			{
				if (! isset($f["optional"]))
				{
					$ret .= "sError += checkEmptyValue(\"" .
						(isset($f["title"]) ? $f["title"] : $name) .
						"\", document.forms[\"" . $form . "\"]." . $name . ".value" .
						");\n";
				}

				if (isset($f["exts"]))
				{
					$ss = "";
					$aE = explode("|", $f["exts"]);
					foreach ($aE as $e)
					{
						if ($ss != "") $ss .= " && ";
						$ss .= "sExt != \"." . $e . "\"";
					}
					if ($ss != "")
					$ret .= 
						"if (document.forms[\"" . $form . "\"]." . $name . ".value.length > 0)\n" .
						"{\n" .
						"	var sExt = document.forms[\"" . $form . "\"]." . $name . ".value;\n" .
						"	var i = sExt.lastIndexOf('.');\n" .
						"	if (i != -1)\n" .
						"	{\n" .
						"		sExt = sExt.substring(i).toLowerCase();\n" .
						"	}\n" .
						"	if (" . $ss . ")\n" .
						"	{\n" .
						"		sError += \"\\t" . rec_field_title($i, $name) . " has incorrect file type\\n\";\n" .
						"	}\n" .
						"}\n";
				}

			}
			else if ($type === "date3")
			{
				if (! isset($f["optional"]))
				{
					$ret .= "sError += checkEmptyValue3(\"" .
						(isset($f["title"]) ? $f["title"] : $name) .
						"\", " .
						"document.forms[\"" . $form . "\"]." . $name . "Day.options[document.forms[\"" . $form . "\"]." . $name . "Day.selectedIndex].value," .
						"document.forms[\"" . $form . "\"]." . $name . "Month.options[document.forms[\"" . $form . "\"]." . $name . "Month.selectedIndex].value," .
						"document.forms[\"" . $form . "\"]." . $name . "Year.options[document.forms[\"" . $form . "\"]." . $name . "Year.selectedIndex].value" .
						");";
				}
			}
			else if ($type === "time2")
			{
				if (! isset($f["optional"]))
				{
					$ret .= "sError += checkIntField(\"" .
						(isset($f["title"]) ? $f["title"] : $name) .
						"\", document.forms[\"" . $this->m_name . "\"]." . $name . "Hour.value," .
						((isset($f["optional"]) && $f["optional"] == 1) ? "false" : "true") .
						",0,23" .
						");";
					$ret .= "sError += checkIntField(\"" .
						(isset($f["title"]) ? $f["title"] : $name) .
						"\", document.forms[\"" . $this->m_name . "\"]." . $name . "Mins.value," .
						((isset($f["optional"]) && $f["optional"] == 1) ? "false" : "true") .
						",0,59" .
						");";

				}
			}
			else if ($type == "check")
			{
				if (! isset($f["optional"]))
					$ret .= "sError += checkCheckField(\"" .
						(isset($f["title"]) ? $f["title"] : $name) .
						"\", document.forms[\"" . $form . "\"]." . $name . ",1" .
						");";
			}
//			else if ($type == "checks" || $type == "checksOn")
//			{
//				if (! isset($f["optional"]))
//				{
//					// TODO ! ! !
//				}
//			}
			else if ($type == "int")
			{
				$ret .= "sError += checkIntField(\"" .
					(isset($f["title"]) ? $f["title"] : $name) .
					"\", document.forms[\"" . $this->m_name . "\"]." . $name . ".value," .
					((isset($f["optional"]) && $f["optional"] == 1) ? "false" : "true") .
					"," .
					(isset($f["min"]) ? $f["min"] : "NaN") .
					"," .
					(isset($f["max"]) ? $f["max"] : "NaN") .
					");";
			}
			else if ($type == "float")
			{
				$ret .= "sError += checkFloatField(\"" .
					(isset($f["title"]) ? $f["title"] : $name) .
					"\", document.forms[\"" . $this->m_name . "\"]." . $name . ".value," .
					((isset($f["optional"]) && $f["optional"] == 1) ? "false" : "true") .
					"," .
					(isset($f["min"]) ? $f["min"] : "NaN") .
					"," .
					(isset($f["max"]) ? $f["max"] : "NaN") .
					");";
			} else {
				$ret .= "sError += checkField(\"" .
					(isset($f["title"]) ? $f["title"] : $name) .
					"\", document.forms[\"" . $this->m_name . "\"]." . $name . ".value," .
					((isset($f["optional"]) && $f["optional"] == 1) ? "false" : "true") .
					"," .
					(isset($f["min"]) ? $f["min"] : "NaN") .
					"," .
					(isset($f["max"]) ? $f["max"] : "NaN") .
					");";
			}
		
			$ret .= "\n";
		}

		return $ret;
	}



	
}


//	Record block
//
//	rec_field_title($field, $name) returns the title of the field
//		$field is a ref. to field array

//	rec_fields_to_sql(&$fields) returns the list of pairs like name=value,name2=lvaue2 for sql-update
//	rec_sql_fields(&$fields) returns the list of fields names for sql=insert
//	rec_sql_values(&$fields) returns the list of fields values for sql=insert

//	rec_get_http($db, &$fields, $_id, $form, $_table) get values from HTTP

//	rec_get_db($db, &$fields, $sqlFromWhere) get values from db
//		$sqlFromWhere is a sub-sql like "FROM table WHERE ..."

//	rec_parse_checks($name, $db, &$html, $sql, $mask) parse set of items (checkboxes usually) by bit mask
//		$name is a html block name; there are tags {title} and {value} in the block
//		$sql select should have 2 fields 1-st is id and 2-nd is a title

//	rec_parse_checksOn($name, $db, &$html, $sql, $mask) the same as above but only checked items parsed
//
//
//
//
//	CHtmlRecord($db, $name, $html_path, $table, $sqlFromWhere, $return_page) constructor
//
//	types: text field by defaut; min max optional
//		plain
//		int			min max unique optional
//		float		min max unique optional
//		iselect		storage optional multiple
//			parse the list of options into {$nameOptions}
//		sselect		storage optional multiple
//			parse the list of options into {$nameOptions}
//		ilov		options optional
//			parse the list of options into {$nameOptions}
//		slov		options optional
//			parse the list of options into {$nameOptions}
//		check		value: 0 or 1; optional or should be 1
//			in db it should be 'Y' or 'N'
//			parse {$name} or {$name_no} as ' checked'
//		-checks		sql(value,title)
//			value: bit mask of checked checkboxes; optional or should be checked at least one
//			parse all values
//		-checksOn	sql(value,title)
//			value: bit mask of checked checkboxes; optional or should be checked at least one
//			parse only checked values by bit mask
//		date3		optional
//			value as 2006-03-11
//			3 http values: nameYear, nameMonth, nameDate
//			parse options for 3 select's: DayOptions, MonthOptions and YearOptions
//		time2		optional
//			value as 23:59
//			2 http values: nameHour, nameMins
//		-idblookup	sql
//		lovlookup	options
//		file		dir file exts max nocheck optional
//		ilovRadio	options optional
//			parse the set of radio buttons by scheme name_value
//		slovRadio	options optional
//			parse the set of radio buttons by scheme name_value
//
//	more options:
//		nodb		- don't get from db
//		noinsert,	- don't insert
//		noupdate,	- don't update
//		nocheck,	- don't check
//		nohttp		- don't get from http
//		dbSelect - field in sql select
//
//	example:
//
//	$compose = new CComposeForm($db, "compose", null, "messages", "FROM messages WHERE messageId=", "compose.php?");
//	$compose->m_fields["message"] = Array ("title"=>"Сообщение", "value"=>"", "min"=>0, "max"=>64);
//	$compose->m_fields["userId"] = Array ("type"=>"int", "value"=>$toId, "noupdate"=>1, "nocheck"=>1);
//	$compose->m_fields["fromId"] = Array ("type"=>"int", "value"=>$userId, "noupdate"=>1, "nohttp"=>1, "nocheck"=>1);
//	$compose->m_fields["sent"] = Array ("plain" => "now()", "type"=>"plain", "noupdate"=>1, "nohttp"=>1, "nocheck"=>1);
//	$page->add($compose);
//
//
// TODO
// change (isset($i["title"]) ? $i["title"] : $name) to rec_field_title($i, $name)
// add date,time and datetime formats
//
//
//	2006-11-28








?>