<?php

namespace MiniPhpRest\core\utils\lang;

/**
 * @template T
 */
class HashSet
{
	/**
	 * @var array<string, T>
	 */
	private array $backedArray = [];

	/**
	 * Ajoute une valeur à l'ensemble.
	 *
	 * Si la valeur existe déjà, elle est simplement remplacée à la même position logique.
	 *
	 * @param T $value
	 * @return void
	 */
	public function add($value): void
	{
		$hash = $this->getHash($value);
		$this->backedArray[$hash] = $value;
	}

	/**
	 * Alias de add() pour garder une API proche de HashMap.
	 *
	 * @param T $value
	 * @return void
	 */
	public function push($value): void
	{
		$this->add($value);
	}

	/**
	 * Vérifie si la valeur est présente dans l'ensemble.
	 *
	 * @param T $value
	 * @return bool
	 */
	public function contains($value): bool
	{
		$hash = $this->getHash($value);
		return array_key_exists($hash, $this->backedArray);
	}

	/**
	 * Supprime une valeur de l'ensemble si elle existe.
	 *
	 * @param T $value
	 * @return void
	 */
	public function remove($value): void
	{
		$hash = $this->getHash($value);
		if (array_key_exists($hash, $this->backedArray)) {
			unset($this->backedArray[$hash]);
		}
	}

	/**
	 * Vide l'ensemble.
	 *
	 * @return void
	 */
	public function clear(): void
	{
		$this->backedArray = [];
	}

	/**
	 * Retourne le nombre d'éléments distincts présents dans l'ensemble.
	 *
	 * @return int
	 */
	public function count(): int
	{
		return count($this->backedArray);
	}

	/**
	 * Indique si l'ensemble est vide.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	/**
	 * Retourne les valeurs de l'ensemble sous forme de tableau indexé.
	 *
	 * @return array<int, T>
	 */
	public function getEntriesAsArray(): array
	{
		return array_values($this->backedArray);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function getHash($value): string
	{
		return hash('sha256', serialize($value));
	}

}