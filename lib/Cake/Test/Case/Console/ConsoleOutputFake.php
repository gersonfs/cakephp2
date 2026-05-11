<?php

class ConsoleOutputFake extends ConsoleOutput
{

	/** @var string */
	public $output = '';

	public array $outputs = [];

	protected function _write($message)
	{
		$this->output .= $message;
		$this->outputs[] = $message;
	}

}
