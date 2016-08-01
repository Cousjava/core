<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\Bootstrap;
use LotGD\Core\Configuration;
use LotGD\Core\EventHandler;
use LotGD\Core\EventManager;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\CharacterViewpoint;
use LotGD\Core\Models\Scene;
use LotGD\Core\Exceptions\CharacterNotFoundException;
use LotGD\Core\Exceptions\InvalidConfigurationException;
use LotGD\Core\Tests\ModelTestCase;

class DefaultSceneProvider implements EventHandler
{
    public static function handleEvent(string $event, array &$context)
    {
        switch ($event) {
            case 'h/lotgd/core/default-scene':
                if (!isset($context['character'])) {
                    throw new \Exception("Key 'character' was expected on event h/lotgd/core/default-scene.");
                }
                $context['scene'] = $context['g']->getEntityManager()->getRepository(Scene::class)->find(1);
        }
    }
}

class GameTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "game";

    private $g;

    public function setUp()
    {
        parent::setUp();

        $logger  = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $this->g = new Game(new Configuration(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . getenv('LOTGD_CONFIG')), $logger, $this->getEntityManager(), new EventManager($this->getEntityManager()));
    }

    public function testGetCharacterException()
    {
        $this->expectException(CharacterNotFoundException::class);
        $this->g->getCharacter();
    }

    public function testSetGetCharacter()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(1);

        $this->g->setCharacter($c);
        $this->assertEquals($c, $this->g->getCharacter());
    }

    public function testGetViewpointException()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(1);
        $this->g->setCharacter($c);

        // There shouldnt be any listeners to provide a default scene.
        $this->expectException(InvalidConfigurationException::class);
        $this->g->getViewpoint();
    }

    public function testGetViewpointStored()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(2);
        $this->g->setCharacter($c);

        $this->assertNotNull($this->g->getViewpoint());
    }

    public function testGetViewpointDefault()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(1);
        $this->g->setCharacter($c);

        $this->g->getEventManager()->subscribe('/h\/lotgd\/core\/default-scene/', DefaultSceneProvider::class, 'lotgd/core/tests');

        $v = $this->g->getViewpoint();
        $this->assertEquals('lotgd/tests/village', $v->getTemplate());
    }
}
