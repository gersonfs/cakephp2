<?php

class PDOStatementFake extends PDOStatement
{
	public function fetch($mode = PDO::FETCH_BOTH,
						  $cursorOrientation = PDO::FETCH_ORI_NEXT,
						  $cursorOffset = 0): mixed
	{
		return parent::fetch();
	}

	public function fetchObject(?string $class = "\stdClass",  array $constructorArgs = []): mixed
	{
		return parent::fetchObject($class, $constructorArgs);
	}

	public function getColumnMeta(int $column): mixed
	{
		return parent::getColumnMeta($column);
	}
}
