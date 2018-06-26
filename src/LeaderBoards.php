<?php
/**
 * User: Jian Jiang <jianzi0307@gmail.com>
 * Date: 2018/6/26
 * Time: 02:34
 */

namespace com\runchina;

use com\runchina\entity\Entity;

class LeaderBoards implements ILeaderBoards
{
    protected $redis;
    protected $alltime = true;
    protected $daily = false;
    protected $monthly = false;
    protected $weekly = false;
    protected $yearly = false;

    public function __construct($host = '127.0.0.1', $port = 6379, $password = '')
    {
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
        if (!empty($password)) {
            $this->redis->auth($password);
        }
    }

    /**
     * 设置周期性排行
     * @param bool $daily
     * @param bool $weekly
     * @param bool $monthly
     * @param bool $yearly
     */
    public function setPeriods($daily = false, $weekly = false, $monthly = false, $yearly = false)
    {
        $this->daily = $daily;
        $this->weekly = $weekly;
        $this->monthly = $monthly;
        $this->yearly = $yearly;
    }

    protected function upsertHightScore($group, $period, $userId, $attrName, $scoreId, $score)
    {
        //高分
        $hScoreKey = 'hscore:' . $group . ':' . $period . ':' . $userId . ':' . $attrName;
        $lbKey = 'leaderboard:' . $group . ':' . $period . ':bestscore:' . $attrName;
        $bestScoreId = $this->redis->hGet($hScoreKey, 'best_score');
        if (!$bestScoreId) {
            $this->redis->hSet($hScoreKey, 'best_score', $scoreId);
            $this->redis->zAdd($lbKey, $score, $userId);
        } else {
            $prevBestScore = $this->redis->hGet('score:' . $bestScoreId, 'score');
            if ($score > $prevBestScore) {
                $this->redis->hSet($hScoreKey, 'best_score', $scoreId);
                $this->redis->zAdd($lbKey, $score, $userId);
            }
        }
        //累计分
        $oldScore = $this->redis->hGet($hScoreKey, 'total_score');
        $newScore = $oldScore + $score;
        $this->redis->hSet($hScoreKey, 'total_score', $newScore);
        $lbKey = 'leaderboard:' . $group . ':' . $period . ':totalscore:' . $attrName;
        $this->redis->zAdd($lbKey, $newScore, $userId);
    }

    /**
     * 添加
     * @param Entity|null $entity
     * @param array $options
     * @return string
     */
    public function add(Entity $entity = null, array $options = [])
    {
        if (!$entity || !$entity->getUserId() || !$entity->getAttrName()) {
            throw new \InvalidArgumentException('parameter error.');
        }
        $entity->setCreatedAt($entity->getCreatedAt() ? $entity->getCreatedAt() : date('Y-m-d H:i:s'));
        $entity->setScore($entity->getScore() > 0 ? $entity->getScore() : 0);
        $entity->setExtra(!empty($entity->getExtra()) ? $entity->getExtra() : '');
        $group = !isset($options['group']) ? 'default' : $options['group'];
        $scoreId = $this->redis->incr('score_id');
        $this->redis->hMset('score:' . $scoreId, [
            'user_id' => $entity->getUserId(),
            'attr_name' => $entity->getAttrName(),
            'created_at' => $entity->getCreatedAt(),
            'score' => $entity->getScore(),
            'extra' => $entity->getExtra()
        ]);
        $this->upsertHightScore(
            $group,
            'alltime',
            $entity->getUserId(),
            $entity->getAttrName(),
            $scoreId,
            $entity->getScore()
        );
        if ($this->daily) {
            $this->upsertHightScore(
                $group,
                'daily',
                $entity->getUserId(),
                $entity->getAttrName(),
                $scoreId,
                $entity->getScore()
            );
        }
        if ($this->weekly) {
            $this->upsertHightScore(
                $group,
                'weekly',
                $entity->getUserId(),
                $entity->getAttrName(),
                $scoreId,
                $entity->getScore()
            );
        }
        if ($this->monthly) {
            $this->upsertHightScore(
                $group,
                'monthly',
                $entity->getUserId(),
                $entity->getAttrName(),
                $scoreId,
                $entity->getScore()
            );
        }
        if ($this->monthly) {
            $this->upsertHightScore(
                $group,
                'yearly',
                $entity->getUserId(),
                $entity->getAttrName(),
                $scoreId,
                $entity->getScore()
            );
        }
    }

    /**
     * 获取排行榜
     * @param array $options
     * @return array
     */
    public function getLeaderboard(array $options = [])
    {
        $group = isset($options['group']) ? $options['group'] : 'default';
        $period = isset($options['period']) ? $options['period'] : 'alltime';
        $attrName = isset($options['attrName']) ? $options['attrName'] : null;
        $scoreType = isset($options['scoreType']) ? $options['scoreType'] : 'best';
        $from = isset($options['from']) ? $options['from'] : 0;
        $to = isset($options['to']) ? $options['to'] : -1;

        if (!$attrName) {
            throw new \InvalidArgumentException('parameter error.');
        }
        $sType = ($scoreType === 'best') ? 'bestscore:' : 'totalscore:';
        return $this->redis->zRevRange('leaderboard:' . $group . ':' . $period . ':' . $sType . $attrName, $from, $to, true);
    }

    /**
     * TOP榜单
     * @param $top
     * @param array $options
     * @return array
     */
    public function getTop($top, array $options = [])
    {
        $options['from'] = 1;
        $options['to'] = $top;
        return $this->getLeaderboard($options);
    }

    /**
     * 获取前后范围列表
     * @param $userId
     * @param array $options
     * @return array
     */
    public function getAroundUserLeaderboard($userId, array $options = [])
    {
        $range = isset($options['range']) ? $options['range'] : 10;
        $rank = $this->getRank($userId, $options);
        $options['from'] = $rank - $range;
        $options['to'] = $rank + $range;
        return $this->getLeaderboard($options);
    }

    /**
     * 获取最高值
     * @param $userId
     * @param array $options
     * @return array
     */
    public function getBestScore($userId, array $options = [])
    {
        $group = isset($options['group']) ? $options['group'] : 'default';
        $period = isset($options['period']) ? $options['period'] : 'alltime';
        $attrName = isset($options['attrName']) ? $options['attrName'] : null;
        if (!$userId || !$attrName) {
            throw new \InvalidArgumentException('parameter error.');
        }
        $key = 'hscore:' . $group . ':' . $period . ':' . $userId . ':' . $attrName;
        $scoreId = $this->redis->hGet($key, 'best_score');
        return $this->redis->hGetAll('score:' . $scoreId);
    }

    /**
     * 获取累计分
     * @param $userId
     * @param array $options
     * @return string
     */
    public function getTotalScore($userId, array $options = [])
    {
        $group = isset($options['group']) ? $options['group'] : 'default';
        $period = isset($options['period']) ? $options['period'] : 'alltime';
        $attrName = isset($options['attrName']) ? $options['attrName'] : null;
        if (!$userId || !$attrName) {
            throw new \InvalidArgumentException('parameter error.');
        }
        $key = 'hscore:' . $group . ':' . $period . ':' . $userId . ':' . $attrName;
        return $this->redis->hGet($key, 'total_score');
    }

    /**
     * 获取排名
     * @param $userId
     * @param array $options
     * @return int
     */
    public function getRank($userId, array $options = [])
    {
        $group = isset($options['group']) ? $options['group'] : 'default';
        $period = isset($options['period']) ? $options['period'] : 'alltime';
        $attrName = isset($options['attrName']) ? $options['attrName'] : null;
        $scoreType = isset($options['scoreType']) ? $options['scoreType'] : 'best';
        if (!$userId || !$attrName) {
            throw new \InvalidArgumentException('parameter error.');
        }
        $sType = ($scoreType === 'best') ? 'bestscore:' : 'totalscore:';
        return $this->redis->zRevRank('leaderboard:' . $group . ':' . $period . ':' . $sType . $attrName, $userId);
    }

    /**
     * 删除排行
     * @param array $options
     */
    public function removeLeaderboards(array $options = [])
    {
        $group = isset($options['group']) ? $options['group'] : 'default';
        $daily = isset($options['daily']);
        $weekly = isset($options['weekly']);
        $monthly = isset($options['monthly']);
        $yearly = isset($options['yearly']);
        if ($daily) {
            $hScoreKey = 'hscore:' . $group . ':daily:*';
            $ldKey = 'leaderboard:' . $group . ':daily:*';
        }
        if ($weekly) {
            $hScoreKey = 'hscore:' . $group . ':weekly:*';
            $ldKey = 'leaderboard:' . $group . ':weekly:*';
        }
        if ($monthly) {
            $hScoreKey = 'hscore:' . $group . ':monthly:*';
            $ldKey = 'leaderboard:' . $group . ':monthly:*';
        }
        if ($yearly) {
            $hScoreKey = 'hscore:' . $group . ':yearly:*';
            $ldKey = 'leaderboard:' . $group . ':yearly:*';
        }
        $keys = $this->redis->keys($ldKey);
        $this->redis->del($keys);
    }

    /**
     * 清空缓存，仅在测试时用
     */
    public function flushAll()
    {
        $this->redis->flushAll();
    }
}
