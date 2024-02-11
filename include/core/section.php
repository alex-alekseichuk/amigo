<?


class SectionInFile
{
	protected $sBegin = "# BEGIN";
	protected $sEnd = "# END";

	protected $linesBefore = Array();
	protected $linesAfter = Array();

	protected $fpw = null;

	function __construct($sBegin, $sEnd)
	{
		$this->sBegin = $sBegin;
		$this->sEnd = $sEnd;
	}

	protected function OnBeforeAddSection()
	{
	}

	private function writeSectionContent($content)
	{
		if (! $this->fpw)
			return;
		
		fputs($this->fpw, $content . "\n");
	}
	private function addSection($content)
	{
		if (! $this->fpw)
			return;

		$this->OnBeforeAddSection();

		fputs($this->fpw, $this->sBegin . "\n");
		$this->writeSectionContent($content);
		fputs($this->fpw, $this->sEnd . "\n");

		fclose($this->fpw);
		$this->fpw = null;
	}
	private function writeLinesBefore($path)
	{
		$this->fpw = @fopen($path, "w");
		if (! $this->fpw)
			return;
		foreach($this->linesBefore as $line)
			fputs($this->fpw, $line);
	}
	private function writeLinesAfter()
	{
		if (! $this->fpw)
			return;

		foreach($this->linesAfter as $line)
			fputs($this->fpw, $line);

		fclose($this->fpw);
		$this->fpw = null;
	}


	///
	/// @param $path path to file with section
	/// @param $content content of the section without markers
	/// @return true for correct process or false if something failed
	public function update($path, $content)
	{
		// open file
		$fpr = @fopen($path, "r");
		if (! $fpr)
			return false;

		$this->linesBefore = Array();
		$this->linesAfter = Array();

		// find the begin of the section
		while (! feof($fpr))
		{
			// just copy lines
			$line = fgets($fpr, 4096);
			$this->linesBefore[] = $line;
			//fputs($fpw, $line);

			// if found the section
			if (ereg("^" . $this->sBegin, $line))
				break;
		}

		if (feof($fpr))
		{
			fclose($fpr);
			$this->writeLinesBefore($path);
			$this->addSection($content);
		} else {

			// skip till the end of the section
			while (! feof($fpr))
			{
				$line = fgets($fpr, 4096);

				// found end of section
				if (ereg("^" . $this->sEnd, $line))
				{
					$this->linesAfter[] = $line;
					break;
				}
			}

			// can't find end mark
			if (feof($fpr))
			{
				fclose($fpr);
				return false;
			}

			// read till the end of the file
			while (! feof($fpr))
			{
				$this->lineAfter[] = fgets($fpr, 4096);
			}			
			fclose($fpr);

			$this->writeLinesBefore($path);
			$this->writeSectionContent($content);
			$this->writeLinesAfter($path);
		}



		return true;
		
	}



	/// @param $path path to file with section
	/// @return true for correct process or false if something failed
	public function remove($path)
	{
		// open file
		$fpr = @fopen($path, "r");
		if (! $fpr)
			return false;

		$this->linesBefore = Array();
		$this->linesAfter = Array();

		// find the begin of the section
		while (! feof($fpr))
		{
			// just copy lines
			$line = fgets($fpr, 4096);

			// if found the section
			if (ereg("^" . $this->sBegin, $line))
				break;

			$this->linesBefore[] = $line;
		}

		if (feof($fpr))
		{
			fclose($fpr);
			$this->writeLinesBefore($path);
			fclose($this->fpw);
			$this->fpw = null;
		} else {

			// skip till the end of the section
			while (! feof($fpr))
			{
				$line = fgets($fpr, 4096);

				// found end of section
				if (ereg("^" . $this->sEnd, $line))
				{
					break;
				}
			}

			// can't find end mark
			if (feof($fpr))
			{
				fclose($fpr);
				return false;
			}

			// read till the end of the file
			while (! feof($fpr))
			{
				$this->lineAfter[] = fgets($fpr, 4096);
			}			
			fclose($fpr);

			$this->writeLinesBefore($path);
			$this->writeLinesAfter($path);
		}
		
		return true;
	}

}


?>