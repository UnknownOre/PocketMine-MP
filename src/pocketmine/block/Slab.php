<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\SlabType;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Slab extends Transparent{
	/** @var int */
	protected $doubleId;

	/** @var SlabType */
	protected $slabType;

	public function __construct(int $id, int $doubleId, int $variant = 0, ?string $name = null){
		parent::__construct($id, $variant, $name . " Slab", $id);
		$this->doubleId = $doubleId;
		$this->slabType = SlabType::BOTTOM();
	}

	public function getId() : int{
		return $this->slabType === SlabType::DOUBLE() ? $this->doubleId : parent::getId();
	}

	protected function writeStateToMeta() : int{
		if($this->slabType !== SlabType::DOUBLE()){
			return ($this->slabType === SlabType::TOP() ? 0x08 : 0);
		}
		return 0;
	}

	public function readStateFromMeta(int $meta) : void{
		if($this->slabType !== SlabType::DOUBLE()){
			$this->slabType = ($meta & 0x08) !== 0 ? SlabType::TOP() : SlabType::BOTTOM();
		}
	}

	public function getStateBitmask() : int{
		return 0b1000;
	}

	public function isTransparent() : bool{
		return $this->slabType !== SlabType::DOUBLE();
	}

	/**
	 * Returns the type of slab block.
	 *
	 * @return SlabType
	 */
	public function getSlabType() : SlabType{
		return $this->slabType;
	}

	/**
	 * @param SlabType $slabType
	 *
	 * @return $this
	 */
	public function setSlabType(SlabType $slabType) : self{
		$this->slabType = $slabType;
		return $this;
	}

	public function canBePlacedAt(Block $blockReplace, Vector3 $clickVector, int $face, bool $isClickedBlock) : bool{
		if(parent::canBePlacedAt($blockReplace, $clickVector, $face, $isClickedBlock)){
			return true;
		}

		if($blockReplace instanceof Slab and $blockReplace->slabType !== SlabType::DOUBLE() and $blockReplace->isSameType($this)){
			if($blockReplace->slabType === SlabType::TOP()){ //Trying to combine with top slab
				return $clickVector->y <= 0.5 or (!$isClickedBlock and $face === Facing::UP);
			}else{
				return $clickVector->y >= 0.5 or (!$isClickedBlock and $face === Facing::DOWN);
			}
		}

		return false;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		/* note these conditions can't be merged, since one targets clicked and the other replace */

		if($blockClicked instanceof Slab and $blockClicked->slabType !== SlabType::DOUBLE() and $blockClicked->isSameType($this) and (
			($face === Facing::DOWN and $blockClicked->slabType === SlabType::TOP()) or
			($face === Facing::UP and $blockClicked->slabType === SlabType::BOTTOM())
		)){
			$this->slabType = SlabType::DOUBLE();
			return $this->level->setBlock($blockClicked, $this);
		}

		if($blockReplace instanceof Slab and $blockReplace->slabType !== SlabType::DOUBLE() and $blockReplace->isSameType($this) and (
			($blockReplace->slabType === SlabType::TOP() and ($clickVector->y <= 0.5 or $face === Facing::UP)) or
			($blockReplace->slabType === SlabType::BOTTOM() and ($clickVector->y >= 0.5 or $face === Facing::DOWN))
		)){
			//Clicked in empty half of existing slab
			$this->slabType = SlabType::DOUBLE();
		}else{
			$this->slabType = (($face !== Facing::UP && $clickVector->y > 0.5) || $face === Facing::DOWN) ? SlabType::TOP() : SlabType::BOTTOM();
		}

		return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		if($this->slabType === SlabType::DOUBLE()){
			return parent::recalculateBoundingBox();
		}
		return AxisAlignedBB::one()->trim($this->slabType === SlabType::TOP() ? Facing::DOWN : Facing::UP, 0.5);
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [$this->getItem()->setCount($this->slabType === SlabType::DOUBLE() ? 2 : 1)];
	}
}
