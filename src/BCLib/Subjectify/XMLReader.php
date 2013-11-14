<?php

namespace BCLib\Subjectify;

use BCLib\LCCallNumbers\CallNumberFactory;

/**
 * Reads UMichigan's subjects XML file
 * @package BCLib\Subjectify
 */
class XMLReader
{
    /**
     * @var Store
     */
    protected $_store;

    /**
     * @var CallNumberFactory
     */
    protected $_cno_factory;

    /**
     * @var TopicFactory
     */
    protected $_topic_factory;

    public function __construct(Store $store, CallNumberFactory $cno_factory, TopicFactory $topic_factory)
    {
        $this->_store = $store;
        $this->_cno_factory = $cno_factory;
        $this->_topic_factory = $topic_factory;
    }

    public function read($file)
    {
        $xml = \simplexml_load_file($file);
        $this->_addNode($xml);
    }

    protected function _addNode(\SimpleXMLElement $node, Topic $parent = null)
    {
        if (isset ($node['name'])) {
            $topic = $this->_topic_factory->create();
            $topic->label = (string) $node['name'];

            if (isset($parent)) {
                $topic->parent = $parent;
            }

            $topic->id = $this->_store->insertTopic($topic);
        } else {
            $topic = $parent;
        }

        foreach ($node->xpath('*[not(self::call-numbers)]') as $child) {
            $this->_addNode($child, $topic);
        }

        foreach ($node->{'call-numbers'} as $call_number) {
            $this->_addCallNumbers($call_number, $topic);
        }
    }

    protected function _addCallNumbers(\SimpleXMLElement $call_numbers, Topic $topic)
    {
        $lo_cno = $this->_getClassNumber($call_numbers['start']);
        $hi_cno = $this->_getClassNumber($call_numbers['end']);
        $this->_store->insertLCCRange($lo_cno, $hi_cno, $topic);
    }

    protected function _getClassNumber($cno_string)
    {
        $temp = \explode(' ', \strtoupper($cno_string));
        $cno = $this->_cno_factory->create();
        $cno->letters = $temp[0];
        $cno->number = $temp[1];
        $cno->cutter_1 = isset($temp[2]) ? $temp[2] : '';
        return $cno;
    }
}