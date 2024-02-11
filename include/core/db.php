<?php

//
//	DB layer class specified for MySQL
//
//	Specify connection paramters in CDB class
//
//	there are several global functions:
//
//	HSelectOptions(&$hash, $value) // returns string with <option>'s for html <select> by hash
//		$value - current  value
//	NSelectOptions($min, $max, $value) // returns string with <option>'s for html <select> by int numbers range
//		$value - current  value
//	to_sql($Value, $ValueType) // converts value into specified sql expr.
//		$ValueType:
//			Plain - as is
//			EmptyString - as string but insert NULL for empty value
//			Number, Float - as double value
//			Check - 'Y' for value=1, 'N' in any other case
//
//
//	CDB class (specified for MySQL)
//		connect()
//		close()
//		execute($sql) - returns 1 if it's ok, 0-error
//		query($sql) - returns 1 if it's ok, 0-error
//		fetch_row() - returns $row[] hash; used with query()
//		affected_rows() - returns the number of affected rows by UPDATE
//		free_result() - close resultset opened by query()
//		get_insert_id() - returns last insert id for this connection
//		DLookUP($sql) - returns 1-st field of 1-st record or 0
//		DSelectOptions($sql, $selected) - returns string with <options>'s for html <select>
//			by specified query: 1-st field - value, 2-nd field - title
//			$selected - current value
//
//	this class is not tested well in case of using several mysql connections
//	



function to_sql($Value, $ValueType = "")
{
  if($ValueType == "Plain")
  {
    return $Value;
  }

  if(strlen($Value) == 0 && $ValueType != "EmptyStr")
  {
    return "NULL";
  }
  else
  {
    if($ValueType == "Number" || $ValueType == "Float")
    {
      return doubleval(str_replace(",", ".", $Value));
    }
    else if ($ValueType == "Check")
    {
      return ($Value == 1 ? "'Y'" : "'N'");
	}
    else
    {
      return "'" . str_replace("'", "''", str_replace("\\", "\\\\", $Value)) . "'";
    }
  }
}

function add_where($s, $s2)
{
	if ($s != "" && $s != "")
		$s .= " AND ";
	return $s . $s2;
}


class CDB
{
	// connection parameters

	var $sHost = DB_HOST;
	var $sDB = DB_DB;
	var $sLogin = DB_Login;
	var $sPassword = DB_Password;

	var $conn = 0;	// the connection
	var $res = 0;	// result created by query(); internal usage

	function connect()
	{
		$this->conn = mysql_connect($this->sHost, $this->sLogin, $this->sPassword);
		if (! $this->conn)
		{
//			die("Can't connect to database: " . mysql_errormsg());
			die("Can't connect to database");
		}
		mysql_select_db($this->sDB, $this->conn) || die("Can't select database");
	}

	function close()
	{
		if ($this->conn)
		{
			mysql_close($this->conn);
			$this->conn = 0;
		}
	}	

	function execute($sql)
	{
		if (! mysql_query($sql, $this->conn))
			return 0;
		return 1;
	}

	function query($sql)
	{
		if ($this->res)
		{
			mysql_free_result($this->res);
			$this->res = 0;
		}
		$this->res = mysql_query($sql, $this->conn);
		if ($this->res)
			return 1;
		else
			return 0;
	}

	function queryAll($sql)
	{
		if ($this->query($sql))
		{
			$a = Array();
			while($row = mysql_fetch_array($this->res))
			{
				$a[] = $row;
			}
			$this->free_result();
			return $a;
		} else
			return false;
	}
	function queryRow($sql)
	{
		if ($this->query($sql))
		{
			$a = Array();
			$row = mysql_fetch_array($this->res);
			$this->free_result();
			if($row)
				return $row;
		}
		return false;
	}
	function queryList($sql)
	{
		if ($this->query($sql))
		{
			$s = "";
			while($row = mysql_fetch_array($this->res))
			{
				if ($s != "")
					$s .= ",";
				$s .= $row[0];
			}
			$this->free_result();
			return $s;
		} else
			return "";
	}
	function querySQLList($sql, $ValueType = "")
	{
		if ($this->query($sql))
		{
			$s = "";
			while($row = mysql_fetch_array($this->res))
			{
				if ($s != "")
					$s .= ",";
				$s .= to_sql($row[0], $ValueType);
			}
			$this->free_result();
			return $s;
		} else
			return "";
	}

	function fetch_row()
	{
		if (! $this->res)
			return 0;
		$ret = mysql_fetch_array($this->res);
		if (! $ret)
		{
			mysql_free_result($this->res);
			$this->res = 0;
		}
		return $ret;
	}

    function affected_rows()
	{
		return mysql_affected_rows($this->conn);
	}

	function free_result()
	{
		if ($this->res)
		{
			mysql_free_result($this->res);
			$this->res = 0;
		}
	}

	function get_insert_id()
	{
		return mysql_insert_id($this->conn);
	}


    function DLookUP($sql)
	{
		$ret = 0;
		if ($this->query($sql))
		{
			if ($row = $this->fetch_row())
			{
				$ret = $row[0];
				mysql_free_result($this->res);
				$this->res = 0;
			}
		}
		return $ret;
	}

    function DSelectOptions($sql, $selected)
	{
		$ret = "";
		if ($this->query($sql))
		{
			while ($row = $this->fetch_row())
			{
				$ret .= "<option value=\"" . $row[0] . "\"" . (($row[0] == $selected) ? " selected" : "") . ">" . $row[1] . "</options>\n";
			}
			$this->free_result();
		}
		return $ret;
	}


}

?>