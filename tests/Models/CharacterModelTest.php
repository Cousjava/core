<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Tests\ModelTestCase;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of CharacterModelTest
 *
 * @author Basilius Sauter
 */
class CharacterModelTest extends ModelTestCase {
    /** @var array */
    protected $entities = [Character::class];
    
    /**
     * Tests character creation
     */
    public function testCreation() {
        $characters = [
            1 => [
                "name" => "Testcharacter",
                "maxhealth" => 250
            ],
            2 => [
                "name" => "Spamegg",
                "maxhealth" => 42
            ],
        ];
        
        foreach($characters as $characterId => $characterData) {
            $characterEntity = Character::create($characterData);
            $characterEntity->save($this->getEntityManager());
            
            $this->assertEquals($characterEntity->getId(), $characterId);
        }
        
        $entities = $this->getEntityManager()->getRepository(Character::class)
                        ->findAll();
        $this->assertCount(count($characters), $entities);
        
        return $entities;
    }
    
    /**
     * @expectedException TypeError
     */
    public function testCreationTypes() {
        $faultyCharacters = [
            1 => [
                "name" => 16,
                "maxhealth" => 16,
            ],
            2 => [
                "name" => "Faulter",
                "maxhealth" => 17.8,
            ]
        ];
        
        foreach($faultyCharacters as $faultyCharacterData) {
            $char = Character::create($faultyCharacterData);
        }
    }
    
    /**
     * @depends testCreation
     */
    public function testDeletion(array $characters) {
        foreach($characters as $character) {
            $character->save($this->getEntityManager());
        }
        
        $character = $this->getEntityManager()->getRepository(Character::class)->find(1);
        $character->delete($this->getEntityManager());
        
        $entities = $this->getEntityManager()->getRepository(Character::class)
                        ->findAll();
        $this->assertCount(1, $entities);
    }
}
