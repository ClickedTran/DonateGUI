<?php

namespace ClickedTran\DonateGUI;

use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\block\inventory\AnvilInventory as Anvil;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerUIIds;

use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;

use ClickedTran\DonateGUI\DonateGUI;
use ClickedTran\DonateGUI\inventory\AnvilMenu as Inv;
use ClickedTran\DonateGUI\gui\GUIManager;

class EventListener implements Listener {
  
  public DonateGUI $plugin;
  
  public function __construct(DonateGUI $plugin){
    $this->plugin = $plugin;
  }
  
  public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        foreach ($this->plugin->getOfflineTopup()->get($player->getName(), []) as $date => $declaredValue) {
            // EconomyAPI::getInstance()->addMoney($player, $reward);
            $this->plugin->getOfflineTopup()->removeNested($player->getName() . "." . $date);
            $this->plugin->getOfflineTopup()->save();

            $this->plugin->getDonateData()->setNested($player->getName() . ".total", $this->plugin->getDonateData()->getNested($player->getName() . ".total", 0) + $declaredValue);
            $this->plugin->getDonateData()->setNested($player->getName() . "." . $date, $declaredValue);
            $this->plugin->getDonateData()->save();
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();

        if (isset($this->plugin->processDonateData[$player->getName()])) unset($this->plugin->processDonateData[$player->getName()]);
    }
    
    public function onReceive(DataPacketReceiveEvent $event): void {
        $gui = new GUIManager();
        if ($event->isCancelled()) return;

        $player = $event->getOrigin()->getPlayer();
        if (!is_null($player)) {
            $inv = $player->getCurrentWindow();
            if ($inv instanceof Inv) {
                $pk = $event->getPacket();
                if ($pk instanceof ItemStackRequestPacket) {
                    foreach ($pk->getRequests() as $request) {
                        foreach ($request->getActions() as $action) {
                            if ($action instanceof PlaceStackRequestAction) {
                                if ($action->getSource()->getContainerId() === ContainerUIIds::CREATED_OUTPUT) {
                                    if (isset($this->plugin->processDonateData[$player->getName()])) {
                                        $gui->processDonate($player, $inv, $request->getFilterStrings(), $this->plugin->processDonateData[$player->getName()]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
