<?php

class PDOStatementFake extends PDOStatement
{
	/**
	 * @param $mode
	 * @param $cursorOrientation
	 * @param $cursorOffset
	 * @return mixed
	 */
	public function fetch($mode = PDO::FETCH_BOTH,
						  $cursorOrientation = PDO::FETCH_ORI_NEXT,
						  $cursorOffset = 0)
	{
		return parent::fetch($mode, $cursorOrientation, $cursorOffset);
	}

	/**
	 * @param string|null $class
	 * @param array $constructorArgs
	 * @return false|mixed|object
	 */
	public function fetchObject(?string $class = "\stdClass",  array $constructorArgs = [])
	{
		return parent::fetchObject($class, $constructorArgs);
	}

	/**
	 * @param int $column
	 * @return array|false
	 */
	public function getColumnMeta(int $column)
	{
		return parent::getColumnMeta($column);
	}

	/**
	 * @return array|void
	 */
	public function errorInfo()
	{
		return parent::errorInfo();
	}
}
