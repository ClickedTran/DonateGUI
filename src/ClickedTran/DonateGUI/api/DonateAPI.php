<?php

namespace ClickedTran\DonateGUI\api;

use pocketmine\player\Player;
use pocketmine\utils\{Config, SingletonTrait};
use pocketmine\scheduler\Task;

use onebone\coinapi\CoinAPI;
use ClickedTran\DonateGUI\DonateGUI;
use ClickedTran\DonateGUI\task\TaskManager;

class DonateAPI {
  use SingletonTrait;
  
  public DonateGUI $plugin;
  public $coin;
  public string $donate_menu = "§b====§6DonateGUI§b====";
  public function __construct(){
    $this->plugin = DonateGUI::getInstance();
    $this->coin = CoinAPI::getInstance();
  }
  
  public function simplePostRequest(string $page, array $args = [], float $timeout = 10): array {
        $ch = curl_init($page);
        if ($ch === false) throw new \Exception("Unable to create new cURL session");

        curl_setopt_array($ch, [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($timeout * 1000),
            CURLOPT_TIMEOUT_MS => (int) ($timeout * 1000),
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $args,
            CURLOPT_HTTPHEADER => ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 DonateAPI/1.0.0"],
            CURLOPT_HEADER => true
        ]);

        try {
            $raw = curl_exec($ch);
            if ($raw === false) throw new \Exception(curl_error($ch));
            if (!is_string($raw)) throw new \Exception("curl_exec() should return string|false when CURLOPT_RETURNTRANSFER is set");

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $rawHeaders = substr($raw, 0, $headerSize);
            $body = substr($raw, $headerSize);
            $headers = [];

            foreach (explode("\r\n\r\n", $rawHeaders) as $rawHeaderGroup) {
                $headerGroup = [];
                foreach (explode("\r\n", $rawHeaderGroup) as $line) {
                    $nameValue = explode(":", $line, 2);
                    if (isset($nameValue[1])) {
                        $headerGroup[trim(strtolower($nameValue[0]))] = trim($nameValue[1]);
                    }
                }
                $headers[] = $headerGroup;
            }

            return ["headers" => $headers, "body" => $body, "httpCode" => $httpCode];
        } finally {
            curl_close($ch);
        }
    }

    public function processResult(Player $player, array $result): void {
        $isOnline = $player->isOnline();

        $bonus = $this->plugin->getConfig()->get("multip"); // [1;+Infinity)
        $reward = 0;

        switch ($result["status"]) {
            case 1: // Gửi thẻ thành công (đúng mệnh giá)
                $isOnline && $player->sendMessage($this->donate_menu ."\n". str_replace(["{type}", "{money}"], ["đúng mệnh giá", substr($result["declared_value"], 0, -3) * $bonus], $this->plugin->getConfig()->getNested("donate.successfully"))."\n§b================");
                $reward = substr($result["declared_value"], 0, -3) * $bonus;
                break;
            case 2: // Gửi thẻ thành công (sai mệnh giá)
                $isOnline && $player->sendMessage($this->donate_menu ."\n". str_replace(["{type}", "{money}"], ["sai mệnh giá", (substr($result["declared_value"], 0, -3) * $bonus)/2], $this->plugin->getConfig()->getNested("donate.successfully"))."\n§b================");
                $reward = (substr($result["declared_value"], 0, -3) * $bonus) / 2;
                break;
            case 3: // Thẻ lỗi
                $isOnline && $player->sendMessage($this->donate_menu ."\n". str_replace(["{reason}", "{error}"], ["Thẻ sai, đã sử dụng, không đúng định dạng, ...", $result["message"]], $this->plugin->getConfig()->getNested("donate.failed.1"))."\n§b================");
                break;
            case 4: // Hệ thống bảo trì
                $isOnline && $player->sendMessage($this->donate_menu ."\n". str_replace(["{reason}", "{error}"], ["Hệ thống bảo trì", $result["message"]], $this->plugin->getConfig()->getNested("donate.failed.1"))."\n§b================");
                break;
            case 100: // Gửi thẻ thất bại - Có lý do đi kèm ở phần thông báo trả về
                $isOnline && $player->sendMessage($player->sendMessage($this->donate_menu ."\n".str_replace(["{reason}"], [ $result["message"]], $this->plugin->getConfig()->getNested("donate.failed.1")))."\n§b================");
                break;
            default:
                $isOnline && $player->sendMessage($this->donate_menu ."\n". str_replace(["{request_id}", "{message}"], [$result["request_id"], $result["message"]], $this->plugin->getConfig()->getNested("donate.failed.2"))."\n§b================");
                break;
        }

        if ($result["status"] == 1 || $result["status"] == 2) {
            if (!$isOnline) {
                $this->plugin->getOfflineTopup()->setNested($player->getName() . "." . date("d/m/Y H:i:s"), $result["declared_value"]);
                $this->plugin->getOfflineTopup()->save();
            } else {
                $this->coin->addCoin($player, $reward);
                $this->plugin->getDonateData()->setNested($player->getName() . ".total", $this->getDonateData()->getNested($player->getName() . ".total", 0) + $result["declared_value"]);
                $this->getDonateData()->setNested($player->getName() . "." . date("d/m/Y H:i:s"), $result["declared_value"]);
                $this->plugin->getDonateData()->save();
            }
        }
    }

    public function postCard(Player $player, string $telco, int $amount, string $code, string $serial, int $requestId = null, bool $check = false): void {
        $requestId ??= hrtime(true);

        $result = $this->simplePostRequest(
            "https://" . $this->plugin->getConfig()->get("domain") . "/chargingws/v2",
            [
                "telco" => $telco,
                "code" => $code,
                "serial" => $serial,
                "amount" => $amount,
                "request_id" => $requestId,
                "partner_id" => $this->plugin->getConfig()->get("partnerId"),
                "sign" => md5($this->plugin->getConfig()->get("partnerKey") . $code . $serial),
                "command" => $check ? "check" : "charging"
            ]
        );

        $result = json_decode($result["body"], true);
        if ($result == null || $result["message"] == "PENDING" || $result["status"] == 99) {
            $this->plugin->getScheduler()->scheduleDelayedTask(new class($this, $player, $telco, $amount, $code, $serial, $requestId) extends Task {
                public function __construct(
                    private DonateAPI $api,
                    private Player $player,
                    private string $telco,
                    private int $amount,
                    private string $code,
                    private string $serial,
                    private int $requestId
                ) {
                }

                public function onRun(): void {
                    $this->player->sendMessage(DonateGUI::getInstance()->getConfig()->get("prefix") . "§bThẻ đang được xử lý, vui lòng đợi!");

                    DonateAPI::getInstance()->postCard($this->player, $this->telco, $this->amount, $this->code, $this->serial, $this->requestId, true);
                }
            }, 20 * 3);
        } else {
            $this->processResult($player, $result);
        }
    }
}
