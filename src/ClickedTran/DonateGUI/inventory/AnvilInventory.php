<?php

namespace ClickedTran\DonateGUI\inventory;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\inventory\AnvilInventory as Anvil;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\graphic\InvMenuGraphic;
use muqsit\invmenu\type\InvMenuType;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;


class AnvilInventory implements InvMenuType {
  
  public InvMenuType $type;
  public function __construct(){
    $this->type = InvMenuTypeBuilders::BLOCK_ACTOR_FIXED()
                        ->setBlock(VanillaBlocks::ANVIL())
                        ->setBlockActorId("Anvil")
                        ->setNetworkWindowType(WindowTypes::ANVIL)
                        ->setSize(2)
                        ->build();
  }
  
  public function createGraphic(InvMenu $inv, Player $player) : ?InvMenuGraphic{
    return $this->type->createGraphic($inv, $player);
  }
  
  public function createInventory() : AnvilMenu{
    return new AnvilMenu(new Position(0, 0, 0, null));
  }
}
