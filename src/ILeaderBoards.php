<?php
/**
 * User: Jian Jiang <jianzi0307@gmail.com>
 * Date: 2018/6/26
 * Time: 02:34
 */

namespace com\runchina;

use com\runchina\entity\Entity;

interface ILeaderBoards
{
    public function add(Entity $entity = null, array $options = []);

    public function getLeaderboard(array $options = []);

    public function getTop($top, array $options = []);

    public function getAroundUserLeaderboard($userId, array $options = []);

    public function getBestScore($userId, array $options = []);

    public function getTotalScore($userId, array $options = []);

    public function getRank($userId, array $options = []);

    public function removeLeaderboards(array $options = []);

    public function getRedisInstance();
}
