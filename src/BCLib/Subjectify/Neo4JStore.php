<?php
namespace BCLib\Subjectify;

use BCLib\LCCallNumbers\LCCallNumber;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Label;

/**
 * Class Neo4JStore
 * @package BCLib\Subjectify
 *
 * Subject store for the Neo4J Graph Library
 */
class Neo4JStore implements Store
{

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $_client;

    /**
     * @var \Everyman\Neo4j\Label
     */
    protected $_topic_label;

    /**
     * @var \Everyman\Neo4j\Label
     */
    protected $_range_label;

    protected $_topics = array();

    public function __construct(Client $client, Label $topic_label, Label $range_label)
    {
        $this->_client = $client;
        $this->_topic_label = $topic_label;
        $this->_range_label = $range_label;
    }

    public function insertTopic(Topic $topic)
    {
        $topic_node = $this->_loadTopicNode($topic);

        if (isset($topic->parent)) {
            $parent_node = $this->_client->makeNode();
            $parent_node->setId($topic->parent->id);
            $topic_node->relateTo($parent_node, 'subtopic')->save();
        }

        $this->_topics[$topic->label] = $topic_node;

        return $topic_node->getId();
    }

    public function insertLCCRange(LCCallNumber $low_lc, LCCallNumber $high_lc, Topic $topic)
    {
        $topic_node = $this->_loadTopicNode($topic);
        $range_node = $this->_client->makeNode();
        $range_node->setProperty('low_lc', $low_lc->normalizeClass());
        $range_node->setProperty('hi_lc', $high_lc->normalizeClass());
        $range_node->save();
        $range_node->addLabels(array($this->_range_label));
        $range_node->relateTo($topic_node, 'of_topic')->save();
    }

    public function search(LCCallNumber $call_number)
    {
        $query_string = <<<CYPHER
MATCH (range:range)-[:of_topic|:subtopic*1..5]->(t:topic)
WHERE range.hi_lc >= {callno}
AND range.low_lc <= {callno}
RETURN DISTINCT t
CYPHER;

        $query = new Query($this->_client,
            $query_string,
            array('callno' => $call_number->normalizeClass()));

        $return_array = array();

        foreach ($query->getResultSet() as $row) {
            $return_array[] = $row['x']->getProperty('name');
        }
        return $return_array;
    }

    protected function _loadTopicNode(Topic $topic)
    {
        if (isset($this->_topics[$topic->label])) {
            return $this->_topics[$topic->label];
        }

        $topic_node = $this->_client->makeNode();
        $topic_node->setProperty('name', $topic->label)->save();
        $topic_node->addLabels(array($this->_topic_label));
        return $topic_node;
    }
}