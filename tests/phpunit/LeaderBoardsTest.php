<?php
/**
 * Created by PhpStorm.
 * User: jianzi0307
 * Date: 2018/6/26
 * Time: 11:47
 */

namespace com\runchina;

use com\runchina\entity\Entity;
use PHPUnit\Framework\TestCase;

class LeaderBoardsTest extends TestCase
{
    protected $lb;

    public function setUp()
    {
        $this->lb = new LeaderBoards("127.0.0.1", 63790);
        $this->lb->flushAll();
        $this->lb->setPeriods(false, false, false, false);
        $entities = [];
        for ($i = 1; $i <= 10; $i++) {
            $entity = new Entity();
            $entity->setUserId($i);
            $entity->setAttrName('distance');
            $entity->setCreatedAt(date('Y-m-d H:i:s'));
            $entity->setScore($i);
            $entity->setExtra(json_encode(["nickname" => "user" . $i]));
            $entities[] = $entity;
            $this->lb->add($entity);
        }
        $entity = new Entity();
        $entity->setUserId(5);
        $entity->setAttrName('distance');
        $entity->setCreatedAt(date('Y-m-d H:i:s'));
        $entity->setScore(100);
        $entity->setExtra(json_encode(["nickname" => "user5"]));
        $entities[] = $entity;
        $this->lb->add($entity);
    }

    public function tearDown()
    {
        $this->lb->flushAll();
        $this->lb = null;
    }

    /**
     * 测试添加失败
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function addFailed()
    {
        $entity = new Entity();
        $entity->setCreatedAt(date('Y-m-d H:i:s'));
        $entity->setScore(1000);
        $entity->setExtra(json_encode(["nickname" => "user12"]));
        $this->lb->add($entity);
    }

    /**
     * 获取排行榜
     * @test
     */
    public function getLeaderboard()
    {
        $lbs = $this->lb->getLeaderboard([
            'group' => 'default',
            'attrName' => 'distance',
            'period' => 'alltime'
        ]);
        $this->assertCount(10, $lbs);
    }

    /**
     * 获取top榜
     * @test
     */
    public function top()
    {
        $tops = $this->lb->getTop(3, [
            'group' => 'default',
            'attrName' => 'distance',
            'period' => 'alltime'
        ]);
        $this->assertCount(3, $tops);
    }

    /**
     * 获取范围排行
     * @test
     */
    public function getAroundUserLeaderboard()
    {
        $lbs = $this->lb->getAroundUserLeaderboard(2, [
            'group' => 'default',
            'attrName' => 'distance',
            'period' => 'alltime',
            'range' => 1
        ]);
        $this->assertCount(3, $lbs);
    }

    /**
     * 获取排名
     * @test
     */
    public function getRank()
    {
        $rank = $this->lb->getRank(5, [
            'group' => 'default',
            'attrName' => 'distance',
            'period' => 'alltime',
            'scoreType' => 'total'
        ]);
        $this->assertEquals(0, $rank);
    }


    /**
     * 获取最高分
     * @test
     */
    public function getBestScore()
    {
        $bestScore = $this->lb->getBestScore(5, [
            'group' => 'default',
            'attrName' => 'distance',
            'period' => 'alltime'
        ]);
        $this->assertEquals(100, $bestScore['score']);
    }

    /**
     * 获取累计分
     * @test
     */
    public function getTotalScore()
    {
        $totalScore = $this->lb->getTotalScore(5, [
            'group' => 'default',
            'attrName' => 'distance',
            'period' => 'alltime'
        ]);
        $this->assertEquals(105, $totalScore);
    }
}
