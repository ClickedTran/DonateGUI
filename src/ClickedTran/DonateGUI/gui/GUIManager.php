<?php
namespace ClickedTran\DonateGUI\gui;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\MobHeadType;
use pocketmine\item\VanillaItems;
use pocketmine\world\sound\FizzSound;
use pocketmine\world\sound\XpLevelUpSound;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use ClickedTran\DonateGUI\DonateGUI;
use ClickedTran\DonateGUI\inventory\AnvilMenu;
use ClickedTran\DonateGUI\api\DonateAPI;

class GUIManager {
  
  public DonateGUI $plugin;
  public DonateAPI $api;
  
  public function __construct(){
    $this->plugin = DonateGUI::getInstance();
    $this->api = DonateAPI::getInstance();
  }
  
  public function processDonate(Player $player, AnvilMenu $inv, array $filterStrings, array $data): void {
        $inv->clearAll();

        $name = str_replace(" ", "", $filterStrings[0]);

        if (empty($filterStrings) || !is_numeric($name)) {
            $player->removeCurrentWindow();
            $player->sendToastNotification("Donate Menu", "Please enter a valid " . ($this->plugin->processDonateData[$player->getName()]["menu-type"] == 1 ? "code" : "seri"));
            return;
        }

        $menuType = $data["menu-type"];
        $telco = $data["telco"];
        $amount = $data["amount"];
        $code = $data["code"];
        $serial = $data["serial"];

        if ($menuType == 1) {
            $code = $name;
        } else {
            $serial = $name;
        }

        if ($menuType == 1) {
            $this->donateMenu($player, $telco, $amount, $code, $serial, 2);
        } else {
            $player->removeCurrentWindow();

            unset($this->plugin->processDonateData[$player->getName()]);

            $this->api->postCard($player, $telco, $amount, $code, $serial);
        }
    }

    public function donateMenu(Player $player, string $telco, int $amount, string $code = "", string $serial = "", int $menuType = 1): void {
        //$menuType => 1: Input code, 2: Input serial

        $menu = InvMenu::create(DonateGUI::INV_ANVIL);
        $inventory = $menu->getInventory();

        $inputItem = VanillaItems::PAPER();
        if ($menuType == 1) {
            $inputItem->setCustomName("Đổi tên item này thành mã thẻ");
        } else {
            $inputItem->setCustomName("Đổi tên item này thành số seri");
        }
        $inventory->setItem(0, $inputItem);

        unset($this->plugin->processDonateData[$player->getName()]);
        $this->plugin->processDonateData[$player->getName()] = [
            "menu-type" => $menuType,
            "telco" => $telco,
            "amount" => $amount,
            "code" => $code,
            "serial" => $serial
        ];

        $menu->setListener(function (InvMenuTransaction $transaction) use ($menuType, $code, $serial): InvMenuTransactionResult {
            return $transaction->discard();
        });

        $menu->send($player, "Donate Menu");
    }

    public function chooseAmountMenu(Player $player, string $telco): void {
        $menu = InvMenu::create(InvMenu::TYPE_HOPPER);
        $inventory = $menu->getInventory();

        $tenKItem = VanillaItems::PAPER()->setCustomName("10,000");
        $twentyKItem = VanillaItems::PAPER()->setCustomName("20,000");
        $fivetyKItem = VanillaItems::PAPER()->setCustomName("50,000");
        $hundredKItem = VanillaItems::PAPER()->setCustomName("100,000");
        $twoHundredKItem = VanillaItems::PAPER()->setCustomName("200,000");

        $inventory->setContents([
            $tenKItem,
            $twentyKItem,
            $fivetyKItem,
            $hundredKItem,
            $twoHundredKItem
        ]);

        $menu->setListener(function (InvMenuTransaction $transaction) use ($tenKItem, $twentyKItem, $fivetyKItem, $hundredKItem, $twoHundredKItem, $telco): InvMenuTransactionResult {
            switch ($transaction->getItemClicked()->getNamedTag()) {
                case $tenKItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player) use ($telco): void {
                        $this->donateMenu($player, $telco, 10000);
                    });
                case $twentyKItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player) use ($telco): void {
                        $this->donateMenu($player, $telco, 20000);
                    });
                case $fivetyKItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player) use ($telco): void {
                        $this->donateMenu($player, $telco, 50000);
                    });
                case $hundredKItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player) use ($telco): void {
                        $this->donateMenu($player, $telco, 100000);
                    });
                case $twoHundredKItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player) use ($telco): void {
                        $this->donateMenu($player, $telco, 200000);
                    });
            }

            return $transaction->discard();
        });

        $menu->send($player, "Donate Menu");
    }

    public function chooseTelcoMenu(Player $player): void {
        $menu = InvMenu::create(InvMenu::TYPE_HOPPER);
        $inventory = $menu->getInventory();

        $viettelItem = VanillaItems::BOOK()->setCustomName("Viettel");
        $mobifoneItem = VanillaItems::BOOK()->setCustomName("Mobifone");
        $vinaphoneItem = VanillaItems::BOOK()->setCustomName("Vinaphone");
        $vietnamobileItem = VanillaItems::BOOK()->setCustomName("Vietnamobile");
        $garenaItem = VanillaItems::BOOK()->setCustomName("Garena");

        $inventory->setContents([
            $viettelItem,
            $mobifoneItem,
            $vinaphoneItem,
            $vietnamobileItem,
            $garenaItem
        ]);

        $menu->setListener(function (InvMenuTransaction $transaction) use ($viettelItem, $mobifoneItem, $vinaphoneItem, $vietnamobileItem, $garenaItem): InvMenuTransactionResult {
            switch ($transaction->getItemClicked()->getNamedTag()) {
                case $viettelItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player): void {
                        $this->chooseAmountMenu($player, "VIETTEL");
                    });
                case $mobifoneItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player): void {
                        $this->chooseAmountMenu($player, "MOBIFONE");
                    });
                case $vinaphoneItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player): void {
                        $this->chooseAmountMenu($player, "VINAPHONE");
                    });
                case $vietnamobileItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player): void {
                        $this->chooseAmountMenu($player, "VIETNAMOBILE");
                    });
                case $garenaItem->getNamedTag():
                    return $transaction->discard()->then(function (Player $player): void {
                        $this->chooseAmountMenu($player, "GARENA");
                    });
            }

            return $transaction->discard();
        });

        $menu->send($player, "Donate Menu");
    }

    public function topMenu(Player $player): void {
        $menu = InvMenu::create(InvMenu::TYPE_HOPPER);
        $inventory = $menu->getInventory();

        $steveHeadItem = VanillaBlocks::MOB_HEAD()->setMobHeadType(MobHeadType::PLAYER())->asItem();

        $top = [];
        foreach ($this->plugin->getDonateData()->getAll() as $name => $data) {
            $top[$name] = $data["total"];
        }
        arsort($top);

        $i = 0;
        foreach ($top as $name => $total) {
            if ($i >= 5) return;
            $inventory->setItem($i, $steveHeadItem->setCustomName($name . ": " . number_format($total) . "₫"));
            ++$i;
        }

        for (; $i < 5; $i++) {
            $inventory->setItem($i, $steveHeadItem->setCustomName("No data"));
        }

        $menu->setListener($menu->readonly());

        $menu->send($player, "Donate Menu");
    }

    public function infoMenu(Player $player): void {
        $menu = InvMenu::create(InvMenu::TYPE_HOPPER);
        $inventory = $menu->getInventory();

        $sendMessage = false;

        $infoText = $this->plugin->getConfig()->get("info_donate");

        foreach ($this->plugin->getDonateData()->get($player->getName(), []) as $key => $data) {
            if ($key == "total") {
                $infoText += ["  - Tổng tiền đã nạp: " . number_format($data) . "₫"];
            } else {
                $infoText += ["  - " . $key . ": " . number_format($data) . "₫"];
            }
        }

        $barrierItem = VanillaBlocks::BARRIER()->asItem()->setCustomName("!");
        $paperItem = VanillaItems::BOOK()->setCustomName("THÔNG TIN CHUYỂN KHOẢN");
        !$sendMessage && $paperItem->setLore([$infoText]);

        $inventory->setContents([
            $barrierItem,
            $barrierItem,
            $paperItem,
            $barrierItem,
            $barrierItem
        ]);

        $menu->setListener(function (InvMenuTransaction $transaction) use ($paperItem, $sendMessage, $infoText): InvMenuTransactionResult {
            $player = $transaction->getPlayer();

            if ($transaction->getItemClicked()->equals($paperItem)) {
                $player->removeCurrentWindow();
                $player->getWorld()->addSound($player->getPosition(), new XpLevelUpSound(100), [$player]);

                if ($sendMessage) {
                    foreach ($infoText as $text) {
                        $player->sendMessage($text);
                    }
                } else {
                    return $transaction->discard()->then(function (Player $player): void {
                        $this->mainMenu($player);
                    });
                }
            } else {
                $player->getWorld()->addSound($player->getPosition(), new FizzSound, [$player]);
                $player->sendToastNotification("Donate Menu", "Don't click that border item");
                return $transaction->discard();
            }

            return $transaction->discard();
        });

        $menu->send($player, "Donate Menu");
    }

    public function mainMenu(Player $player): void {
        $menu = InvMenu::create(InvMenu::TYPE_HOPPER);
        $inventory = $menu->getInventory();

        $barrierItem = VanillaBlocks::BARRIER()->asItem()->setCustomName("!");
        $donateItem = VanillaItems::WRITABLE_BOOK()->setCustomName("NẠP THẺ");
        $topItem = VanillaItems::BOOK()->setCustomName("TOP NẠP");
        $infoItem = VanillaItems::WRITTEN_BOOK()->setCustomName("THÔNG TIN THÊM");

        $inventory->setContents([
            $barrierItem,
            $donateItem,
            $topItem,
            $infoItem,
            $barrierItem
        ]);

        $menu->setListener(
            function (InvMenuTransaction $transaction) use ($donateItem, $topItem, $infoItem): InvMenuTransactionResult {
                $player = $transaction->getPlayer();
                switch ($transaction->getItemClicked()->getNamedTag()) {
                    case $donateItem->getNamedTag():
                        return $transaction->discard()->then(function (Player $player): void {
                            $this->chooseTelcoMenu($player);
                        });
                    case $topItem->getNamedTag():
                        return $transaction->discard()->then(function (Player $player): void {
                            $this->topMenu($player);
                        });
                    case $infoItem->getNamedTag():
                        return $transaction->discard()->then(function (Player $player): void {
                            $this->infoMenu($player);
                        });
                    default:
                        $player->getWorld()->addSound($player->getPosition(), new FizzSound, [$player]);
                        $player->sendToastNotification("Donate Menu", "Don't click that border item");
                }

                return $transaction->discard();
            }
        );

        $menu->send($player, "Donate Menu");
    }
}
