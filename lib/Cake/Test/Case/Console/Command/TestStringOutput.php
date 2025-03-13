<?php

class TestStringOutput extends ConsoleOutput
{

	/** @var string */
	public $output = '';

	protected function _write($message)
	{
		$this->output .= $message;
	}

}
