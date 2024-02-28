<?php
namespace ClickedTran\DonateGUI\command;

use pocketmine\player\Player;
use pocketmine\command\{CommandSender, Command};
use pocketmine\plugin\PluginOwned;

use ClickedTran\DonateGUI\DonateGUI;
use ClickedTran\DonateGUI\gui\GUIManager;

class DonateGUICommand extends Command implements PluginOwned {
  
  public DonateGUI $plugin;
  public function __construct(DonateGUI $plugin){
    $this->plugin = $plugin;
    parent::__construct("donate", "§bOpen Donate Menu", null, ["napthe"]);
    $this->setPermission("donategui.command");
  }
  
  public function execute(CommandSender $sender, String $label, Array $args){
    if(!$sender instanceof Player){
      $this->plugin->getLogger()->error("§cERROR, Please use in-game");
    }
    $gui = new GUIManager();
    $gui->mainMenu($sender);
  }
    
    public function getOwningPlugin() : DonateGUI{
        //F*CK PMMP
        }
}
