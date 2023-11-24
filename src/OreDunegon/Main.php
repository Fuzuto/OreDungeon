<?php

namespace OreDunegon;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\block\VanillaBlocks;

use pocketmine\scheduler\ClosureTask;

use pocketmine\utils\Config;

use pocketmine\world\particle\BlockBreakParticle;

class Main extends PluginBase implements Listener{
    
    public Config $config;
    
    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->config = new Config($this->getDataFolder() . 'Config.yml', Config::YAML);
    }
        
    public function onBlockBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $blockId = $block->getTypeId();
        $drops = $event->getDrops();
        $player = $event->getPlayer();
        $event->cancel(true);
        $block->getPosition()->getWorld()->setBlock($block->getPosition(), VanillaBlocks::AIR(), true, true);
        $block->getPosition()->getWorld()->addParticle($block->getPosition()->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
        foreach($drops as $item){
            if($player->getInventory()->canAddItem($item)){
                $player->getInventory()->addItem($item);
            }else{
                $player->getWorld()->dropItem($block->getPosition(), $item);
                $player->sendTitle("§c§l!INVENTORY FULL!");
            }
        }
        if(!$player->isCreative()) {
            if($this->config->exists($blockId)){
                $worldName = $block->getPosition()->getWorld()->getFolderName();
                if(in_array($worldName, $this->config->get($blockId)['World'])) {
                    $BlockBeSetedTo = strtoupper($this->config->get($blockId)['BlockBeSetedTo']);
                    $block->getPosition()->getWorld()->setBlock($block->getPosition(), VanillaBlocks::$BlockBeSetedTo(), true, true);
                    $rate = 0;
                    foreach($this->config->get($blockId)['Blocks'] as $blockName => $data){
                        if((int)$data['rate'] + $rate >= mt_rand(1, 100)){
                            $blockName = strtoupper($blockName);
                            $resetBlock = VanillaBlocks::$blockName();
                            if(isset($this->config->get($blockId)['BlockBeSetedTo'])){
                                $this->runTask($block, $blockId, $resetBlock);
                            }
                            break;
                        }else{
                            $rate += (int)$data['rate'];
                        }
                    }
                }
            }
        }
    }    
    
    public function runTask($block, $blockId, $resetBlock){
        $cooldown = $this->config->get($blockId)['Cooldown'] ?? 3;
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($block, $resetBlock): void {
            $block->getPosition()->getWorld()->setBlock($block->getPosition(), $resetBlock, true, true);
        }), 20 * $cooldown);
    }
}
