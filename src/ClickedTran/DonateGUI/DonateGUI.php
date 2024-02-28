<?php

namespace ClickedTran\DonateGUI;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use muqsit\invmenu\InvMenuHandler;

use ClickedTran\DonateGUI\command\DonateGUICommand;
use ClickedTran\DonateGUI\inventory\AnvilInventory;

class DonateGUI extends PluginBase {

   /**DonateGUI getInstance()*/
    public static $instance;
    public static function getInstance() : DonateGUI {
      return self::$instance;
    }
   /*************************/
    public const INV_ANVIL = "donateapi:anvil";

    public Config $donateData;
    public Config $offlineTopup;

    public array $processDonateData;


    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("DonateGUI", new DonateGUICommand($this));

        $this->donateData = new Config($this->getDataFolder() . "data.json", Config::JSON);
        $this->offlineTopup = new Config($this->getDataFolder() . "offlinetopup.json", Config::JSON);

        if (!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
        InvMenuHandler::getTypeRegistry()->register(self::INV_ANVIL, new AnvilInventory());
        
        $this->saveDefaultConfig();
        self::$instance = $this;
    }
    
    public function getDonateData(){
      return $this->donateData;
    }
    
    public function getOfflineTopup(){
      return $this->offlineTopup;
    }
}
