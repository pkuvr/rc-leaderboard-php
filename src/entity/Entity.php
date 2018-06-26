<?php
/**
 * User: Jian Jiang <jianzi0307@gmail.com>
 * Date: 2018/6/26
 * Time: 02:34
 */

namespace com\runchina\entity;

class Entity
{
    private $userId;
    private $attrName;
    private $createdAt;
    private $score;
    private $extra;

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getAttrName()
    {
        return $this->attrName;
    }

    /**
     * @param mixed $attrName
     */
    public function setAttrName($attrName)
    {
        $this->attrName = $attrName;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param mixed $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param mixed $extra
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }
}
