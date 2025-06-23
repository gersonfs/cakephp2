<?php

App::uses('ConsoleInput', 'Console');

class ConsoleInputFake extends ConsoleInput
{
	private array $returnValues = [];

	public function addReturnValue(mixed $valor): void
	{
		$this->returnValues[] = $valor;
	}

	public function read(): mixed
	{
		return array_shift($this->returnValues);
	}
}
