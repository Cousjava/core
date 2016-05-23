<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\{
    Battle,
    Models\Character,
    Models\Monster
};

use LotGD\Core\Tests\ModelTestCase;

/**
 * Tests the management of Characters
 */
class BattleTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "battle";
    
    public function testBasicMonster()
    {
        $em = $this->getEntityManager();
        
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $this->assertSame(5, $monster->getLevel());
        $this->assertSame(52, $monster->getMaxHealth());
        $this->assertSame(9, $monster->getAttack());
        $this->assertSame(7, $monster->getDefense());
        $this->assertSame($monster->getMaxHealth(), $monster->getHealth());
    }
    
    public function testFairBattle()
    {
        $em = $this->getEntityManager();
        
        $character = $em->getRepository(Character::class)->find(1);
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $battle = new Battle($character, $monster);
        
        for ($n = 0; $n < 99; $n++) {
            $oldPlayerHealth = $character->getHealth();
            $oldMonsterHealth = $monster->getHealth();
            
            $battle->fightNRounds(1);
            
            $this->assertLessThanOrEqual($oldPlayerHealth, $character->getHealth());
            $this->assertLessThanOrEqual($oldMonsterHealth, $monster->getHealth());
            
            if ($battle->isOver()) {
                break;
            }
        }
        
        $this->assertTrue($battle->isOver());
        $this->assertTrue($character->isAlive() xor $monster->isAlive());
    }
    
    public function testPlayerWinBattle()
    {
        $em = $this->getEntityManager();
        
        $highLevelPlayer = $em->getRepository(Character::class)->find(2);
        $lowLevelMonster = $em->getRepository(Monster::class)->find(3);
        
        $battle = new Battle($highLevelPlayer, $lowLevelMonster);
        
        for ($n = 0; $n < 99; $n++) {
            $oldPlayerHealth = $highLevelPlayer->getHealth();
            $oldMonsterHealth = $lowLevelMonster->getHealth();
            
            $battle->fightNRounds(1);
            
            $this->assertLessThanOrEqual($oldPlayerHealth, $highLevelPlayer->getHealth());
            $this->assertLessThanOrEqual($oldMonsterHealth, $lowLevelMonster->getHealth());
            
            if ($battle->isOver()) {
                break;
            }
        }
        
        $this->assertTrue($highLevelPlayer->isAlive());
        $this->assertFalse($lowLevelMonster->isAlive());
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getWinner(), $highLevelPlayer);
    }
    
    public function testPlayerLooseBattle()
    {
        $em = $this->getEntityManager();
        
        $lowLevelPlayer = $em->getRepository(Character::class)->find(3);
        $highLevelMonster = $em->getRepository(Monster::class)->find(2);
        
        $battle = new Battle($lowLevelPlayer, $highLevelMonster);
        
        for ($n = 0; $n < 99; $n++) {
            $oldPlayerHealth = $lowLevelPlayer->getHealth();
            $oldMonsterHealth = $highLevelMonster->getHealth();
            
            $battle->fightNRounds(1);
            
            $this->assertLessThanOrEqual($oldPlayerHealth, $lowLevelPlayer->getHealth());
            $this->assertLessThanOrEqual($oldMonsterHealth, $highLevelMonster->getHealth());
            
            if ($battle->isOver()) {
                break;
            }
        }
        
        $this->assertFalse($lowLevelPlayer->isAlive());
        $this->assertTrue($highLevelMonster->isAlive());
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getWinner(), $highLevelMonster);
    }
}
