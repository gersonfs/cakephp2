<?php

class PDOStatementFake extends PDOStatement
{
	#[\ReturnTypeWillChange]
	public function fetch($mode = PDO::FETCH_BOTH,
						  $cursorOrientation = PDO::FETCH_ORI_NEXT,
						  $cursorOffset = 0)
	{
		return parent::fetch($mode, $cursorOrientation, $cursorOffset);
	}

	#[\ReturnTypeWillChange]
	public function fetchObject(?string $class = "\stdClass",  array $constructorArgs = [])
	{
		return parent::fetchObject($class, $constructorArgs);
	}

	#[\ReturnTypeWillChange]
	public function getColumnMeta(int $column)
	{
		return parent::getColumnMeta($column);
	}

	#[\ReturnTypeWillChange]
	public function errorInfo()
	{
		return parent::errorInfo();
	}
}
