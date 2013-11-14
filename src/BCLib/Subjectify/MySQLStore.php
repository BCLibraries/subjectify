<?php

namespace BCLib\Subjectify;

use BCLib\LCCallNumbers\LCCallNumber;

class MySQLStore implements Store
{
    /** @var \PDO */
    protected $_pdo;

    public function __construct(\PDO $pdo)
    {
        $this->_pdo = $pdo;
        $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function insertTopic(Topic $topic)
    {
        if (!isset($topic->parent)) {
            $topic->parent = new Topic();
            $topic->parent->id = '';
        }

        $sql = "CALL insertTopic(:label, :parent, @last_added_id);";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindValue(':label', $topic->label, \PDO::PARAM_STR);
        $stmt->bindValue(':parent', $topic->parent->id, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $return_array = $this->_pdo->query("select @last_added_id")->fetch(\PDO::FETCH_ASSOC);
        return $return_array['@last_added_id'];
    }

    public function insertLCCRange(LCCallNumber $low_lc, LCCallNumber $high_lc, Topic $topic)
    {
        $sql = <<<SQL
INSERT IGNORE INTO `lccranges` (`displaymin`,`displaymax`,`searchmin`,`searchmax`)
  VALUES (:displaymin, :displaymax, :searchmin, :searchmax);
SELECT `lccranges`.id FROM `lccranges`
WHERE `searchmin` = :searchmin AND `searchmax` = :searchmax;
SQL;

        $display_min = $this->_displayCallNumber($low_lc);
        $display_max = $this->_displayCallNumber($high_lc);

        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindValue(':displaymin', $display_min, \PDO::PARAM_STR);
        $stmt->bindValue(':displaymax', $display_max, \PDO::PARAM_INT);
        $stmt->bindValue(':searchmin', $low_lc->normalizeClass(), \PDO::PARAM_STR);
        $stmt->bindValue(':searchmax', $high_lc->normalizeClass(), \PDO::PARAM_INT);

        $stmt->execute();

        $stmt->nextRowset();
        $results = $stmt->fetch(\PDO::FETCH_ASSOC);

        $lccrange_id = $results['id'];

        if (is_null($lccrange_id)) {
            print_r($results);
            print_r($low_lc);
            print_r($high_lc);
            exit();
        }

        $sql = "INSERT IGNORE INTO `lccranges_topics` VALUES (:lccrange_id, :topic_id);";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindValue(':lccrange_id', $lccrange_id);
        $stmt->bindValue(':topic_id', $topic->id);
        $stmt->execute();
    }

    private function _displayCallNumber(LCCallNumber $call_number)
    {
        return $call_number->letters . $call_number->number . ' ' . $call_number->cutter_1;
    }

    public function search(LCCallNumber $call_number)
    {
        $sql = <<<SQL
SELECT topics.id
FROM topics
  JOIN lccranges_topics ON lccranges_topics.topic_id = topics.id
  JOIN lccranges ON lccranges.id = lccranges_topics.lccrange_id
WHERE lccranges.searchmin <= :call_number AND lccranges.searchmax >= :call_number
SQL;

        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindValue(':call_number', $call_number->normalizeClass(), \PDO::PARAM_STR);
        $stmt->execute();

        $labels = array();
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $result) {
            $labels[] = $result[0];
        }
        return $labels;
    }
}