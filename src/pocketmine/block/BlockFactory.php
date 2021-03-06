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

use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\InvalidBlockStateException;
use pocketmine\block\utils\PillarRotationTrait;
use pocketmine\block\utils\SlabType;
use pocketmine\block\utils\TreeType;
use pocketmine\item\Item;
use pocketmine\level\Position;
use function array_fill;
use function file_get_contents;
use function get_class;
use function json_decode;
use function max;
use function min;

/**
 * Manages block registration and instance creation
 */
class BlockFactory{
	/** @var \SplFixedArray<Block> */
	private static $fullList = null;

	/** @var \SplFixedArray<int> */
	public static $lightFilter = null;
	/** @var \SplFixedArray<bool> */
	public static $diffusesSkyLight = null;
	/** @var \SplFixedArray<float> */
	public static $blastResistance = null;

	/** @var \SplFixedArray|int[] */
	private static $stateMasks = null;

	/** @var int[] */
	public static $staticRuntimeIdMap = [];

	/** @var int[] */
	public static $legacyIdMap = [];

	/** @var int */
	private static $lastRuntimeId = 0;

	/**
	 * Initializes the block factory. By default this is called only once on server start, however you may wish to use
	 * this if you need to reset the block factory back to its original defaults for whatever reason.
	 */
	public static function init() : void{
		self::$fullList = new \SplFixedArray(8192);

		self::$lightFilter = \SplFixedArray::fromArray(array_fill(0, 8192, 1));
		self::$diffusesSkyLight = \SplFixedArray::fromArray(array_fill(0, 8192, false));
		self::$blastResistance = \SplFixedArray::fromArray(array_fill(0, 8192, 0));

		self::$stateMasks = new \SplFixedArray(8192);

		self::register(new ActivatorRail());
		self::register(new Air());
		self::register(new Anvil(Block::ANVIL, Anvil::TYPE_NORMAL, "Anvil"));
		self::register(new Anvil(Block::ANVIL, Anvil::TYPE_SLIGHTLY_DAMAGED, "Slightly Damaged Anvil"));
		self::register(new Anvil(Block::ANVIL, Anvil::TYPE_VERY_DAMAGED, "Very Damaged Anvil"));
		self::register(new Bed());
		self::register(new Bedrock());
		self::register(new Beetroot());
		self::register(new BoneBlock());
		self::register(new Bookshelf());
		self::register(new BrewingStand());
		self::register(new BrickStairs());
		self::register(new Bricks());
		self::register(new BrownMushroom());
		self::register(new BrownMushroomBlock());
		self::register(new Cactus());
		self::register(new Cake());
		self::register(new Carrot());
		self::register(new Chest());
		self::register(new Clay());
		self::register(new Coal());
		self::register(new CoalOre());
		self::register(new CoarseDirt(Block::DIRT, Dirt::COARSE, "Coarse Dirt"));
		self::register(new Cobblestone());
		self::register(new CobblestoneStairs());
		self::register(new Cobweb());
		self::register(new CocoaBlock());
		self::register(new CraftingTable());
		self::register(new Dandelion());
		self::register(new DaylightSensor());
		self::register((new DaylightSensor())->setInverted()); //flattening hack
		self::register(new DeadBush());
		self::register(new DetectorRail());
		self::register(new Diamond());
		self::register(new DiamondOre());
		self::register(new Dirt(Block::DIRT, Dirt::NORMAL, "Dirt"));
		self::register(new DoublePlant(Block::DOUBLE_PLANT, 0, "Sunflower"));
		self::register(new DoublePlant(Block::DOUBLE_PLANT, 1, "Lilac"));
		self::register(new DoublePlant(Block::DOUBLE_PLANT, 4, "Rose Bush"));
		self::register(new DoublePlant(Block::DOUBLE_PLANT, 5, "Peony"));
		self::register(new DoubleTallGrass(Block::DOUBLE_PLANT, 2, "Double Tallgrass"));
		self::register(new DoubleTallGrass(Block::DOUBLE_PLANT, 3, "Large Fern"));
		self::register(new Emerald());
		self::register(new EmeraldOre());
		self::register(new EnchantingTable());
		self::register(new EndPortalFrame());
		self::register(new EndRod());
		self::register(new EndStone());
		self::register(new EndStoneBricks());
		self::register(new EnderChest());
		self::register(new Farmland());
		self::register(new FenceGate(Block::ACACIA_FENCE_GATE, 0, "Acacia Fence Gate"));
		self::register(new FenceGate(Block::BIRCH_FENCE_GATE, 0, "Birch Fence Gate"));
		self::register(new FenceGate(Block::DARK_OAK_FENCE_GATE, 0, "Dark Oak Fence Gate"));
		self::register(new FenceGate(Block::JUNGLE_FENCE_GATE, 0, "Jungle Fence Gate"));
		self::register(new FenceGate(Block::OAK_FENCE_GATE, 0, "Oak Fence Gate"));
		self::register(new FenceGate(Block::SPRUCE_FENCE_GATE, 0, "Spruce Fence Gate"));
		self::register(new Fire());
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_ALLIUM, "Allium"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_AZURE_BLUET, "Azure Bluet"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_BLUE_ORCHID, "Blue Orchid"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_ORANGE_TULIP, "Orange Tulip"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_OXEYE_DAISY, "Oxeye Daisy"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_PINK_TULIP, "Pink Tulip"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_POPPY, "Poppy"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_RED_TULIP, "Red Tulip"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_WHITE_TULIP, "White Tulip"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_CORNFLOWER, "Cornflower"));
		self::register(new Flower(Block::RED_FLOWER, Flower::TYPE_LILY_OF_THE_VALLEY, "Lily of the Valley"));
		self::register(new FlowerPot());
		self::register(new Furnace());
		self::register((new Furnace())->setLit()); //flattening hack
		self::register(new Glass(Block::GLASS, 0, "Glass"));
		self::register(new GlassPane(Block::GLASS_PANE, 0, "Glass Pane"));
		self::register(new GlazedTerracotta(Block::BLACK_GLAZED_TERRACOTTA, 0, "Black Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::BLUE_GLAZED_TERRACOTTA, 0, "Blue Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::BROWN_GLAZED_TERRACOTTA, 0, "Brown Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::CYAN_GLAZED_TERRACOTTA, 0, "Cyan Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::GRAY_GLAZED_TERRACOTTA, 0, "Grey Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::GREEN_GLAZED_TERRACOTTA, 0, "Green Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::LIGHT_BLUE_GLAZED_TERRACOTTA, 0, "Light Blue Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::LIME_GLAZED_TERRACOTTA, 0, "Lime Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::MAGENTA_GLAZED_TERRACOTTA, 0, "Magenta Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::ORANGE_GLAZED_TERRACOTTA, 0, "Orange Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::PINK_GLAZED_TERRACOTTA, 0, "Pink Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::PURPLE_GLAZED_TERRACOTTA, 0, "Purple Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::RED_GLAZED_TERRACOTTA, 0, "Red Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::SILVER_GLAZED_TERRACOTTA, 0, "Light Grey Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::WHITE_GLAZED_TERRACOTTA, 0, "White Glazed Terracotta"));
		self::register(new GlazedTerracotta(Block::YELLOW_GLAZED_TERRACOTTA, 0, "Yellow Glazed Terracotta"));
		self::register(new GlowingObsidian());
		self::register(new Glowstone());
		self::register(new Gold());
		self::register(new GoldOre());
		self::register(new Grass());
		self::register(new GrassPath());
		self::register(new Gravel());
		self::register(new HardenedClay(Block::HARDENED_CLAY, 0, "Hardened Clay"));
		self::register(new HardenedGlass(Block::HARD_GLASS, 0, "Hardened Glass"));
		self::register(new HardenedGlassPane(Block::HARD_GLASS_PANE, 0, "Hardened Glass Pane"));
		self::register(new HayBale());
		self::register(new Ice());
		self::register(new InfoUpdate(Block::INFO_UPDATE, 0, "update!"));
		self::register(new InfoUpdate(Block::INFO_UPDATE2, 0, "ate!upd"));
		self::register(new InvisibleBedrock());
		self::register(new Iron());
		self::register(new IronBars());
		self::register(new IronDoor());
		self::register(new IronOre());
		self::register(new IronTrapdoor());
		self::register(new ItemFrame());
		self::register(new Ladder());
		self::register(new Lapis());
		self::register(new LapisOre());
		self::register(new Lava());
		self::register((new Lava())->setStill()); //flattening hack
		self::register(new Lever());
		self::register(new LitPumpkin());
		self::register(new Magma());
		self::register(new Melon());
		self::register(new MelonStem());
		self::register(new MonsterSpawner());
		self::register(new MossyCobblestone());
		self::register(new Mycelium());
		self::register(new NetherBrick(Block::NETHER_BRICK_BLOCK, 0, "Nether Bricks"));
		self::register(new NetherBrick(Block::RED_NETHER_BRICK, 0, "Red Nether Bricks"));
		self::register(new NetherBrickFence());
		self::register(new NetherBrickStairs());
		self::register(new NetherQuartzOre());
		self::register(new NetherReactor());
		self::register(new NetherWartBlock());
		self::register(new NetherWartPlant());
		self::register(new Netherrack());
		self::register(new NoteBlock());
		self::register(new Obsidian());
		self::register(new PackedIce());
		self::register(new Podzol());
		self::register(new Potato());
		self::register(new PoweredRail());
		self::register(new Prismarine(Block::PRISMARINE, Prismarine::BRICKS, "Prismarine Bricks"));
		self::register(new Prismarine(Block::PRISMARINE, Prismarine::DARK, "Dark Prismarine"));
		self::register(new Prismarine(Block::PRISMARINE, Prismarine::NORMAL, "Prismarine"));
		self::register(new Pumpkin());
		self::register(new PumpkinStem());
		self::register(new Purpur(Block::PURPUR_BLOCK, 0, "Purpur Block"));
		self::register(new class(Block::PURPUR_BLOCK, 2, "Purpur Pillar") extends Purpur{
			use PillarRotationTrait;
		});
		self::register(new PurpurStairs());
		self::register(new Quartz(Block::QUARTZ_BLOCK, Quartz::NORMAL, "Quartz Block"));
		self::register(new class(Block::QUARTZ_BLOCK, Quartz::CHISELED, "Chiseled Quartz Block") extends Quartz{
			use PillarRotationTrait;
		});
		self::register(new class(Block::QUARTZ_BLOCK, Quartz::PILLAR, "Quartz Pillar") extends Quartz{
			use PillarRotationTrait;
		});
		self::register(new Quartz(Block::QUARTZ_BLOCK, Quartz::SMOOTH, "Smooth Quartz Block")); //TODO: this has axis rotation in 1.9, unsure if a bug (https://bugs.mojang.com/browse/MCPE-39074)
		self::register(new QuartzStairs());
		self::register(new Rail());
		self::register(new RedMushroom());
		self::register(new RedMushroomBlock());
		self::register(new RedSandstoneStairs());
		self::register(new Redstone());
		self::register(new RedstoneLamp());
		self::register((new RedstoneLamp())->setLit()); //flattening hack
		self::register(new RedstoneOre());
		self::register((new RedstoneOre())->setLit()); //flattening hack
		self::register(new RedstoneRepeater());
		self::register((new RedstoneRepeater())->setPowered());
		self::register(new RedstoneTorch());
		self::register((new RedstoneTorch())->setLit(false)); //flattening hack
		self::register(new RedstoneWire());
		self::register(new Reserved6(Block::RESERVED6, 0, "reserved6"));
		self::register(new Sand(Block::SAND, 0, "Sand"));
		self::register(new Sand(Block::SAND, 1, "Red Sand"));
		self::register(new SandstoneStairs());
		self::register(new SeaLantern());
		self::register(new SignPost());
		self::register(new Skull());
		self::register(new SmoothStone(Block::STONE, Stone::NORMAL, "Stone"));
		self::register(new Snow());
		self::register(new SnowLayer());
		self::register(new SoulSand());
		self::register(new Sponge());
		self::register(new StandingBanner());
		self::register(new Stone(Block::STONE, Stone::ANDESITE, "Andesite"));
		self::register(new Stone(Block::STONE, Stone::DIORITE, "Diorite"));
		self::register(new Stone(Block::STONE, Stone::GRANITE, "Granite"));
		self::register(new Stone(Block::STONE, Stone::POLISHED_ANDESITE, "Polished Andesite"));
		self::register(new Stone(Block::STONE, Stone::POLISHED_DIORITE, "Polished Diorite"));
		self::register(new Stone(Block::STONE, Stone::POLISHED_GRANITE, "Polished Granite"));
		self::register(new StoneBrickStairs());
		self::register(new StoneBricks(Block::STONE_BRICKS, StoneBricks::CHISELED, "Chiseled Stone Bricks"));
		self::register(new StoneBricks(Block::STONE_BRICKS, StoneBricks::CRACKED, "Cracked Stone Bricks"));
		self::register(new StoneBricks(Block::STONE_BRICKS, StoneBricks::MOSSY, "Mossy Stone Bricks"));
		self::register(new StoneBricks(Block::STONE_BRICKS, StoneBricks::NORMAL, "Stone Bricks"));
		self::register(new StoneButton());
		self::register(new StonePressurePlate());
		self::register(new Stonecutter());
		self::register(new Sugarcane());
		self::register(new TNT());
		self::register(new TallGrass(Block::TALL_GRASS, 0, "Fern"));
		self::register(new TallGrass(Block::TALL_GRASS, 1, "Tall Grass"));
		self::register(new TallGrass(Block::TALL_GRASS, 2, "Fern"));
		self::register(new TallGrass(Block::TALL_GRASS, 3, "Fern"));
		self::register(new Torch(Block::COLORED_TORCH_BP, 0, "Blue Torch"));
		self::register(new Torch(Block::COLORED_TORCH_BP, 8, "Purple Torch"));
		self::register(new Torch(Block::COLORED_TORCH_RG, 0, "Red Torch"));
		self::register(new Torch(Block::COLORED_TORCH_RG, 8, "Green Torch"));
		self::register(new Torch(Block::TORCH, 0, "Torch"));
		self::register(new Trapdoor());
		self::register(new TrappedChest());
		self::register(new Tripwire());
		self::register(new TripwireHook());
		self::register(new UnderwaterTorch(Block::UNDERWATER_TORCH, 0, "Underwater Torch"));
		self::register(new Vine());
		self::register(new WallBanner());
		self::register(new WallSign());
		self::register(new Water());
		self::register((new Water())->setStill()); //flattening hack
		self::register(new WaterLily());
		self::register(new WeightedPressurePlateHeavy());
		self::register(new WeightedPressurePlateLight());
		self::register(new Wheat());
		self::register(new WoodenButton());
		self::register(new WoodenDoor(Block::ACACIA_DOOR_BLOCK, 0, "Acacia Door", Item::ACACIA_DOOR));
		self::register(new WoodenDoor(Block::BIRCH_DOOR_BLOCK, 0, "Birch Door", Item::BIRCH_DOOR));
		self::register(new WoodenDoor(Block::DARK_OAK_DOOR_BLOCK, 0, "Dark Oak Door", Item::DARK_OAK_DOOR));
		self::register(new WoodenDoor(Block::JUNGLE_DOOR_BLOCK, 0, "Jungle Door", Item::JUNGLE_DOOR));
		self::register(new WoodenDoor(Block::OAK_DOOR_BLOCK, 0, "Oak Door", Item::OAK_DOOR));
		self::register(new WoodenDoor(Block::SPRUCE_DOOR_BLOCK, 0, "Spruce Door", Item::SPRUCE_DOOR));
		self::register(new WoodenPressurePlate());
		self::register(new WoodenStairs(Block::ACACIA_STAIRS, 0, "Acacia Stairs"));
		self::register(new WoodenStairs(Block::BIRCH_STAIRS, 0, "Birch Stairs"));
		self::register(new WoodenStairs(Block::DARK_OAK_STAIRS, 0, "Dark Oak Stairs"));
		self::register(new WoodenStairs(Block::JUNGLE_STAIRS, 0, "Jungle Stairs"));
		self::register(new WoodenStairs(Block::OAK_STAIRS, 0, "Oak Stairs"));
		self::register(new WoodenStairs(Block::SPRUCE_STAIRS, 0, "Spruce Stairs"));

		foreach(TreeType::getAll() as $treeType){
			$magicNumber = $treeType->getMagicNumber();
			$name = $treeType->getDisplayName();
			self::register(new Planks(Block::PLANKS, $magicNumber, $name . " Planks"));
			self::register(new Sapling(Block::SAPLING, $magicNumber, $treeType, $name . " Sapling"));
			self::register(new WoodenFence(Block::FENCE, $magicNumber, $name . " Fence"));

			//TODO: find a better way to deal with this split
			self::register(new Leaves($magicNumber >= 4 ? Block::LEAVES2 : Block::LEAVES, $magicNumber & 0x03, $treeType, $name . " Leaves"));
			self::register(new Log($magicNumber >= 4 ? Block::WOOD2 : Block::WOOD, $magicNumber & 0x03, $treeType, $name . " Log"));
			self::register(new Wood($magicNumber >= 4 ? Block::WOOD2 : Block::WOOD, ($magicNumber & 0x03) | 0b1100, $treeType, $name . " Wood"));
		}

		static $sandstoneTypes = [
			Sandstone::NORMAL => "",
			Sandstone::CHISELED => "Chiseled ",
			Sandstone::CUT => "Cut ",
			Sandstone::SMOOTH => "Smooth "
		];
		foreach($sandstoneTypes as $variant => $prefix){
			self::register(new Sandstone(Block::SANDSTONE, $variant, $prefix . "Sandstone"));
			self::register(new Sandstone(Block::RED_SANDSTONE, $variant, $prefix . "Red Sandstone"));
		}

		foreach(DyeColor::getAll() as $color){
			self::register(new Carpet(Block::CARPET, $color->getMagicNumber(), $color->getDisplayName() . " Carpet"));
			self::register(new Concrete(Block::CONCRETE, $color->getMagicNumber(), $color->getDisplayName() . " Concrete"));
			self::register(new ConcretePowder(Block::CONCRETE_POWDER, $color->getMagicNumber(), $color->getDisplayName() . " Concrete Powder"));
			self::register(new Glass(Block::STAINED_GLASS, $color->getMagicNumber(), $color->getDisplayName() . " Stained Glass"));
			self::register(new GlassPane(Block::STAINED_GLASS_PANE, $color->getMagicNumber(), $color->getDisplayName() . " Stained Glass Pane"));
			self::register(new HardenedClay(Block::STAINED_CLAY, $color->getMagicNumber(), $color->getDisplayName() . " Stained Clay"));
			self::register(new HardenedGlass(Block::HARD_STAINED_GLASS, $color->getMagicNumber(), "Hardened " . $color->getDisplayName() . " Stained Glass"));
			self::register(new HardenedGlassPane(Block::HARD_STAINED_GLASS_PANE, $color->getMagicNumber(), "Hardened " . $color->getDisplayName() . " Stained Glass Pane"));
			self::register(new Wool(Block::WOOL, $color->getMagicNumber(), $color->getDisplayName() . " Wool"));
		}

		/** @var Slab[] $slabTypes */
		$slabTypes = [
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 0, "Stone"),
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 1, "Sandstone"),
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 2, "Fake Wooden"),
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 3, "Cobblestone"),
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 4, "Brick"),
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 5, "Stone Brick"),
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 6, "Quartz"),
			new StoneSlab(Block::STONE_SLAB, Block::DOUBLE_STONE_SLAB, 7, "Nether Brick"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 0, "Red Sandstone"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 1, "Purpur"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 2, "Prismarine"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 3, "Dark Prismarine"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 4, "Prismarine Bricks"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 5, "Mossy Cobblestone"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 6, "Smooth Sandstone"),
			new StoneSlab(Block::STONE_SLAB2, Block::DOUBLE_STONE_SLAB2, 7, "Red Nether Brick")
		];
		foreach(TreeType::getAll() as $woodType){
			$slabTypes[] = new WoodenSlab(Block::WOODEN_SLAB, Block::DOUBLE_WOODEN_SLAB, $woodType->getMagicNumber(), $woodType->getDisplayName());
		}
		foreach($slabTypes as $type){
			self::register($type);
			self::register((clone $type)->setSlabType(SlabType::DOUBLE())); //flattening hack
		}

		static $wallTypes = [
			CobblestoneWall::ANDESITE_WALL => "Andesite",
			CobblestoneWall::BRICK_WALL => "Brick",
			CobblestoneWall::DIORITE_WALL => "Diorite",
			CobblestoneWall::END_STONE_BRICK_WALL => "End Stone Brick",
			CobblestoneWall::GRANITE_WALL => "Granite",
			CobblestoneWall::MOSSY_STONE_BRICK_WALL => "Mossy Stone Brick",
			CobblestoneWall::MOSSY_WALL => "Mossy Cobblestone",
			CobblestoneWall::NETHER_BRICK_WALL => "Nether Brick",
			CobblestoneWall::NONE_MOSSY_WALL => "Cobblestone",
			CobblestoneWall::PRISMARINE_WALL => "Prismarine",
			CobblestoneWall::RED_NETHER_BRICK_WALL => "Red Nether Brick",
			CobblestoneWall::RED_SANDSTONE_WALL => "Red Sandstone",
			CobblestoneWall::SANDSTONE_WALL => "Sandstone",
			CobblestoneWall::STONE_BRICK_WALL => "Stone Brick"
		];
		foreach($wallTypes as $magicNumber => $prefix){
			self::register(new CobblestoneWall(Block::COBBLESTONE_WALL, $magicNumber, $prefix . " Wall"));
		}

		//TODO: minecraft:acacia_button
		//TODO: minecraft:acacia_pressure_plate
		//TODO: minecraft:acacia_standing_sign
		//TODO: minecraft:acacia_trapdoor
		//TODO: minecraft:acacia_wall_sign
		//TODO: minecraft:andesite_stairs
		//TODO: minecraft:bamboo
		//TODO: minecraft:bamboo_sapling
		//TODO: minecraft:barrel
		//TODO: minecraft:barrier
		//TODO: minecraft:beacon
		//TODO: minecraft:bell
		//TODO: minecraft:birch_button
		//TODO: minecraft:birch_pressure_plate
		//TODO: minecraft:birch_standing_sign
		//TODO: minecraft:birch_trapdoor
		//TODO: minecraft:birch_wall_sign
		//TODO: minecraft:blast_furnace
		//TODO: minecraft:blue_ice
		//TODO: minecraft:bubble_column
		//TODO: minecraft:cartography_table
		//TODO: minecraft:carved_pumpkin
		//TODO: minecraft:cauldron
		//TODO: minecraft:chain_command_block
		//TODO: minecraft:chemical_heat
		//TODO: minecraft:chemistry_table
		//TODO: minecraft:chorus_flower
		//TODO: minecraft:chorus_plant
		//TODO: minecraft:command_block
		//TODO: minecraft:conduit
		//TODO: minecraft:coral
		//TODO: minecraft:coral_block
		//TODO: minecraft:coral_fan
		//TODO: minecraft:coral_fan_dead
		//TODO: minecraft:coral_fan_hang
		//TODO: minecraft:coral_fan_hang2
		//TODO: minecraft:coral_fan_hang3
		//TODO: minecraft:dark_oak_button
		//TODO: minecraft:dark_oak_pressure_plate
		//TODO: minecraft:dark_oak_trapdoor
		//TODO: minecraft:dark_prismarine_stairs
		//TODO: minecraft:darkoak_standing_sign
		//TODO: minecraft:darkoak_wall_sign
		//TODO: minecraft:diorite_stairs
		//TODO: minecraft:dispenser
		//TODO: minecraft:double_stone_slab3
		//TODO: minecraft:double_stone_slab4
		//TODO: minecraft:dragon_egg
		//TODO: minecraft:dried_kelp_block
		//TODO: minecraft:dropper
		//TODO: minecraft:element_0
		//TODO: minecraft:end_brick_stairs
		//TODO: minecraft:end_gateway
		//TODO: minecraft:end_portal
		//TODO: minecraft:fletching_table
		//TODO: minecraft:frosted_ice
		//TODO: minecraft:granite_stairs
		//TODO: minecraft:grindstone
		//TODO: minecraft:hopper
		//TODO: minecraft:jukebox
		//TODO: minecraft:jungle_button
		//TODO: minecraft:jungle_pressure_plate
		//TODO: minecraft:jungle_standing_sign
		//TODO: minecraft:jungle_trapdoor
		//TODO: minecraft:jungle_wall_sign
		//TODO: minecraft:kelp
		//TODO: minecraft:lantern
		//TODO: minecraft:lava_cauldron
		//TODO: minecraft:monster_egg
		//TODO: minecraft:mossy_cobblestone_stairs
		//TODO: minecraft:mossy_stone_brick_stairs
		//TODO: minecraft:movingBlock
		//TODO: minecraft:normal_stone_stairs
		//TODO: minecraft:observer
		//TODO: minecraft:piston
		//TODO: minecraft:pistonArmCollision
		//TODO: minecraft:polished_andesite_stairs
		//TODO: minecraft:polished_diorite_stairs
		//TODO: minecraft:polished_granite_stairs
		//TODO: minecraft:portal
		//TODO: minecraft:powered_comparator
		//TODO: minecraft:prismarine_bricks_stairs
		//TODO: minecraft:prismarine_stairs
		//TODO: minecraft:red_nether_brick_stairs
		//TODO: minecraft:repeating_command_block
		//TODO: minecraft:scaffolding
		//TODO: minecraft:sea_pickle
		//TODO: minecraft:seagrass
		//TODO: minecraft:shulker_box
		//TODO: minecraft:slime
		//TODO: minecraft:smithing_table
		//TODO: minecraft:smoker
		//TODO: minecraft:smooth_quartz_stairs
		//TODO: minecraft:smooth_red_sandstone_stairs
		//TODO: minecraft:smooth_sandstone_stairs
		//TODO: minecraft:smooth_stone
		//TODO: minecraft:spruce_button
		//TODO: minecraft:spruce_pressure_plate
		//TODO: minecraft:spruce_standing_sign
		//TODO: minecraft:spruce_trapdoor
		//TODO: minecraft:spruce_wall_sign
		//TODO: minecraft:sticky_piston
		//TODO: minecraft:stone_slab3
		//TODO: minecraft:stone_slab4
		//TODO: minecraft:stripped_acacia_log
		//TODO: minecraft:stripped_birch_log
		//TODO: minecraft:stripped_dark_oak_log
		//TODO: minecraft:stripped_jungle_log
		//TODO: minecraft:stripped_oak_log
		//TODO: minecraft:stripped_spruce_log
		//TODO: minecraft:structure_block
		//TODO: minecraft:turtle_egg
		//TODO: minecraft:undyed_shulker_box
		//TODO: minecraft:unpowered_comparator
	}

	public static function isInit() : bool{
		return self::$fullList !== null;
	}

	/**
	 * Registers a block type into the index. Plugins may use this method to register new block types or override
	 * existing ones.
	 *
	 * NOTE: If you are registering a new block type, you will need to add it to the creative inventory yourself - it
	 * will not automatically appear there.
	 *
	 * @param Block $block
	 * @param bool  $override Whether to override existing registrations
	 *
	 * @throws \RuntimeException if something attempted to override an already-registered block without specifying the
	 * $override parameter.
	 */
	public static function register(Block $block, bool $override = false) : void{
		$id = $block->getId();
		$variant = $block->getVariant();


		$stateMask = $block->getStateBitmask();
		if(($variant & $stateMask) !== 0){
			throw new \InvalidArgumentException("Block variant collides with state bitmask");
		}

		if(!$override and self::isRegistered($id, $variant)){
			throw new \InvalidArgumentException("Block registration conflicts with an existing block");
		}

		for($m = $variant; $m <= ($variant | $stateMask); ++$m){
			if(($m & ~$stateMask) !== $variant){
				continue;
			}

			if(!$override and self::isRegistered($id, $m)){
				throw new \InvalidArgumentException("Block registration " . get_class($block) . " has states which conflict with other blocks");
			}

			$index = ($id << 4) | $m;

			$v = clone $block;
			try{
				$v->readStateFromMeta($m & $stateMask);
				if($v->getDamage() !== $m){
					throw new InvalidBlockStateException("Corrupted meta"); //don't register anything that isn't the same when we read it back again
				}
			}catch(InvalidBlockStateException $e){ //invalid property combination
				continue;
			}

			self::fillStaticArrays($index, $v);
		}

		if(!self::isRegistered($id, $variant)){
			self::fillStaticArrays(($id << 4) | $variant, $block); //register default state mapped to variant, for blocks which don't use 0 as valid state
		}
	}

	private static function fillStaticArrays(int $index, Block $block) : void{
		self::$fullList[$index] = $block;
		self::$stateMasks[$index] = $block->getStateBitmask();
		self::$lightFilter[$index] = min(15, $block->getLightFilter() + 1); //opacity plus 1 standard light filter
		self::$diffusesSkyLight[$index] = $block->diffusesSkyLight();
		self::$blastResistance[$index] = $block->getBlastResistance();
	}

	/**
	 * Returns a new Block instance with the specified ID, meta and position.
	 *
	 * @param int      $id
	 * @param int      $meta
	 * @param Position $pos
	 *
	 * @return Block
	 */
	public static function get(int $id, int $meta = 0, Position $pos = null) : Block{
		if($meta < 0 or $meta > 0xf){
			throw new \InvalidArgumentException("Block meta value $meta is out of bounds");
		}

		/** @var Block|null $block */
		$block = null;
		try{
			$index = ($id << 4) | $meta;
			if(self::$fullList[$index] !== null){
				$block = clone self::$fullList[$index];
			}
		}catch(\RuntimeException $e){
			throw new \InvalidArgumentException("Block ID $id is out of bounds");
		}

		if($block === null){
			$block = new UnknownBlock($id, $meta);
		}

		if($pos !== null){
			$block->position($pos->getLevel(), $pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
		}

		return $block;
	}

	public static function fromFullBlock(int $fullState, ?Position $pos = null) : Block{
		return self::get($fullState >> 4, $fullState & 0xf, $pos);
	}

	public static function getStateMask(int $id) : int{
		return self::$stateMasks[$id] ?? 0;
	}

	/**
	 * Returns whether a specified block state is already registered in the block factory.
	 *
	 * @param int $id
	 * @param int $meta
	 *
	 * @return bool
	 */
	public static function isRegistered(int $id, int $meta = 0) : bool{
		$b = self::$fullList[($id << 4) | $meta];
		return $b !== null and !($b instanceof UnknownBlock);
	}

	public static function registerStaticRuntimeIdMappings() : void{
		/** @var mixed[] $runtimeIdMap */
		$runtimeIdMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "runtimeid_table.json"), true);
		$legacyIdMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "legacy_id_map.json"), true);
		foreach($runtimeIdMap as $k => $obj){
			//this has to use the json offset to make sure the mapping is consistent with what we send over network, even though we aren't using all the entries
			if(!isset($legacyIdMap[$obj["name"]])){
				continue;
			}
			self::registerMapping($k, $legacyIdMap[$obj["name"]], $obj["data"]);
		}
	}

	/**
	 * @internal
	 *
	 * @param int $id
	 * @param int $meta
	 *
	 * @return int
	 */
	public static function toStaticRuntimeId(int $id, int $meta = 0) : int{
		/*
		 * try id+meta first
		 * if not found, try id+0 (strip meta)
		 * if still not found, return update! block
		 */
		return self::$staticRuntimeIdMap[($id << 4) | $meta] ?? self::$staticRuntimeIdMap[$id << 4] ?? self::$staticRuntimeIdMap[BlockIds::INFO_UPDATE << 4];
	}

	/**
	 * @internal
	 *
	 * @param int $runtimeId
	 *
	 * @return int[] [id, meta]
	 */
	public static function fromStaticRuntimeId(int $runtimeId) : array{
		$v = self::$legacyIdMap[$runtimeId];
		return [$v >> 4, $v & 0xf];
	}

	private static function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void{
		self::$staticRuntimeIdMap[($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
		self::$legacyIdMap[$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
		self::$lastRuntimeId = max(self::$lastRuntimeId, $staticRuntimeId);
	}
}
